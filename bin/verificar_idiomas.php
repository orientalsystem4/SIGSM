<?php
// Compara las claves de los archivos de idioma e informa las que faltan.
// Uso: php bin/verificar_idiomas.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta por consola.');
}

$rutasIdioma = [
    dirname(__DIR__) . '/src/serviciosComunes/idioma/lang',
    dirname(__DIR__) . '/serviciosComunes/idioma/lang'
];
$carpeta = null;
foreach ($rutasIdioma as $ruta) {
    if (is_dir($ruta)) {
        $carpeta = $ruta;
        break;
    }
}
if ($carpeta === null) {
    fwrite(STDERR, "[ERROR] No se encontro la carpeta de idiomas.\n");
    exit(1);
}

$idiomas = [];
$problemas = 0;
foreach (['es', 'en'] as $codigo) {
    $datos = json_decode((string) @file_get_contents("$carpeta/$codigo.json"), true);
    if (!is_array($datos)) {
        fwrite(STDERR, "[ERROR] $codigo.json no existe o no es JSON valido.\n");
        exit(1);
    }
    foreach ($datos as $clave => $texto) {
        if (!is_string($texto) || trim($texto) === '') {
            echo "[!] $codigo.json: la clave '$clave' esta vacia.\n";
            $problemas++;
        }
    }
    $idiomas[$codigo] = $datos;
}

// Las etiquetas de la base ("bd.*") solo existen en ingles: en español se usa la base.
$clavesEs = array_keys($idiomas['es']);
$clavesEn = array_filter(array_keys($idiomas['en']), fn($c) => strpos($c, 'bd.') !== 0);

foreach (array_diff($clavesEs, $clavesEn) as $clave) {
    echo "[!] Falta en en.json: $clave\n";
    $problemas++;
}
foreach (array_diff($clavesEn, $clavesEs) as $clave) {
    echo "[!] Sobra en en.json (no existe en es.json): $clave\n";
    $problemas++;
}

// Claves usadas en el codigo con las funciones t() y te() que no existen en es.json.
$raiz = dirname($carpeta, 3);
$iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
$usadas = [];
foreach ($iterador as $archivo) {
    if ($archivo->getExtension() !== 'php') {
        continue;
    }
    // Solo literales completos: se ignoran claves armadas con concatenacion.
    preg_match_all('/\bte?\(\s*[\'"]([A-Za-z][A-Za-z0-9_.]*[A-Za-z0-9_])[\'"]\s*[,)]/', (string) file_get_contents($archivo->getPathname()), $coincidencias);
    foreach ($coincidencias[1] as $clave) {
        $usadas[$clave][] = substr($archivo->getPathname(), strlen($raiz) + 1);
    }
}
foreach ($usadas as $clave => $archivos) {
    if (!array_key_exists($clave, $idiomas['es'])) {
        echo "[!] Clave usada pero inexistente en es.json: $clave (" . implode(', ', array_unique($archivos)) . ")\n";
        $problemas++;
    }
}

if ($problemas > 0) {
    echo "Se encontraron $problemas problema(s).\n";
    exit(1);
}
echo "[OK] es.json y en.json tienen las mismas " . count($clavesEs) . " claves.\n";
exit(0);
