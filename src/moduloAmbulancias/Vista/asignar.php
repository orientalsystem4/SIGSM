<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 3]);

require_once __DIR__ . '/../Model/TrasladoModelo.php';

$idSolicitud = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$solicitud = $idSolicitud
    ? TrasladoModelo::obtenerSolicitudPendiente($idSolicitud)
    : null;

if (!$solicitud) {
    http_response_code(404);
    exit(htmlspecialchars(t('asignar.no_disponible'), ENT_QUOTES, 'UTF-8'));
}

$vehiculos = TrasladoModelo::listarVehiculosCompatibles(
    (int) $solicitud['id_tipo_elemento']
);

$choferes = TrasladoModelo::listarPersonalPorRol(5);
$proveedores = TrasladoModelo::listarProveedores();
$enfermeria = TrasladoModelo::listarPersonalPorRol(6);

$rutas = TrasladoModelo::listarRutas(
    (int) $solicitud['id_origen'],
    (int) $solicitud['id_destino']
);

$esPaciente = (int) $solicitud['id_tipo_elemento'] === 1;
$esDomicilio = (int) $solicitud['es_domicilio'] === 1;
$domicilioInvalido = $esDomicilio && !$esPaciente;

if (empty($_SESSION['csrf_asignacion'])) {
    $_SESSION['csrf_asignacion'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['error_asignacion'] ?? '';
unset($_SESSION['error_asignacion']);

function escaparAsignacion($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGSM | <?= te('enlace.asignar') ?></title>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }
        main {
            max-width: 700px;
            margin: auto;
            padding: 24px;
            background: white;
            border-radius: 12px;
        }
        label {
            display: block;
            margin: 20px 0 8px;
            font-weight: bold;
        }
        select, input[type=text], button {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }
        button {
            margin-top: 24px;
            background: #0284c7;
            color: white;
            cursor: pointer;
        }
        button:disabled { opacity: .5; cursor: not-allowed; }
        .error { color: #991b1b; background: #fee2e2; padding: 12px; }
        a { color: #0284c7; }
    </style>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261021-1">
</head>
<body>
<main>
    <a href="unidadEnlace.php">← <?= te('asignar.volver') ?></a>

    <h1><?= te('asignar.titulo', ['id' => (int) $idSolicitud]) ?></h1>

    <p>
        <strong><?= te('campo.origen') ?>:</strong>
        <?= escaparAsignacion(nombreUbicacion($solicitud['origen'], $solicitud['origen_tipo'])) ?>
    </p>

    <p>
        <strong><?= te('campo.destino') ?>:</strong>
        <?= escaparAsignacion(nombreUbicacion($solicitud['destino'], $solicitud['destino_tipo'])) ?>
    </p>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert">
            <?= escaparAsignacion($error) ?>
        </p>
    <?php endif; ?>

    <form action="../Controlador/AsignarTraslado.php" method="POST">

        <input type="hidden" name="token"
               value="<?= escaparAsignacion($_SESSION['csrf_asignacion']) ?>">

        <input type="hidden" name="id_solicitud"
               value="<?= (int) $idSolicitud ?>">

        <?php if ($proveedores): ?>
            <label for="proveedor"><?= te('asignar.proveedor') ?></label>
            <select id="proveedor" name="id_proveedor">
                <option value=""><?= te('asignar.proveedor_ninguno') ?></option>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?= (int) $proveedor['id_proveedor'] ?>">
                        <?= te('asignar.terceros') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p><?= te('asignar.proveedor_ayuda') ?></p>
        <?php endif; ?>

        <label for="vehiculo"><?= te('campo.vehiculo') ?></label>
        <select id="vehiculo" name="id_vehiculo">
            <option value=""><?= te('asignar.seleccione_vehiculo') ?></option>
            <?php foreach ($vehiculos as $vehiculo): ?>
                <option value="<?= (int) $vehiculo['id_vehiculo'] ?>">
                    <?= escaparAsignacion(
                        etiquetaBd('vehiculo', $vehiculo['id_tipo_vehiculo'], $vehiculo['tipo']) . ' - ' . $vehiculo['matricula']
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!$vehiculos): ?>
            <p class="error"><?= te('asignar.sin_vehiculos') ?></p>
        <?php endif; ?>

        <label for="chofer"><?= te('campo.chofer') ?></label>
        <select id="chofer" name="id_chofer">
            <option value=""><?= te('asignar.seleccione_chofer') ?></option>
            <?php foreach ($choferes as $chofer): ?>
                <option value="<?= (int) $chofer['id_usuario'] ?>">
                    <?= escaparAsignacion($chofer['nombre_usuario']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="enfermeria"><?= te('campo.enfermeria') ?></label>
        <select id="enfermeria" name="id_enfermero"
                <?= $esPaciente ? 'required' : '' ?>>
            <option value="">
                <?= $esPaciente
                    ? te('asignar.seleccione_enfermeria')
                    : te('comun.sin_asignar') ?>
            </option>
            <?php foreach ($enfermeria as $persona): ?>
                <option value="<?= (int) $persona['id_usuario'] ?>">
                    <?= escaparAsignacion($persona['nombre_usuario']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($domicilioInvalido): ?>
            <p class="error"><?= te('domicilio.solo_pacientes') ?></p>
        <?php elseif ($esDomicilio): ?>
            <label for="direccion_domicilio"><?= te('campo.direccion_domicilio') ?></label>
            <input type="text" id="direccion_domicilio"
                   name="direccion_domicilio" maxlength="255" required
                   autocomplete="off">
        <?php endif; ?>

        <label for="ruta"><?= te('campo.ruta') ?></label>
        <select id="ruta" name="id_ruta" required>
            <option value=""><?= te('asignar.seleccione_ruta') ?></option>
            <?php foreach ($rutas as $ruta): ?>
                <option value="<?= (int) $ruta['id_ruta'] ?>">
                    <?= te('asignar.ruta_numero', ['id' => (int) $ruta['id_ruta']]) ?>:
                    <?= escaparAsignacion(
                        nombreUbicacion($solicitud['origen'], $solicitud['origen_tipo']) . ' → ' . nombreUbicacion($solicitud['destino'], $solicitud['destino_tipo'])
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!$rutas): ?>
            <p class="error">
                <?= te('asignar.sin_ruta') ?>
            </p>
        <?php endif; ?>

        <button type="submit"
            <?= ((!$proveedores && (!$vehiculos || !$choferes)) || !$rutas || $domicilioInvalido
                || ($esPaciente && !$enfermeria)) ? 'disabled' : '' ?>>
            <?= te('asignar.confirmar') ?>
        </button>
    </form>
</main>
<script src="assets/js/asignar.js?v=20261109-1"></script>
</body>
</html>
