<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 3]);
$usuarioActual = obtenerUsuarioActual();
require_once __DIR__ . '/../Model/SolicitudModelo.php';

$solicitudesPendientes = SolicitudModelo::listarPendientes();
$solicitudesCanceladas = SolicitudModelo::listarCanceladas();
require_once __DIR__ . '/../Model/SeguimientoModelo.php';

$trasladosEnlace = SeguimientoModelo::listar(null);

$activosEnlace = array_filter(
    $trasladosEnlace,
    fn($viaje) => !in_array((int) $viaje['id_estado'], [6, 7], true)
);
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | Unidad de Enlace</title>

    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/layout.css">

    <link rel="stylesheet" href="assets/css/unidadEnlace.css?v=20261021-1">

    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261021-1">
</head>


<body>

    <div class="app-layout">


        <!-- ================================================= -->
        <!-- SIDEBAR -->
        <!-- ================================================= -->

        <div class="sidebar-overlay" id="sidebarOverlay">
        </div>


        <aside class="sidebar" id="sidebar">


            <div class="sidebar-header">

                <button class="btn-close-menu" id="closeMenuBtn">

                    <i data-lucide="layout-dashboard"></i>

                </button>


                <img src="../../serviciosComunes/autenticacion/assets/image.png" alt="Logo" class="sidebar-logo">


                <span>S.I.G.S.M.</span>

            </div>



            <nav class="sidebar-nav">

                <button class="nav-btn active" data-target="sec-traslados">

                    <i data-lucide="ambulance" class="icon-sm">
                    </i>

                    Control de Traslados
                </button>
            </nav>

            <?php if (tieneRol('admin') || tieneRol(1)): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> Cambiar Módulo
                </a>
            <?php endif; ?>

            <div class="sidebar-profile">

                <i data-lucide="layout-dashboard"></i>

                <div class="profile-info">

                    <span class="profile-name">
                        <?= htmlspecialchars($usuarioActual['nombre_usuario'] ?? 'Coord. Central') ?>
                    </span>
                    <span class="profile-role">
                        <?= htmlspecialchars($usuarioActual['nombre_rol'] ?? 'Unidad de Enlace') ?>
                    </span>

                </div>

                <a href="../../serviciosComunes/autenticacion/logout.php" class="btn-logout" title="Cerrar Sesión">

                    <i data-lucide="log-out"></i>

                </a>

            </div>

        </aside>



        <!-- ================================================= -->
        <!-- CONTENIDO PRINCIPAL -->
        <!-- ================================================= -->

        <main class="main-content">


            <header class="topbar">

                <div class="topbar-left">

                    <button class="btn-menu" id="openMenuBtn">

                        <i data-lucide="menu"></i>

                    </button>


                    <h2 class="section-title mb-0">
                        Portal Unidad de Enlace
                    </h2>

                </div>

            </header>



            <section class="content-wrapper">
                <?php if (isset($_SESSION['mensaje_traslado'])): ?>
    <p role="status">
        <?= htmlspecialchars(
            $_SESSION['mensaje_traslado'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>
    <?php unset($_SESSION['mensaje_traslado']); ?>
<?php endif; ?>


                <!-- ========================================= -->
                <!-- SECCIÓN TRASLADOS -->
                <!-- ========================================= -->

                <div id="sec-traslados" class="view-section active">


                    <!-- SUB NAVEGACIÓN -->

                    <div class="sub-nav">

                        <button class="sub-btn active" data-sub="sub-curso">

                            Traslados activos

                        </button>


                        <button class="sub-btn" data-sub="sub-pendientes">

                            Solicitudes pendientes

                        </button>


                        <button class="sub-btn" data-sub="sub-canceladas">Solicitudes canceladas</button>

                        <button class="sub-btn" data-sub="sub-historial">

                            Historial

                        </button>

                    </div>



                    <!-- ===================================== -->
                    <!-- TRASLADOS ACTIVOS -->
                    <!-- ===================================== -->

                    <div id="sub-curso" class="sub-content active">

    <?php if (!$activosEnlace): ?>
        <p>No hay traslados activos.</p>
    <?php endif; ?>

    <?php foreach ($activosEnlace as $viaje): ?>
        <div class="card-traslado">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <div class="header-info">
                    <h3>
                        Traslado #<?= (int) $viaje['id_traslado'] ?>
                        — <?= htmlspecialchars(
                            $viaje['matricula'] ?? 'Sin vehículo',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <span class="badge badge-warning">
                        <?= htmlspecialchars(
                            $viaje['estado'] ?? 'Sin estado',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>
                </div>

                <i data-lucide="chevron-down" class="accordion-icon"></i>
            </div>

            <div class="accordion-content open">
                <div style="padding: 15px;">
                    <?php
                    $datosTraslado = [
                        'Solicitud' => '#' . $viaje['id_solicitud'],
                        'Tipo de elemento' => $viaje['elemento'],
                        'Origen' => $viaje['origen'],
                        'Destino' => $viaje['destino'],
                        'Chofer' => $viaje['chofer'] ?? 'Sin asignar',
                        'Enfermería' => $viaje['enfermeria'] ?? 'Sin asignar'
                    ];
                    ?>

                    <?php foreach ($datosTraslado as $etiqueta => $valor): ?>
                        <p class="detail-item">
                            <strong>
                                <?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?>:
                            </strong>
                            <?= htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>
                    <!-- ===================================== -->
                    <!-- SOLICITUDES PENDIENTES -->
                    <!-- ===================================== -->

                    <div id="sub-pendientes" class="sub-content hidden">

                        <?php if (empty($solicitudesPendientes)): ?>
                            <p>No hay solicitudes pendientes.</p>
                        <?php endif; ?>

                        <?php foreach ($solicitudesPendientes as $solicitud): ?>
                            <div class="card-pendiente">

                                <div class="item-header">
                                    <h3 class="card-title">
                                        Solicitud #<?= (int) $solicitud['id_solicitud'] ?>
                                    </h3>

                                    <span class="badge badge-gray">Pendiente</span>
                                </div>

                                <?php
                                $detalles = [
                                    'Tipo de elemento' => $solicitud['tipo_elemento'],
                                    'Origen' => $solicitud['origen'],
                                    'Destino' => $solicitud['destino'],
                                    'Canal' => $solicitud['canal'],
                                    'Solicitante' => $solicitud['solicitante'],
                                    'Fecha de solicitud' => $solicitud['fecha_solicitud'],
                                    'Prioridad' => $solicitud['prioridad'],
                                    'Debe llegar antes de' =>
                                    $solicitud['fecha_hora_limite'] ?? 'Sin horario limite',
                                    'Observaciones' =>
                                    $solicitud['observaciones'] ?? 'Sin observaciones'
                                ];

                                if ($solicitud['ci_paciente'] !== null) {
                                    $detalles['Cedula del paciente'] =
                                        $solicitud['ci_paciente'];

                                    $detalles['Paciente'] = trim(
                                        ($solicitud['nombre_paciente'] ?? '') . ' ' .
                                            ($solicitud['apellido_paciente'] ?? '')
                                    );
                                }
                                ?>

                                <?php foreach ($detalles as $etiqueta => $valor): ?>
                                    <p class="detail-item">
                                        <strong>
                                            <?= htmlspecialchars(
                                                $etiqueta,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>:
                                        </strong>

                                        <?= htmlspecialchars(
                                            (string) $valor,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                <?php endforeach; ?>
                                <?php if (tieneRol([1, 3])): ?>
    <a
        href="asignar.php?id=<?= (int) $solicitud['id_solicitud'] ?>"
        class="btn-action btn-success"
    >
        Asignar traslado
    </a>
<?php endif; ?>

                            </div>
                        <?php endforeach; ?>

                    </div>

                    <!-- ===================================== -->
                    <!-- HISTORIAL -->
                    <!-- ===================================== -->

                    <div id="sub-canceladas" class="sub-content hidden">
                        <h3>Solicitudes canceladas</h3>
                        <p class="text-muted">Registro de solicitudes canceladas antes de asignar un traslado.</p>
                        <label class="filtro-listado">Buscar cancelación
                            <input type="search" class="form-input" data-filtrar="tabla-canceladas"
                                placeholder="Solicitud, origen, destino, usuario o motivo">
                        </label>
                        <div class="tabla-responsive">
                            <table class="tabla-historial" id="tabla-canceladas">
                                <thead><tr>
                                    <th>Solicitud</th><th>Elemento</th><th>Origen</th><th>Destino</th>
                                    <th>Solicitante</th><th>Cancelada por</th><th>Fecha</th><th>Motivo</th>
                                </tr></thead>
                                <tbody>
                                <?php if (!$solicitudesCanceladas): ?>
                                    <tr><td colspan="8">No hay solicitudes canceladas.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($solicitudesCanceladas as $cancelada): ?>
                                    <tr data-fila-busqueda>
                                        <td>#<?= (int) $cancelada['id_solicitud'] ?></td>
                                        <?php foreach (['elemento', 'origen', 'destino', 'solicitante',
                                            'cancelada_por', 'fecha_cancelacion', 'motivo_cancelacion'] as $campo): ?>
                                            <td><?= htmlspecialchars((string) ($cancelada[$campo] ?? 'Sin registro'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="hidden" data-sin-resultados="tabla-canceladas">No hay coincidencias.</p>
                    </div>

                    <div id="sub-historial" class="sub-content hidden">


                        <div class="search-container mb-md">

                            <i data-lucide="search" class="icon-sm text-muted">
                            </i>


                            <input type="search" aria-label="Buscar en el historial" data-filtrar="tabla-estados" class="search-input"
                                placeholder="Buscar por traslado, estado o usuario...">

                        </div>



                        <div class="tabla-responsive">


                            <table class="tabla-historial" id="tabla-estados">


                                <thead>

                                    <tr>

                                        <th>
                                            Traslado
                                        </th>

                                        <th>
                                            Fecha/Hora
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                        <th>
                                            Usuario
                                        </th>

                                        <th>
                                            Observaciones
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>
    <?php if (!$trasladosEnlace): ?>
        <tr>
            <td colspan="5">No hay historial de traslados.</td>
        </tr>
    <?php endif; ?>

    <?php foreach ($trasladosEnlace as $viaje): ?>
        <?php foreach (
            SeguimientoModelo::historial((int) $viaje['id_traslado'])
            as $evento
        ): ?>
            <tr data-fila-busqueda>
                <td>
                    <strong>
                        Traslado #<?= (int) $viaje['id_traslado'] ?>
                    </strong>
                </td>

                <?php foreach (
                    ['fecha_hora', 'estado', 'usuario', 'observaciones']
                    as $campo
                ): ?>
                    <td>
                        <?= htmlspecialchars(
                            (string) ($evento[$campo] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
</tbody>

                            </table>

                        </div>

                    </div>


                </div>

            </section>

        </main>

    </div>



    <!-- ===================================================== -->
    <!-- SCRIPTS -->
    <!-- ===================================================== -->

    <script src="../../serviciosComunes/vistaGeneral/assets/js/layout.js"></script>

<script src="assets/js/unidadEnlace.js?v=20261021-1"></script>    <script>
        if (typeof lucide !== "undefined") { lucide.createIcons(); }
    </script>


</body>

</html>
