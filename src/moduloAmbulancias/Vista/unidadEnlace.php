<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 3]);
$usuarioActual = obtenerUsuarioActual();
require_once __DIR__ . '/../Model/SolicitudModelo.php';

$solicitudesPendientes = SolicitudModelo::listarPendientes();
$solicitudesCanceladas = SolicitudModelo::listarCanceladas();
require_once __DIR__ . '/../Model/SeguimientoModelo.php';

$trasladosEnlace = SeguimientoModelo::listar(null, tieneRol([1, 3]));

$activosEnlace = array_filter(
    $trasladosEnlace,
    fn($viaje) => !in_array((int) $viaje['id_estado'], [6, 7], true)
);
?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | <?= te('modulos.unidad_enlace') ?></title>

    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/layout.css">

    <link rel="stylesheet" href="assets/css/unidadEnlace.css?v=20261021-1">

    <script src="../../serviciosComunes/vistaGeneral/assets/vendor/lucide-1.70.0.min.js"></script>

    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261109-1">
</head>


<body>

    <div class="app-layout">



        <!-- SIDEBAR -->


        <div class="sidebar-overlay" id="sidebarOverlay">
        </div>


        <aside class="sidebar" id="sidebar">


            <div class="sidebar-header">

                <button class="btn-close-menu" id="closeMenuBtn">

                    <i data-lucide="layout-dashboard"></i>

                </button>


                <img src="../../serviciosComunes/autenticacion/assets/image.png" alt="<?= te('comun.logo_alt') ?>" class="sidebar-logo">


                <span>S.I.G.S.M.</span>

            </div>



            <nav class="sidebar-nav">

                <button class="nav-btn active" data-target="sec-traslados">

                    <i data-lucide="ambulance" class="icon-sm">
                    </i>

                    <?= te('menu.control_traslados') ?>
                </button>
            </nav>

            <?php if (tieneRol([1, 3])): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> <?= te('menu.cambiar_modulo') ?>
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

                <a href="../../serviciosComunes/autenticacion/logout.php" class="btn-logout" title="<?= te('menu.cerrar_sesion') ?>" aria-label="<?= te('menu.cerrar_sesion') ?>">

                    <i data-lucide="log-out"></i>

                </a>

            </div>

        </aside>



        <!-- CONTENIDO PRINCIPAL -->
        

        <main class="main-content">


            <header class="topbar">

                <div class="topbar-left">

                    <button class="btn-menu" id="openMenuBtn">

                        <i data-lucide="menu"></i>

                    </button>


                    <h2 class="section-title mb-0">
                        <?= te('modulos.unidad_enlace') ?>
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


                <!-- SECCIÓN TRASLADOS -->

                <div id="sec-traslados" class="view-section active">


                    <!-- SUB NAVEGACIÓN -->

                    <div class="sub-nav">

                        <button class="sub-btn active" data-sub="sub-curso">

                            <?= te('traslado.activos') ?>

                        </button>


                        <button class="sub-btn" data-sub="sub-pendientes">

                            <?= te('enlace.solicitudes_pendientes') ?>

                        </button>


                        <button class="sub-btn" data-sub="sub-canceladas"><?= te('enlace.solicitudes_canceladas') ?></button>

                        <button class="sub-btn" data-sub="sub-historial">

                            <?= te('enlace.historial') ?>

                        </button>

                    </div>



                    <!-- TRASLADOS ACTIVOS -->

                    <div id="sub-curso" class="sub-content active">

                        <?php if (!$activosEnlace): ?>
                            <p><?= te('enlace.sin_activos') ?></p>
                        <?php endif; ?>

                        <?php foreach ($activosEnlace as $viaje): ?>
                            <div class="card-traslado">
                                <div class="accordion-header" onclick="toggleAccordion(this)">
                                    <div class="header-info">
                                        <h3>
                                            <?= te('traslado.numero', ['id' => (int) $viaje['id_traslado']]) ?>
                                            — <?= htmlspecialchars(
                                                    textoVehiculoTraslado($viaje['matricula'], $viaje['proveedor']),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                        </h3>

                                        <span class="badge badge-warning">
                                            <?= htmlspecialchars(
                                                $viaje['estado'] !== null ? etiquetaBd('estado', $viaje['id_estado'], $viaje['estado']) : t('traslado.sin_estado'),
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
                                            t('campo.solicitud') => '#' . $viaje['id_solicitud'],
                                            t('campo.tipo_elemento') => etiquetaBd('elemento', $viaje['id_tipo_elemento'], $viaje['elemento']),
                                            t('campo.origen') => nombreUbicacion($viaje['origen'], $viaje['origen_tipo']),
                                            t('campo.destino') => nombreUbicacion($viaje['destino'], $viaje['destino_tipo']),
                                            t('campo.chofer') => textoChoferTraslado($viaje['chofer'], $viaje['proveedor']),
                                            t('campo.enfermeria') => $viaje['enfermeria'] ?? t('comun.sin_asignar')
                                        ];
                                        if (!empty($viaje['direccion_domicilio'])) {
                                            $datosTraslado[t('campo.direccion_domicilio')] = $viaje['direccion_domicilio'];
                                        }
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
                    <!-- SOLICITUDES PENDIENTES -->

                    <div id="sub-pendientes" class="sub-content hidden">

                        <?php if (empty($solicitudesPendientes)): ?>
                            <p><?= te('enlace.sin_pendientes') ?></p>
                        <?php endif; ?>

                        <?php foreach ($solicitudesPendientes as $solicitud): ?>
                            <div class="card-pendiente">

                                <div class="item-header">
                                    <h3 class="card-title">
                                        <?= te('solicitud.numero', ['id' => (int) $solicitud['id_solicitud']]) ?>
                                    </h3>

                                    <span class="badge badge-gray"><?= htmlspecialchars(etiquetaBd('estado_solicitud', 'Pendiente', 'Pendiente'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>

                                <?php
                                $detalles = [
                                    t('campo.tipo_elemento') => etiquetaBd('elemento', $solicitud['id_tipo_elemento'], $solicitud['tipo_elemento']),
                                    t('campo.origen') => nombreUbicacion($solicitud['origen'], $solicitud['origen_tipo']),
                                    t('campo.destino') => nombreUbicacion($solicitud['destino'], $solicitud['destino_tipo']),
                                    t('campo.canal') => etiquetaBd('canal', $solicitud['id_canal'], $solicitud['canal']),
                                    t('campo.solicitante') => $solicitud['solicitante'],
                                    t('campo.fecha_solicitud') => $solicitud['fecha_solicitud'],
                                    t('campo.prioridad') => textoPrioridad($solicitud['prioridad']),
                                    t('campo.limite') =>
                                    $solicitud['fecha_hora_limite'] ?? t('enlace.sin_limite'),
                                    t('campo.observaciones') =>
                                    $solicitud['observaciones'] ?? t('comun.sin_observaciones')
                                ];

                                if ($solicitud['ci_paciente'] !== null) {
                                    $detalles[t('campo.cedula_paciente')] =
                                        $solicitud['ci_paciente'];

                                    $detalles[t('campo.paciente')] = trim(
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
                                        class="btn-action btn-success">
                                        <?= te('enlace.asignar') ?>
                                    </a>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>

                    </div>

                    <!-- HISTORIAL -->

                    <div id="sub-canceladas" class="sub-content hidden">
                        <h3><?= te('enlace.solicitudes_canceladas') ?></h3>

                        <label class="filtro-listado"><?= te('enlace.buscar_cancelacion') ?>
                            <input type="search" class="form-input" data-filtrar="tabla-canceladas"
                                placeholder="<?= te('enlace.buscar_cancelacion_placeholder') ?>">
                        </label>
                        <div class="tabla-responsive">
                            <table class="tabla-historial" id="tabla-canceladas">
                                <thead>
                                    <tr>
                                        <th><?= te('campo.solicitud') ?></th>
                                        <th><?= te('campo.elemento') ?></th>
                                        <th><?= te('campo.origen') ?></th>
                                        <th><?= te('campo.destino') ?></th>
                                        <th><?= te('campo.solicitante') ?></th>
                                        <th><?= te('campo.cancelada_por') ?></th>
                                        <th><?= te('campo.fecha') ?></th>
                                        <th><?= te('campo.motivo') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$solicitudesCanceladas): ?>
                                        <tr>
                                            <td colspan="8"><?= te('enlace.sin_canceladas') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($solicitudesCanceladas as $cancelada): ?>
                                        <tr data-fila-busqueda>
                                            <td>#<?= (int) $cancelada['id_solicitud'] ?></td>
                                            <?php foreach (
                                                [
                                                    'elemento',
                                                    'origen',
                                                    'destino',
                                                    'solicitante',
                                                    'cancelada_por',
                                                    'fecha_cancelacion',
                                                    'motivo_cancelacion'
                                                ] as $campo
                                            ): ?>
                                                <?php
                                                if ($campo === 'elemento') {
                                                    $valorCancelada = etiquetaBd('elemento', $cancelada['id_tipo_elemento'], $cancelada['elemento']);
                                                } elseif ($campo === 'origen' || $campo === 'destino') {
                                                    $valorCancelada = nombreUbicacion($cancelada[$campo], $cancelada[$campo . '_tipo']);
                                                } else {
                                                    $valorCancelada = $cancelada[$campo] ?? t('comun.sin_registro');
                                                }
                                                ?>
                                                <td><?= htmlspecialchars((string) $valorCancelada, ENT_QUOTES, 'UTF-8') ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="hidden" data-sin-resultados="tabla-canceladas"><?= te('comun.sin_coincidencias') ?></p>
                    </div>

                    <div id="sub-historial" class="sub-content hidden">


                        <div class="search-container mb-md">

                            <i data-lucide="search" class="icon-sm text-muted">
                            </i>


                            <input type="search" aria-label="<?= te('enlace.buscar_historial') ?>" id="buscar-historial" class="search-input"
                                placeholder="<?= te('enlace.buscar_historial_placeholder') ?>">

                        </div>



                        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:end; margin-bottom:20px;">
                            <label for="historial-cedula"><?= te('campo.cedula_paciente') ?>
                                <input id="historial-cedula" class="form-input" type="search"
                                    inputmode="numeric" placeholder="<?= te('enlace.cedula_placeholder') ?>" maxlength="20">
                            </label>
                            <label for="historial-vehiculo"><?= te('campo.vehiculo') ?>
                                <select id="historial-vehiculo" class="form-input">
                                    <option value=""><?= te('enlace.todos_vehiculos') ?></option>
                                    <?php
                                    $vehiculosHistorial = [];
                                    foreach ($trasladosEnlace as $viajeHistorial) {
                                        if ($viajeHistorial['id_vehiculo'] !== null) {
                                            $vehiculosHistorial[(int) $viajeHistorial['id_vehiculo']] =
                                                $viajeHistorial['matricula'] ?? t('enlace.vehiculo_numero', ['id' => (int) $viajeHistorial['id_vehiculo']]);
                                        }
                                    }
                                    asort($vehiculosHistorial);
                                    ?>
                                    <?php foreach ($vehiculosHistorial as $idVehiculo => $matricula): ?>
                                        <option value="<?= (int) $idVehiculo ?>"><?= htmlspecialchars($matricula, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                    <option value="sin-vehiculo"><?= te('enlace.sin_vehiculo_asignado') ?></option>
                                </select>
                            </label>
                            <button id="limpiar-historial" class="btn-primary" type="button"><?= te('enlace.limpiar_filtros') ?></button>
                        </div>
                        <p id="historial-resultados" role="status" aria-live="polite"></p>

                        <div class="tabla-responsive">


                            <table class="tabla-historial" id="tabla-estados">


                                <thead>

                                    <tr>

                                        <th><?= te('campo.traslado') ?></th>
                                        <th><?= te('campo.cedula_paciente') ?></th>
                                        <th><?= te('campo.vehiculo') ?></th>
                                        <th><?= te('campo.fecha_hora') ?></th>
                                        <th><?= te('campo.estado') ?></th>
                                        <th><?= te('campo.usuario') ?></th>
                                        <th><?= te('campo.observaciones') ?></th>

                                    </tr>

                                </thead>


                                <tbody>
                                    <?php if (!$trasladosEnlace): ?>
                                        <tr id="historial-vacio">
                                            <td colspan="7"><?= te('enlace.sin_historial') ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($trasladosEnlace as $viaje): ?>
                                        <?php foreach (
                                            SeguimientoModelo::historial((int) $viaje['id_traslado'])
                                            as $evento
                                        ): ?>
                                            <tr data-fila-busqueda
                                                data-ci="<?= htmlspecialchars((string) ($viaje['ci_paciente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-vehiculo="<?= $viaje['id_vehiculo'] !== null ? (int) $viaje['id_vehiculo'] : 'sin-vehiculo' ?>"
                                                data-traslado="<?= (int) $viaje['id_traslado'] ?>">
                                                <td>
                                                    <strong>
                                                        <?= te('traslado.numero', ['id' => (int) $viaje['id_traslado']]) ?>
                                                    </strong>
                                                </td>

                                                <td><?= htmlspecialchars((string) ($viaje['ci_paciente'] ?? t('comun.no_corresponde')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars(textoVehiculoTraslado($viaje['matricula'], $viaje['proveedor']), ENT_QUOTES, 'UTF-8') ?></td>
                                                <?php foreach (
                                                    ['fecha_hora', 'estado', 'usuario', 'observaciones']
                                                    as $campo
                                                ): ?>
                                                    <td>
                                                        <?= htmlspecialchars(
                                                            $campo === 'estado'
                                                                ? etiquetaBd('estado', $evento['id_estado'], $evento['estado'])
                                                                : ($campo === 'observaciones'
                                                                    ? textoObservacionHistorial($evento['observaciones'])
                                                                    : (string) ($evento[$campo] ?? '')),
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



    <!-- SCRIPTS -->

    <script src="../../serviciosComunes/vistaGeneral/assets/js/layout.js"></script>

    <script src="assets/js/unidadEnlace.js?v=20261021-1"></script>
    <script>
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    </script>


    <?= scriptTraduccionesJs(['js.historial.']) ?>
    <script src="assets/js/filtrosHistorial.js?v=20261107-1"></script>
</body>

</html>