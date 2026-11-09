<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 3]);

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Vista/unidadEnlace.php');
    exit;
}

function leerIdAsignacion(string $campo): int
{
    $valor = $_POST[$campo] ?? '';
    if (!is_string($valor)) {
        return 0;
    }

    $id = filter_var($valor, FILTER_VALIDATE_INT);
    return $id !== false && $id > 0 ? $id : 0;
}

$idSolicitud = leerIdAsignacion('id_solicitud');
$idVehiculo = leerIdAsignacion('id_vehiculo');
$idChofer = leerIdAsignacion('id_chofer');
$idEnfermero = leerIdAsignacion('id_enfermero');
$idRuta = leerIdAsignacion('id_ruta');

$conexion = null;

try {
    $token = $_POST['token'] ?? '';
    $tokenSesion = $_SESSION['csrf_asignacion'] ?? '';

    if (
        !is_string($token)
        || $tokenSesion === ''
        || !hash_equals($tokenSesion, $token)
    ) {
        throw new DomainException(
            t('error.recargar_formulario')
        );
    }

    // Proveedor externo (tercerizado): o es propio, o es de un proveedor.
    $campoProveedor = $_POST['id_proveedor'] ?? '';
    $campoProveedor = is_string($campoProveedor) ? trim($campoProveedor) : null;
    $idProveedor = 0;

    if ($campoProveedor === null) {
        throw new DomainException(t('asignar.error_proveedor'));
    }

    if ($campoProveedor !== '') {
        $idProveedor = leerIdAsignacion('id_proveedor');
        if (!$idProveedor) {
            throw new DomainException(t('asignar.error_proveedor'));
        }

        // Con proveedor, vehiculo y chofer deben venir vacios (aunque sean invalidos).
        foreach (['id_vehiculo', 'id_chofer'] as $campo) {
            $valor = $_POST[$campo] ?? '';
            if (!is_string($valor) || trim($valor) !== '') {
                throw new DomainException(t('asignar.error_ambos'));
            }
        }
    }

    $esTercerizado = $idProveedor > 0;

    if (
        !$idSolicitud || !$idRuta
        || (!$esTercerizado && (!$idVehiculo || !$idChofer))
    ) {
        throw new DomainException(t('asignar.error_obligatorios'));
    }

    if ($idChofer && $idChofer === $idEnfermero) {
        throw new DomainException(
            t('asignar.error_mismas_personas')
        );
    }

    $conexion = Conexion::conectar();

    // Las comprobaciones ven los cambios confirmados de otras asignaciones.
    $conexion->exec(
        'SET TRANSACTION ISOLATION LEVEL READ COMMITTED'
    );
    $conexion->beginTransaction();

    // Bloquear la solicitud mientras se asigna.
    $consulta = $conexion->prepare(
        'SELECT * FROM solicitud_traslado
         WHERE id_solicitud = :id FOR UPDATE'
    );
    $consulta->execute([':id' => $idSolicitud]);
    $solicitud = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud || $solicitud['estado_solicitud'] !== 'Pendiente') {
        throw new DomainException(
            t('asignar.error_no_pendiente')
        );
    }

    // Traslado al domicilio: solo pacientes y con direccion obligatoria.
    $consulta = $conexion->prepare(
        "SELECT COUNT(*) FROM ubicacion
         WHERE id_ubicacion IN (:origen, :destino)
           AND tipo_ubicacion = 'Domicilio'"
    );
    $consulta->execute([
        ':origen' => $solicitud['id_origen'],
        ':destino' => $solicitud['id_destino']
    ]);
    $direccionDomicilio = null;

    if ((int) $consulta->fetchColumn() > 0) {
        if ((int) $solicitud['id_tipo_elemento'] !== 1) {
            throw new DomainException(
                t('domicilio.solo_pacientes')
            );
        }

        $direccionDomicilio = $_POST['direccion_domicilio'] ?? '';
        $direccionDomicilio = is_string($direccionDomicilio)
            ? trim($direccionDomicilio)
            : '';

        if (
            $direccionDomicilio === ''
            || preg_match('//u', $direccionDomicilio) !== 1
            || preg_match('/[\x00-\x1F\x7F]/', $direccionDomicilio) === 1
            || mb_strlen($direccionDomicilio, 'UTF-8') > 255
        ) {
            throw new DomainException(
                t('domicilio.error_direccion')
            );
        }
    }

    $consulta = $conexion->prepare(
        'SELECT id_traslado FROM traslado
         WHERE id_solicitud = :id'
    );
    $consulta->execute([':id' => $idSolicitud]);

    if ($consulta->fetchColumn()) {
        throw new DomainException(t('asignar.error_ya_tiene'));
    }

    if ((int) $solicitud['id_tipo_elemento'] === 1 && !$idEnfermero) {
        throw new DomainException(
            t('asignar.error_falta_enfermeria')
        );
    }

    if ($esTercerizado) {
        // El proveedor debe existir.
        $consulta = $conexion->prepare(
            'SELECT 1 FROM proveedor_externo WHERE id_proveedor = :id'
        );
        $consulta->execute([':id' => $idProveedor]);

        if (!$consulta->fetchColumn()) {
            throw new DomainException(t('asignar.error_proveedor'));
        }
    } else {
        // Bloquear el vehiculo.
        $consulta = $conexion->prepare(
            'SELECT id_tipo_vehiculo, activo FROM vehiculo
             WHERE id_vehiculo = :id FOR UPDATE'
        );
        $consulta->execute([':id' => $idVehiculo]);
        $vehiculo = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$vehiculo || (int) $vehiculo['activo'] !== 1) {
            throw new DomainException(t('asignar.error_vehiculo_inactivo'));
        }

        $consulta = $conexion->prepare(
            'SELECT 1 FROM compatibilidad
             WHERE id_tipo_vehiculo = :vehiculo
               AND id_tipo_elemento = :elemento'
        );
        $consulta->execute([
            ':vehiculo' => $vehiculo['id_tipo_vehiculo'],
            ':elemento' => $solicitud['id_tipo_elemento']
        ]);

        if (!$consulta->fetchColumn()) {
            throw new DomainException(
                t('asignar.error_vehiculo_incompatible')
            );
        }
    }

    // Bloquear las personas siempre en el mismo orden.
    $personas = [];
    if ($idChofer) {
        $personas[] = $idChofer;
    }
    if ($idEnfermero) {
        $personas[] = $idEnfermero;
    }
    sort($personas, SORT_NUMERIC);

    foreach ($personas as $idPersona) {
        $consulta = $conexion->prepare(
            'SELECT activo FROM usuario
             WHERE id_usuario = :id FOR UPDATE'
        );
        $consulta->execute([':id' => $idPersona]);

        if ((int) $consulta->fetchColumn() !== 1) {
            throw new DomainException(
                t('asignar.error_persona_inactiva')
            );
        }

        $rolRequerido = $idPersona === $idChofer ? 5 : 6;

        $consulta = $conexion->prepare(
            'SELECT 1 FROM usuario_rol
             WHERE id_usuario = :usuario AND id_rol = :rol'
        );
        $consulta->execute([
            ':usuario' => $idPersona,
            ':rol' => $rolRequerido
        ]);

        if (!$consulta->fetchColumn()) {
            throw new DomainException(
                t('asignar.error_rol')
            );
        }
    }

    // Finalizado = 6; Cancelado = 7.
    // Sin historial se considera ocupado por precaucion.
    // Las condiciones se arman solo con lo que se eligio: un vehiculo o chofer
    // ausente (tercerizado) no entra en la consulta.
    $condiciones = [];
    $parametros = [];

    if (!$esTercerizado) {
        $condiciones[] = 't.id_vehiculo = ?';
        $parametros[] = $idVehiculo;
    }

    if ($personas) {
        $marcas = implode(', ', array_fill(0, count($personas), '?'));
        $condiciones[] = "t.id_chofer IN ($marcas)";
        $condiciones[] = "t.id_enfermero IN ($marcas)";
        $parametros = array_merge($parametros, $personas, $personas);
    }

    if ($condiciones) {
        $sqlOcupados = "
            SELECT t.id_traslado
            FROM traslado t
            WHERE (" . implode(' OR ', $condiciones) . ")
            AND COALESCE((
                SELECT h.id_estado
                FROM historial_estado h
                WHERE h.id_traslado = t.id_traslado
                ORDER BY h.fecha_hora DESC, h.id_historial DESC
                LIMIT 1
            ), 0) NOT IN (6, 7)
            LIMIT 1
        ";

        $consulta = $conexion->prepare($sqlOcupados);
        $consulta->execute($parametros);

        if ($consulta->fetchColumn()) {
            throw new DomainException(
                t('asignar.error_ocupado')
            );
        }
    }

    $consulta = $conexion->prepare(
        'SELECT 1 FROM ruta
         WHERE id_ruta = :ruta
           AND id_origen = :origen
           AND id_destino = :destino'
    );
    $consulta->execute([
        ':ruta' => $idRuta,
        ':origen' => $solicitud['id_origen'],
        ':destino' => $solicitud['id_destino']
    ]);

    if (!$consulta->fetchColumn()) {
        throw new DomainException(
            t('asignar.error_ruta')
        );
    }

    $consulta = $conexion->prepare(
        'INSERT INTO traslado (
            id_solicitud, id_vehiculo, id_chofer,
            id_enfermero, id_proveedor, id_ruta
         ) VALUES (
            :solicitud, :vehiculo, :chofer,
            :enfermero, :proveedor, :ruta
         )'
    );
    $consulta->execute([
        ':solicitud' => $idSolicitud,
        ':vehiculo' => $esTercerizado ? null : $idVehiculo,
        ':chofer' => $esTercerizado ? null : $idChofer,
        ':enfermero' => $idEnfermero ?: null,
        ':proveedor' => $esTercerizado ? $idProveedor : null,
        ':ruta' => $idRuta
    ]);

    $idTraslado = (int) $conexion->lastInsertId();

    $consulta = $conexion->prepare(
        "UPDATE solicitud_traslado
         SET estado_solicitud = 'Asignado',
             direccion_domicilio = :direccion
         WHERE id_solicitud = :id"
    );
    $consulta->execute([
        ':id' => $idSolicitud,
        ':direccion' => $direccionDomicilio
    ]);

    $consulta = $conexion->prepare(
        "INSERT INTO historial_estado (
            id_traslado, id_estado, fecha_hora,
            id_usuario, observaciones
         ) VALUES (
            :traslado, 2, NOW(),
            :usuario, :observacion
         )"
    );
    $consulta->execute([
        ':traslado' => $idTraslado,
        ':usuario' => (int) $_SESSION['id_usuario'],
        ':observacion' => $esTercerizado ? 'hist.asignado_proveedor' : 'hist.asignado'
    ]);

    $conexion->commit();

    $_SESSION['mensaje_traslado'] =
        t('asignar.ok', ['id' => $idTraslado]);

    header('Location: ../Vista/unidadEnlace.php');
    exit;

} catch (Throwable $e) {
    if ($conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($e instanceof DomainException) {
        $_SESSION['error_asignacion'] = $e->getMessage();
    } else {
        error_log('Error al asignar traslado: ' . $e->getMessage());
        $_SESSION['error_asignacion'] =
            t('asignar.error_general');
    }

    header('Location: ../Vista/asignar.php?id=' . $idSolicitud);
    exit;
}