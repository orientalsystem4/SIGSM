<?php
require_once __DIR__ . '/../../../servicios/permisos/verificarSesion.php';
requiereRol('administrador');
require_once __DIR__ . '/../modelo/UsuarioModelo.php';

$tituloPagina = 'Nuevo usuario';
$roles = UsuarioModelo::listarRoles();

$errores = $_SESSION['errores_usuario'] ?? [];
$previos = $_SESSION['datos_previos'] ?? ['nombre_usuario' => '', 'nombre' => '', 'apellido' => '', 'email' => '', 'roles' => []];
unset($_SESSION['errores_usuario'], $_SESSION['datos_previos']);

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Nuevo usuario</h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <?php if (count($errores) > 0): ?>
        <div class="alerta" role="alert">
            <?php foreach ($errores as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="formUsuario" class="tarjeta-formulario" action="../controlador/ControladorUsuarios.php" method="POST" novalidate>
        <input type="hidden" name="accion" value="crear">

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombre_usuario">Nombre de usuario</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" maxlength="50"
                       value="<?= htmlspecialchars($previos['nombre_usuario']) ?>">
                <span class="error" id="errorNombreUsuario"></span>
            </div>
            <div class="campo">
                <label for="contrasenha">Contraseña</label>
                <input type="password" id="contrasenha" name="contrasenha" maxlength="64" autocomplete="new-password" data-requerida="true">
                <span class="error" id="errorContrasenha"></span>
            </div>
        </div>

        <div class="fila-formulario">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="50"
                       value="<?= htmlspecialchars($previos['nombre']) ?>">
                <span class="error" id="errorNombre"></span>
            </div>
            <div class="campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="50"
                       value="<?= htmlspecialchars($previos['apellido']) ?>">
                <span class="error" id="errorApellido"></span>
            </div>
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" maxlength="120"
                   value="<?= htmlspecialchars($previos['email']) ?>">
            <span class="error" id="errorEmail"></span>
        </div>

        <fieldset class="campo-roles">
            <legend>Roles</legend>
            <?php foreach ($roles as $r): ?>
                <label class="opcion-rol">
                    <input type="checkbox" name="roles[]" value="<?= (int) $r['id_rol'] ?>"
                        <?= in_array((int) $r['id_rol'], $previos['roles'] ?? [], true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($r['nombre_rol']) ?>
                </label>
            <?php endforeach; ?>
            <span class="error" id="errorRoles"></span>
        </fieldset>

        <label class="opcion-check">
            <input type="checkbox" name="activo" checked>
            Usuario habilitado para iniciar sesión
        </label>

        <div class="acciones-formulario">
            <button type="submit" class="boton boton-primario">Guardar usuario</button>
            <a href="listado.php" class="boton boton-secundario">Cancelar</a>
        </div>
    </form>
    <script src="/sigsm/assets/js/validacion-usuario.js"></script>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>
