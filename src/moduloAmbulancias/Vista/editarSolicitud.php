<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 6]);
require_once __DIR__ . '/../Model/EdicionSolicitudModelo.php';
header('Cache-Control: no-store');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$solicitud = $id && $id > 0 ? EdicionSolicitudModelo::obtener($id, (int) $_SESSION['id_usuario']) : null;
if (!$solicitud) {
    $_SESSION['mensaje_edicion'] = 'La solicitud no está disponible para editar: debe ser tuya, estar pendiente y sin traslado asignado.';
    unset($_SESSION['edicion_solicitud']);
    header('Location: ../../moduloDocumentacion/Vista/enfermeria.php?vista=mis-solicitudes');
    exit;
}
$ubicaciones = EdicionSolicitudModelo::ubicaciones();
$datos = $solicitud;
$datos['fecha_hora_limite'] = $solicitud['fecha_hora_limite']
    ? str_replace(' ', 'T', substr($solicitud['fecha_hora_limite'], 0, 16)) : '';
$anterior = $_SESSION['edicion_solicitud'] ?? [];
unset($_SESSION['edicion_solicitud']);
$error = '';
if (($anterior['id'] ?? null) === $id) {
    $datos = array_merge($datos, $anterior['datos']);
    $error = $anterior['error'];
}
if (empty($_SESSION['csrf_edicion_solicitud'])) {
    $_SESSION['csrf_edicion_solicitud'] = bin2hex(random_bytes(32));
}
function escaparEdicion($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGSM | Editar solicitud</title>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261021-1">
    <style>
        body { margin:0; padding:24px; font-family:Arial,sans-serif; background:#eef2f6; color:#1f2937; }
        main { max-width:700px; margin:auto; padding:28px; background:white; border-radius:16px; }
        label { display:block; margin:18px 0 8px; font-weight:bold; }
        select,input,textarea { box-sizing:border-box; width:100%; padding:12px; font:inherit; border:1px solid #cbd5e1; border-radius:8px; }
        textarea { min-height:120px; resize:vertical; }
        .acciones { display:flex; flex-wrap:wrap; gap:18px; align-items:center; margin-top:24px; }
        button { padding:12px 18px; background:#0f766e; color:white; border:0; border-radius:8px; cursor:pointer; font:inherit; }
        a { color:#0f766e; } .error { color:#991b1b; background:#fee2e2; padding:12px; border-radius:8px; }
    </style>
</head>
<body>
<main>
    <a href="../../moduloDocumentacion/Vista/enfermeria.php?vista=mis-solicitudes">← Volver a Mis solicitudes</a>
    <h1>Editar solicitud #<?= (int) $id ?></h1>
    <p>Podés modificarla mientras esté pendiente y sin traslado asignado.</p>
    <?php if ($error !== ''): ?><p class="error" role="alert"><?= escaparEdicion($error) ?></p><?php endif; ?>
    <form action="../Controlador/EditarSolicitud.php" method="POST">
        <input type="hidden" name="token" value="<?= escaparEdicion($_SESSION['csrf_edicion_solicitud']) ?>">
        <input type="hidden" name="id_solicitud" value="<?= (int) $id ?>">
        <?php foreach (['id_origen' => 'Origen', 'id_destino' => 'Destino'] as $campo => $etiqueta): ?>
            <label for="<?= $campo ?>"><?= $etiqueta ?></label>
            <select id="<?= $campo ?>" name="<?= $campo ?>" required>
                <option value="">Seleccioná una ubicación...</option>
                <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option value="<?= (int) $ubicacion['id_ubicacion'] ?>" <?= (int) $datos[$campo] === (int) $ubicacion['id_ubicacion'] ? 'selected' : '' ?>><?= escaparEdicion($ubicacion['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>
        <label for="prioridad">Prioridad</label>
        <?php if ((int) $solicitud['id_tipo_elemento'] === 2): ?>
            <input id="prioridad" value="Máxima (traslado de órgano)" readonly>
            <input type="hidden" name="prioridad" value="maxima">
        <?php else: ?>
            <select id="prioridad" name="prioridad" required>
                <?php foreach (['normal' => 'Normal', 'alta' => 'Alta', 'maxima' => 'Máxima'] as $valor => $texto): ?>
                    <option value="<?= $valor ?>" <?= $datos['prioridad'] === $valor ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <label for="fecha_hora_limite">Fecha y hora límite (opcional)</label>
        <input type="datetime-local" id="fecha_hora_limite" name="fecha_hora_limite" value="<?= escaparEdicion($datos['fecha_hora_limite']) ?>">
        <label for="observaciones">Observaciones (opcional)</label>
        <textarea id="observaciones" name="observaciones" maxlength="500"><?= escaparEdicion($datos['observaciones']) ?></textarea>
        <div class="acciones">
            <button type="submit">Guardar cambios</button>
            <a href="../../moduloDocumentacion/Vista/enfermeria.php?vista=mis-solicitudes">Volver sin guardar</a>
        </div>
    </form>
</main>
</body>
</html>
