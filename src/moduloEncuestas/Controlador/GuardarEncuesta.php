<?php
require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $idCategoria = isset($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : 1;
    
    // El id_encuesta será igual al id_categoria (así lo seteamos en InitBD)
    $idEncuesta = $idCategoria; 

    try {
        $conexion = Conexion::conectar();
        
        // Iniciar transacción
        $conexion->beginTransaction();

        // 1. Guardar la cabecera de la respuesta
        $insertEncuesta = $conexion->prepare("INSERT INTO respuesta_encuesta (id_encuesta, fecha_envio) VALUES (:id, NOW())");
        $insertEncuesta->execute([':id' => $idEncuesta]);
        
        // Obtener el ID generado
        $idRespEncuesta = $conexion->lastInsertId();

        // 2. Guardar el detalle de cada pregunta
        $insertPregunta = $conexion->prepare("INSERT INTO respuesta_pregunta (id_resp_encuesta, id_pregunta, texto_libre) VALUES (:id_resp, :id_preg, :valor)");

        // Recorrer las 8 preguntas institucionales
        for ($i = 1; $i <= 8; $i++) {
            $nombreCampo = 'preg_' . $i;
            if (isset($_POST[$nombreCampo])) {
                $valor = $_POST[$nombreCampo];
                $insertPregunta->execute([
                    ':id_resp' => $idRespEncuesta,
                    ':id_preg' => $i,
                    ':valor'   => $valor
                ]);
            }
        }

        // 3. Guardar el comentario libre (si existe)
        // Lo podemos asociar a una pregunta virtual (ej. id_pregunta = 9) o simplemente un registro extra.
        // Dado que la base de datos lo permite, lo insertamos como id_pregunta = 9 para comentarios.
        if (!empty($_POST['comentario_libre'])) {
            // Asegurarnos que la pregunta 9 exista
            $conexion->prepare("INSERT IGNORE INTO pregunta (id_pregunta, id_encuesta, tipo_pregunta) VALUES (9, 1, 'abierta')")->execute();
            
            $insertPregunta->execute([
                ':id_resp' => $idRespEncuesta,
                ':id_preg' => 9,
                ':valor'   => trim($_POST['comentario_libre'])
            ]);
        }

        // Confirmar transacción
        $conexion->commit();

        // Redirigir a la vista de encuesta con mensaje de éxito
        header("Location: ../Vista/responder.php?categoria=" . $idCategoria . "&exito=1");
        exit;

    } catch (Exception $e) {
        $conexion->rollBack();
        die("Error al guardar la encuesta: " . $e->getMessage());
    }
} else {
    // Acceso no válido
    header("Location: ../Vista/responder.php");
    exit;
}

