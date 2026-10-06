<?php
session_start();
require_once __DIR__ . '/../conexionBD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($usuario) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Por favor complete todos los campos.';
        header('Location: registro_vista.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Las contraseñas no coinciden.';
        header('Location: registro_vista.php');
        exit;
    }

    try {
        $conexion = Conexion::conectar();
        
        // Verificar si el usuario ya existe
        $stmt = $conexion->prepare('SELECT id_usuario FROM usuario WHERE nombre_usuario = :usuario');
        $stmt->execute([':usuario' => $usuario]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'El nombre de usuario ya está en uso.';
            header('Location: registro_vista.php');
            exit;
        }

        // Hashear contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar nuevo usuario
        $stmt = $conexion->prepare('INSERT INTO usuario (nombre_usuario, contrasenha_hash, activo) VALUES (:usuario, :hash, 1)');
        $stmt->execute([
            ':usuario' => $usuario,
            ':hash' => $hash
        ]);

        $_SESSION['mensaje'] = 'Registro exitoso. Ahora puede iniciar sesión.';
        header('Location: sesion.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = 'Error en el sistema al registrar usuario.';
        header('Location: registro_vista.php');
        exit;
    }
} else {
    header('Location: registro_vista.php');
    exit;
}
