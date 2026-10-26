<?php
require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';
require_once __DIR__ . '/../Model/ValidacionEncuesta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Vista/responder.php');
    exit;
}

$conexion = null;
try {
    $datos = ValidacionEncuesta::validar($_POST);
    $conexion = Conexion::conectar();
    $consulta = $conexion->prepare('SELECT id_encuesta FROM encuesta WHERE id_encuesta = ? AND id_categoria = ?');
    $consulta->execute([$datos['categoria'], $datos['categoria']]);
    if (!$consulta->fetchColumn()) {
        throw new DomainException('La encuesta no esta disponible. Avise al responsable del sistema.');
    }

    $conexion->beginTransaction();
    $cabecera = $conexion->prepare('INSERT INTO respuesta_encuesta (id_encuesta, fecha_envio) VALUES (?, NOW())');
    $cabecera->execute([$datos['categoria']]);
    $idRespuesta = $conexion->lastInsertId();
    $detalle = $conexion->prepare('INSERT INTO respuesta_pregunta (id_resp_encuesta, id_pregunta, texto_libre) VALUES (?, ?, ?)');
    foreach ($datos['respuestas'] as $idPregunta => $valor) {
        $detalle->execute([$idRespuesta, $idPregunta, $valor]);
    }
    if ($datos['comentario'] !== '') {
        $detalle->execute([$idRespuesta, 9, $datos['comentario']]);
    }
    $conexion->commit();
    header('Location: ../Vista/responder.php?categoria=' . $datos['categoria'] . '&exito=1');
    exit;
} catch (Throwable $error) {
    if ($conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $esValidacion = $error instanceof DomainException;
    http_response_code($esValidacion ? 400 : 500);
    header('Content-Type: text/html; charset=utf-8');
    if (!$esValidacion) {
        error_log('Error al guardar encuesta: ' . $error->getMessage());
    }
    $mensaje = $esValidacion ? $error->getMessage() : 'No se pudo guardar la encuesta. Intente nuevamente.';
    echo '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>Use el boton Atras del navegador para volver al formulario.</p>';
}
