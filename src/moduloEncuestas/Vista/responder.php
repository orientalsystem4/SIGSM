<?php
// Recibir la categoría del paciente (por defecto 1 si no viene)
$idCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 1;

// Definición estática de la Encuesta Institucional de Satisfacción
$encuestaInstitucional = [
    [
        'id_seccion' => 1,
        'titulo' => 'Atención y Trato del Personal',
        'preguntas' => [
            ['id' => 1, 'texto' => '¿Cómo califica la amabilidad y el respeto recibido por el personal de salud?', 'tipo' => 'escala'],
            ['id' => 2, 'texto' => '¿El personal le brindó explicaciones claras sobre su atención o procedimiento?', 'tipo' => 'escala']
        ]
    ],
    [
        'id_seccion' => 2,
        'titulo' => 'Tiempos de Espera y Organización',
        'preguntas' => [
            ['id' => 3, 'texto' => '¿Considera que el tiempo de espera para su atención fue adecuado?', 'tipo' => 'escala'],
            ['id' => 4, 'texto' => '¿La orientación y señalización para llegar a su consulta fue clara?', 'tipo' => 'escala']
        ]
    ],
    [
        'id_seccion' => 3,
        'titulo' => 'Instalaciones, Higiene y Confort',
        'preguntas' => [
            ['id' => 5, 'texto' => '¿Cómo evalúa la limpieza e higiene de las salas y consultorios?', 'tipo' => 'escala'],
            ['id' => 6, 'texto' => '¿El confort y la privacidad durante su estadía fueron suficientes?', 'tipo' => 'escala']
        ]
    ],
    [
        'id_seccion' => 4,
        'titulo' => 'Satisfacción General',
        'preguntas' => [
            ['id' => 7, 'texto' => 'En términos generales, ¿qué tan conforme se encuentra con el servicio recibido?', 'tipo' => 'escala'],
            ['id' => 8, 'texto' => '¿Recomendaría la atención de este centro de salud a un familiar?', 'tipo' => 'escala']
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | Encuesta Institucional de Satisfacción</title>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/encuesta.css">
</head>
<body>

    <div class="encuesta-wrapper">

        <!-- CABECERA PRINCIPAL -->
        <div class="encuesta-card">
            <div class="encuesta-header">
                <h1>Encuesta de Satisfacción Institucional</h1>
                <p>Su opinión nos ayuda a evaluar la calidad del servicio recibido y detectar posibles mejoras.</p>
                <div class="anonima-badge">
                    🔒 Esta encuesta es 100% anónima
                </div>
            </div>
        </div>

        <form id="formEncuesta" method="POST" action="../Controlador/GuardarEncuesta.php">
            <!-- Campo oculto para saber de qué área viene -->
            <input type="hidden" name="id_categoria" value="<?php echo $idCategoria; ?>">

            <?php foreach ($encuestaInstitucional as $seccion): ?>
            <div class="encuesta-card">
                <div class="seccion-header">
                    <?php echo htmlspecialchars($seccion['titulo']); ?>
                </div>
                <div class="formulario">
                    
                    <?php foreach ($seccion['preguntas'] as $pregunta): ?>
                    <div class="pregunta">
                        <h3><?php echo htmlspecialchars($pregunta['texto']); ?></h3>
                        
                        <?php if ($pregunta['tipo'] === 'escala'): ?>
                        <div class="escala">
                            <label><input type="radio" name="preg_<?php echo $pregunta['id']; ?>" value="1" required>1</label>
                            <label><input type="radio" name="preg_<?php echo $pregunta['id']; ?>" value="2">2</label>
                            <label><input type="radio" name="preg_<?php echo $pregunta['id']; ?>" value="3">3</label>
                            <label><input type="radio" name="preg_<?php echo $pregunta['id']; ?>" value="4">4</label>
                            <label><input type="radio" name="preg_<?php echo $pregunta['id']; ?>" value="5">5</label>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
            <?php endforeach; ?>

            <!-- COMENTARIOS ADICIONALES -->
            <div class="encuesta-card">
                <div class="seccion-header">Comentarios Adicionales</div>
                <div class="formulario">
                    <p class="text-muted" style="margin-top:0; color:#667085;">¿Desea agregar alguna sugerencia o detalle sobre su atención?</p>
                    <textarea name="comentario_libre" maxlength="500" placeholder="Escriba aquí (opcional)..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-enviar">Enviar Encuesta</button>

        </form>

        <div id="mensajeExito" class="encuesta-card mensaje-exito">
            <h2>¡Gracias por su respuesta!</h2>
            <p>La encuesta fue registrada correctamente y nos ayudará a mejorar.</p>
        </div>

    </div>

    <script>
        // Si venimos de un envío exitoso, mostrar el mensaje de éxito
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('exito') === '1') {
            document.getElementById('formEncuesta').style.display = 'none';
            document.getElementById('mensajeExito').style.display = 'block';
        }
    </script>
</body>
</html>

