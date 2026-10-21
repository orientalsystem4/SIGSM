-- Ejecutar en cada base existente antes de usar las cancelaciones.
-- No borra solicitudes. Se puede volver a ejecutar en MariaDB 10.4.
ALTER TABLE sigsm.solicitud_traslado
    ADD COLUMN IF NOT EXISTS observaciones VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS fecha_cancelacion DATETIME NULL,
    ADD COLUMN IF NOT EXISTS id_usuario_cancelacion INT NULL;
