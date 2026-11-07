<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexionBD/conexion.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/../idioma/idioma.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sesion.php');
    exit;
}

// Limpiar cualquier ingreso anterior.
unset(
    $_SESSION['id_usuario'],
    $_SESSION['nombre_usuario'],
    $_SESSION['id_rol'],
    $_SESSION['nombre_rol'],
    $_SESSION['id_usuario_pendiente'],
    $_SESSION['token_seleccion_rol']
);

$usuario = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($usuario) || !is_string($password)) {
    $_SESSION['error'] = t('login.error_datos_invalidos');
    header('Location: sesion.php');
    exit;
}

$usuario = trim($usuario);

// Limite de intentos fallidos por nombre de usuario (no por IP: detras del
// proxy REMOTE_ADDR es siempre la IP interna de Docker).
const LOGIN_MAX_FALLOS = 5;
const LOGIN_VENTANA_MIN = 10;
$usuarioIntento = mb_substr($usuario, 0, 50);

if ($usuario === '' || $password === '') {
    $_SESSION['error'] = t('login.error_campos');
    header('Location: sesion.php');
    exit;
}

try {
    $conexion = Conexion::conectar();

    // Bloqueo temporal: 5 fallos en 10 minutos. Los intentos se registran por
    // nombre aunque la cuenta no exista, asi que el aviso no revela si existe.
    $bloqueo = $conexion->prepare("
        SELECT fecha FROM intento_login
        WHERE nombre_usuario = :usuario
        ORDER BY fecha DESC
        LIMIT 1 OFFSET " . (LOGIN_MAX_FALLOS - 1)
    );
    $bloqueo->execute([':usuario' => $usuarioIntento]);
    $quintoFallo = $bloqueo->fetchColumn();
    if ($quintoFallo !== false) {
        $vigente = $conexion->prepare("SELECT :fecha > NOW() - INTERVAL " . LOGIN_VENTANA_MIN . " MINUTE");
        $vigente->execute([':fecha' => $quintoFallo]);
        if ((int) $vigente->fetchColumn() === 1) {
            $_SESSION['error'] = t('login.error_bloqueado', ['minutos' => LOGIN_VENTANA_MIN]);
            header('Location: sesion.php');
            exit;
        }
    }

    // Buscar la cuenta. Los roles se consultan por separado.
    $sql = "
        SELECT
            id_usuario,
            nombre_usuario,
            contrasenha_hash,
            activo
        FROM usuario
        WHERE nombre_usuario = :usuario
        LIMIT 1
    ";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([
        ':usuario' => $usuario
    ]);

    $user = $consulta->fetch(PDO::FETCH_ASSOC);

    if (
        !$user
        || !password_verify($password, $user['contrasenha_hash'])
    ) {
        $registrar = $conexion->prepare(
            "INSERT INTO intento_login (nombre_usuario, fecha, ip) VALUES (:usuario, NOW(), :ip)"
        );
        $registrar->execute([
            ':usuario' => $usuarioIntento,
            ':ip'      => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)
        ]);
        $conexion->exec("DELETE FROM intento_login WHERE fecha < NOW() - INTERVAL 1 DAY");

        $_SESSION['error'] = t('login.error_credenciales');
        header('Location: sesion.php');
        exit;
    }

    // Credenciales correctas: se descartan los fallos previos.
    $limpiar = $conexion->prepare("DELETE FROM intento_login WHERE nombre_usuario = :usuario");
    $limpiar->execute([':usuario' => $usuarioIntento]);

    if ((int) $user['activo'] !== 1) {
        $_SESSION['error'] = t('login.error_inactivo');
        header('Location: sesion.php');
        exit;
    }

    $roles = consultarRolesUsuario(
        $conexion,
        (int) $user['id_usuario']
    );

    if (empty($roles)) {
        $_SESSION['error'] = t('login.error_sin_roles');
        header('Location: sesion.php');
        exit;
    }

    // Comprobar que todos los roles tengan un destino definido.
    foreach ($roles as $rol) {
        if (destinoPorRol((int) $rol['id_rol']) === null) {
            $_SESSION['error'] = t('login.error_rol_sin_destino');
            header('Location: sesion.php');
            exit;
        }
    }

    session_regenerate_id(true);
    unset($_SESSION['error']);

    // Varios roles: completar el ingreso desde el selector.
    if (count($roles) > 1) {
        $_SESSION['id_usuario_pendiente'] = (int) $user['id_usuario'];

        header('Location: seleccionarRol.php');
        exit;
    }

    // Un solo rol: iniciar la sesion directamente.
    $rol = $roles[0];

    $_SESSION['id_usuario'] = (int) $user['id_usuario'];
    $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
    $_SESSION['id_rol'] = (int) $rol['id_rol'];
    $_SESSION['nombre_rol'] = $rol['nombre_rol'];

    header('Location: ' . destinoPorRol((int) $rol['id_rol']));
    exit;

} catch (Exception $e) {
    error_log('Error en login: ' . $e->getMessage());

    unset(
        $_SESSION['id_usuario'],
        $_SESSION['nombre_usuario'],
        $_SESSION['id_rol'],
        $_SESSION['nombre_rol'],
        $_SESSION['id_usuario_pendiente'],
        $_SESSION['token_seleccion_rol']
    );

    $_SESSION['error'] = t('login.error_general');
    header('Location: sesion.php');
    exit;
}