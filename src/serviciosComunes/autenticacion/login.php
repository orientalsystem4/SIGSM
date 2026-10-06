<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexionBD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sesion.php');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($usuario) || empty($password)) {
    $_SESSION['error'] = 'Por favor complete todos los campos.';
    header('Location: sesion.php');
    exit;
}

try {
    $conexion = Conexion::conectar();

    // Consulta en un solo viaje cruzando usuario, usuario_rol y rol
    $sql = "
        SELECT 
            u.id_usuario,
            u.nombre_usuario,
            u.contrasenha_hash,
            u.activo,
            ur.id_rol,
            r.nombre_rol
        FROM usuario u
        LEFT JOIN usuario_rol ur 
            ON u.id_usuario = ur.id_usuario
        LEFT JOIN rol r 
            ON ur.id_rol = r.id_rol
        WHERE u.nombre_usuario = :usuario
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Usuario no encontrado.';
        header('Location: sesion.php');
        exit;
    }

    if ((int)$user['activo'] !== 1) {
        $_SESSION['error'] = 'El usuario se encuentra inactivo. Comuníquese con el administrador.';
        header('Location: sesion.php');
        exit;
    }

    if (!password_verify($password, $user['contrasenha_hash'])) {
        $_SESSION['error'] = 'Contraseña incorrecta.';
        header('Location: sesion.php');
        exit;
    }

    if (empty($user['id_rol'])) {
        $_SESSION['error'] = 'Su usuario no posee un rol asignado. Contacte al administrador.';
        header('Location: sesion.php');
        exit;
    }

    // Inicio de sesión exitoso: prevenir fijación de sesión
    session_regenerate_id(true);

    $_SESSION['id_usuario'] = (int)$user['id_usuario'];
    $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
    $_SESSION['id_rol'] = $user['id_rol'] !== null ? (int)$user['id_rol'] : null;
    $_SESSION['nombre_rol'] = $user['nombre_rol'] ?? '';

    // Redirección directa basada en el rol del usuario (sin pasar por bifurcación para usuarios operativos)
    $idRol = (int)($user['id_rol'] ?? 0);
    $nombreRol = strtolower($user['nombre_rol'] ?? '');

    if ($idRol === 2) {
        // Administrador de Enfermería: Va directo a gestionar personal
        header('Location: ../../usuario/Vista/listado.php');
        exit;
    } elseif ($idRol === 5) {
        // Chofer
        header('Location: ../../moduloAmbulancias/Vista/choferes.php');
        exit;
    } elseif ($idRol === 6) {
        // Personal Asistencial (Médico / Enfermero)
        header('Location: ../../moduloDocumentacion/Vista/enfermeria.php');
        exit;
    } elseif ($idRol === 3) {
        // Coordinador de Traslados / Unidad de Enlace
        header('Location: ../../moduloAmbulancias/Vista/unidadEnlace.php');
        exit;
    } elseif ($idRol === 4) {
        // Archivo y Documentación
        header('Location: ../../moduloDocumentacion/Vista/documentacion.php');
        exit;
    } elseif ($idRol === 1) {
        // Superadmin: entra al hub de bifurcación
        header('Location: ../vistaGeneral/bifurcacion.php');
        exit;
    } else {
        // Fallback o roles no mapeados
        header('Location: ../vistaGeneral/bifurcacion.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    $_SESSION['error'] = 'Ocurrió un error en el servidor al procesar la solicitud.';
    header('Location: sesion.php');
    exit;
} catch (Exception $e) {
    error_log("Error inesperado en login: " . $e->getMessage());
    $_SESSION['error'] = 'Error en el sistema.';
    header('Location: sesion.php');
    exit;
}
