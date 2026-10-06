<?php
session_start();
require_once __DIR__ . '/../conexionBD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $_SESSION['error'] = 'Por favor complete todos los campos.';
        header('Location: sesion.php');
        exit;
    }

    try {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare('SELECT id_usuario, contrasenha_hash, activo FROM usuario WHERE nombre_usuario = :usuario');
        $stmt->execute([':usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['activo'] != 1) {
                $_SESSION['error'] = 'El usuario está inactivo.';
                header('Location: sesion.php');
                exit;
            }

            if (password_verify($password, $user['contrasenha_hash'])) {
                // Login exitoso
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario;
                
                // Redirigir a la bifurcación (o dashboard)
                header('Location: ../vistaGeneral/bifurcacion.html');
                exit;
            } else {
                $_SESSION['error'] = 'Contraseña incorrecta.';
                header('Location: sesion.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Usuario no encontrado.';
            header('Location: sesion.php');
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['error'] = 'Error en el sistema.';
        header('Location: sesion.php');
        exit;
    }
} else {
    header('Location: sesion.php');
    exit;
}
