<?php
// Consulta publica de indicaciones generales por categoria. No requiere login.
require_once __DIR__ . '/../Model/DocumentacionPublicaModelo.php';
header('Cache-Control: no-store');
$id = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$categoria = null;
$documentos = [];
$error = '';
try {
    $categoria = $id && $id > 0 ? DocumentacionPublicaModelo::categoria($id) : null;
    if (!$categoria) {
        http_response_code(404);
        $error = 'La categoría solicitada no existe o no está disponible.';
    } else {
        $documentos = DocumentacionPublicaModelo::documentos($id);
    }
} catch (Throwable $e) {
    error_log('Documentacion publica: ' . $e->getMessage());
    http_response_code(500);
    $error = 'No pudimos cargar los documentos. Intentá nuevamente más tarde.';
}
function escaparPublico($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escaparPublico($categoria['nombre'] ?? 'Documentación') ?> | SIGSM</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#eef2f6;color:#1f2937;font-family:Arial,sans-serif;line-height:1.5}
        header{background:#115e59;color:white;padding:28px 20px}header div,main{max-width:800px;margin:auto}
        h1{margin:8px 0;font-size:clamp(1.6rem,5vw,2.2rem)}main{padding:24px 16px}
        ul{list-style:none;padding:0}li,.aviso{background:white;border:1px solid #dbe3e9;border-radius:12px;padding:20px;margin-bottom:16px}
        h2{font-size:1.1rem;overflow-wrap:anywhere;margin:0 0 8px}.fecha{color:#526174;font-size:.9rem}
        a{display:inline-block;background:#0f766e;color:white;padding:12px 18px;border-radius:8px;text-decoration:none;margin-top:8px}
        a:focus-visible{outline:3px solid #1f2937;outline-offset:3px}footer{color:#526174;font-size:.9rem;margin-top:24px}
    </style>
</head>
<body>
<header><div><strong>S.I.G.S.M.</strong><h1><?= escaparPublico($categoria['nombre'] ?? 'Documentación') ?></h1><p>Documentación e indicaciones para pacientes</p></div></header>
<main>
    <?php if ($error !== ''): ?>
        <p class="aviso" role="alert"><?= escaparPublico($error) ?></p>
    <?php elseif (!$documentos): ?>
        <p class="aviso">Todavía no hay documentos disponibles para esta categoría.</p>
    <?php else: ?>
        <p>Seleccioná un documento para abrirlo en formato PDF.</p>
        <ul>
        <?php foreach ($documentos as $documento): ?>
            <li>
                <h2><?= escaparPublico(DocumentacionPublicaModelo::titulo($documento['archivo_url'])) ?></h2>
                <p class="fecha">Fecha de carga: <?= escaparPublico(substr($documento['fecha_carga'], 0, 10)) ?></p>
                <a href="archivoPublico.php?id=<?= (int) $documento['id_documento'] ?>" target="_blank" rel="noopener">Abrir PDF <span aria-hidden="true">↗</span><span style="position:absolute;width:1px;height:1px;overflow:hidden"> en una pestaña nueva</span></a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <footer>Documentos publicados por el área de Registros Médicos.</footer>
</main>
</body>
</html>
