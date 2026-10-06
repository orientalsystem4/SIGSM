<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpiar variables de sesión en memoria
$_SESSION = [];

// 2. Destruir cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Iniciar sesión limpia para almacenar mensaje de confirmación
session_start();
$_SESSION['mensaje'] = 'Ha cerrado sesión correctamente.';

// 5. Redirigir a la vista de login
header('Location: sesion.php');
exit;

