<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 6]);
require_once __DIR__ . '/../Model/EdicionSolicitudModelo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}
$token = $_POST['token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_edicion_solicitud']) || !hash_equals($_SESSION['csrf_edicion_solicitud'], $token)) {
    http_response_code(403);
    exit('La solicitud no es válida. Volvé al formulario y recargá la página.');
}
$id = filter_input(INPUT_POST, 'id_solicitud', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Identificador no válido.');
}
try {
    EdicionSolicitudModelo::guardar($id, (int) $_SESSION['id_usuario'], $_POST);
    unset($_SESSION['edicion_solicitud']);
    $_SESSION['mensaje_edicion'] = 'Solicitud #' . $id . ' actualizada correctamente.';
    header('Location: ../../moduloDocumentacion/Vista/enfermeria.php?vista=mis-solicitudes');
} catch (Throwable $e) {
    $error = 'No se pudo guardar la solicitud. Intentá nuevamente.';
    if ($e instanceof DomainException) {
        $error = $e->getMessage();
    } else {
        error_log('Error al editar solicitud: ' . $e->getMessage());
    }
    $previos = [];
    foreach (['id_origen', 'id_destino', 'prioridad', 'fecha_hora_limite', 'observaciones'] as $campo) {
        $valor = $_POST[$campo] ?? '';
        $previos[$campo] = is_string($valor) ? $valor : '';
    }
    $_SESSION['edicion_solicitud'] = ['id' => $id, 'error' => $error, 'datos' => $previos];
    header('Location: ../Vista/editarSolicitud.php?id=' . $id);
}
exit;
