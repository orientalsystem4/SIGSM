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
            'Recargue el formulario e intente nuevamente.'
        );
    }

    if (!$idSolicitud || !$idVehiculo || !$idChofer || !$idRuta) {
        throw new DomainException('Complete los campos obligatorios.');
    }

    if ($idChofer === $idEnfermero) {
        throw new DomainException(
            'Chofer y Enfermeria deben ser personas diferentes.'
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
            'La solicitud ya no esta pendiente o no existe.'
        );
    }

    $consulta = $conexion->prepare(
        'SELECT id_traslado FROM traslado
         WHERE id_solicitud = :id'
    );
    $consulta->execute([':id' => $idSolicitud]);

    if ($consulta->fetchColumn()) {
        throw new DomainException('La solicitud ya tiene un traslado.');
    }

    if ((int) $solicitud['id_tipo_elemento'] === 1 && !$idEnfermero) {
        throw new DomainException(
            'Debe asignar Enfermeria para trasladar un paciente.'
        );
    }

    // Bloquear el vehiculo.
    $consulta = $conexion->prepare(
        'SELECT id_tipo_vehiculo, activo FROM vehiculo
         WHERE id_vehiculo = :id FOR UPDATE'
    );
    $consulta->execute([':id' => $idVehiculo]);
    $vehiculo = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo || (int) $vehiculo['activo'] !== 1) {
        throw new DomainException('El vehiculo no esta activo.');
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
            'El vehiculo no es compatible con lo que se transporta.'
        );
    }

    // Bloquear las personas siempre en el mismo orden.
    $personas = [$idChofer];
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
                'Una de las personas seleccionadas no esta activa.'
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
                'Una persona no tiene el rol necesario para esta asignacion.'
            );
        }
    }

    // Finalizado = 6; Cancelado = 7.
    // Sin historial se considera ocupado por precaucion.
    $sqlOcupados = "
        SELECT t.id_traslado
        FROM traslado t
        WHERE (
            t.id_vehiculo = ?
            OR t.id_chofer IN (?, ?)
            OR t.id_enfermero IN (?, ?)
        )
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
    $consulta->execute([
        $idVehiculo,
        $idChofer,
        $idEnfermero,
        $idChofer,
        $idEnfermero
    ]);

    if ($consulta->fetchColumn()) {
        throw new DomainException(
            'El vehiculo o una persona ya tiene un traslado sin finalizar.'
        );
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
            'La ruta no corresponde al origen y destino de la solicitud.'
        );
    }

    $consulta = $conexion->prepare(
        'INSERT INTO traslado (
            id_solicitud, id_vehiculo, id_chofer,
            id_enfermero, id_proveedor, id_ruta
         ) VALUES (
            :solicitud, :vehiculo, :chofer,
            :enfermero, NULL, :ruta
         )'
    );
    $consulta->execute([
        ':solicitud' => $idSolicitud,
        ':vehiculo' => $idVehiculo,
        ':chofer' => $idChofer,
        ':enfermero' => $idEnfermero ?: null,
        ':ruta' => $idRuta
    ]);

    $idTraslado = (int) $conexion->lastInsertId();

    $consulta = $conexion->prepare(
        "UPDATE solicitud_traslado
         SET estado_solicitud = 'Asignado'
         WHERE id_solicitud = :id"
    );
    $consulta->execute([':id' => $idSolicitud]);

    $consulta = $conexion->prepare(
        "INSERT INTO historial_estado (
            id_traslado, id_estado, fecha_hora,
            id_usuario, observaciones
         ) VALUES (
            :traslado, 2, NOW(),
            :usuario, 'Vehiculo y personal asignados.'
         )"
    );
    $consulta->execute([
        ':traslado' => $idTraslado,
        ':usuario' => (int) $_SESSION['id_usuario']
    ]);

    $conexion->commit();

    $_SESSION['mensaje_traslado'] =
        "Traslado #{$idTraslado} asignado correctamente.";

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
            'No se pudo guardar la asignacion. Intente nuevamente.';
    }

    header('Location: ../Vista/asignar.php?id=' . $idSolicitud);
    exit;
}