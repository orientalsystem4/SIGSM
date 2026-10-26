<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';

requiereRol([1, 2, 6]);
$usuarioActual = obtenerUsuarioActual();
require_once __DIR__ . '/../../moduloAmbulancias/Model/SolicitudModelo.php';

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
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>S.I.G.S.M. | Portal Enfermería</title>

    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/layout.css?v=2">

    <link rel="stylesheet" href="Assets/css/enfermeria.css?v=2">

    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261021-1">
</head>


<body>

    <div class="app-layout">

        <div class="sidebar-overlay" id="sidebarOverlay"></div>


        <!-- =====================================================
         SIDEBAR
    ====================================================== -->

        <aside class="sidebar" id="sidebar">

            <div class="sidebar-header">

                <button class="btn-close-menu" id="closeMenuBtn">
                    <i data-lucide="x"></i>
                </button>

                <img src="../../serviciosComunes/autenticacion/assets/image.png" alt="Logo" class="sidebar-logo">

                <span>S.I.G.S.M.</span>

            </div>


            <nav class="sidebar-nav">

                <button class="nav-btn active" data-target="sec-traslados">
                    <i data-lucide="ambulance" class="icon-sm"></i>
                    Solicitar Traslado
                </button>
                <button class="nav-btn" data-target="sec-mis-solicitudes">
                    <i data-lucide="list-checks" class="icon-sm"></i>
                    Mis solicitudes
                </button>


                <button class="nav-btn" data-target="sec-doc-estatica">
                    <i data-lucide="folder-open" class="icon-sm"></i>
                    Documentación (QR)
                </button>


                <button class="nav-btn" data-target="sec-formularios">
                    <i data-lucide="qr-code" class="icon-sm"></i>
                    Encuestas
                </button>
            </nav>

            <?php if (tieneRol('admin') || tieneRol(1)): ?>
                <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub">
                    <i data-lucide="grid"></i> Cambiar Módulo
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


                <a href="../../serviciosComunes/autenticacion/logout.php" class="btn-logout" title="Cerrar Sesión">
                    <i data-lucide="log-out"></i>
                </a>

            </div>

        </aside>



        <!-- =====================================================
         CONTENIDO
    ====================================================== -->

        <main class="main-content">

            <header class="topbar">

                <div class="topbar-left">

                    <button class="btn-menu" id="openMenuBtn">
                        <i data-lucide="menu"></i>
                    </button>

                    <h2 class="section-title mb-0">
                        Portal de Enfermería
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
                        <h3>Mis solicitudes de traslado</h3>

                        

                        <?php if (!$misSolicitudes): ?>
                            <p>Todavía no enviaste solicitudes.</p>
                        <?php else: ?>
                            <div style="overflow-x: auto; margin-top: 20px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <?php foreach (
                                                [
                                                    'Solicitud',
                                                    'Fecha',
                                                    'Elemento',
                                                    'Origen',
                                                    'Destino',
                                                    'Prioridad',
                                                    'Estado',
                                                    'Traslado',
                                                    'Acciones'
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
                $solicitud['elemento'],
                $solicitud['origen'],
                $solicitud['destino'],
                $solicitud['prioridad'],
                $solicitud['estado'],
                $solicitud['id_traslado'] !== null
                    ? '#' . $solicitud['id_traslado']
                    : 'Sin asignar'
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
                       href="../../moduloAmbulancias/Vista/editarSolicitud.php?id=<?= (int) $solicitud['id_solicitud'] ?>">Editar</a>
                    <form
                        action="../../moduloAmbulancias/Controlador/CancelarSolicitud.php"
                        method="POST"
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
                            Motivo de cancelación
                            <input type="text" name="motivo"
                                maxlength="500" required>
                        </label>

                        <button type="submit">Cancelar solicitud</button>
                    </form>
                <?php elseif ($solicitud['estado'] === 'Cancelada'): ?>
    <details>
        <summary>Ver cancelación</summary>

        <p>
            <strong>Motivo:</strong>
            <?= htmlspecialchars(
                $solicitud['motivo_cancelacion'] ?? 'Sin registro',
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <p>
            <strong>Fecha:</strong>
            <?= htmlspecialchars(
                $solicitud['fecha_cancelacion'] ?? 'Sin registro',
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <p>
            <strong>Cancelada por:</strong>
            <?= htmlspecialchars(
                $solicitud['cancelada_por'] ?? 'Sin registro',
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


                <!-- =================================================
                 SOLICITUD DE TRASLADO
            ================================================== -->

                <div id="sec-traslados" class="view-section active">

                    <div class="card-formulario">

                        <div class="card-header">

                            <h3>
                                <i data-lucide="ambulance" class="icon-md text-primary"></i>

                                Nueva Solicitud de Traslado
                            </h3>

                            

                        </div>



                        <form id="form-traslado" class="mt-3">
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
                                    Tipo de elemento a trasladar
                                </label>

                                <select id="tipo-elemento" class="form-input" required>

                                    <option value="">
                                        Seleccione el tipo...
                                    </option>

                                    <option value="1">
                                        Paciente
                                    </option>

                                    <option value="2">
                                        Órgano
                                    </option>

                                    <option value="3">
                                        Muestra biológica
                                    </option>

                                    <option value="4">
                                        Cadetería de trámites
                                    </option>

                                    <option value="5">
                                        Insumo médico
                                    </option>

                                    <option value="6">
                                        Equipamiento
                                    </option>

                                </select>

                            </div>



                            <!-- DATOS DEL PACIENTE -->

                            <div id="datos-paciente" class="hidden">

                                <div class="form-group">

                                    <label>
                                        Cédula de Identidad del paciente
                                    </label>

                                    <input type="text" id="ci-paciente" class="form-input"
                                        placeholder="Ingrese la cédula" autocomplete="off" inputmode="numeric">

                                    <p id="estado-paciente" class="text-muted">
                                        Ingrese la cédula para verificar
                                        que el paciente esté registrado.
                                    </p>

                                </div>

                            </div>



                            <!-- ORIGEN Y DESTINO -->

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>
                                        Origen
                                    </label>

                                    <select id="origen" class="form-input" required>

                                        <option value="">
                                            Seleccione el origen...
                                        </option>

                                        <option value="1">
                                            Hospital de Clínicas
                                        </option>

                                        <option value="2">
                                            Hospital Maciel
                                        </option>

                                        <option value="3">
                                            CHPR
                                        </option>

                                    </select>

                                </div>


                                <div class="form-group">

                                    <label>
                                        Destino
                                    </label>

                                    <select id="destino" class="form-input" required>

                                        <option value="">
                                            Seleccione el destino...
                                        </option>

                                        <option value="1">
                                            Hospital de Clínicas
                                        </option>

                                        <option value="2">
                                            Hospital Maciel
                                        </option>

                                        <option value="3">
                                            CHPR
                                        </option>

                                    </select>

                                </div>

                            </div>



                            <!-- CANAL -->

                            <div class="form-group">

                                <label>
                                    Canal de solicitud
                                </label>

                                <select id="canal-solicitud" class="form-input" required>

                                    <option value="">
                                        Seleccione el canal...
                                    </option>

                                    <option value="1">
                                        Gestión Salud
                                    </option>

                                    <option value="2">
                                        Correo
                                    </option>

                                    <option value="3">
                                        Papel
                                    </option>

                                </select>

                            </div>



                            <!-- PRIORIDAD Y HORA LÍMITE -->

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>
                                        Prioridad del traslado
                                    </label>

                                    <select id="prioridad" class="form-input" required>

                                        <option value="normal">
                                            Normal
                                        </option>

                                        <option value="alta">
                                            Alta
                                        </option>

                                        <option value="maxima">
                                            Máxima
                                        </option>

                                    </select>

                                    <p id="mensaje-prioridad" class="text-muted" hidden></p>

                                </div>


                                <div class="form-group">

                                    <label>
                                        Debe llegar antes de
                                    </label>

                                    <input type="datetime-local" id="fecha-hora-limite" class="form-input">

                                    <p class="text-muted">
                                        Opcional. Utilice este campo cuando
                                        exista un límite de fecha y hora para
                                        completar el traslado.
                                    </p>

                                </div>

                            </div>



                            <!-- OBSERVACIONES -->

                            <div class="form-group">

                                <label>
                                    Observaciones
                                </label>

                                <textarea id="observaciones" class="form-input" rows="3" maxlength="500"
                                    placeholder="Ingrese información relevante para realizar el traslado."></textarea>

                            </div>



                            <!-- DATOS AUTOMÁTICOS -->

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>
                                        Solicitante
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
                                        Estado inicial
                                    </label>

                                    <input type="text" id="estado-solicitud" class="form-input" value="Pendiente"
                                        readonly>

                                </div>

                            </div>



                            <button type="submit" class="btn-primary w-100 mt-2">

                                <i data-lucide="send"></i>

                                Enviar Solicitud a Enlace

                            </button>

                        </form>



                        <div id="mensaje-exito" class="alerta-exito hidden mt-3">

                            <i data-lucide="check-circle"></i>

                            Solicitud enviada correctamente a la
                            Unidad de Enlace.

                        </div>

                    </div>

                </div>



                <!-- =================================================
                 DOCUMENTACIÓN QR
            ================================================== -->

                <?php require __DIR__ . '/directorioQR.php'; ?>

                <div id="sec-formularios" class="view-section hidden">

                    <div class="generador-container">


                        <div class="card-formulario">

                            <div class="card-header">

                                <h3>

                                    <i data-lucide="qr-code" class="icon-md text-primary"></i>

                                    Generar QR de Encuesta

                                </h3>

                                

                            </div>



                            <form id="form-generador" class="mt-3">

                                <div class="form-group">

                                    <label>
                                        Área / Categoría
                                    </label>

                                    <select id="qr-categoria" class="form-input" required>

                                        <option value="">
                                            Seleccione el área...
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

                                    Generar Código QR

                                </button>

                            </form>

                        </div>



                        <!-- RESULTADO QR -->

                        <div class="card-resultado-qr">

                            <div id="qr-vacio" class="qr-placeholder">

                                <i data-lucide="smartphone" class="icon-xl text-muted"></i>

                                <p>
                                    El código QR generado aparecerá aquí.
                                </p>

                            </div>


                            <div id="qr-generado" class="qr-display hidden">

                                <h4 class="mb-2">
                                    Código de Encuesta
                                </h4>


                                <img id="qr-dinamico-img" src="" alt="QR de Encuesta" class="qr-img">


                                <div class="qr-info mt-3">

                                    <p>
                                        <strong>Área:</strong>
                                        <span id="lbl-categoria">-</span>
                                    </p>

                                    <p class="text-muted">
                                        Encuesta de Satisfacción Institucional (Anónima)
                                    </p>

                                </div>


                                <a id="btn-abrir-encuesta" href="#" target="_blank" class="btn-primary w-100 mt-3 btn-abrir-directo">
                                    <i data-lucide="external-link"></i>
                                    Abrir Encuesta Directamente
                                </a>

                                <button type="button" class="btn-outline w-100 mt-2" onclick="window.print()">
                                    <i data-lucide="printer"></i>
                                    Imprimir QR
                                </button>

                            </div>

                        </div>

                    </div>

                </div>


            </section>

        </main>

    </div>



    <!-- =========================================================
     JAVASCRIPT
========================================================== -->

    <script src="../../serviciosComunes/vistaGeneral/assets/js/layout.js"></script>
    <script src="Assets/js/enfermeria.js?v=20261026-2"></script>
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
