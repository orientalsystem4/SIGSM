<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 3, 5]);
$usuarioActual = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | <?= te('modulos.choferes') ?></title>

    <link rel="stylesheet"
        href="../../serviciosComunes/vistaGeneral/assets/css/layout.css">

        <link rel="stylesheet" href="assets/css/choferes.css?v=20261019-2">
    <script src="../../serviciosComunes/vistaGeneral/assets/vendor/lucide-1.70.0.min.js"></script>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261021-1">
</head>

<body>

    <div class="app-layout">

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <aside class="sidebar" id="sidebar">

            <div class="sidebar-header">

                <button class="btn-close-menu" id="closeMenuBtn">
                    <i data-lucide="x"></i>
                </button>

                <img
                    src="../../serviciosComunes/autenticacion/assets/image.png"
                    alt="<?= te('comun.logo_alt') ?>"
                    class="sidebar-logo">

                <span>S.I.G.S.M.</span>

            </div>


            <nav class="sidebar-nav">

                <button
                    class="nav-btn active"
                    data-target="seccion-traslados">

                    <i data-lucide="map-pin" class="icon-sm"></i>
                    <?= te('menu.mis_traslados') ?>

                </button>

            </nav>

            <?php if (tieneRol([1, 3])): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> <?= te('menu.cambiar_modulo') ?>
                </a>
            <?php endif; ?>

            <div class="sidebar-profile">

                <i data-lucide="ambulance"></i>

                <div class="profile-info">

                    <span
                        class="profile-name"
                        id="user-name">
                        <?= htmlspecialchars($usuarioActual['nombre_usuario'] ?? 'Chofer') ?>
                    </span>

                    <span class="profile-role">
                        <?= htmlspecialchars($usuarioActual['nombre_rol'] ?? 'Chofer') ?>
                    </span>

                </div>

                <a
                    href="../../serviciosComunes/autenticacion/logout.php"
                    class="btn-logout"
                    title="<?= te('menu.cerrar_sesion') ?>" aria-label="<?= te('menu.cerrar_sesion') ?>">

                    <i data-lucide="log-out"></i>

                </a>

            </div>

        </aside>


        <main class="main-content">


            <header class="topbar">

                <div class="topbar-left">

                    <button
                        class="btn-menu"
                        id="openMenuBtn">

                        <i data-lucide="menu"></i>

                    </button>

                    <h2 class="section-title mb-0">
                        <?= te('modulos.choferes') ?>
                    </h2>

                </div>

            </header>


            <section class="content-wrapper">


                <?php require __DIR__ . '/seguimientoChofer.php'; ?>

            </section>

        </main>

    </div>


    <script
        src="../../serviciosComunes/vistaGeneral/assets/js/layout.js">
    </script>

    <script src="assets/js/choferes.js?v=20261019-1"></script>
    <script>
        lucide.createIcons();
    </script>

</body>

</html>
