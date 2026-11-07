<?php
// Cambia el idioma de la interfaz y vuelve a la pagina anterior del sistema.
require_once __DIR__ . '/idioma.php';

// Base del sistema (por ejemplo /SIGSM/), derivada de la ubicacion de este script.
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$posicion = strpos($script, '/serviciosComunes/idioma/');
$base = $posicion !== false ? substr($script, 0, $posicion + 1) : '/SIGSM/';
$destino = $base . 'serviciosComunes/autenticacion/sesion.php';

// Solo rutas internas: sin esquema, dominio, consulta ni segmentos "..".
// Un destino invalido anula el pedido completo: no se cambia el idioma.
$volver = $_GET['volver'] ?? null;
$volverValido = true;
if ($volver !== null) {
    $volverValido = is_string($volver)
        && strpos($volver, $base) === 0
        && preg_match('#^(?:[A-Za-z0-9_]+/)*[A-Za-z0-9_]+\.php$#', substr($volver, strlen($base))) === 1;
    if ($volverValido) {
        $destino = $volver;
    }
}

$idioma = $_GET['lang'] ?? '';
if ($volverValido && esIdiomaValido($idioma)) {
    setcookie(IDIOMA_COOKIE, $idioma, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => $base,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

header('Location: ' . $destino);
exit;
