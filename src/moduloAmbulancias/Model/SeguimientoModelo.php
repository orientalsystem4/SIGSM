<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class SeguimientoModelo
{
    // La direccion de domicilio es un dato personal: solo se incluye si quien consulta puede verla.
    public static function listar(?int $idChofer, bool $verDomicilio = false): array
    {
        $conexion = Conexion::conectar();
        // La vista v_traslado_detalle no incluye la direccion: se lee de la solicitud solo con permiso.
        $columnaDomicilio = $verDomicilio
            ? '(SELECT s.direccion_domicilio FROM solicitud_traslado s WHERE s.id_solicitud = vt.id_solicitud)'
            : 'NULL';

        $sql = "
            SELECT
                vt.id_traslado,
                vt.id_solicitud,
                vt.id_chofer,
                vt.id_vehiculo,
                vt.id_proveedor,
                vt.ci_paciente,
                vt.id_tipo_elemento,
                $columnaDomicilio AS direccion_domicilio,
                vt.matricula,
                vt.proveedor,
                vt.chofer,
                vt.enfermeria,
                vt.origen,
                vt.origen_tipo,
                vt.destino,
                vt.destino_tipo,
                vt.elemento,
                vt.id_estado,
                vt.estado
            FROM v_traslado_detalle vt
        ";

        $params = [];

        if ($idChofer !== null) {
            $sql .= ' WHERE vt.id_chofer = :chofer';
            $params[':chofer'] = $idChofer;
        }

        $sql .= ' ORDER BY vt.id_traslado DESC';

        $consulta = $conexion->prepare($sql);
        $consulta->execute($params);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function historial(int $idTraslado): array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare("
            SELECT h.id_estado, h.fecha_hora, h.observaciones,
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