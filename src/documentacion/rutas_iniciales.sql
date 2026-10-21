-- Rutas entre los tres puntos de prueba actuales. No son rutas oficiales del hospital.
-- Comprobar primero: 1 Hospital de Clinicas, 2 Hospital Maciel, 3 CHPR.
-- No modifica rutas existentes ni inventa distancia o duracion.
INSERT INTO sigsm.ruta (id_origen, id_destino, distancia_km, tiempo_min)
SELECT o.id_ubicacion, d.id_ubicacion, NULL, NULL
FROM sigsm.ubicacion o CROSS JOIN sigsm.ubicacion d
WHERE o.id_ubicacion IN (1, 2, 3) AND d.id_ubicacion IN (1, 2, 3)
  AND o.id_ubicacion <> d.id_ubicacion
  AND NOT EXISTS (SELECT 1 FROM sigsm.ruta r
                  WHERE r.id_origen = o.id_ubicacion AND r.id_destino = d.id_ubicacion);
