<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1]);
if (empty($_SESSION['csrf_usuarios'])) {
    $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../Model/UsuarioModelo.php';

$tituloPagina = 'Gestión de Usuarios y Roles';
$usuarios = UsuarioModelo::listarTodos();

$mensaje = $_SESSION['mensaje'] ?? null;
$error = $_SESSION['error'] ?? null;

unset($_SESSION['mensaje'], $_SESSION['error']);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | <?= htmlspecialchars($tituloPagina) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .tabla-datos th,
        .tabla-datos td {
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
            padding: 0;
        }

        @media (max-width: 768px) {
            body {
                padding: 0;
            }

            .cabecera-seccion {
                flex-direction: column;
                align-items: flex-start;
            }

            .grupo-botones {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/layout.css?v=2">
    <link rel="stylesheet" href="../../serviciosComunes/vistaGeneral/assets/css/tema-institucional.css?v=20261021-1">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Ajustes exclusivos de Gestión de Usuarios */
        .contenedor {
            max-width: 1200px;
            margin: 0 auto;
        }

        .cabecera-seccion {
            margin-bottom: 18px;
        }

        .grupo-botones {
            margin-left: auto;
        }

        .sidebar .nav-btn {
            text-decoration: none;
        }

        .tabla-datos th {
            background: #f0f7f5;
            color: #115e59;
        }

        .celda-acciones {
            flex-wrap: wrap;
        }

        .main-content {
            min-width: 0;
        }

        .content-wrapper {
            overflow: auto;
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="btn-close-menu" id="closeMenuBtn" type="button" aria-label="Cerrar menú"><i data-lucide="x"></i></button>
                <img src="../../serviciosComunes/autenticacion/assets/image.png" alt="Logo S.I.G.S.M." class="sidebar-logo">
                <span>S.I.G.S.M.</span>
            </div>
            <nav class="sidebar-nav" aria-label="Gestión de Usuarios">
                <a class="nav-btn active" href="listado.php" aria-current="page"><i data-lucide="users"></i> Gestión de Usuarios</a>
                <a class="nav-btn" href="crear.php"><i data-lucide="user-plus"></i> Nuevo Usuario</a>
            </nav>
            <a href="../../serviciosComunes/vistaGeneral/bifurcacion.php" class="btn-volver-hub"><i data-lucide="grid"></i> Cambiar Módulo</a>
            <div class="sidebar-profile">
                <div class="profile-info">
                    <span class="profile-name"><?= htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="profile-role">Administrador de DTI</span>
                </div>
                <a href="../../serviciosComunes/autenticacion/logout.php" class="btn-logout" title="Cerrar Sesión" aria-label="Cerrar sesión"><i data-lucide="log-out"></i></a>
            </div>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="btn-menu" id="openMenuBtn" type="button" aria-label="Abrir menú"><i data-lucide="menu"></i></button>
                    <h2 class="section-title">Gestión de Usuarios</h2>
                </div>
            </header>
            <section class="content-wrapper">
                <div class="contenedor">
                    <div class="cabecera-seccion">
                        <div class="grupo-botones">
                            <a href="crear.php" class="boton boton-primario">+ Nuevo Usuario</a>
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
                                    <th>Nombre completo</th>
                                    <th>Cédula</th>
                                    <th>Rol Asignado</th>
                                    <th style="width: 120px;">Estado</th>
                                    <th style="width: 260px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="7" class="celda-vacia">No hay usuarios registrados en la base de datos.</td>
                                    </tr>
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
                                            <td><?= htmlspecialchars($u['nombre_completo'] ?? 'Pendiente') ?></td>
                                            <td><?= htmlspecialchars($u['cedula'] ?? 'Pendiente') ?></td>
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
                                                            onsubmit="return confirm('¿Seguro que desea cambiar el estado de este usuario?');">                                                            <input type="hidden" name="token"
                                                                value="<?= htmlspecialchars($_SESSION['csrf_usuarios'], ENT_QUOTES, 'UTF-8') ?>">
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
            </section>
        </main>
    </div>
    <script src="../../serviciosComunes/vistaGeneral/assets/js/layout.js"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>

</html>