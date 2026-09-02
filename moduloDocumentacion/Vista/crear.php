<?php

session_start();

require_once __DIR__ . '/../Model/DocumentoModelo.php';

$categorias = DocumentoModelo::listarCategorias();

$errores = $_SESSION['errores_documento'] ?? [];
$previos = $_SESSION['datos_previos_documento'] ?? [
    'id_categoria' => ''
];

unset(
    $_SESSION['errores_documento'],
    $_SESSION['datos_previos_documento']
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>S.I.G.S.M. | Nuevo documento</title>

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

        .alerta {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .alerta p {
            margin: 4px 0;
        }

        .tarjeta-formulario {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 20px;
        }

        .campo label {
            font-weight: bold;
        }

        .campo input,
        .campo select {
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 15px;
        }

        .ayuda {
            color: #64748b;
            font-size: 13px;
        }

        .acciones-formulario {
            display: flex;
            gap: 10px;
            margin-top: 10px;
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

        <h1>Nuevo documento</h1>

        <a
            href="listado.php"
            class="enlace-volver"
        >
            ← Volver al listado
        </a>

    </div>


    <?php if (count($errores) > 0): ?>

        <div class="alerta" role="alert">

            <?php foreach ($errores as $error): ?>

                <p>
                    <?= htmlspecialchars($error) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <form
        class="tarjeta-formulario"
        action="../Controlador/ControladorDocumentos.php"
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="accion"
            value="crear"
        >


        <div class="campo">

            <label for="id_categoria">
                Categoría
            </label>

            <select
                id="id_categoria"
                name="id_categoria"
                required
            >

                <option value="">
                    Seleccione una categoría...
                </option>

                <?php foreach ($categorias as $categoria): ?>

                    <option
                        value="<?= (int) $categoria['id_categoria'] ?>"
                        <?php
                        if (
                            (string) $previos['id_categoria'] ===
                            (string) $categoria['id_categoria']
                        ) {
                            echo 'selected';
                        }
                        ?>
                    >
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="campo">

            <label for="archivo">
                Archivo PDF
            </label>

            <input
                type="file"
                id="archivo"
                name="archivo"
                accept=".pdf,application/pdf"
                required
            >

            <span class="ayuda">
                El archivo debe estar en formato PDF y no superar los 15 MB.
            </span>

        </div>


        <div class="acciones-formulario">

            <button
                type="submit"
                class="boton boton-primario"
            >
                Guardar documento
            </button>

            <a
                href="listado.php"
                class="boton boton-secundario"
            >
                Cancelar
            </a>

        </div>

    </form>

</div>

</body>
</html>