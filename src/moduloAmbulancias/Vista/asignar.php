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
    exit('La solicitud no existe o ya no esta pendiente.');
}

$vehiculos = TrasladoModelo::listarVehiculosCompatibles(
    (int) $solicitud['id_tipo_elemento']
);

$choferes = TrasladoModelo::listarPersonalPorRol(5);
$enfermeria = TrasladoModelo::listarPersonalPorRol(6);

$rutas = TrasladoModelo::listarRutas(
    (int) $solicitud['id_origen'],
    (int) $solicitud['id_destino']
);

$esPaciente = (int) $solicitud['id_tipo_elemento'] === 1;

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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGSM | Asignar traslado</title>

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
        select, button {
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
</head>
<body>
<main>
    <a href="unidadEnlace.php">← Volver a Unidad de Enlace</a>

    <h1>Asignar solicitud #<?= (int) $idSolicitud ?></h1>

    <p>
        <strong>Origen:</strong>
        <?= escaparAsignacion($solicitud['origen']) ?>
    </p>

    <p>
        <strong>Destino:</strong>
        <?= escaparAsignacion($solicitud['destino']) ?>
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

        <label for="vehiculo">Vehículo</label>
        <select id="vehiculo" name="id_vehiculo" required>
            <option value="">Seleccione vehículo...</option>
            <?php foreach ($vehiculos as $vehiculo): ?>
                <option value="<?= (int) $vehiculo['id_vehiculo'] ?>">
                    <?= escaparAsignacion(
                        $vehiculo['tipo'] . ' - ' . $vehiculo['matricula']
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!$vehiculos): ?>
            <p class="error">No hay vehículos activos compatibles.</p>
        <?php endif; ?>

        <label for="chofer">Chofer</label>
        <select id="chofer" name="id_chofer" required>
            <option value="">Seleccione chofer...</option>
            <?php foreach ($choferes as $chofer): ?>
                <option value="<?= (int) $chofer['id_usuario'] ?>">
                    <?= escaparAsignacion($chofer['nombre_usuario']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="enfermeria">Enfermería</label>
        <select id="enfermeria" name="id_enfermero"
                <?= $esPaciente ? 'required' : '' ?>>
            <option value="">
                <?= $esPaciente
                    ? 'Seleccione Enfermería...'
                    : 'Sin asignar' ?>
            </option>
            <?php foreach ($enfermeria as $persona): ?>
                <option value="<?= (int) $persona['id_usuario'] ?>">
                    <?= escaparAsignacion($persona['nombre_usuario']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="ruta">Ruta</label>
        <select id="ruta" name="id_ruta" required>
            <option value="">Seleccione ruta...</option>
            <?php foreach ($rutas as $ruta): ?>
                <option value="<?= (int) $ruta['id_ruta'] ?>">
                    Ruta #<?= (int) $ruta['id_ruta'] ?>:
                    <?= escaparAsignacion(
                        $solicitud['origen'] . ' → ' . $solicitud['destino']
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!$rutas): ?>
            <p class="error">
                No hay una ruta registrada para este origen y destino.
            </p>
        <?php endif; ?>

        <button type="submit"
            <?= (!$vehiculos || !$choferes || !$rutas
                || ($esPaciente && !$enfermeria)) ? 'disabled' : '' ?>>
            Confirmar asignación
        </button>
    </form>
</main>
</body>
</html>