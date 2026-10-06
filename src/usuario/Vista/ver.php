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

$tituloPagina = 'Detalle de Usuario';
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
        .tarjeta-detalle {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .fila-detalle {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .fila-detalle:last-child {
            border-bottom: none;
        }
        .etiqueta {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
        }
        .valor {
            font-weight: 500;
            color: #0f172a;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-activo { background: #dcfce7; color: #166534; }
        .badge-inactivo { background: #fee2e2; color: #991b1b; }
        .badge-rol { background: #e0f2fe; color: #0369a1; }
        .acciones {
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
        .boton-primario { background: #0284c7; color: white; }
        .boton-primario:hover { background: #0369a1; }
        .boton-secundario { background: #e2e8f0; color: #334155; }
        .boton-secundario:hover { background: #cbd5e1; }
    </style>
</head>
<body>

<div class="contenedor">
    <div class="cabecera-seccion">
        <h1>Detalle de Usuario</h1>
        <a href="listado.php" class="enlace-volver">← Volver al listado</a>
    </div>

    <div class="tarjeta-detalle">
        <div class="fila-detalle">
            <span class="etiqueta">Identificador (ID):</span>
            <span class="valor">#<?= (int)$usuario['id_usuario'] ?></span>
        </div>
        <div class="fila-detalle">
            <span class="etiqueta">Nombre de Usuario:</span>
            <span class="valor"><strong><?= htmlspecialchars($usuario['nombre_usuario']) ?></strong></span>
        </div>
        <div class="fila-detalle">
            <span class="etiqueta">Rol Asignado:</span>
            <span class="valor">
                <?php if (!empty($usuario['nombre_rol'])): ?>
                    <span class="badge badge-rol"><?= htmlspecialchars($usuario['nombre_rol']) ?></span>
                <?php else: ?>
                    <span style="color: #94a3b8; font-style: italic;">Sin rol asignado</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="fila-detalle">
            <span class="etiqueta">Estado de la Cuenta:</span>
            <span class="valor">
                <?php if ((int)$usuario['activo'] === 1): ?>
                    <span class="badge badge-activo">Activo</span>
                <?php else: ?>
                    <span class="badge badge-inactivo">Inactivo</span>
                <?php endif; ?>
            </span>
        </div>

        <div class="acciones">
            <a href="editar.php?id=<?= (int)$usuario['id_usuario'] ?>" class="boton boton-primario">Editar Usuario</a>
            <a href="listado.php" class="boton boton-secundario">Volver al listado</a>
        </div>
    </div>
</div>

</body>
</html>