<?php
require_once __DIR__ . '/../../../servicios/permisos/verificarSesion.php';
requiereRol('administrador');
require_once __DIR__ . '/../modelo/PacienteModelo.php';

$tituloPagina = 'Gestión de Pacientes';
$pacientes = PacienteModelo::listarTodos();

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Gestión de Pacientes</h1>
        <a href="crear.php" class="boton boton-primario">+ Nuevo paciente</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="tabla-envoltorio">
        <table class="tabla-datos">
            <thead>
                <tr>
                    <th>C.I.</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Fecha de Nacimiento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pacientes) === 0): ?>
                    <tr><td colspan="5" class="celda-vacia">No hay pacientes cargados.</td></tr>
                <?php endif; ?>
                <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['ci']) ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['apellido']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha_nacimiento']))) ?></td>
                        <td class="celda-acciones">
                            <a href="ver.php?ci=<?= urlencode($p['ci']) ?>" class="enlace-accion">Ver</a>
                            <a href="editar.php?ci=<?= urlencode($p['ci']) ?>" class="enlace-accion">Editar</a>
                            <form method="POST" action="../controlador/ControladorPacientes.php"
                                  class="forma-en-linea"
                                  onsubmit="return confirm('¿Eliminar al paciente <?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?>? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="ci" value="<?= htmlspecialchars($p['ci']) ?>">
                                <button type="submit" class="enlace-accion enlace-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>