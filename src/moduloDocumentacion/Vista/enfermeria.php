<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';

requiereRol([1, 2, 6]);
$usuarioActual = obtenerUsuarioActual();
require_once __DIR__ . '/../../moduloAmbulancias/Model/SolicitudModelo.php';
require_once __DIR__ . '/../../moduloAmbulancias/Model/EdicionSolicitudModelo.php';

// Mismo origen de datos que la edicion de solicitudes: incluye el domicilio.
$ubicaciones = EdicionSolicitudModelo::ubicaciones();

$misSolicitudes = SolicitudModelo::listarPorSolicitante(
    (int) $_SESSION['id_usuario']
);
if (empty($_SESSION['csrf_traslado'])) {
    $_SESSION['csrf_traslado'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['csrf_cancelacion'])) {
    $_SESSION['csrf_cancelacion'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | <?= te('modulos.enfermeria') ?></title>

    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/layout.css?v=2">

    <link rel="stylesheet" href="Assets/css/enfermeria.css?v=2">

    <script src="../../serviciosComunes/vistaGeneral/assets/vendor/lucide-1.70.0.min.js"></script>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261109-1">
</head>


<body>

    <div class="app-layout">

        <div class="sidebar-overlay" id="sidebarOverlay"></div>


        <!-- menu lateral -->

        <aside class="sidebar" id="sidebar">

            <div class="sidebar-header">

                <button class="btn-close-menu" id="closeMenuBtn">
                    <i data-lucide="x"></i>
                </button>

                <img src="../../serviciosComunes/autenticacion/assets/image.png" alt="<?= te('comun.logo_alt') ?>" class="sidebar-logo">

                <span>S.I.G.S.M.</span>

            </div>


            <nav class="sidebar-nav">

                <button class="nav-btn active" data-target="sec-traslados">
                    <i data-lucide="ambulance" class="icon-sm"></i>
                    <?= te('menu.solicitar_traslado') ?>
                </button>
                <button class="nav-btn" data-target="sec-mis-solicitudes">
                    <i data-lucide="list-checks" class="icon-sm"></i>
                    <?= te('menu.mis_solicitudes') ?>
                </button>


                <button class="nav-btn" data-target="sec-doc-estatica">
                    <i data-lucide="folder-open" class="icon-sm"></i>
                    <?= te('menu.documentacion_qr') ?>
                </button>


                <button class="nav-btn" data-target="sec-formularios">
                    <i data-lucide="qr-code" class="icon-sm"></i>
                    <?= te('menu.encuestas') ?>
                </button>
            </nav>

            <?php if (tieneRol('admin') || tieneRol(1)): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> <?= te('menu.cambiar_modulo') ?>
                </a>
            <?php endif; ?>

            <div class="sidebar-profile">

                <div class="avatar-placeholder bg-blue">
                    <i data-lucide="user"></i>
                </div>


                <div class="profile-info">

                    <span class="profile-name">
                        <?= htmlspecialchars($usuarioActual['nombre_usuario'] ?? 'Enfermería') ?>
                    </span>

                    <span class="profile-role">
                        <?= htmlspecialchars($usuarioActual['nombre_rol'] ?? 'Enfermería') ?>
                    </span>

                </div>


                <a href="../../serviciosComunes/autenticacion/logout.php" class="btn-logout" title="<?= te('menu.cerrar_sesion') ?>" aria-label="<?= te('menu.cerrar_sesion') ?>">
                    <i data-lucide="log-out"></i>
                </a>

            </div>

        </aside>



        <!-- contenido -->

        <main class="main-content">

            <header class="topbar">

                <div class="topbar-left">

                    <button class="btn-menu" id="openMenuBtn">
                        <i data-lucide="menu"></i>
                    </button>

                    <h2 class="section-title mb-0">
                        <?= te('modulos.enfermeria') ?>
                    </h2>

                </div>

            </header>


            <section class="content-wrapper">
                <?php if (isset($_SESSION['mensaje_edicion'])): ?>
                    <p role="alert"><?= htmlspecialchars($_SESSION['mensaje_edicion'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php unset($_SESSION['mensaje_edicion']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['mensaje_cancelacion'])): ?>
                    <p role="alert">
                        <?= htmlspecialchars(
                            $_SESSION['mensaje_cancelacion'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                    <?php unset($_SESSION['mensaje_cancelacion']); ?>
                <?php endif; ?>
                <div id="sec-mis-solicitudes" class="view-section hidden">
                    <div class="card-formulario">
                        <h3><?= te('enfermeria.mis_solicitudes_titulo') ?></h3>

                        

                        <?php if (!$misSolicitudes): ?>
                            <p><?= te('enfermeria.sin_solicitudes') ?></p>
                        <?php else: ?>
                            <div style="overflow-x: auto; margin-top: 20px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <?php foreach (
                                                [
                                                    t('campo.solicitud'),
                                                    t('campo.fecha'),
                                                    t('campo.elemento'),
                                                    t('campo.origen'),
                                                    t('campo.destino'),
                                                    t('campo.prioridad'),
                                                    t('campo.estado'),
                                                    t('campo.traslado'),
                                                    t('campo.acciones')
                                                ]
                                                as $titulo
                                            ): ?>
                                                <th style="padding: 12px; text-align: left;
                                           border-bottom: 2px solid #cbd5e1;">
                                                    <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>

                                    <tbody>
    <?php foreach ($misSolicitudes as $solicitud): ?>
        <tr>
            <?php
            $celdas = [
                '#' . $solicitud['id_solicitud'],
                $solicitud['fecha_solicitud'],
                etiquetaBd('elemento', $solicitud['id_tipo_elemento'], $solicitud['elemento']),
                nombreUbicacion($solicitud['origen'], $solicitud['origen_tipo']),
                nombreUbicacion($solicitud['destino'], $solicitud['destino_tipo']),
                textoPrioridad($solicitud['prioridad']),
                $solicitud['id_estado'] !== null
                    ? etiquetaBd('estado', $solicitud['id_estado'], $solicitud['estado'])
                    : etiquetaBd('estado_solicitud', $solicitud['estado_solicitud'], $solicitud['estado']),
                $solicitud['id_traslado'] !== null
                    ? '#' . $solicitud['id_traslado']
                    : t('comun.sin_asignar')
            ];
            ?>

            <?php foreach ($celdas as $celda): ?>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                    <?= htmlspecialchars((string) $celda, ENT_QUOTES, 'UTF-8') ?>
                </td>
            <?php endforeach; ?>

            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                <?php if (
                    $solicitud['estado'] === 'Pendiente'
                    && $solicitud['id_traslado'] === null
                ): ?>
                    <a class="btn-primary" style="display: inline-block; margin-bottom: 12px;"
                       href="../../moduloAmbulancias/Vista/editarSolicitud.php?id=<?= (int) $solicitud['id_solicitud'] ?>"><?= te('comun.editar') ?></a>
                    <form
                        action="../../moduloAmbulancias/Controlador/CancelarSolicitud.php"
                        method="POST"
                        data-validar-form
                    >
                        <input type="hidden" name="token"
                            value="<?= htmlspecialchars(
                                $_SESSION['csrf_cancelacion'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                        <input type="hidden" name="id_solicitud"
                            value="<?= (int) $solicitud['id_solicitud'] ?>">

                        <label>
                            <?= te('enfermeria.motivo_cancelacion') ?>
                            <input type="text" name="motivo"
                                maxlength="500" required
                                data-validar="requerido max:500"
                                data-msg="<?= te('cancelar.error_motivo') ?>">
                        </label>

                        <button type="submit"><?= te('enfermeria.cancelar_solicitud') ?></button>
                    </form>
                <?php elseif ($solicitud['estado'] === 'Cancelada'): ?>
    <details>
        <summary><?= te('enfermeria.ver_cancelacion') ?></summary>

        <p>
            <strong><?= te('campo.motivo') ?>:</strong>
            <?= htmlspecialchars(
                $solicitud['motivo_cancelacion'] ?? t('comun.sin_registro'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <p>
            <strong><?= te('campo.fecha') ?>:</strong>
            <?= htmlspecialchars(
                $solicitud['fecha_cancelacion'] ?? t('comun.sin_registro'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <p>
            <strong><?= te('campo.cancelada_por') ?>:</strong>
            <?= htmlspecialchars(
                $solicitud['cancelada_por'] ?? t('comun.sin_registro'),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </details>
<?php else: ?>
    —
<?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- soli de traslado -->

                <div id="sec-traslados" class="view-section active">

                    <div class="card-formulario">

                        <div class="card-header">

                            <h3>
                                <i data-lucide="ambulance" class="icon-md text-primary"></i>

                                <?= te('enfermeria.nueva_solicitud') ?>
                            </h3>

                            

                        </div>



                        <form id="form-traslado" class="mt-3" data-validar-form>
                            <input
                                type="hidden"
                                id="csrf-traslado"
                                value="<?= htmlspecialchars(
                                            $_SESSION['csrf_traslado'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">


                            <!-- TIPO DE ELEMENTO -->

                            <div class="form-group">

                                <label>
                                    <?= te('enfermeria.tipo_elemento') ?>
                                </label>

                                <select id="tipo-elemento" class="form-input" required
                                    data-validar="requerido"
                                    data-msg-requerido="<?= te('js.enf.error_tipo') ?>">

                                    <option value="">
                                        <?= te('enfermeria.seleccione_tipo') ?>
                                    </option>

                                    <option value="1">
                                        <?= htmlspecialchars(etiquetaBd('elemento', 1, 'Paciente'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="2">
                                        <?= htmlspecialchars(etiquetaBd('elemento', 2, 'Órgano'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="3">
                                        <?= htmlspecialchars(etiquetaBd('elemento', 3, 'Muestra biológica'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="4">
                                        <?= htmlspecialchars(etiquetaBd('elemento', 4, 'Cadetería de trámites'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="5">
                                        <?= htmlspecialchars(etiquetaBd('elemento', 5, 'Insumo médico'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="6">
                                        <?= htmlspecialchars(etiquetaBd('elemento', 6, 'Equipamiento'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                </select>

                            </div>



                            <!-- datos paciente -->

                            <div id="datos-paciente" class="hidden">

                                <div class="form-group">

                                    <label>
                                        <?= te('enfermeria.cedula_paciente') ?>
                                    </label>

                                    <input type="text" id="ci-paciente" class="form-input"
                                        placeholder="<?= te('enfermeria.cedula_placeholder') ?>" autocomplete="off" inputmode="numeric"
                                        data-validar-si="tipo-elemento=1"
                                        data-validar="requerido patron" data-patron="^(?:[ .-]*[0-9]){7,8}[ .-]*$"
                                        data-msg-requerido="<?= te('js.enf.error_sin_cedula') ?>"
                                        data-msg-patron="<?= te('paciente.error_cedula') ?>">

                                    <p id="estado-paciente" class="text-muted">
                                        <?= te('enfermeria.cedula_ayuda') ?>
                                    </p>

                                </div>

                            </div>



                            <!-- ORIGEN Y DESTINO -->

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>
                                        <?= te('campo.origen') ?>
                                    </label>

                                    <select id="origen" class="form-input" required
                                        data-validar="requerido"
                                        data-msg-requerido="<?= te('js.enf.error_lugares') ?>">

                                        <option value="">
                                            <?= te('enfermeria.seleccione_origen') ?>
                                        </option>

                                        <?php foreach ($ubicaciones as $ubicacion): ?>
                                            <option value="<?= (int) $ubicacion['id_ubicacion'] ?>">
                                                <?= htmlspecialchars(nombreUbicacion($ubicacion['nombre'], $ubicacion['tipo_ubicacion']), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                </div>


                                <div class="form-group">

                                    <label>
                                        <?= te('campo.destino') ?>
                                    </label>

                                    <select id="destino" class="form-input" required
                                        data-validar="requerido distinto:origen"
                                        data-msg-requerido="<?= te('js.enf.error_lugares') ?>"
                                        data-msg-distinto="<?= te('js.enf.error_mismo_lugar') ?>">

                                        <option value="">
                                            <?= te('enfermeria.seleccione_destino') ?>
                                        </option>

                                        <?php foreach ($ubicaciones as $ubicacion): ?>
                                            <option value="<?= (int) $ubicacion['id_ubicacion'] ?>">
                                                <?= htmlspecialchars(nombreUbicacion($ubicacion['nombre'], $ubicacion['tipo_ubicacion']), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                </div>

                            </div>



                            <!-- CANAL -->

                            <div class="form-group">

                                <label>
                                    <?= te('enfermeria.canal') ?>
                                </label>

                                <select id="canal-solicitud" class="form-input" required
                                    data-validar="requerido"
                                    data-msg-requerido="<?= te('js.enf.error_canal') ?>">

                                    <option value="">
                                        <?= te('enfermeria.seleccione_canal') ?>
                                    </option>

                                    <option value="1">
                                        <?= htmlspecialchars(etiquetaBd('canal', 1, 'Gestión Salud'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="2">
                                        <?= htmlspecialchars(etiquetaBd('canal', 2, 'Correo'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="3">
                                        <?= htmlspecialchars(etiquetaBd('canal', 3, 'Papel'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                </select>

                            </div>



                            <!-- PRIORIDAD Y HORA LIMITE -->

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>
                                        <?= te('enfermeria.prioridad') ?>
                                    </label>

                                    <select id="prioridad" class="form-input" required
                                        data-validar="requerido en:normal,alta,maxima"
                                        data-msg="<?= te('solicitud.error_prioridad_acento') ?>">

                                        <option value="normal">
                                            <?= te('prioridad.normal') ?>
                                        </option>

                                        <option value="alta">
                                            <?= te('prioridad.alta') ?>
                                        </option>

                                        <option value="maxima">
                                            <?= te('prioridad.maxima') ?>
                                        </option>

                                    </select>

                                    <p id="mensaje-prioridad" class="text-muted" hidden></p>

                                </div>


                                <div class="form-group">

                                    <label>
                                        <?= te('campo.limite') ?>
                                    </label>

                                    <input type="datetime-local" id="fecha-hora-limite" class="form-input"
                                        data-validar="futura"
                                        data-msg-futura="<?= te('js.enf.error_fecha') ?>">

                                    <p class="text-muted">
                                        <?= te('enfermeria.limite_ayuda') ?>
                                    </p>

                                </div>

                            </div>



                            <!-- OBSERVACIONES -->

                            <div class="form-group">

                                <label>
                                    <?= te('campo.observaciones') ?>
                                </label>

                                <textarea id="observaciones" class="form-input" rows="3" maxlength="500"
                                    placeholder="<?= te('enfermeria.observaciones_placeholder') ?>"
                                    data-validar="max:500"
                                    data-msg-max="<?= te('solicitud.error_obs_largo') ?>"></textarea>

                            </div>



                            <!-- DATOS AUTOMATICOS -->

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>
                                        <?= te('campo.solicitante') ?>
                                    </label>

                                    <input
                                        type="text"
                                        id="solicitante"
                                        class="form-input"
                                        value="<?= htmlspecialchars(
                                                    $usuarioActual['nombre_usuario'] ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                        readonly>

                                </div>


                                <div class="form-group">

                                    <label>
                                        <?= te('enfermeria.estado_inicial') ?>
                                    </label>

                                    <input type="text" id="estado-solicitud" class="form-input" value="<?= htmlspecialchars(etiquetaBd('estado_solicitud', 'Pendiente', 'Pendiente'), ENT_QUOTES, 'UTF-8') ?>"
                                        readonly>

                                </div>

                            </div>



                            <button type="submit" class="btn-primary w-100 mt-2">

                                <i data-lucide="send"></i>

                                <?= te('enfermeria.enviar') ?>

                            </button>

                        </form>



                        <div id="mensaje-exito" class="alerta-exito hidden mt-3">

                            <i data-lucide="check-circle"></i>

                            <?= te('enfermeria.enviada') ?>

                        </div>

                    </div>

                </div>



                <!-- documentacion qr -->

                <?php require __DIR__ . '/directorioQR.php'; ?>

                <div id="sec-formularios" class="view-section hidden">

                    <div class="generador-container">


                        <div class="card-formulario">

                            <div class="card-header">

                                <h3>

                                    <i data-lucide="qr-code" class="icon-md text-primary"></i>

                                    <?= te('enfermeria.generar_qr_titulo') ?>

                                </h3>

                                

                            </div>



                            <form id="form-generador" class="mt-3">

                                <div class="form-group">

                                    <label>
                                        <?= te('enfermeria.area_categoria') ?>
                                    </label>

                                    <select id="qr-categoria" class="form-input" required>

                                        <option value="">
                                            <?= te('enfermeria.seleccione_area') ?>
                                        </option>

                                        <option value="1">
                                            Urología
                                        </option>

                                        <option value="2">
                                            Cardiología
                                        </option>

                                        <option value="3">
                                            Traumatología
                                        </option>

                                        <option value="4">
                                            Gastroenterología
                                        </option>

                                        <option value="5">
                                            Ginecobstetricia
                                        </option>

                                        <option value="6">
                                            Imagenología
                                        </option>

                                        <option value="7">
                                            Medicina Nuclear
                                        </option>

                                    </select>

                                </div>







                                <button type="button" id="btn-generar-qr" class="btn-primary w-100 mt-2">

                                    <i data-lucide="qr-code"></i>

                                    <?= te('enfermeria.generar_qr') ?>

                                </button>

                            </form>

                        </div>



                        <!-- RESULTADO QR -->

                        <div class="card-resultado-qr">

                            <div id="qr-vacio" class="qr-placeholder">

                                <i data-lucide="smartphone" class="icon-xl text-muted"></i>

                                <p>
                                    <?= te('enfermeria.qr_vacio') ?>
                                </p>

                            </div>


                            <div id="qr-generado" class="qr-display hidden">

                                <h4 class="mb-2">
                                    <?= te('enfermeria.codigo_encuesta') ?>
                                </h4>


                                <img id="qr-dinamico-img" src="" alt="<?= te('enfermeria.qr_alt') ?>" class="qr-img">


                                <div class="qr-info mt-3">

                                    <p>
                                        <strong><?= te('enfermeria.area') ?>:</strong>
                                        <span id="lbl-categoria">-</span>
                                    </p>

                                    <p class="text-muted">
                                        <?= te('enfermeria.encuesta_nombre') ?>
                                    </p>

                                </div>


                                <a id="btn-abrir-encuesta" href="#" target="_blank" class="btn-primary w-100 mt-3 btn-abrir-directo">
                                    <i data-lucide="external-link"></i>
                                    <?= te('enfermeria.abrir_encuesta') ?>
                                </a>

                                <button type="button" class="btn-outline w-100 mt-2" onclick="window.print()">
                                    <i data-lucide="printer"></i>
                                    <?= te('enfermeria.imprimir_qr') ?>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>


            </section>

        </main>

    </div>



    <!-- JAVASCRIPT -->

    <script src="../../serviciosComunes/vistaGeneral/assets/js/layout.js"></script>
    <?= scriptTraduccionesJs(['js.enf.']) ?>
    <script src="../../serviciosComunes/vistaGeneral/assets/js/validacion.js?v=20261109-2"></script>
    <script src="Assets/js/enfermeria.js?v=20261107-1"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (new URLSearchParams(window.location.search).get('vista') === 'mis-solicitudes') {
                const boton = document.querySelector('[data-target="sec-mis-solicitudes"]');
                if (boton) boton.click();
            }
        });
    </script>
</body>

</html>
