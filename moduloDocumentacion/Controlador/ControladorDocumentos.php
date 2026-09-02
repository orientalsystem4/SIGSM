<?php

require_once __DIR__ . '/../Model/DocumentoModelo.php';

session_start();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

/*
    Mientras no esté implementado el login real,
    usamos un usuario de prueba existente en la base.
    Cambialo después si corresponde.
*/
$idUsuarioCarga = 4;


// ============================================================
// CREAR
// ============================================================

if ($accion === 'crear') {

    $errores = [];

    $idCategoria = (int) ($_POST['id_categoria'] ?? 0);

    if ($idCategoria <= 0) {
        $errores[] = 'Debe seleccionar una categoría.';
    }

    if (
        !isset($_FILES['archivo']) ||
        $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
    ) {
        $errores[] = 'Debe seleccionar un archivo PDF.';
    }

    if (count($errores) === 0) {

        $archivo = $_FILES['archivo'];

        $maximo = 15 * 1024 * 1024;

        if ($archivo['size'] > $maximo) {
            $errores[] = 'El archivo no puede superar los 15 MB.';
        }

        $extension = strtolower(
            pathinfo(
                $archivo['name'],
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'pdf') {
            $errores[] = 'El archivo debe estar en formato PDF.';
        }
    }

    if (count($errores) > 0) {

        $_SESSION['errores_documento'] = $errores;
        $_SESSION['datos_previos_documento'] = [
            'id_categoria' => $idCategoria
        ];

        header(
            'Location: ../Vista/crear.php'
        );

        exit;
    }


    $nombreOriginal = pathinfo(
        $archivo['name'],
        PATHINFO_FILENAME
    );

    $nombreSeguro = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        $nombreOriginal
    );

    $nombreFinal =
        time() .
        '_' .
        $nombreSeguro .
        '.pdf';


    $carpeta = __DIR__ .
        '/../Vista/documentos/';


    if (!is_dir($carpeta)) {
        mkdir(
            $carpeta,
            0777,
            true
        );
    }


    $rutaFisica =
        $carpeta .
        $nombreFinal;


    if (
        !move_uploaded_file(
            $archivo['tmp_name'],
            $rutaFisica
        )
    ) {

        $_SESSION['errores_documento'] = [
            'No se pudo guardar el archivo.'
        ];

        header(
            'Location: ../Vista/crear.php'
        );

        exit;
    }


    $archivoUrl =
        'documentos/' .
        $nombreFinal;


    DocumentoModelo::crear(
        $archivoUrl,
        $idCategoria,
        $idUsuarioCarga
    );


    $_SESSION['mensaje'] =
        'Documento cargado correctamente.';


    header(
        'Location: ../Vista/listado.php'
    );

    exit;
}


// ============================================================
// EDITAR
// ============================================================

if ($accion === 'editar') {

    $idDocumento =
        (int) ($_POST['id_documento'] ?? 0);

    $idCategoria =
        (int) ($_POST['id_categoria'] ?? 0);


    if (
        $idDocumento <= 0 ||
        $idCategoria <= 0
    ) {

        $_SESSION['mensaje'] =
            'Los datos ingresados no son válidos.';

        header(
            'Location: ../Vista/listado.php'
        );

        exit;
    }


    $archivoUrl = null;


    if (
        isset($_FILES['archivo']) &&
        $_FILES['archivo']['error'] === UPLOAD_ERR_OK
    ) {

        $archivo = $_FILES['archivo'];

        $maximo =
            15 * 1024 * 1024;


        if (
            $archivo['size'] >
            $maximo
        ) {

            $_SESSION['errores_documento'] = [
                'El archivo no puede superar los 15 MB.'
            ];

            header(
                'Location: ../Vista/editar.php?id=' .
                $idDocumento
            );

            exit;
        }


        $extension = strtolower(
            pathinfo(
                $archivo['name'],
                PATHINFO_EXTENSION
            )
        );


        if (
            $extension !== 'pdf'
        ) {

            $_SESSION['errores_documento'] = [
                'El archivo debe estar en formato PDF.'
            ];

            header(
                'Location: ../Vista/editar.php?id=' .
                $idDocumento
            );

            exit;
        }


        $nombreOriginal = pathinfo(
            $archivo['name'],
            PATHINFO_FILENAME
        );


        $nombreSeguro = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $nombreOriginal
        );


        $nombreFinal =
            time() .
            '_' .
            $nombreSeguro .
            '.pdf';


        $carpeta =
            __DIR__ .
            '/../Vista/documentos/';


        if (
            !is_dir($carpeta)
        ) {

            mkdir(
                $carpeta,
                0777,
                true
            );
        }


        if (
            !move_uploaded_file(
                $archivo['tmp_name'],
                $carpeta . $nombreFinal
            )
        ) {

            $_SESSION['errores_documento'] = [
                'No se pudo guardar el nuevo archivo.'
            ];

            header(
                'Location: ../Vista/editar.php?id=' .
                $idDocumento
            );

            exit;
        }


        $archivoUrl =
            'documentos/' .
            $nombreFinal;
    }


    DocumentoModelo::editar(
        $idDocumento,
        $idCategoria,
        $archivoUrl
    );


    $_SESSION['mensaje'] =
        'Documento actualizado correctamente.';


    header(
        'Location: ../Vista/listado.php'
    );

    exit;
}


// ============================================================
// ELIMINAR - BAJA LÓGICA
// ============================================================

if ($accion === 'eliminar') {

    $idDocumento =
        (int) ($_POST['id_documento'] ?? 0);


    if ($idDocumento <= 0) {

        $_SESSION['mensaje'] =
            'Documento inválido.';

        header(
            'Location: ../Vista/listado.php'
        );

        exit;
    }


    DocumentoModelo::eliminar(
        $idDocumento
    );


    $_SESSION['mensaje'] =
        'Documento dado de baja correctamente.';


    header(
        'Location: ../Vista/listado.php'
    );

    exit;
}


// ============================================================
// SI ENTRAN DIRECTO AL CONTROLADOR
// ============================================================

header(
    'Location: ../Vista/listado.php'
);

exit;