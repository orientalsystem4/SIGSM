<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2]);

require_once __DIR__ . '/../Model/UsuarioModelo.php';

$tituloPagina = 'Gestión de Usuarios y Roles';
$usuarios = UsuarioModelo::listarTodos();

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | <?= htmlspecialchars($tituloPagina) ?></title>
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
            max-width: 1200px;
            margin: auto;
        }
        .cabecera-seccion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .cabecera-seccion h1 {
            margin: 0;
            font-size: 1.6rem;
            color: #0f172a;
        }
        .grupo-botones {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .boton {
            display: inline-block;
            padding: 9px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
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
        .boton-peligro {
            background: #fee2e2;
            color: #dc2626;
        }
        .boton-peligro:hover {
            background: #fecaca;
        }
        .aviso {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #dcfce7;
            border: 1px solid #86efac;
            border-radius: 6px;
            color: #166534;
        }
        .aviso-error {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #fee2e2;
            border: 1px solid #f87171;
            border-radius: 6px;
            color: #991b1b;
        }
        .tabla-envoltorio {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }
        .tabla-datos th, .tabla-datos td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .tabla-datos th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }
        .tabla-datos tr:hover {
            background: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-activo {
            background: #dcfce7;
            color: #166534;
        }
        .badge-inactivo {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-rol {
            background: #e0f2fe;
            color: #0369a1;
        }
        .celda-acciones {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .enlace-accion {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            color: #0284c7;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
        }
        .enlace-accion:hover {
            background: #e0f2fe;
        }
        .enlace-toggle {
            padding: 5px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        .enlace-desactivar {
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #fecdd3;
        }
        .enlace-desactivar:hover {
            background: #ffe4e6;
        }
        .enlace-activar {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .enlace-activar:hover {
            background: #d1fae5;
        }
        .forma-en-linea {
            display: inline;
            margin: 0;
        }
        .celda-vacia {
            text-align: center;
            color: #64748b;
            padding: 30px;
        }
        @media (max-width: 768px) {
            body { padding: 15px; }
            .cabecera-seccion { flex-direction: column; align-items: flex-start; }
            .grupo-botones { width: 100%; }
        }
    </style>
</head>
<body>

<div class="contenedor">
    <div class="cabecera-seccion">
        <div>
            <h1>Gestión de Usuarios</h1>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 14px;">Administración de empleados, credenciales y asignación de roles</p>
        </div>
        <div class="grupo-botones">
            <a href="crear.php" class="boton boton-primario">+ Nuevo Usuario</a>
            <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="boton boton-secundario">← Volver a Módulos</a>
            <a href="../../serviciosComunes/autenticacion/logout.php" class="boton boton-peligro">Cerrar Sesión</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="aviso-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tabla-envoltorio">
        <table class="tabla-datos">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Nombre de Usuario</th>
                    <th>Rol Asignado</th>
                    <th style="width: 120px;">Estado</th>
                    <th style="width: 260px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="5" class="celda-vacia">No hay usuarios registrados en la base de datos.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= (int)$u['id_usuario'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['nombre_usuario']) ?></strong>
                                <?php if ((int)$u['id_usuario'] === (int)$_SESSION['id_usuario']): ?>
                                    <span style="font-size: 11px; color: #0284c7; font-weight: normal;">(Tú)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($u['nombre_rol'])): ?>
                                    <span class="badge badge-rol"><?= htmlspecialchars($u['nombre_rol']) ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">Sin rol asignado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$u['activo'] === 1): ?>
                                    <span class="badge badge-activo">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="celda-acciones">
                                    <a href="ver.php?id=<?= (int)$u['id_usuario'] ?>" class="enlace-accion">Ver</a>
                                    <a href="editar.php?id=<?= (int)$u['id_usuario'] ?>" class="enlace-accion">Editar</a>
                                    
                                    <?php if ((int)$u['id_usuario'] !== (int)$_SESSION['id_usuario']): ?>
                                        <form method="POST" action="../Controlador/ControladorUsuarios.php" class="forma-en-linea"
                                              onsubmit="return confirm('¿Seguro que desea cambiar el estado del usuario <?= htmlspecialchars($u['nombre_usuario']) ?>?');">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                            <input type="hidden" name="activo" value="<?= (int)$u['activo'] === 1 ? 0 : 1 ?>">
                                            <?php if ((int)$u['activo'] === 1): ?>
                                                <button type="submit" class="enlace-toggle enlace-desactivar">Desactivar</button>
                                            <?php else: ?>
                                                <button type="submit" class="enlace-toggle enlace-activar">Activar</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>