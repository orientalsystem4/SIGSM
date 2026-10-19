<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2]);

require_once __DIR__ . '/../Model/UsuarioModelo.php';

$idUsuario = (int)($_GET['id'] ?? 0);
$usuario = UsuarioModelo::obtenerPorId($idUsuario);

if (!$usuario) {
    $_SESSION['error'] = 'El usuario solicitado no existe.';
    header('Location: listado.php');
    exit;
}

$esAdminDTI = (int)($_SESSION['id_rol'] ?? 0) === 1;
$rolesUsuario = UsuarioModelo::obtenerRolesUsuario($idUsuario);

if (!$esAdminDTI) {
    $puedeEditar = count($rolesUsuario) === 1
        && (int)$rolesUsuario[0]['id_rol'] === 6;

    if (!$puedeEditar) {
        $_SESSION['error'] =
            'No tiene permisos para editar esta cuenta.';

        header('Location: listado.php');
        exit;
    }
}

$roles = UsuarioModelo::listarRoles();

if (!$esAdminDTI) {
    $roles = array_values(array_filter(
        $roles,
        function ($rol) {
            return (int)$rol['id_rol'] === 6;
        }
    ));
}
$errores = $_SESSION['errores_usuario'] ?? [];
unset($_SESSION['errores_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | Editar Usuario</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }
        .contenedor {
            max-width: 650px;
            margin: auto;
        }
        .cabecera-seccion {
            margin-bottom: 25px;
        }
        .cabecera-seccion h1 {
            margin: 0 0 5px 0;
            font-size: 1.5rem;
            color: #0f172a;
        }
        .enlace-volver {
            color: #0284c7;
            text-decoration: none;
            font-size: 14px;
        }
        .enlace-volver:hover {
            text-decoration: underline;
        }
        .tarjeta-formulario {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .campo {
            margin-bottom: 20px;
        }
        .campo label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }
        .campo input, .campo select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .campo input:focus, .campo select:focus {
            border-color: #0284c7;
        }
        .campo-ayuda {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .acciones-formulario {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        .boton {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .boton-primario {
            background: #0284c7;
            color: white;
        }
        .boton-primario:hover {
            background: #0369a1;
        }
        .boton-secundario {
            background: #e2e8f0;
            color: #334155;
        }
        .boton-secundario:hover {
            background: #cbd5e1;
        }
        .alerta-errores {
            background: #fee2e2;
            border: 1px solid #f87171;
            padding: 12px 16px;
            border-radius: 6px;
            color: #991b1b;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alerta-errores ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
    </style>
</head>
<body>

<div class="contenedor">
    <div class="cabecera-seccion">
        <h1>Editar Usuario #<?= (int)$usuario['id_usuario'] ?></h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alerta-errores">
            <strong>Por favor corrija los siguientes errores:</strong>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="tarjeta-formulario" action="../Controlador/ControladorUsuarios.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_usuario" value="<?= (int)$usuario['id_usuario'] ?>">

        <div class="campo">
            <label for="nombre_usuario">Nombre de Usuario *</label>
            <input type="text" id="nombre_usuario" name="nombre_usuario" 
                   value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required>
        </div>

                <fieldset class="campo">
            <legend>Roles del usuario *</legend>

            <p class="campo-ayuda">
                Seleccione uno o varios roles.
            </p>

            <?php foreach ($roles as $r): ?>
                <label
                    style="display: flex; align-items: center; gap: 8px;"
                >
                    <input
                        type="checkbox"
                        name="ids_roles[]"
                        value="<?= (int) $r['id_rol'] ?>"
                        style="width: auto;"
                        <?= in_array(
                            (int) $r['id_rol'],
                            $usuario['ids_roles'],
                            true
                        ) ? 'checked' : '' ?>
                    >

                    <?= htmlspecialchars(
                        $r['nombre_rol'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="campo">
            <label for="password">Nueva Contraseña (Opcional)</label>
            <input type="password" id="password" name="password" placeholder="Dejar en blanco para no modificar">
            <div class="campo-ayuda">Solo complete este campo si desea cambiar la contraseña actual del usuario.</div>
        </div>

        <div class="campo">
            <label for="activo">Estado de la Cuenta *</label>
            <select id="activo" name="activo" required>
                <option value="1" <?= ((int)$usuario['activo'] === 1) ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ((int)$usuario['activo'] === 0) ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="boton boton-primario">Guardar Cambios</button>
            <a href="listado.php" class="boton boton-secundario">Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>