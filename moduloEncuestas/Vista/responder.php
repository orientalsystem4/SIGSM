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
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }
        .encuesta-wrapper {
            max-width: 760px;
            margin: 40px auto;
            padding: 20px;
        }
        .encuesta-card {
            background: white;
            border: 1px solid #dbe3ee;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .encuesta-header {
            padding: 28px;
            border-bottom: 1px solid #e4e9f0;
            background: #0d8acb;
            color: white;
        }
        .encuesta-header h1 {
            margin: 0 0 10px 0;
            font-size: 26px;
        }
        .encuesta-header p {
            margin: 5px 0;
            color: #e0f2fe;
            line-height: 1.5;
        }
        .anonima-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,0.2);
            font-size: 13px;
            font-weight: bold;
        }
        .seccion-header {
            background: #f8fafc;
            padding: 15px 28px;
            border-bottom: 1px solid #e4e9f0;
            font-weight: bold;
            font-size: 18px;
            color: #334155;
        }
        .formulario {
            padding: 28px;
        }
        .pregunta {
            margin-bottom: 32px;
        }
        .pregunta h3 {
            margin-bottom: 14px;
            font-size: 17px;
        }
        .escala {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .escala label {
            width: 48px;
            height: 48px;
            border: 1px solid #ccd5e1;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }
        .escala input {
            display: none;
        }
        .escala label:has(input:checked) {
            background: #0d8acb;
            color: white;
            border-color: #0d8acb;
        }
        .escala label:hover {
            background: #eef2f6;
        }
        .escala label:has(input:checked):hover {
            background: #0d8acb;
        }
        textarea {
            width: 100%;
            min-height: 100px;
            resize: vertical;
            border: 1px solid #ccd5e1;
            border-radius: 10px;
            padding: 12px;
            font-family: inherit;
            font-size: 15px;
            margin-top: 20px;
        }
        .btn-enviar {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 14px;
            background: #0d8acb;
            color: white;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-enviar:hover {
            opacity: 0.92;
        }
        .mensaje-exito {
            display: none;
            padding: 40px 25px;
            text-align: center;
        }
        .mensaje-exito h2 {
            margin-bottom: 10px;
            color: #0d8acb;
        }
        .mensaje-exito p {
            color: #5c677a;
        }
        @media (max-width: 600px) {
            .encuesta-wrapper { margin: 10px auto; padding: 10px; }
            .encuesta-header, .formulario { padding: 20px; }
        }
    </style>
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

