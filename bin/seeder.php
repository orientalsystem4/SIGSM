<?php
/**
 * Script CLI Seeder General - S.I.G.S.M.
 * Ejecuta la inicialización del Administrador y usuarios estándar de prueba para cada rol.
 * Uso:
 *   php bin/seeder.php
 */

if (php_sapi_name() !== 'cli') {
    die("Este comando solo puede ejecutarse por consola CLI.\n");
}

echo "Iniciando sembrado de datos (Seeder general)...\n";
require __DIR__ . '/crear_admin.php';

