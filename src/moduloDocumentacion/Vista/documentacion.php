<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 4]);
if (empty($_SESSION['csrf_documentos'])) {
    $_SESSION['csrf_documentos'] = bin2hex(random_bytes(32));
}

$usuarioActual = obtenerUsuarioActual();
require_once __DIR__ . '/../Model/DocumentoModelo.php';
$documentos = DocumentoModelo::listarTodos();
$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | <?= te('documentacion.portal') ?></title>

    <link rel="stylesheet"
        href="../../serviciosComunes/vistaGeneral/assets/css/layout.css">

    <link rel="stylesheet"
        href="Assets/css/documentacion.css">
    <script src="../../serviciosComunes/vistaGeneral/assets/vendor/chart-4.6.1.umd.min.js"></script>
    <script src="../../serviciosComunes/vistaGeneral/assets/vendor/lucide-1.70.0.min.js"></script>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261109-1">
</head>

<body>

    <div class="app-layout">

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- SIDEBAR -->
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
                    data-target="sec-documentos">
                    <i data-lucide="folder-open" class="icon-sm"></i>
                    <?= te('menu.documentos_categorias') ?>
                </button>



                <button
                    class="nav-btn"
                    data-target="sec-encuestas">
                    <i data-lucide="pie-chart" class="icon-sm"></i>
                    <?= te('menu.encuestas_metricas') ?>
                </button>
            </nav>

            <?php if (tieneRol('admin') || tieneRol(1)): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> <?= te('menu.cambiar_modulo') ?>
                </a>
            <?php endif; ?>

            <div class="sidebar-profile">

                <div class="avatar-placeholder bg-primary">
                    <i data-lucide="shield"></i>
                </div>

                <div class="profile-info">

                    <span class="profile-name">
                        <?= htmlspecialchars($usuarioActual['nombre_usuario'] ?? 'Documentación') ?>
                    </span>

                    <span class="profile-role">
                        <?= htmlspecialchars($usuarioActual['nombre_rol'] ?? 'Documentación') ?>
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


        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">

            <header class="topbar">

                <div class="topbar-left">

                    <button
                        class="btn-menu"
                        id="openMenuBtn">

                        <i data-lucide="menu"></i>

                    </button>


                    <h2 class="section-title mb-0">
                        <?= te('documentacion.portal') ?>
                    </h2>

                </div>

            </header>


            <section class="content-wrapper">
                <?php if ($mensaje !== null): ?>
                    <div role="status" style="padding: 12px; margin-bottom: 16px; background: #e8f0fe; border-radius: 6px;">
                        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>


                <!-- SECCION 1: DOCUMENTOS POR CATEGORIA -->

                <div
                    id="sec-documentos"
                    class="view-section active">


                    <div class="flex-between mb-3">

                        <h3>
                            <?= te('documentacion.directorio') ?>
                        </h3>


                        <a href="crear.php" class="btn-primary" style="text-decoration: none;">
                            <i data-lucide="upload"></i>
                            <?= te('documentacion.subir') ?>
                        </a>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="filtro-estado-documentos"><?= te('documentacion.mostrar') ?>:</label>

                        <select id="filtro-estado-documentos">
                            <option value="1"><?= te('documentacion.activos') ?></option>
                            <option value="0"><?= te('documentacion.inactivos') ?></option>
                        </select>
                    </div>


                    <!-- SUBNAVEGACIÓN -->

                    <div class="sub-nav">

                        <button
                            class="sub-btn active"
                            data-sub="cat-urologia">

                            Urología

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="cat-cardiologia">

                            Cardiología

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="cat-traumatologia">

                            Traumatología

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="cat-gastroenterologia">

                            Gastroenterología

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="cat-ginecobstetricia">

                            Ginecobstetricia

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="cat-imagenologia">

                            Imagenología

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="cat-medicina-nuclear">

                            Medicina Nuclear

                        </button>

                    </div>



                    <!-- UROLOGIA -->

                    <div
                        id="cat-urologia"
                        class="sub-content active">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th>
                                            <?= te('documentacion.documento') ?>
                                        </th>

                                        <th>
                                            <?= te('documentacion.fecha_carga') ?>
                                        </th>

                                        <th>
                                            <?= te('documentacion.responsable') ?>
                                        </th>

                                        <th>
                                            <?= te('campo.acciones') ?>
                                        </th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-1">
                                </tbody>

                            </table>


                            <p
                                id="vacio-1"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>



                    <!-- CARDIOLOGÍA -->

                    <div
                        id="cat-cardiologia"
                        class="sub-content hidden">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th><?= te('documentacion.documento') ?></th>

                                        <th><?= te('documentacion.fecha_carga') ?></th>

                                        <th><?= te('documentacion.responsable') ?></th>

                                        <th><?= te('campo.acciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-2">
                                </tbody>

                            </table>


                            <p
                                id="vacio-2"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>



                    <!-- TRAUMATOLOGÍA -->

                    <div
                        id="cat-traumatologia"
                        class="sub-content hidden">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th><?= te('documentacion.documento') ?></th>

                                        <th><?= te('documentacion.fecha_carga') ?></th>

                                        <th><?= te('documentacion.responsable') ?></th>

                                        <th><?= te('campo.acciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-3">
                                </tbody>

                            </table>


                            <p
                                id="vacio-3"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>



                    <!-- GASTROENTEROLOGÍA -->

                    <div
                        id="cat-gastroenterologia"
                        class="sub-content hidden">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th><?= te('documentacion.documento') ?></th>

                                        <th><?= te('documentacion.fecha_carga') ?></th>

                                        <th><?= te('documentacion.responsable') ?></th>

                                        <th><?= te('campo.acciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-4">
                                </tbody>

                            </table>


                            <p
                                id="vacio-4"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>



                    <!-- GINECOBSTETRICIA -->

                    <div
                        id="cat-ginecobstetricia"
                        class="sub-content hidden">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th><?= te('documentacion.documento') ?></th>

                                        <th><?= te('documentacion.fecha_carga') ?></th>

                                        <th><?= te('documentacion.responsable') ?></th>

                                        <th><?= te('campo.acciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-5">
                                </tbody>

                            </table>


                            <p
                                id="vacio-5"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>



                    <!-- IMAGENOLOGÍA -->

                    <div
                        id="cat-imagenologia"
                        class="sub-content hidden">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th><?= te('documentacion.documento') ?></th>

                                        <th><?= te('documentacion.fecha_carga') ?></th>

                                        <th><?= te('documentacion.responsable') ?></th>

                                        <th><?= te('campo.acciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-6">
                                </tbody>

                            </table>


                            <p
                                id="vacio-6"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>



                    <!-- MEDICINA NUCLEAR -->

                    <div
                        id="cat-medicina-nuclear"
                        class="sub-content hidden">

                        <div class="card-formulario tabla-responsive">

                            <table class="tabla-admin">

                                <thead>

                                    <tr>

                                        <th><?= te('documentacion.documento') ?></th>

                                        <th><?= te('documentacion.fecha_carga') ?></th>

                                        <th><?= te('documentacion.responsable') ?></th>

                                        <th><?= te('campo.acciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody id="tabla-documentos-7">
                                </tbody>

                            </table>


                            <p
                                id="vacio-7"
                                class="text-muted"
                                style="
                                    text-align: center;
                                    padding: 20px;
                                    display: none;
                                ">

                                <?= te('documentacion.sin_documentos') ?>

                            </p>

                        </div>

                    </div>

                </div>



                <!-- SECCIÓN 2: ENCUESTAS Y MÉTRICAS -->

                <div
                    id="sec-encuestas"
                    class="view-section hidden">


                    <div class="sub-nav">

                        <button
                            class="sub-btn active"
                            data-sub="enc-metricas">

                            <i
                                data-lucide="bar-chart-2"
                                class="icon-sm">
                            </i>

                            <?= te('documentacion.metricas_generales') ?>

                        </button>


                        <button
                            class="sub-btn"
                            data-sub="enc-estado">

                            <i
                                data-lucide="clipboard-list"
                                class="icon-sm">
                            </i>

                            <?= te('menu.encuestas') ?>

                        </button>

                    </div>



                    <!-- MÉTRICAS -->

                    <div id="enc-metricas" class="sub-content active">

                        <div class="flex-between mb-3" style="flex-wrap: wrap; gap: 15px;">
                            <h3><?= te('documentacion.metricas_titulo') ?></h3>
                            <select id="filtro-categoria" class="form-input" style="width: auto; min-width: 200px;">
                                <option value="0"><?= te('documentacion.todas_areas') ?></option>
                                <option value="1">Urología</option>
                                <option value="2">Cardiología</option>
                                <option value="3">Traumatología</option>
                                <option value="4">Gastroenterología</option>
                                <option value="5">Ginecobstetricia</option>
                                <option value="6">Imagenología</option>
                                <option value="7">Medicina Nuclear</option>
                            </select>
                        </div>

                        <!-- TARJETAS DE KPIs -->
                        <div class="kpi-grid mb-md">
                            <div class="kpi-card">
                                <i data-lucide="users" class="kpi-icon text-primary"></i>
                                <div class="kpi-data">
                                    <h4 id="lbl-total-encuestas">-</h4>
                                    <p><?= te('documentacion.respuestas_totales') ?></p>
                                </div>
                            </div>
                            <div class="kpi-card">
                                <i data-lucide="star" class="kpi-icon text-warning"></i>
                                <div class="kpi-data">
                                    <h4 id="lbl-promedio-general">-</h4>
                                    <p><?= te('documentacion.promedio_general') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- CONTENEDOR DE GRÁFICOS DINÁMICOS -->
                        <div id="graficos-container" style="display: flex; flex-direction: column; gap: 20px;">
                            <!-- Aquí se renderizan las tarjetas de preguntas (Chart.js) vía JS -->
                            <p class="text-center text-muted"><?= te('documentacion.cargando_metricas') ?></p>
                        </div>

                        <!-- COMENTARIOS LIBRES -->
                        <h3 class="mb-3 mt-4" style="margin-top: 2rem;"><?= te('documentacion.comentarios') ?></h3>
                        <div id="comentarios-container" style="display: flex; flex-direction: column; gap: 15px;">
                            <!-- Comentarios vía JS -->
                        </div>

                    </div>
                    <!-- ESTADO DE LAS ENCUESTAS -->

                    <div
                        id="enc-estado"
                        class="sub-content hidden">


                        <h3 class="mb-3">
                            <?= te('menu.encuestas') ?>
                        </h3>


                        <div class="card-formulario">


                            <div
                                style="
                                    display: flex;
                                    gap: 15px;
                                    align-items: flex-start;
                                ">


                                <i
                                    data-lucide="info"
                                    class="text-primary">
                                </i>


                                <div>

                                    <h4>
                                        <?= te('documentacion.encuestas_pendientes') ?>
                                    </h4>


                                    <p
                                        class="text-muted"
                                        style="
                                            margin-top: 8px;
                                            margin-bottom: 0;
                                        ">

                                        <?= te('documentacion.encuestas_pendientes_detalle') ?>

                                    </p>




                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

        </main>

    </div>







    <script
        src="../../serviciosComunes/vistaGeneral/assets/js/layout.js">
    </script>

    <script>
        window.tokenDocumentos = <?= json_encode($_SESSION['csrf_documentos']) ?>;
        window.documentosBD = <?= json_encode(
                                    $documentos,
                                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                                ) ?>;
    </script>


    <?= scriptTraduccionesJs(['js.doc.', 'js.met.']) ?>
    <script src="Assets/js/documentacion.js?v=20261107-1"></script>
    <script src="Assets/js/metricas.js?v=20261107-1"></script>


    <script>
        lucide.createIcons();
    </script>

</body>

</html>
