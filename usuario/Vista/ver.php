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

$tituloPagina = 'Detalle de usuario';
$todosLosRoles = UsuarioModelo::listarRoles();
$nombresRoles = array_map(
    fn($r) => $r['nombre_rol'],
    array_filter($todosLosRoles, fn($r) => in_array((int) $r['id_rol'], $usuario['roles'], true))
);

require __DIR__ . '/../../../servicios/vista_general/encabezado.php';
?>
    <div class="cabecera-seccion">
        <h1>Detalle de usuario</h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <div class="tarjeta-detalle">
        <dl class="lista-detalle">
            <dt>Nombre de usuario</dt>
            <dd><?= htmlspecialchars($usuario['nombre_usuario']) ?></dd>

            <dt>Nombre completo</dt>
            <dd><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></dd>

            <dt>Correo electrónico</dt>
            <dd><?= htmlspecialchars($usuario['email']) ?></dd>

            <dt>Roles</dt>
            <dd>
                <?php foreach ($nombresRoles as $r): ?>
                    <span class="etiqueta etiqueta-rol"><?= htmlspecialchars($r) ?></span>
                <?php endforeach; ?>
                <?php if (count($nombresRoles) === 0): ?>—<?php endif; ?>
            </dd>

            <dt>Estado</dt>
            <dd>
                <?php if ((int) $usuario['activo'] === 1): ?>
                    <span class="etiqueta etiqueta-activo">Activo</span>
                <?php else: ?>
                    <span class="etiqueta etiqueta-inactivo">Inactivo</span>
                <?php endif; ?>
            </dd>
        </dl>

        <div class="acciones-formulario">
            <a href="editar.php?id=<?= (int) $usuario['id_usuario'] ?>" class="boton boton-primario">Editar</a>
            <a href="listado.php" class="boton boton-secundario">Volver</a>
        </div>
    </div>
<?php require __DIR__ . '/../../../servicios/vista_general/pie.php'; ?>
