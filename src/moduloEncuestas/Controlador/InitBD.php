<?php
/**
 * Inicializador de Base de Datos - Módulo Encuestas (DEPRECADO / PROTEGIDO)
 *
 * Por razones de seguridad y buenas prácticas de arquitectura,
 * los scripts de inicialización no deben ser ejecutados vía HTTP desde el navegador.
 *
 * La inicialización y sembrado de datos ha sido centralizada en el CLI Seeder:
 *   docker compose exec web php bin/seeder.php
 *   o en local: php bin/seeder.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die("HTTP 403 Prohibido: Este script de inicialización no puede ejecutarse vía web por motivos de seguridad.\nPor favor, ejecute el seeder unificado desde la consola:\n  docker compose exec web php bin/seeder.php\n  o: php bin/seeder.php\n");
}

// En caso de invocación directa por CLI, delegar al seeder general
$posiblesRutas = [
    __DIR__ . '/../../../bin/seeder.php',
    __DIR__ . '/../../bin/seeder.php',
    dirname(__DIR__, 3) . '/bin/seeder.php',
    dirname(__DIR__, 2) . '/bin/seeder.php',
];

foreach ($posiblesRutas as $ruta) {
    if (file_exists($ruta)) {
        require $ruta;
        exit(0);
    }
}

echo "El script de inicialización ha sido trasladado a bin/seeder.php. Ejecute: php bin/seeder.php\n";
exit(1);
