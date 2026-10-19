<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 3, 5]);
$usuarioActual = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | Portal Choferes</title>

    <link rel="stylesheet"
        href="../../serviciosComunes/vistaGeneral/assets/css/layout.css">

    <link rel="stylesheet"
href="assets/css/choferes.css?v=20261019-2"
    <script src="https://unpkg.com/lucide@latest"></script>
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
                    alt="Logo"
                    class="sidebar-logo">

                <span>S.I.G.S.M.</span>

            </div>


            <nav class="sidebar-nav">

                <button
                    class="nav-btn active"
                    data-target="seccion-traslados">

                    <i data-lucide="map-pin" class="icon-sm"></i>
                    Mis Traslados

                </button>

                <button
                    class="nav-btn"
                    data-target="seccion-planilla">

                    <i data-lucide="clipboard-list" class="icon-sm"></i>
                    Planilla Diaria
                </button>

            </nav>

            <?php if (tieneRol('admin') || tieneRol(1)): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> Cambiar Módulo
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
                    title="Cerrar Sesión">

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
                        Portal de Choferes
                    </h2>

                </div>

            </header>


            <section class="content-wrapper">


                <?php require __DIR__ . '/seguimientoChofer.php'; ?>

                <!-- PLANILLA DIARIA -->
                <div
                    id="seccion-planilla"
                    class="view-section hidden">


                    <div class="card-formulario">

                        <h3>
                            Iniciar Turno - Planilla
                        </h3>

                        <p class="text-muted">
                            Sus datos personales y la fecha se registran automáticamente.
                        </p>


                        <form class="planilla-form mt-3">


                            <div class="form-group">

                                <label>
                                    Chofer
                                </label>

                                <input
                                    type="text"
                                    class="form-input"
                                    value="Pedro Miniño"
                                    readonly>

                            </div>


                            <div class="form-group">

                                <label>
                                    Vehículo Asignado
                                </label>

                                <select
                                    class="form-input"
                                    required>

                                    <option value="">
                                        Seleccione el vehículo...
                                    </option>

                                    <option value="1">
                                        Ambulancia - AMB001
                                    </option>

                                    <option value="2">
                                        Auto - AUT001
                                    </option>

                                    <option value="3">
                                        Camión - CAM001
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Enfermero Acompañante
                                </label>

                                <select
                                    class="form-input"
                                    required>

                                    <option value="">
                                        Seleccione el enfermero...
                                    </option>

                                    <option value="6">
                                        enfermero_traslado
                                    </option>

                                </select>

                            </div>


                            <button
                                type="button"
                                class="btn-primary w-100 mt-2">

                                Guardar Planilla

                            </button>

                        </form>

                    </div>

                </div>


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