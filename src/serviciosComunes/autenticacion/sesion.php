<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../idioma/idioma.php';

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
<html lang="<?= idiomaActual() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | <?= te('login.titulo') ?></title>
<link rel="stylesheet" href="assets/login_sistema.css?v=6">
</head><body class="pagina-login">
    <?php $otroIdioma = idiomaActual() === 'es' ? 'en' : 'es'; ?>
    <a
        class="selector-idioma"
        href="../idioma/cambiar.php?lang=<?= $otroIdioma ?>&amp;volver=<?= rawurlencode($_SERVER['SCRIPT_NAME'] ?? '') ?>"
        lang="<?= $otroIdioma ?>"
        aria-label="<?= te('idioma.cambiar_a') ?>"
        title="<?= te('idioma.cambiar_a') ?>"
    >
        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        <span><?= strtoupper($otroIdioma) ?></span>
    </a>
    <main class="login-wrapper">
        <section class="login-left" aria-labelledby="titulo-sistema">
            <div class="login-overlay">
                <div class="login-brand">
                    <h1 id="titulo-sistema">S.I.G.S.M.</h1>
                    <h2><?= te('login.hospital') ?></h2>
                    <p>
                        <?= te('login.descripcion') ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="login-right" aria-labelledby="titulo-acceso">
            <div class="login-box">
                <div class="login-header">
                    <img
                        src="assets/image.png"
                        alt="<?= te('comun.logo_alt') ?>"
                        class="login-logo"
                    >
                    <h2 id="titulo-acceso"><?= te('login.titulo') ?></h2>
                    <p><?= te('login.instrucciones') ?></p>
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

                <form action="login.php" method="POST" class="login-form" data-validar-form>
                    <div class="login-field">
                        <label for="usuario"><?= te('login.usuario') ?></label>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="<?= te('login.usuario_placeholder') ?>"
                            autocomplete="username"
                            required
                            data-validar="requerido"
                            data-msg-requerido="<?= te('login.error_campos') ?>"
                            autofocus
                        >
                    </div>

                    <div class="login-field">
                        <label for="password"><?= te('login.contrasena') ?></label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="<?= te('login.contrasena_placeholder') ?>"
                            autocomplete="current-password"
                            required
                            data-validar="requerido"
                            data-msg-requerido="<?= te('login.error_campos') ?>"
                        >
                    </div>

                    <button type="submit"><?= te('login.boton') ?></button>
                </form>

                <p class="login-help">
                    <?= te('login.ayuda') ?>
                </p>
            </div>
        </section>
    </main>
    <script src="../vistaGeneral/assets/js/validacion.js?v=20261109-6"></script>
</body>
</html>
