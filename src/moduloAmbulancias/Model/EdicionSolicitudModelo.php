<?php
require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';
require_once __DIR__ . '/../../serviciosComunes/idioma/idioma.php';

class EdicionSolicitudModelo
{
    public static function obtener(int $id, int $usuario): ?array
    {
        $db = Conexion::conectar();
        $q = $db->prepare("SELECT s.* FROM solicitud_traslado s
            WHERE s.id_solicitud = :id AND s.id_usuario_solicit = :usuario
            AND s.estado_solicitud = 'Pendiente'
            AND NOT EXISTS (SELECT 1 FROM traslado t WHERE t.id_solicitud = s.id_solicitud)");
        $q->execute([':id' => $id, ':usuario' => $usuario]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function ubicaciones(): array
    {
        return Conexion::conectar()->query('SELECT id_ubicacion, nombre, tipo_ubicacion FROM ubicacion ORDER BY id_ubicacion')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function validar(array $datos): array
    {
        $resultado = [];
        foreach (['id_origen', 'id_destino'] as $campo) {
            $valor = $datos[$campo] ?? null;
            if (!is_scalar($valor) || !filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                throw new DomainException(t('editar_solicitud.error_lugares'));
            }
            $resultado[$campo] = (int) $valor;
        }
        if ($resultado['id_origen'] === $resultado['id_destino']) {
            throw new DomainException(t('solicitud.error_mismo_lugar'));
        }
        $prioridad = $datos['prioridad'] ?? '';
        if (!in_array($prioridad, ['normal', 'alta', 'maxima'], true)) {
            throw new DomainException(t('solicitud.error_prioridad_acento'));
        }
        $resultado['prioridad'] = $prioridad;
        $texto = $datos['observaciones'] ?? '';
        if (!is_string($texto) || preg_match('//u', $texto) !== 1 || preg_match_all('/./us', $texto) > 500) {
            throw new DomainException(t('editar_solicitud.error_obs'));
        }
        $resultado['observaciones'] = trim($texto) !== '' ? trim($texto) : null;
        $limite = $datos['fecha_hora_limite'] ?? '';
        if (!is_string($limite)) {
            throw new DomainException(t('editar_solicitud.error_fecha'));
        }
        $resultado['fecha_hora_limite'] = null;
        if ($limite !== '') {
            $zona = new DateTimeZone('America/Montevideo');
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $limite, $zona);
            if (!$fecha || $fecha->format('Y-m-d\TH:i') !== $limite || $fecha <= new DateTimeImmutable('now', $zona)) {
                throw new DomainException(t('editar_solicitud.error_fecha_pasada'));
            }
            $resultado['fecha_hora_limite'] = $fecha->format('Y-m-d H:i:s');
        }
        return $resultado;
    }

    public static function guardar(int $id, int $usuario, array $datos): void
    {
        $datos = self::validar($datos);
        $db = Conexion::conectar();
        try {
            $db->beginTransaction();
            // Asignacion y cancelacion bloquean esta misma fila antes de modificarla.
            $q = $db->prepare('SELECT id_usuario_solicit, estado_solicitud, id_tipo_elemento FROM solicitud_traslado WHERE id_solicitud = :id FOR UPDATE');
            $q->execute([':id' => $id]);
            $solicitud = $q->fetch(PDO::FETCH_ASSOC);
            if (!$solicitud || (int) $solicitud['id_usuario_solicit'] !== $usuario || $solicitud['estado_solicitud'] !== 'Pendiente') {
                throw new DomainException(t('editar_solicitud.error_ajena'));
            }
            $q = $db->prepare('SELECT id_traslado FROM traslado WHERE id_solicitud = :id FOR UPDATE');
            $q->execute([':id' => $id]);
            if ($q->fetchColumn() !== false) {
                throw new DomainException(t('editar_solicitud.error_con_traslado'));
            }
            $q = $db->prepare('SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion IN (:origen, :destino)');
            $q->execute([':origen' => $datos['id_origen'], ':destino' => $datos['id_destino']]);
            if (count($q->fetchAll(PDO::FETCH_COLUMN)) !== 2) {
                throw new DomainException(t('editar_solicitud.error_lugar_inexistente'));
            }
            if ((int) $solicitud['id_tipo_elemento'] !== 1) {
                $q = $db->prepare("SELECT 1 FROM ubicacion WHERE id_ubicacion IN (:origen, :destino) AND tipo_ubicacion = 'Domicilio' LIMIT 1");
                $q->execute([':origen' => $datos['id_origen'], ':destino' => $datos['id_destino']]);
                if ($q->fetchColumn()) {
                    throw new DomainException(t('domicilio.solo_pacientes'));
                }
            }
            // Conserva la prioridad maxima de organos, igual que al crear.
            if ((int) $solicitud['id_tipo_elemento'] === 2) {
                $datos['prioridad'] = 'maxima';
            }
            $q = $db->prepare('UPDATE solicitud_traslado SET id_origen = :id_origen,
                id_destino = :id_destino, prioridad = :prioridad,
                fecha_hora_limite = :fecha_hora_limite, observaciones = :observaciones
                WHERE id_solicitud = :id');
            $datos['id'] = $id;
            $q->execute($datos);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
