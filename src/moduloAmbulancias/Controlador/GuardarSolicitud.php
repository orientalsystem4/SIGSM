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
        'mensaje' => 'No tiene permisos para crear solicitudes.'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    responderSolicitud(405, [
        'exito' => false,
        'mensaje' => 'Metodo no permitido.'
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
        'mensaje' => 'Recargue la pagina e intente nuevamente.'
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
    $errores[] = 'Complete tipo, origen, destino y canal.';
}

if ($idOrigen === $idDestino) {
    $errores[] = 'El origen y el destino deben ser diferentes.';
}

// Mantiene la regla que ya tiene el formulario.
if ($idTipo === 2) {
    $prioridad = 'maxima';
}

if (!in_array($prioridad, ['normal', 'alta', 'maxima'], true)) {
    $errores[] = 'La prioridad no es valida.';
}

if ($idTipo === 1 && !preg_match('/^[0-9]{7,8}$/', $cedula)) {
    $errores[] = 'Ingrese una cedula de 7 u 8 digitos.';
}

if (preg_match('//u', $observaciones) !== 1) {
    $errores[] = 'Las observaciones contienen caracteres invalidos.';
} elseif (preg_match_all('/./us', $observaciones) > 500) {
    $errores[] = 'Las observaciones no pueden superar 500 caracteres.';
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
        $errores[] = 'La fecha y hora limite no son validas.';
    } elseif ($fecha <= new DateTimeImmutable('now', $zona)) {
        $errores[] = 'La fecha limite debe ser posterior al momento actual.';
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

    foreach ($referencias as [$tabla, $columna, $id]) {
        $consulta = $conexion->prepare(
            "SELECT 1 FROM $tabla WHERE $columna = :id LIMIT 1"
        );

        $consulta->execute([':id' => $id]);

        if (!$consulta->fetchColumn()) {
            responderSolicitud(422, [
                'exito' => false,
                'mensaje' => 'Una de las opciones seleccionadas no existe.'
            ]);
        }
    }

    if (
        $idTipo === 1
        && PacienteModelo::buscarPorCedula($cedula) === null
    ) {
        responderSolicitud(422, [
            'exito' => false,
            'mensaje' => 'No se encontro un paciente con esa cedula.'
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
        'mensaje' => "Solicitud #{$idSolicitud} registrada correctamente."
    ]);

} catch (Exception $e) {
    error_log('Error al guardar solicitud: ' . $e->getMessage());

    responderSolicitud(500, [
        'exito' => false,
        'mensaje' => 'No se pudo guardar la solicitud. Intente nuevamente.'
    ]);
}