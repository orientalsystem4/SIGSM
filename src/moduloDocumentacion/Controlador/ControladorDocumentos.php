<?php
require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 4]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit(t('error.metodo'));
}
$token = $_POST['token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_documentos'])
    || !hash_equals($_SESSION['csrf_documentos'], $token)) {
    http_response_code(403);
    exit(t('doc.error_token'));
}

require_once __DIR__ . '/../Model/DocumentoModelo.php';

$accion = $_POST['accion'] ?? '';
$idUsuarioCarga = (int)($_SESSION['id_usuario'] ?? 0);



// CREAR


if ($accion === 'crear') {

    $errores = [];

    $idCategoria = (int) ($_POST['id_categoria'] ?? 0);

    if ($idCategoria <= 0) {
        $errores[] = t('doc.error_categoria');
    }

    if (
        !isset($_FILES['archivo']) ||
        $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
    ) {
        $errores[] = t('doc.error_sin_archivo');
    }

    if (count($errores) === 0) {

        $archivo = $_FILES['archivo'];

        $maximo = 15 * 1024 * 1024;

        if ($archivo['size'] > $maximo) {
            $errores[] = t('doc.error_tamano');
        }

        $extension = strtolower(
            pathinfo(
                $archivo['name'],
                PATHINFO_EXTENSION
            )
        );

                if ($extension !== 'pdf') {
            $errores[] = t('doc.error_formato');
        } else {
            // Comprueba el tipo de contenido del archivo.
            $info = new finfo(FILEINFO_MIME_TYPE);
            $tipoArchivo = $info->file($archivo['tmp_name']);

            if ($tipoArchivo !== 'application/pdf') {
                $errores[] = t('doc.error_contenido');
            }
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
        time() . random_int(100000000, 999999999) .
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
            t('doc.error_guardar')
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
        t('doc.ok_carga');


    header(
        'Location: ../Vista/documentacion.php'
    );

    exit;
}



// EDITAR


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
            t('login.error_datos_invalidos');

        header(
            'Location: ../Vista/documentacion.php'
        );

        exit;
    }


    $archivoUrl = null;
        $errorArchivo = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;

    if (
        $errorArchivo !== UPLOAD_ERR_OK
        && $errorArchivo !== UPLOAD_ERR_NO_FILE
    ) {
        $_SESSION['errores_documento'] = [
            t('doc.error_subida')
        ];

        header('Location: ../Vista/editar.php?id=' . $idDocumento);
        exit;
    }


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
                t('doc.error_tamano')
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
                t('doc.error_formato')
            ];

            header(
                'Location: ../Vista/editar.php?id=' .
                $idDocumento
            );

            exit;
        }

                $info = new finfo(FILEINFO_MIME_TYPE);
        $tipoArchivo = $info->file($archivo['tmp_name']);

        if ($tipoArchivo !== 'application/pdf') {
            $_SESSION['errores_documento'] = [
                t('doc.error_contenido')
            ];

            header('Location: ../Vista/editar.php?id=' . $idDocumento);
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
            time() . random_int(100000000, 999999999) .
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
                t('doc.error_guardar_nuevo')
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
        t('doc.ok_edicion');


    header(
        'Location: ../Vista/documentacion.php'
    );

    exit;
}



// ELIMINAR - BAJA LÓGICA


if ($accion === 'eliminar') {

    $idDocumento =
        (int) ($_POST['id_documento'] ?? 0);


    if ($idDocumento <= 0) {

        $_SESSION['mensaje'] =
            t('doc.error_invalido');

        header(
            'Location: ../Vista/documentacion.php'
        );

        exit;
    }


    DocumentoModelo::eliminar(
        $idDocumento
    );


    $_SESSION['mensaje'] =
        t('doc.ok_baja');


    header(
        'Location: ../Vista/documentacion.php'
    );

    exit;
}



// SI ENTRAN DIRECTO AL CONTROLADOR


header(
    'Location: ../Vista/documentacion.php'
);

exit;
