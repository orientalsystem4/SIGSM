<?php

session_start();

require_once __DIR__ . '/../Model/DocumentoModelo.php';

$idDocumento = (int) ($_GET['id'] ?? 0);

$documento = DocumentoModelo::obtenerPorId($idDocumento);

if ($documento === null) {

    $_SESSION['mensaje'] =
        'El documento solicitado no existe.';

    header('Location: listado.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>S.I.G.S.M. | Detalle de documento</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }

        .contenedor {
            max-width: 800px;
            margin: auto;
        }

        .cabecera-seccion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .cabecera-seccion h1 {
            margin: 0;
        }

        .enlace-volver {
            color: #2563eb;
            text-decoration: none;
        }

        .tarjeta-detalle {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }

        .lista-detalle {
            margin: 0;
        }

        .lista-detalle dt {
            font-weight: bold;
            margin-top: 18px;
            color: #475569;
        }

        .lista-detalle dt:first-child {
            margin-top: 0;
        }

        .lista-detalle dd {
            margin: 6px 0 0 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .estado-activo {
            color: #166534;
            font-weight: bold;
        }

        .estado-inactivo {
            color: #991b1b;
            font-weight: bold;
        }

        .acciones-formulario {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .boton {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .boton-primario {
            background: #2563eb;
            color: white;
        }

        .boton-secundario {
            background: #64748b;
            color: white;
        }

        @media (max-width: 700px) {

            body {
                padding: 15px;
            }

            .cabecera-seccion {
                flex-direction: column;
                align-items: flex-start;
            }

            .acciones-formulario {
                flex-direction: column;
            }

            .boton {
                text-align: center;
            }
        }

    </style>

</head>

<body>

<div class="contenedor">

    <div class="cabecera-seccion">

        <h1>Detalle de documento</h1>

        <a
            href="listado.php"
            class="enlace-volver"
        >
            ← Volver al listado
        </a>

    </div>


    <div class="tarjeta-detalle">

        <dl class="lista-detalle">

            <dt>ID</dt>

            <dd>
                <?= (int) $documento['id_documento'] ?>
            </dd>


            <dt>Archivo</dt>

            <dd>
                <?= htmlspecialchars(
                    basename($documento['archivo_url'])
                ) ?>
            </dd>


            <dt>Categoría</dt>

            <dd>
                <?= htmlspecialchars(
                    $documento['categoria']
                ) ?>
            </dd>


            <dt>Fecha de carga</dt>

            <dd>
                <?= htmlspecialchars(
                    $documento['fecha_carga']
                ) ?>
            </dd>


            <dt>Cargado por</dt>

            <dd>
                <?= htmlspecialchars(
                    $documento['usuario_carga']
                ) ?>
            </dd>


            <dt>Estado</dt>

            <dd>

                <?php if ((int) $documento['activo'] === 1): ?>

                    <span class="estado-activo">
                        Activo
                    </span>

                <?php else: ?>

                    <span class="estado-inactivo">
                        Inactivo
                    </span>

                <?php endif; ?>

            </dd>

        </dl>


        <div class="acciones-formulario">

            <a
                href="editar.php?id=<?= (int) $documento['id_documento'] ?>"
                class="boton boton-primario"
            >
                Editar
            </a>

            <a
                href="listado.php"
                class="boton boton-secundario"
            >
                Volver
            </a>

        </div>

    </div>

</div>

</body>

</html>