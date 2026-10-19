<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 5]);

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Vista/choferes.php');
    exit;
}

$conexion = null;

try {
    $token = $_POST['token'] ?? '';
    $tokenSesion = $_SESSION['csrf_seguimiento'] ?? '';

    if (
        !is_string($token)
        || $tokenSesion === ''
        || !hash_equals($tokenSesion, $token)
    ) {
        throw new DomainException('La solicitud no es válida. Recargue la página.');
    }

    $idTraslado = filter_input(INPUT_POST, 'id_traslado', FILTER_VALIDATE_INT);
    $nuevoEstado = filter_input(INPUT_POST, 'id_estado', FILTER_VALIDATE_INT);

    if (!$idTraslado || $idTraslado <= 0 || !$nuevoEstado) {
        throw new DomainException('Datos de traslado inválidos.');
    }

    $idUsuario = (int) $_SESSION['id_usuario'];
    $esDTI = tieneRol(1);

    $conexion = Conexion::conectar();
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        'SELECT id_solicitud, id_chofer
         FROM traslado
         WHERE id_traslado = :id
         FOR UPDATE'
    );
    $consulta->execute([':id' => $idTraslado]);
    $traslado = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$traslado) {
        throw new DomainException('El traslado no existe.');
    }

    if (!$esDTI && (int) $traslado['id_chofer'] !== $idUsuario) {
        throw new DomainException('Solo puede actualizar sus propios traslados.');
    }

    $consulta = $conexion->prepare(
        'SELECT id_estado, fecha_hora
         FROM historial_estado
         WHERE id_traslado = :id
         ORDER BY fecha_hora DESC, id_historial DESC
         LIMIT 1
         FOR UPDATE'
    );
    $consulta->execute([':id' => $idTraslado]);
    $ultimo = $consulta->fetch(PDO::FETCH_ASSOC);

    $transiciones = [
        2 => 3,
        3 => 4,
        4 => 5,
        5 => 6
    ];

    $estadoActual = (int) ($ultimo['id_estado'] ?? 0);

    if (($transiciones[$estadoActual] ?? null) !== $nuevoEstado) {
        throw new DomainException(
            'El estado cambió o la acción no corresponde. Recargue la página.'
        );
    }

    $fecha = $conexion->query('SELECT NOW()')->fetchColumn();

    if ($fecha <= $ultimo['fecha_hora']) {
        throw new DomainException(
            'Espere un segundo antes de registrar el siguiente estado.'
        );
    }

    $observaciones = [
        3 => 'Se inició el traslado.',
        4 => 'El vehículo llegó al destino.',
        5 => 'Se inició el retorno.',
        6 => 'Traslado finalizado.'
    ];

    $consulta = $conexion->prepare(
        'SELECT nombre FROM estado_traslado WHERE id_estado = :id'
    );
    $consulta->execute([':id' => $nuevoEstado]);
    $nombreEstado = $consulta->fetchColumn();

    if ($nombreEstado === false) {
        throw new DomainException('El estado no está configurado en la base.');
    }

    $consulta = $conexion->prepare(
        'INSERT INTO historial_estado
            (id_traslado, id_estado, fecha_hora, id_usuario, observaciones)
         VALUES (:traslado, :estado, :fecha, :usuario, :observaciones)'
    );
    $consulta->execute([
        ':traslado' => $idTraslado,
        ':estado' => $nuevoEstado,
        ':fecha' => $fecha,
        ':usuario' => $idUsuario,
        ':observaciones' => $observaciones[$nuevoEstado]
    ]);

    $consulta = $conexion->prepare(
        'UPDATE solicitud_traslado
         SET estado_solicitud = :estado
         WHERE id_solicitud = :id'
    );
    $consulta->execute([
        ':estado' => $nombreEstado,
        ':id' => $traslado['id_solicitud']
    ]);

    $conexion->commit();

    $_SESSION['mensaje_seguimiento'] =
        "Traslado #{$idTraslado}: {$nombreEstado}.";

} catch (Throwable $e) {
    if ($conexion && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($e instanceof DomainException) {
        $_SESSION['error_seguimiento'] = $e->getMessage();
    } else {
        error_log('Error de seguimiento: ' . $e->getMessage());
        $_SESSION['error_seguimiento'] =
            'No se pudo guardar el cambio de estado.';
    }
}

header('Location: ../Vista/choferes.php');
exit;