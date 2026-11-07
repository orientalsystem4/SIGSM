<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
require_once __DIR__ . '/../Model/PacienteModelo.php';
require_once __DIR__ . '/../Model/SolicitudModelo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderSolicitud(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!tieneRol([1, 2, 6])) {
    responderSolicitud(403, [
        'exito' => false,
        'mensaje' => t('solicitud.error_permisos')
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    responderSolicitud(405, [
        'exito' => false,
        'mensaje' => t('error.metodo')
    ]);
}

$token = $_POST['token'] ?? '';
$tokenSesion = $_SESSION['csrf_traslado'] ?? '';

if (
    !is_string($token)
    || $tokenSesion === ''
    || !hash_equals($tokenSesion, $token)
) {
    responderSolicitud(403, [
        'exito' => false,
        'mensaje' => t('error.recargar_pagina_usted')
    ]);
}

function textoSolicitud(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

function enteroSolicitud(string $campo): int
{
    $valor = filter_var(
        textoSolicitud($campo),
        FILTER_VALIDATE_INT
    );

    return $valor !== false && $valor > 0 ? $valor : 0;
}

$idTipo = enteroSolicitud('id_tipo_elemento');
$idOrigen = enteroSolicitud('id_origen');
$idDestino = enteroSolicitud('id_destino');
$idCanal = enteroSolicitud('id_canal');

$cedula = str_replace(
    ['.', '-', ' '],
    '',
    textoSolicitud('ci_paciente')
);

$prioridad = textoSolicitud('prioridad');
$observaciones = textoSolicitud('observaciones');
$limiteTexto = textoSolicitud('fecha_hora_limite');

$errores = [];
$fechaLimite = null;

if (!$idTipo || !$idOrigen || !$idDestino || !$idCanal) {
    $errores[] = t('solicitud.error_obligatorios');
}

if ($idOrigen === $idDestino) {
    $errores[] = t('solicitud.error_mismo_lugar');
}

// Mantiene la regla que ya tiene el formulario.
if ($idTipo === 2) {
    $prioridad = 'maxima';
}

if (!in_array($prioridad, ['normal', 'alta', 'maxima'], true)) {
    $errores[] = t('solicitud.error_prioridad');
}

if ($idTipo === 1 && !preg_match('/^[0-9]{7,8}$/', $cedula)) {
    $errores[] = t('paciente.error_cedula');
}

if (preg_match('//u', $observaciones) !== 1) {
    $errores[] = t('solicitud.error_obs_caracteres');
} elseif (preg_match_all('/./us', $observaciones) > 500) {
    $errores[] = t('solicitud.error_obs_largo');
}

if ($limiteTexto !== '') {
    $zona = new DateTimeZone('America/Montevideo');

    $fecha = DateTimeImmutable::createFromFormat(
        '!Y-m-d\TH:i',
        $limiteTexto,
        $zona
    );

    if (
        !$fecha
        || $fecha->format('Y-m-d\TH:i') !== $limiteTexto
    ) {
        $errores[] = t('solicitud.error_fecha');
    } elseif ($fecha <= new DateTimeImmutable('now', $zona)) {
        $errores[] = t('solicitud.error_fecha_pasada');
    } else {
        $fechaLimite = $fecha->format('Y-m-d H:i:s');
    }
}

if ($errores) {
    responderSolicitud(422, [
        'exito' => false,
        'mensaje' => implode(' ', $errores)
    ]);
}

try {
    $conexion = Conexion::conectar();

    // Las tablas y columnas son fijas, no vienen del navegador.
    $referencias = [
        ['tipo_elemento', 'id_tipo_elemento', $idTipo],
        ['ubicacion', 'id_ubicacion', $idOrigen],
        ['ubicacion', 'id_ubicacion', $idDestino],
        ['canal_solicitud', 'id_canal', $idCanal]
    ];

    if ($idTipo !== 1) {
        $consulta = $conexion->prepare(
            "SELECT 1 FROM ubicacion
             WHERE id_ubicacion IN (:origen, :destino)
               AND tipo_ubicacion = 'Domicilio' LIMIT 1"
        );
        $consulta->execute([':origen' => $idOrigen, ':destino' => $idDestino]);

        if ($consulta->fetchColumn()) {
            responderSolicitud(422, [
                'exito' => false,
                'mensaje' => t('domicilio.solo_pacientes')
            ]);
        }
    }

    foreach ($referencias as [$tabla, $columna, $id]) {
        $consulta = $conexion->prepare(
            "SELECT 1 FROM $tabla WHERE $columna = :id LIMIT 1"
        );

        $consulta->execute([':id' => $id]);

        if (!$consulta->fetchColumn()) {
            responderSolicitud(422, [
                'exito' => false,
                'mensaje' => t('solicitud.error_opcion')
            ]);
        }
    }

    if (
        $idTipo === 1
        && PacienteModelo::buscarPorCedula($cedula) === null
    ) {
        responderSolicitud(422, [
            'exito' => false,
            'mensaje' => t('paciente.no_encontrado')
        ]);
    }

    $datos = [
        'ci_paciente' => $idTipo === 1 ? $cedula : null,
        'id_tipo_elemento' => $idTipo,
        'id_origen' => $idOrigen,
        'id_destino' => $idDestino,
        'id_canal' => $idCanal,
        'prioridad' => $prioridad,
        'fecha_hora_limite' => $fechaLimite,
        'observaciones' => $observaciones !== ''
            ? $observaciones
            : null
    ];

    $idSolicitud = SolicitudModelo::crear(
        $datos,
        (int) $_SESSION['id_usuario']
    );

    responderSolicitud(201, [
        'exito' => true,
        'id_solicitud' => $idSolicitud,
        'mensaje' => t('solicitud.ok', ['id' => $idSolicitud])
    ]);

} catch (Exception $e) {
    error_log('Error al guardar solicitud: ' . $e->getMessage());

    responderSolicitud(500, [
        'exito' => false,
        'mensaje' => t('solicitud.error_general')
    ]);
}