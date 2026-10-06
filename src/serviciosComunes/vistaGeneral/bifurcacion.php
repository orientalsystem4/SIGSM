<?php
require_once __DIR__ . '/../seguridad/guardian.php';

$usuarioActual = obtenerUsuarioActual();
$esAdmin = tieneRol('admin') || tieneRol(1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | Panel de Módulos</title>
    <link rel="stylesheet" href="assets/css/bifurcacion.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <main class="bifurcacion-container">
        <div class="card">
            <h2 class="title">Bienvenido a S.I.G.S.M.</h2>
            <p class="subtitle">
                Usuario: <strong><?= htmlspecialchars($usuarioActual['nombre_usuario'] ?? 'Usuario') ?></strong> 
                <?php if (!empty($usuarioActual['nombre_rol'])): ?>
                    (<em><?= htmlspecialchars($usuarioActual['nombre_rol']) ?></em>)
                <?php endif; ?>
                <br>Seleccione el módulo al que desea ingresar
            </p>
            
            <div class="modules-grid">
                <!-- Portal Unidad de Enlace -->
                <a href="../../moduloAmbulancias/Vista/unidadEnlace.php" class="module-card">
                    <div class="icon-wrapper">
                        <i data-lucide="layout-dashboard"></i>
                    </div>
                    <h3 class="card-title">Portal Unidad de Enlace</h3>
                    <p class="card-subtitle">Coordinación y Archivos</p>
                </a>
                
                <!-- Portal Choferes -->
                <a href="../../moduloAmbulancias/Vista/choferes.php" class="module-card">
                    <div class="icon-wrapper">
                        <i data-lucide="ambulance"></i>
                    </div>
                    <h3 class="card-title">Portal Choferes</h3>
                    <p class="card-subtitle">Planilla y Rutas Activas</p>
                </a>
                
                <!-- Portal Enfermería -->
                <a href="../../moduloDocumentacion/Vista/enfermeria.php" class="module-card">
                    <div class="icon-wrapper">
                        <i data-lucide="clipboard-plus"></i>
                    </div>
                    <h3 class="card-title">Portal Enfermería</h3>
                    <p class="card-subtitle">Solicitudes y Documentación</p>
                </a>

                <!-- Portal Documentación y Encuestas -->
                <a href="../../moduloDocumentacion/Vista/documentacion.php" class="module-card">
                    <div class="icon-wrapper">
                        <i data-lucide="folder-open"></i>
                    </div>
                    <h3 class="card-title">Portal Documentación</h3>
                    <p class="card-subtitle">Archivos y Encuestas</p>
                </a>

                <!-- GESTIÓN DE USUARIOS: Exclusivo para administradores -->
                <?php if ($esAdmin): ?>
                <a href="../../usuario/Vista/listado.php" class="module-card">
                    <div class="icon-wrapper">
                        <i data-lucide="users"></i>
                    </div>
                    <h3 class="card-title">Gestión de Usuarios</h3>
                    <p class="card-subtitle">Administración y Roles</p>
                </a>
                <?php endif; ?>
            </div>

            <div class="logout-section">
                <a href="../autenticacion/logout.php" class="btn-logout-link">
                    <i data-lucide="log-out"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

