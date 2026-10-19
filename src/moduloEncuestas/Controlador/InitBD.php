<?php
// Script para inicializar los datos base de la encuesta.
require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

try {
    $conexion = Conexion::conectar();
    
    echo "<h1>Inicializando datos base de Encuestas...</h1>";

    // 1. Crear las encuestas base para cada categoría que existe
    $categorias = $conexion->query("SELECT id_categoria, nombre FROM categoria")->fetchAll(PDO::FETCH_ASSOC);
    
    $insertEncuesta = $conexion->prepare("INSERT IGNORE INTO encuesta (id_encuesta, id_categoria, segmento) VALUES (:id_encuesta, :id_categoria, :segmento)");
    
    foreach ($categorias as $cat) {
        $insertEncuesta->execute([
            ':id_encuesta' => $cat['id_categoria'], // Usamos el mismo ID de categoría para la encuesta
            ':id_categoria' => $cat['id_categoria'],
            ':segmento' => 'Institucional'
        ]);
        echo "<p>Encuesta vinculada para categoría: " . $cat['nombre'] . "</p>";
    }

    // 2. Crear las 8 preguntas institucionales base si no existen
    $preguntas = [
        ['id' => 1, 'encuesta' => 1, 'tipo' => 'escala'], // Atención
        ['id' => 2, 'encuesta' => 1, 'tipo' => 'escala'], // Explicaciones
        ['id' => 3, 'encuesta' => 1, 'tipo' => 'escala'], // Espera
        ['id' => 4, 'encuesta' => 1, 'tipo' => 'escala'], // Señalización
        ['id' => 5, 'encuesta' => 1, 'tipo' => 'escala'], // Limpieza
        ['id' => 6, 'encuesta' => 1, 'tipo' => 'escala'], // Confort
        ['id' => 7, 'encuesta' => 1, 'tipo' => 'escala'], // Conformidad
        ['id' => 8, 'encuesta' => 1, 'tipo' => 'escala'], // Recomendación
    ];

    $insertPregunta = $conexion->prepare("INSERT IGNORE INTO pregunta (id_pregunta, id_encuesta, tipo_pregunta) VALUES (:id, :enc, :tipo)");
    
    foreach ($categorias as $cat) {
        foreach ($preguntas as $p) {
            // Creamos las preguntas para cada encuesta
            // Para simplificar, insertamos las preguntas base 1-8 referenciadas a la encuesta 1 (o genéricas).
            // Pero dado que la restricción exige un id_encuesta, y las preguntas son genéricas, las asociaremos a la primera encuesta, 
            // ya que en respuesta_pregunta podemos referenciarlas. 
            // Para no duplicar 8 preguntas por categoría, podemos insertarlas solo asociadas a la id_encuesta = 1.
            $insertPregunta->execute([
                ':id' => $p['id'],
                ':enc' => 1, // Las preguntas "modelo" se atan a la encuesta 1
                ':tipo' => $p['tipo']
            ]);
        }
    }
    
    echo "<p>Preguntas base creadas con éxito.</p>";
    echo "<h2 style='color:green;'>¡Base de datos lista para recibir respuestas!</h2>";
    echo "<p><a href='../Vista/responder.php?categoria=1'>Ir a ver la encuesta de prueba</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

