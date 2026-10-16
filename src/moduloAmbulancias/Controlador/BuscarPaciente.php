<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!tieneRol([1, 2, 6])) {
    http_response_code(403);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'No tiene permisos para consultar pacientes.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Metodo no permitido.'
    ]);
    exit;
}

require_once __DIR__ . '/../Model/PacienteModelo.php';

$cedula = $_GET['ci'] ?? '';

if (!is_string($cedula)) {
    $cedula = '';
}

// Permitir cedulas escritas con puntos y guion.
$cedula = str_replace(['.', '-', ' '], '', trim($cedula));

if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Ingrese una cedula de 7 u 8 digitos.'
    ]);
    exit;
}

try {
    $paciente = PacienteModelo::buscarPorCedula($cedula);

    echo json_encode([
        'exito' => true,
        'encontrado' => $paciente !== null,
        'paciente' => $paciente,
        'mensaje' => $paciente !== null
            ? 'Paciente encontrado.'
            : 'No se encontro un paciente con esa cedula.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error al buscar paciente: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'No se pudo consultar el paciente. Intente nuevamente.'
    ]);
}