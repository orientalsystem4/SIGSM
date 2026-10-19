<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class TrasladoModelo
{
    public static function obtenerSolicitudPendiente(int $id): ?array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
            SELECT
                s.*,
                o.nombre AS origen,
                d.nombre AS destino
            FROM solicitud_traslado s
            INNER JOIN ubicacion o
                ON o.id_ubicacion = s.id_origen
            INNER JOIN ubicacion d
                ON d.id_ubicacion = s.id_destino
            WHERE s.id_solicitud = :id
              AND s.estado_solicitud = 'Pendiente'
              AND NOT EXISTS (
                  SELECT 1
                  FROM traslado t
                  WHERE t.id_solicitud = s.id_solicitud
              )
        ");

        $consulta->execute([':id' => $id]);

        return $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function listarVehiculosCompatibles(int $idTipo): array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
            SELECT
                v.id_vehiculo,
                v.matricula,
                tv.nombre AS tipo
            FROM vehiculo v
            INNER JOIN tipo_vehiculo tv
                ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
            WHERE v.activo = 1
              AND EXISTS (
                  SELECT 1
                  FROM compatibilidad c
                  WHERE c.id_tipo_vehiculo = v.id_tipo_vehiculo
                    AND c.id_tipo_elemento = :tipo
              )
            ORDER BY v.matricula
        ");

        $consulta->execute([':tipo' => $idTipo]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarPersonalPorRol(int $idRol): array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
            SELECT u.id_usuario, u.nombre_usuario
            FROM usuario u
            WHERE u.activo = 1
              AND EXISTS (
                  SELECT 1
                  FROM usuario_rol ur
                  WHERE ur.id_usuario = u.id_usuario
                    AND ur.id_rol = :rol
              )
            ORDER BY u.nombre_usuario
        ");

        $consulta->execute([':rol' => $idRol]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarRutas(
        int $idOrigen,
        int $idDestino
    ): array {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
            SELECT id_ruta, distancia_km, tiempo_min
            FROM ruta
            WHERE id_origen = :origen
              AND id_destino = :destino
            ORDER BY id_ruta
        ");

        $consulta->execute([
            ':origen' => $idOrigen,
            ':destino' => $idDestino
        ]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}