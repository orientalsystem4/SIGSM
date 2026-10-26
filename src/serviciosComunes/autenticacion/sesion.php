<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya tiene sesión activa, redirigir según su rol
if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])) {
    $idRol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
    require_once __DIR__ . '/roles.php';
    $destino = destinoPorRol($idRol);
    if ($destino !== null) {
        header('Location: ' . $destino);
        exit;
    }
    unset($_SESSION['id_usuario'], $_SESSION['id_rol'], $_SESSION['nombre_usuario'], $_SESSION['nombre_rol']);
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
    <title>S.I.G.S.M. | Iniciar sesión</title>
<link rel="stylesheet" href="assets/login_sistema.css?v=4">
</head><body class="pagina-login">
    <main class="login-wrapper">
        <section class="login-left" aria-labelledby="titulo-sistema">
            <div class="login-overlay">
                <div class="login-brand">
                    <h1 id="titulo-sistema">S.I.G.S.M.</h1>
                    <h2>Hospital de Clínicas</h2>
                    <p>
                        Gestión de traslados, documentación
                        y encuestas institucionales.
                    </p>
                </div>
            </div>
        </section>

        <section class="login-right" aria-labelledby="titulo-acceso">
            <div class="login-box">
                <div class="login-header">
                    <img
                        src="assets/image.png"
                        alt="Logo S.I.G.S.M."
                        class="login-logo"
                    >
                    <h2 id="titulo-acceso">Iniciar sesión</h2>
                    <p>Ingresá tus credenciales para acceder al sistema.</p>
                </div>

                <?php if ($error): ?>
                    <div class="login-alert login-error" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje): ?>
                    <div class="login-alert login-success" role="status">
                        <?= htmlspecialchars(
                            $mensaje,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="login-form">
                    <div class="login-field">
                        <label for="usuario">Usuario</label>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingrese su usuario"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="login-field">
                        <label for="password">Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingrese su contraseña"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit">Iniciar sesión</button>
                </form>

                <p class="login-help">
                    Si necesitás ayuda con tu cuenta, contactá a DTI.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
