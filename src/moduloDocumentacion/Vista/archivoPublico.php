<?php
require_once __DIR__ . '/../Model/DocumentacionPublicaModelo.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
try {
    $documento = $id && $id > 0 ? DocumentacionPublicaModelo::documento($id) : null;
    $archivo = $documento ? DocumentacionPublicaModelo::rutaPDF($documento['archivo_url']) : null;
    if (!$archivo) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El documento no está disponible. Volvé al listado de la categoría.');
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="documento-' . (int) $id . '.pdf"');
    header('Content-Length: ' . filesize($archivo));
    readfile($archivo);
} catch (Throwable $e) {
    error_log('Archivo publico: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No se pudo abrir el documento. Intentá nuevamente más tarde.';
}
