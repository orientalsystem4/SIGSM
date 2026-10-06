<?php
session_start();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | Registro</title>
    <link rel="stylesheet" href="login.css">
    <style>
        .error-msg { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .login-link { text-align: center; margin-top: 15px; display: block; color: #0056b3; text-decoration: none; }
        .login-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="login-container">
        <div class="card">
            <div class="header-logo">
                <img src="assets/image.png" alt="Logo S.I.G.S.M." class="logo-img">
                <h1 class="title">Registro</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="registro.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-input" placeholder="Elija un usuario" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-primary">
                    Registrarse
                </button>
            </form>
            
            <a href="sesion.php" class="login-link">¿Ya tienes cuenta? Inicia sesión</a>
        </div>
    </main>
</body>
</html>
