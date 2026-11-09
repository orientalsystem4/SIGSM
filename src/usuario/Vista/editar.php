<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1]);
if (empty($_SESSION['csrf_usuarios'])) {
    $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../Model/UsuarioModelo.php';

$idUsuario = (int) ($_GET['id'] ?? 0);
$usuario = UsuarioModelo::obtenerPorId($idUsuario);

if (!$usuario) {
    $_SESSION['error'] = t('usuarios.no_existe');
    header('Location: listado.php');
    exit;
}

$roles = UsuarioModelo::listarRoles();

$errores = $_SESSION['errores_usuario'] ?? [];
unset($_SESSION['errores_usuario']);

?>
<!DOCTYPE html>
<html lang="<?= idiomaActual() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | <?= te('usuarios.editar') ?></title>
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
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261109-1">
</head>
<body>

<div class="contenedor">
    <div class="cabecera-seccion">
        <h1><?= te('usuarios.editar_numero', ['id' => (int) $usuario['id_usuario']]) ?></h1>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alerta-errores">
            <strong><?= te('usuarios.corrija_errores') ?></strong>
            <ul>
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="tarjeta-formulario" action="../Controlador/ControladorUsuarios.php" method="POST" data-validar-form>
        <input type="hidden" name="token"
       value="<?= htmlspecialchars($_SESSION['csrf_usuarios'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_usuario" value="<?= (int)$usuario['id_usuario'] ?>">

        <div class="campo">
            <label for="cedula"><?= te('usuarios.cedula_identidad') ?></label>
            <input type="text" id="cedula" name="cedula" maxlength="12"
                   value="<?= htmlspecialchars($usuario['cedula'] ?? '') ?>"
                   data-validar="<?= !empty($usuario['cedula']) ? 'requerido ' : '' ?>cedula"
                   data-msg-requerido="<?= te('usuarios.error_quitar_cedula') ?>"
                   data-msg-cedula="<?= te('usuarios.error_cedula') ?>">
        </div>
        <div class="campo">
            <label for="nombre_completo"><?= te('usuarios.nombre_completo') ?></label>
            <input type="text" id="nombre_completo" name="nombre_completo" maxlength="150"
                   value="<?= htmlspecialchars($usuario['nombre_completo'] ?? '') ?>"
                   data-validar="<?= !empty($usuario['nombre_completo']) ? 'requerido ' : '' ?>nombre"
                   data-msg-requerido="<?= te('usuarios.error_quitar_nombre') ?>"
                   data-msg-nombre="<?= te('usuarios.error_nombre_completo') ?>">
        </div>
        <div class="campo">
            <label for="nombre_usuario"><?= te('usuarios.nombre_usuario') ?> *</label>
            <input type="text" id="nombre_usuario" name="nombre_usuario"
                   value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required
                   data-validar="requerido bytesmin:3 bytesmax:50 patron" data-patron="^[a-zA-Z0-9_.-]+$"
                   data-msg-requerido="<?= te('usuarios.error_nombre_obligatorio') ?>"
                   data-msg-bytesmin="<?= te('usuarios.error_nombre_largo') ?>"
                   data-msg-bytesmax="<?= te('usuarios.error_nombre_largo') ?>"
                   data-msg-patron="<?= te('usuarios.error_nombre_caracteres') ?>">
        </div>

                <fieldset class="campo" id="roles" data-validar="grupo"
                  data-msg-grupo="<?= te('usuarios.error_sin_rol') ?>">
            <legend><?= te('usuarios.roles') ?> *</legend>

            <p class="campo-ayuda">
                <?= te('usuarios.seleccione_roles') ?>
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
                        etiquetaBd('rol', $r['id_rol'], $r['nombre_rol']),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="campo">
            <label for="password"><?= te('usuarios.nueva_contrasena') ?></label>
           <input type="password" id="password" name="password"
                  data-validar="bytesmin:12"
                  data-msg-bytesmin="<?= te('usuarios.error_nueva_corta') ?>">
        </div>
        <div class="campo">
            <label for="activo"><?= te('usuarios.estado_cuenta') ?> *</label>
            <select id="activo" name="activo" required>
                <option value="1" <?= ((int)$usuario['activo'] === 1) ? 'selected' : '' ?>><?= te('comun.activo') ?></option>
                <option value="0" <?= ((int)$usuario['activo'] === 0) ? 'selected' : '' ?>><?= te('comun.inactivo') ?></option>
            </select>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="boton boton-primario"><?= te('comun.guardar_cambios') ?></button>
            <a href="listado.php" class="boton boton-secundario"><?= te('doc.volver_listado') ?></a>
        </div>
    </form>
</div>

<script src="../../serviciosComunes/vistaGeneral/assets/js/validacion.js?v=20261109-6"></script>
</body>
</html>
