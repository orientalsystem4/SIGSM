<?php
require_once __DIR__ . '/../../../servicios/permisos/verificarSesion.php';
requiereRol('administrador');
require_once __DIR__ . '/../modelo/PacienteModelo.php';

$ciPaciente = $_GET['ci'] ?? '';
$paciente = PacienteModelo::obtenerPorCi($ciPaciente);

if ($paciente === null) {
    $_SESSION['mensaje'] = 'El paciente solicitado no existe.';
    header('Location: listado.php');
    exit;
}

$tituloPagina = 'Detalle de paciente';

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Detalle de paciente</h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <div class="tarjeta-detalle">
        <dl class="lista-detalle">
            <dt>Cédula de Identidad</dt>
            <dd><?= htmlspecialchars($paciente['ci']) ?></dd>

            <dt>Nombre completo</dt>
            <dd><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']) ?></dd>

            <dt>Fecha de Nacimiento</dt>
            <dd><?= htmlspecialchars(date('d/m/Y', strtotime($paciente['fecha_nacimiento']))) ?></dd>
        </dl>

        <div class="acciones-formulario">
            <a href="editar.php?ci=<?= urlencode($paciente['ci']) ?>" class="boton boton-primario">Editar</a>
            <a href="listado.php" class="boton boton-secundario">Volver</a>
        </div>
    </div>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>