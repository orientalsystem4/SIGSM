<?php

session_start();

require_once __DIR__ . '/../Model/DocumentoModelo.php';

$tituloPagina = 'Gestión de documentos';

$documentos = DocumentoModelo::listarTodos();

$mensaje = $_SESSION['mensaje'] ?? null;
unset($_SESSION['mensaje']);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>S.I.G.S.M. | Gestión de documentos</title>

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
            max-width: 1200px;
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

        .boton {
            display: inline-block;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .boton-primario {
            background: #2563eb;
            color: white;
        }

        .aviso {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #dcfce7;
            border-radius: 6px;
            color: #166534;
        }

        .tabla-envoltorio {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-datos th,
        .tabla-datos td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .tabla-datos th {
            background: #f8fafc;
        }

        .etiqueta {
            padding: 5px 9px;
            border-radius: 15px;
            font-size: 13px;
        }

        .etiqueta-activo {
            background: #dcfce7;
            color: #166534;
        }

        .etiqueta-inactivo {
            background: #fee2e2;
            color: #991b1b;
        }

        .celda-acciones {
            white-space: nowrap;
        }

        .enlace-accion {
            margin-right: 10px;
            color: #2563eb;
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
        }

        .enlace-peligro {
            color: #dc2626;
        }

        .forma-en-linea {
            display: inline;
        }

        .celda-vacia {
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 700px) {

            body {
                padding: 15px;
            }

            .cabecera-seccion {
                flex-direction: column;
                align-items: flex-start;
            }
        }

    </style>

</head>

<body>

<div class="contenedor">

    <div class="cabecera-seccion">

        <h1>Gestión de documentos</h1>

        <a
            href="crear.php"
            class="boton boton-primario"
        >
            + Nuevo documento
        </a>

    </div>


    <?php if ($mensaje): ?>

        <div class="aviso">
            <?= htmlspecialchars($mensaje) ?>
        </div>

    <?php endif; ?>


    <div class="tabla-envoltorio">

        <table class="tabla-datos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Archivo</th>
                    <th>Categoría</th>
                    <th>Fecha de carga</th>
                    <th>Cargado por</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

            <?php if (count($documentos) === 0): ?>

                <tr>
                    <td
                        colspan="7"
                        class="celda-vacia"
                    >
                        No hay documentos cargados.
                    </td>
                </tr>

            <?php endif; ?>


            <?php foreach ($documentos as $d): ?>

                <tr>

                    <td>
                        <?= (int) $d['id_documento'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            basename($d['archivo_url'])
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d['categoria']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d['fecha_carga']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($d['usuario_carga']) ?>
                    </td>

                    <td>

                        <?php if ((int) $d['activo'] === 1): ?>

                            <span class="etiqueta etiqueta-activo">
                                Activo
                            </span>

                        <?php else: ?>

                            <span class="etiqueta etiqueta-inactivo">
                                Inactivo
                            </span>

                        <?php endif; ?>

                    </td>

                    <td class="celda-acciones">

                        <a
                            href="ver.php?id=<?= (int) $d['id_documento'] ?>"
                            class="enlace-accion"
                        >
                            Ver
                        </a>

                        <a
                            href="editar.php?id=<?= (int) $d['id_documento'] ?>"
                            class="enlace-accion"
                        >
                            Editar
                        </a>


                        <?php if ((int) $d['activo'] === 1): ?>

                            <form
                                method="POST"
                                action="../Controlador/ControladorDocumentos.php"
                                class="forma-en-linea"
                                onsubmit="return confirm('¿Desea dar de baja este documento?');"
                            >

                                <input
                                    type="hidden"
                                    name="accion"
                                    value="eliminar"
                                >

                                <input
                                    type="hidden"
                                    name="id_documento"
                                    value="<?= (int) $d['id_documento'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="enlace-accion enlace-peligro"
                                >
                                    Eliminar
                                </button>

                            </form>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>