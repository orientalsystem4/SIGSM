<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexionBD/conexion.php';
require_once __DIR__ . '/roles.php';

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
    $_SESSION['error'] = 'Los datos ingresados no son válidos.';
    header('Location: sesion.php');
    exit;
}

$usuario = trim($usuario);

if ($usuario === '' || $password === '') {
    $_SESSION['error'] = 'Por favor complete todos los campos.';
    header('Location: sesion.php');
    exit;
}

try {
    $conexion = Conexion::conectar();

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
        $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
        header('Location: sesion.php');
        exit;
    }

    if ((int) $user['activo'] !== 1) {
        $_SESSION['error'] = 'El usuario se encuentra inactivo. Comuníquese con el administrador.';
        header('Location: sesion.php');
        exit;
    }

    $roles = consultarRolesUsuario(
        $conexion,
        (int) $user['id_usuario']
    );

    if (empty($roles)) {
        $_SESSION['error'] = 'Su usuario no tiene roles asignados. Contacte al administrador.';
        header('Location: sesion.php');
        exit;
    }

    // Comprobar que todos los roles tengan un destino definido.
    foreach ($roles as $rol) {
        if (destinoPorRol((int) $rol['id_rol']) === null) {
            $_SESSION['error'] = 'Uno de sus roles no tiene una pantalla configurada. Contacte al administrador.';
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

    $_SESSION['error'] = 'Ocurrió un error al iniciar sesión. Intente nuevamente.';
    header('Location: sesion.php');
    exit;
}