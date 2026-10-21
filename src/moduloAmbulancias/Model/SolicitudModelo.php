<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class SolicitudModelo
{
    public static function crear(array $datos, int $idUsuario): int
    {
        $conexion = Conexion::conectar();

        $sql = "
            INSERT INTO solicitud_traslado (
                ci_paciente,
                id_tipo_elemento,
                id_origen,
                id_destino,
                id_canal,
                id_usuario_solicit,
                fecha_solicitud,
                estado_solicitud,
                prioridad,
                fecha_hora_limite,
                observaciones
            ) VALUES (
                :ci_paciente,
                :id_tipo_elemento,
                :id_origen,
                :id_destino,
                :id_canal,
                :id_usuario_solicit,
                NOW(),
                'Pendiente',
                :prioridad,
                :fecha_hora_limite,
                :observaciones
            )
        ";

        $consulta = $conexion->prepare($sql);

        $consulta->execute([
            ':ci_paciente' => $datos['ci_paciente'],
            ':id_tipo_elemento' => $datos['id_tipo_elemento'],
            ':id_origen' => $datos['id_origen'],
            ':id_destino' => $datos['id_destino'],
            ':id_canal' => $datos['id_canal'],
            ':id_usuario_solicit' => $idUsuario,
            ':prioridad' => $datos['prioridad'],
            ':fecha_hora_limite' => $datos['fecha_hora_limite'],
            ':observaciones' => $datos['observaciones']
        ]);

        return (int) $conexion->lastInsertId();
    }
    public static function listarPendientes(): array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT
                s.id_solicitud,
                s.ci_paciente,
                s.id_tipo_elemento,
                s.fecha_solicitud,
                s.estado_solicitud,
                s.prioridad,
                s.fecha_hora_limite,
                s.observaciones,
                te.nombre AS tipo_elemento,
                origen.nombre AS origen,
                destino.nombre AS destino,
                c.nombre AS canal,
                u.nombre_usuario AS solicitante,
                p.nombre AS nombre_paciente,
                p.apellido AS apellido_paciente
            FROM solicitud_traslado s
            INNER JOIN tipo_elemento te
                ON te.id_tipo_elemento = s.id_tipo_elemento
            INNER JOIN ubicacion origen
                ON origen.id_ubicacion = s.id_origen
            INNER JOIN ubicacion destino
                ON destino.id_ubicacion = s.id_destino
            INNER JOIN canal_solicitud c
                ON c.id_canal = s.id_canal
            INNER JOIN usuario u
                ON u.id_usuario = s.id_usuario_solicit
            LEFT JOIN paciente p
                ON p.ci = s.ci_paciente
            WHERE s.estado_solicitud = 'Pendiente'
              AND NOT EXISTS (
                  SELECT 1
                  FROM traslado t
                  WHERE t.id_solicitud = s.id_solicitud
              )
            ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarPorSolicitante(int $idUsuario): array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
        SELECT
            s.id_solicitud,
            s.fecha_solicitud,
            s.prioridad,
            te.nombre AS elemento,
            o.nombre AS origen,
            d.nombre AS destino,
            t.id_traslado,
COALESCE(e.nombre, s.estado_solicitud) AS estado,
s.motivo_cancelacion,
s.fecha_cancelacion,
(
    SELECT uc.nombre_usuario
    FROM usuario uc
    WHERE uc.id_usuario = s.id_usuario_cancelacion
) AS cancelada_por      
   FROM solicitud_traslado s
        JOIN tipo_elemento te
            ON te.id_tipo_elemento = s.id_tipo_elemento
        JOIN ubicacion o ON o.id_ubicacion = s.id_origen
        JOIN ubicacion d ON d.id_ubicacion = s.id_destino
        LEFT JOIN traslado t ON t.id_solicitud = s.id_solicitud
        LEFT JOIN historial_estado h ON h.id_historial = (
            SELECT hx.id_historial
            FROM historial_estado hx
            WHERE hx.id_traslado = t.id_traslado
            ORDER BY hx.fecha_hora DESC, hx.id_historial DESC
            LIMIT 1
        )
        LEFT JOIN estado_traslado e ON e.id_estado = h.id_estado
        WHERE s.id_usuario_solicit = :usuario
        ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC
    ");

        $consulta->execute([':usuario' => $idUsuario]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
