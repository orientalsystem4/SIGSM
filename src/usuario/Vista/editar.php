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

$tituloPagina = 'Editar paciente';

$errores = $_SESSION['errores_paciente'] ?? [];
unset($_SESSION['errores_paciente']);

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Editar paciente</h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <?php if (count($errores) > 0): ?>
        <div class="alerta" role="alert">
            <?php foreach ($errores as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="formPaciente" class="tarjeta-formulario" action="../controlador/ControladorPacientes.php" method="POST" novalidate>
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="ci_original" value="<?= htmlspecialchars($paciente['ci']) ?>">

        <div class="fila-formulario">
            <div class="campo">
                <label for="ci">Cédula de Identidad</label>
                <input type="text" id="ci" name="ci" maxlength="20"
                       value="<?= htmlspecialchars($paciente['ci']) ?>">
                <span class="error" id="errorCi"></span>
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombre">Nombres</label>
                <input type="text" id="nombre" name="nombre" maxlength="80"
                       value="<?= htmlspecialchars($paciente['nombre']) ?>">
                <span class="error" id="errorNombre"></span>
            </div>
            <div class="campo">
                <label for="apellido">Apellidos</label>
                <input type="text" id="apellido" name="apellido" maxlength="80"
                       value="<?= htmlspecialchars($paciente['apellido']) ?>">
                <span class="error" id="errorApellido"></span>
            </div>
        </div>

        <div class="campo">
            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                   value="<?= htmlspecialchars($paciente['fecha_nacimiento']) ?>">
            <span class="error" id="errorFechaNacimiento"></span>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="boton boton-primario">Guardar cambios</button>
            <a href="listado.php" class="boton boton-secundario">Cancelar</a>
        </div>
    </form>
    <script src="/sigsm/assets/js/validacion-paciente.js"></script>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>