<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class SeguimientoModelo
{
    public static function listar(?int $idChofer): array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT
                t.id_traslado,
                t.id_solicitud,
                t.id_chofer,
                t.id_vehiculo,
                s.ci_paciente,
                v.matricula,
                ch.nombre_usuario AS chofer,
                en.nombre_usuario AS enfermeria,
                o.nombre AS origen,
                d.nombre AS destino,
                te.nombre AS elemento,
                h.id_estado,
                e.nombre AS estado
            FROM traslado t
            JOIN solicitud_traslado s
                ON s.id_solicitud = t.id_solicitud
            JOIN ubicacion o ON o.id_ubicacion = s.id_origen
            JOIN ubicacion d ON d.id_ubicacion = s.id_destino
            JOIN tipo_elemento te
                ON te.id_tipo_elemento = s.id_tipo_elemento
            LEFT JOIN vehiculo v ON v.id_vehiculo = t.id_vehiculo
            LEFT JOIN usuario ch ON ch.id_usuario = t.id_chofer
            LEFT JOIN usuario en ON en.id_usuario = t.id_enfermero
            LEFT JOIN historial_estado h ON h.id_historial = (
                SELECT hx.id_historial
                FROM historial_estado hx
                WHERE hx.id_traslado = t.id_traslado
                ORDER BY hx.fecha_hora DESC, hx.id_historial DESC
                LIMIT 1
            )
            LEFT JOIN estado_traslado e ON e.id_estado = h.id_estado
        ";

        $params = [];

        if ($idChofer !== null) {
            $sql .= ' WHERE t.id_chofer = :chofer';
            $params[':chofer'] = $idChofer;
        }

        $sql .= ' ORDER BY t.id_traslado DESC';

        $consulta = $conexion->prepare($sql);
        $consulta->execute($params);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function historial(int $idTraslado): array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
            SELECT h.fecha_hora, h.observaciones,
                   e.nombre AS estado, u.nombre_usuario AS usuario
            FROM historial_estado h
            JOIN estado_traslado e ON e.id_estado = h.id_estado
            JOIN usuario u ON u.id_usuario = h.id_usuario
            WHERE h.id_traslado = :id
            ORDER BY h.fecha_hora, h.id_historial
        ");

        $consulta->execute([':id' => $idTraslado]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}