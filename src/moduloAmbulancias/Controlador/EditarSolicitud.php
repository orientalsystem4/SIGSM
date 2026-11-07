<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 6]);
require_once __DIR__ . '/../Model/EdicionSolicitudModelo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit(t('error.metodo_acento'));
}
$token = $_POST['token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_edicion_solicitud']) || !hash_equals($_SESSION['csrf_edicion_solicitud'], $token)) {
    http_response_code(403);
    exit(t('error.solicitud_volver_formulario'));
}
$id = filter_input(INPUT_POST, 'id_solicitud', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit(t('error.identificador'));
}
try {
    EdicionSolicitudModelo::guardar($id, (int) $_SESSION['id_usuario'], $_POST);
    unset($_SESSION['edicion_solicitud']);
    $_SESSION['mensaje_edicion'] = t('editar_solicitud.ok', ['id' => $id]);
    header('Location: ../../moduloDocumentacion/Vista/enfermeria.php?vista=mis-solicitudes');
} catch (Throwable $e) {
    $error = t('editar_solicitud.error_general');
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
