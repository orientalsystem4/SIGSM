<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya tiene sesión activa, redirigir según su rol
if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])) {
    $idRol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
    switch ($idRol) {
        case 5:
            header('Location: ../../moduloAmbulancias/Vista/choferes.php');
            exit;
        case 2:
        case 6:
            header('Location: ../../moduloDocumentacion/Vista/enfermeria.php');
            exit;
        case 3:
            header('Location: ../../moduloAmbulancias/Vista/unidadEnlace.php');
            exit;
        case 4:
            header('Location: ../../moduloDocumentacion/Vista/documentacion.php');
            exit;
        case 1:
        default:
            header('Location: ../vistaGeneral/bifurcacion.php');
            exit;
    }
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | Iniciar Sesión</title>
    <link rel="stylesheet" href="login.css">
    <style>
        .error-msg { 
            color: #b91c1c; 
            background: #fee2e2; 
            border: 1px solid #f87171;
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 18px; 
            font-size: 0.9rem;
            text-align: center; 
        }
        .success-msg { 
            color: #15803d; 
            background: #dcfce7; 
            border: 1px solid #86efac;
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 18px; 
            font-size: 0.9rem;
            text-align: center; 
        }
    </style>
</head>
<body>
    <main class="login-container">
        <div class="card">
            <div class="header-logo">
                <img src="assets/image.png" alt="Logo S.I.G.S.M." class="logo-img">
                <h1 class="title">S.I.G.S.M.</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($mensaje): ?>
                <div class="success-msg"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-input" placeholder="Ingrese su usuario" autocomplete="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" autocomplete="current-password" required>
                </div>
                
                <button type="submit" class="btn-primary">
                    Iniciar Sesión
                </button>
            </form>
        </div>
    </main>
</body>
</html>
