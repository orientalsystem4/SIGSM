-- Ejecutar UNA sola vez sobre la base SIGSM, tras un respaldo.
-- No elimina ni modifica registros existentes.
USE sigsm;
ALTER TABLE usuario
  ADD COLUMN cedula VARCHAR(8) NULL UNIQUE,
  ADD COLUMN nombre_completo VARCHAR(150) NULL;
UPDATE rol SET nombre_rol = 'Enfermero/a' WHERE id_rol = 6;
-- Los seis usuarios anteriores quedan con cédula/nombre NULL hasta completar sus fichas.
