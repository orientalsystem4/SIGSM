<?php
require_once __DIR__ . '/../seguridad/guardian.php';

requiereRol([1, 2, 3, 4, 5, 6]);

$usuarioActual = obtenerUsuarioActual();
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
                Usuario:
                <strong>
                    <?= htmlspecialchars(
                        $usuarioActual['nombre_usuario'] ?? 'Usuario'
                    ) ?>
                </strong>

                <?php if (!empty($usuarioActual['nombre_rol'])): ?>
                    (<em>
                        <?= htmlspecialchars($usuarioActual['nombre_rol']) ?>
                    </em>)
                <?php endif; ?>

                <br>Seleccione el módulo al que desea ingresar
            </p>

            <?php
            $error = $_SESSION['error'] ?? '';
            unset($_SESSION['error']);
            ?>

            <?php if ($error !== ''): ?>
                <p role="alert" style="color: #b91c1c;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <div class="modules-grid">

                <?php if (tieneRol([1, 2, 3])): ?>
                    <a
                        href="../../moduloAmbulancias/Vista/unidadEnlace.php"
                        class="module-card"
                    >
                        <div class="icon-wrapper">
                            <i data-lucide="layout-dashboard"></i>
                        </div>
                        <h3 class="card-title">Portal Unidad de Enlace</h3>
                        <p class="card-subtitle">
                            Coordinación y seguimiento de traslados
                        </p>
                    </a>
                <?php endif; ?>

                <?php if (tieneRol([1, 3, 5])): ?>
                    <a
                        href="../../moduloAmbulancias/Vista/choferes.php"
                        class="module-card"
                    >
                        <div class="icon-wrapper">
                            <i data-lucide="ambulance"></i>
                        </div>
                        <h3 class="card-title">Portal Choferes</h3>
                        <p class="card-subtitle">Planilla y rutas activas</p>
                    </a>
                <?php endif; ?>

                <?php if (tieneRol([1, 2, 6])): ?>
                    <a
                        href="../../moduloDocumentacion/Vista/enfermeria.php"
                        class="module-card"
                    >
                        <div class="icon-wrapper">
                            <i data-lucide="clipboard-plus"></i>
                        </div>
                        <h3 class="card-title">Solicitudes de Traslado</h3>
                        <p class="card-subtitle">Portal de Enfermería</p>
                    </a>
                <?php endif; ?>

                <?php if (tieneRol([1, 4])): ?>
                    <a
                        href="../../moduloDocumentacion/Vista/documentacion.php"
                        class="module-card"
                    >
                        <div class="icon-wrapper">
                            <i data-lucide="folder-open"></i>
                        </div>
                        <h3 class="card-title">Portal Documentación</h3>
                        <p class="card-subtitle">Archivos y encuestas</p>
                    </a>
                <?php endif; ?>

                <?php if (tieneRol([1, 2])): ?>
                    <a
                        href="../../usuario/Vista/listado.php"
                        class="module-card"
                    >
                        <div class="icon-wrapper">
                            <i data-lucide="users"></i>
                        </div>

                        <h3 class="card-title">
                            <?= tieneRol(1)
                                ? 'Gestión de Usuarios'
                                : 'Personal de Enfermería' ?>
                        </h3>

                        <p class="card-subtitle">
                            <?= tieneRol(1)
                                ? 'Administración y roles'
                                : 'Gestión de cuentas de Enfermería' ?>
                        </p>
                    </a>
                <?php endif; ?>

            </div>

            <div class="logout-section">
                <a
                    href="../autenticacion/logout.php"
                    class="btn-logout-link"
                >
                    <i data-lucide="log-out"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </main>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>