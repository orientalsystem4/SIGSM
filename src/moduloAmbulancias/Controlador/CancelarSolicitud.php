<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2, 3, 6]);

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

$destino = tieneRol(3)
    ? '../Vista/unidadEnlace.php'
    : '../../moduloDocumentacion/Vista/enfermeria.php';

$conexion = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new DomainException(t('error.solicitud_no_valida_corta'));
    }

    $token = $_POST['token'] ?? '';
    $tokenSesion = $_SESSION['csrf_cancelacion'] ?? '';

    if (
        !is_string($token)
        || $tokenSesion === ''
        || !hash_equals($tokenSesion, $token)
    ) {
        throw new DomainException(t('error.recargar_pagina'));
    }

    $idSolicitud = filter_input(INPUT_POST, 'id_solicitud', FILTER_VALIDATE_INT);
    $motivo = $_POST['motivo'] ?? '';
    $motivo = is_string($motivo) ? trim($motivo) : '';

    if (!$idSolicitud || $idSolicitud <= 0) {
        throw new DomainException(t('error.solicitud_no_valida'));
    }

    if (
        $motivo === ''
        || preg_match('//u', $motivo) !== 1
        || preg_match_all('/./us', $motivo) > 500
    ) {
        throw new DomainException(t('cancelar.error_motivo'));
    }

    $conexion = Conexion::conectar();
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        'SELECT id_usuario_solicit, estado_solicitud
         FROM solicitud_traslado
         WHERE id_solicitud = :id
         FOR UPDATE'
    );
    $consulta->execute([':id' => $idSolicitud]);
    $solicitud = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        throw new DomainException(t('cancelar.error_no_existe'));
    }

    $esPropia = (int) $solicitud['id_usuario_solicit']
        === (int) $_SESSION['id_usuario'];

    if (!tieneRol([1, 3]) && !$esPropia) {
        throw new DomainException(t('cancelar.error_ajena'));
    }

    if ($solicitud['estado_solicitud'] !== 'Pendiente') {
        throw new DomainException(t('cancelar.error_no_pendiente'));
    }

    $consulta = $conexion->prepare(
        'SELECT id_traslado
         FROM traslado
         WHERE id_solicitud = :id
         FOR UPDATE'
    );
    $consulta->execute([':id' => $idSolicitud]);

    if ($consulta->fetchColumn() !== false) {
        throw new DomainException(t('cancelar.error_con_traslado'));
    }

    $consulta = $conexion->prepare(
        "UPDATE solicitud_traslado
         SET estado_solicitud = 'Cancelada',
             motivo_cancelacion = :motivo,
             fecha_cancelacion = NOW(),
             id_usuario_cancelacion = :usuario
         WHERE id_solicitud = :id"
    );
    $consulta->execute([
        ':motivo' => $motivo,
        ':usuario' => (int) $_SESSION['id_usuario'],
        ':id' => $idSolicitud
    ]);

    $conexion->commit();
    $_SESSION['mensaje_cancelacion'] = t('cancelar.ok');
} catch (Throwable $e) {
    if ($conexion && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($e instanceof DomainException) {
        $_SESSION['mensaje_cancelacion'] = $e->getMessage();
    } else {
        error_log('Error al cancelar solicitud: ' . $e->getMessage());
        $_SESSION['mensaje_cancelacion'] = t('cancelar.error_general');
    }
}

header('Location: ' . $destino);
exit;