<?php
require_once __DIR__ . '/../../../servicios/permisos/verificarSesion.php';
requiereRol('administrador');
require_once __DIR__ . '/../modelo/UsuarioModelo.php';

$idUsuario = (int) ($_GET['id'] ?? 0);
$usuario = UsuarioModelo::obtenerPorId($idUsuario);

if ($usuario === null) {
    $_SESSION['mensaje'] = 'El usuario solicitado no existe.';
    header('Location: listado.php');
    exit;
}

$tituloPagina = 'Editar usuario';
$roles = UsuarioModelo::listarRoles();

$errores = $_SESSION['errores_usuario'] ?? [];
unset($_SESSION['errores_usuario']);

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Editar usuario</h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <?php if (count($errores) > 0): ?>
        <div class="alerta" role="alert">
            <?php foreach ($errores as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="formUsuario" class="tarjeta-formulario" action="../controlador/ControladorUsuarios.php" method="POST" novalidate>
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombre_usuario">Nombre de usuario</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" maxlength="50"
                       value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>">
                <span class="error" id="errorNombreUsuario"></span>
            </div>
            <div class="campo">
                <label for="contrasenha">Nueva contraseña (opcional)</label>
                <input type="password" id="contrasenha" name="contrasenha" maxlength="64" autocomplete="new-password"
                       placeholder="Dejar en blanco para no cambiarla">
                <span class="error" id="errorContrasenha"></span>
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="50"
                       value="<?= htmlspecialchars($usuario['nombre']) ?>">
                <span class="error" id="errorNombre"></span>
            </div>
            <div class="campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="50"
                       value="<?= htmlspecialchars($usuario['apellido']) ?>">
                <span class="error" id="errorApellido"></span>
            </div>
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" maxlength="120"
                   value="<?= htmlspecialchars($usuario['email']) ?>">
            <span class="error" id="errorEmail"></span>
        </div>

        <fieldset class="campo-roles">
            <legend>Roles</legend>
            <?php foreach ($roles as $r): ?>
                <label class="opcion-rol">
                    <input type="checkbox" name="roles[]" value="<?= (int) $r['id_rol'] ?>"
                        <?= in_array((int) $r['id_rol'], $usuario['roles'], true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($r['nombre_rol']) ?>
                </label>
            <?php endforeach; ?>
            <span class="error" id="errorRoles"></span>
        </fieldset>

        <label class="opcion-check">
            <input type="checkbox" name="activo" <?= (int) $usuario['activo'] === 1 ? 'checked' : '' ?>
                <?= (int) $usuario['id_usuario'] === (int) $_SESSION['id_usuario'] ? 'disabled' : '' ?>>
            Usuario habilitado para iniciar sesión
        </label>
        <?php if ((int) $usuario['id_usuario'] === (int) $_SESSION['id_usuario']): ?>
            <input type="hidden" name="activo" value="on">
            <p class="ayuda-campo">No puede inhabilitar su propia cuenta.</p>
        <?php endif; ?>

        <div class="acciones-formulario">
            <button type="submit" class="boton boton-primario">Guardar cambios</button>
            <a href="listado.php" class="boton boton-secundario">Cancelar</a>
        </div>
    </form>
    <script src="/sigsm/assets/js/validacion-usuario.js"></script>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>
