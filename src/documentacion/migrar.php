<?php

// Actualiza la estructura sin borrar los datos existentes.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta por consola.');
}

require_once __DIR__ . '/../serviciosComunes/conexionBD/conexion.php';

try {
    $conexion = Conexion::conectar();

    $conexion->exec("
        ALTER TABLE solicitud_traslado
            ADD COLUMN IF NOT EXISTS observaciones VARCHAR(500) NULL,
            ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(500) NULL,
            ADD COLUMN IF NOT EXISTS fecha_cancelacion DATETIME NULL,
            ADD COLUMN IF NOT EXISTS id_usuario_cancelacion INT NULL
    ");

    echo "Base actualizada: las columnas necesarias existen.\n";
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, "Error al actualizar la base: "
        . $error->getMessage() . "\n");
    exit(1);
}