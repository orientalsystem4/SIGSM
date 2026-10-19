<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

try {
    $conexion = Conexion::conectar();
    
    // Obtener filtro por categoría (0 = Todas)
    $idCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
    
    // Mapa de textos de las 8 preguntas institucionales
    $mapaPreguntas = [
        1 => '¿Cómo califica la amabilidad y el respeto recibido por el personal de salud?',
        2 => '¿El personal le brindó explicaciones claras sobre su atención o procedimiento?',
        3 => '¿Considera que el tiempo de espera para su atención fue adecuado?',
        4 => '¿La orientación y señalización para llegar a su consulta fue clara?',
        5 => '¿Cómo evalúa la limpieza e higiene de las salas y consultorios?',
        6 => '¿El confort y la privacidad durante su estadía fueron suficientes?',
        7 => 'En términos generales, ¿qué tan conforme se encuentra con el servicio recibido?',
        8 => '¿Recomendaría la atención de este centro de salud a un familiar?'
    ];
    
    // Filtro base para la consulta
    $filtroSql = "";
    $params = [];
    if ($idCategoria > 0) {
        $filtroSql = " WHERE re.id_encuesta = :id_categoria ";
        $params[':id_categoria'] = $idCategoria;
    }
    
    // 1. Total de encuestas
    $sqlTotal = "SELECT COUNT(*) as total FROM respuesta_encuesta re" . $filtroSql;
    $stmtTotal = $conexion->prepare($sqlTotal);
    $stmtTotal->execute($params);
    $totalEncuestas = (int)$stmtTotal->fetchColumn();
    
    // 2. Distribución y promedios por pregunta (del 1 al 8)
    $preguntasData = [];
    for ($i = 1; $i <= 8; $i++) {
        $preguntasData[$i] = [
            'id' => $i,
            'texto' => $mapaPreguntas[$i],
            'distribucion' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'promedio' => 0,
            'total_respuestas' => 0
        ];
    }
    
    // Consulta para agrupar respuestas
    $sqlRespuestas = "
        SELECT rp.id_pregunta, rp.texto_libre, COUNT(*) as cantidad 
        FROM respuesta_pregunta rp
        INNER JOIN respuesta_encuesta re ON rp.id_resp_encuesta = re.id_resp_encuesta
        $filtroSql
        GROUP BY rp.id_pregunta, rp.texto_libre
    ";
    
    $stmtResp = $conexion->prepare($sqlRespuestas);
    $stmtResp->execute($params);
    $resultados = $stmtResp->fetchAll(PDO::FETCH_ASSOC);
    
    $comentarios = [];
    
    foreach ($resultados as $fila) {
        $idPregunta = (int)$fila['id_pregunta'];
        
        if ($idPregunta >= 1 && $idPregunta <= 8) {
            $valorStr = trim($fila['texto_libre']);
            $valor = (int)$valorStr;
            
            if ($valor >= 1 && $valor <= 5) {
                $cantidad = (int)$fila['cantidad'];
                $preguntasData[$idPregunta]['distribucion'][$valor] += $cantidad;
                $preguntasData[$idPregunta]['total_respuestas'] += $cantidad;
            }
        } 
        else if ($idPregunta === 9) {
            // Es un comentario libre. Como los agrupamos, desempaquetamos si hay repetidos, aunque el group by junta los iguales
            // Para ver los textos, mejor hacemos una consulta individual para los comentarios, o devolvemos esto.
            // Wait, el GROUP BY me da el texto y cuántas veces se repite.
            $texto = trim($fila['texto_libre']);
            if ($texto !== '') {
                for ($k=0; $k < $fila['cantidad']; $k++) {
                    $comentarios[] = $texto;
                }
            }
        }
    }
    
    // Calcular promedios
    foreach ($preguntasData as $id => $data) {
        $suma = 0;
        foreach ($data['distribucion'] as $valor => $cantidad) {
            $suma += ($valor * $cantidad);
        }
        if ($data['total_respuestas'] > 0) {
            $preguntasData[$id]['promedio'] = round($suma / $data['total_respuestas'], 1);
        }
    }
    
    echo json_encode([
        'exito' => true,
        'total' => $totalEncuestas,
        'preguntas' => array_values($preguntasData),
        'comentarios' => $comentarios
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
}

