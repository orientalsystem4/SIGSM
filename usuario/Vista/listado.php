<?php
require_once __DIR__ . '/../../../servicios/permisos/verificarSesion.php';
requiereRol('administrador');
require_once __DIR__ . '/../modelo/UsuarioModelo.php';

$tituloPagina = 'Gestión de usuarios';
$usuarios = UsuarioModelo::listarTodos();

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Gestión de usuarios</h1>
        <a href="crear.php" class="boton boton-primario">+ Nuevo usuario</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="tabla-envoltorio">
        <table class="tabla-datos">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($usuarios) === 0): ?>
                    <tr><td colspan="6" class="celda-vacia">No hay usuarios cargados.</td></tr>
                <?php endif; ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                        <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['roles'] ?? '—') ?></td>
                        <td>
                            <?php if ((int) $u['activo'] === 1): ?>
                                <span class="etiqueta etiqueta-activo">Activo</span>
                            <?php else: ?>
                                <span class="etiqueta etiqueta-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="celda-acciones">
                            <a href="ver.php?id=<?= (int) $u['id_usuario'] ?>" class="enlace-accion">Ver</a>
                            <a href="editar.php?id=<?= (int) $u['id_usuario'] ?>" class="enlace-accion">Editar</a>
                            <?php if ((int) $u['id_usuario'] !== (int) $_SESSION['id_usuario']): ?>
                                <form method="POST" action="../controlador/ControladorUsuarios.php"
                                      class="forma-en-linea"
                                      onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars($u['nombre_usuario']) ?>? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?= (int) $u['id_usuario'] ?>">
                                    <button type="submit" class="enlace-accion enlace-peligro">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>
