<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1]);
// Solo acepta cambios enviados desde los formularios de esta sesión.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit(t('error.metodo_acento'));
}

$token = $_POST['token'] ?? '';
$tokenSesion = $_SESSION['csrf_usuarios'] ?? '';

if (
    !is_string($token)
    || !is_string($tokenSesion)
    || $tokenSesion === ''
    || !hash_equals($tokenSesion, $token)
) {
    http_response_code(403);
    exit(t('usuarios.error_token'));
}

require_once __DIR__ . '/../Model/UsuarioModelo.php';
require_once __DIR__ . '/../Model/ValidacionIdentidad.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';


// CREAR

if ($accion === 'crear') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../Vista/listado.php');
        exit;
    }

    $nombreUsuario = $_POST['nombre_usuario'] ?? '';
    $cedulaTexto = is_string($_POST['cedula'] ?? null) ? trim($_POST['cedula']) : '';
    $nombreCompleto = is_string($_POST['nombre_completo'] ?? null) ? trim($_POST['nombre_completo']) : '';
    $cedula = normalizarCedula($cedulaTexto);
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';
    $rolesRecibidos = $_POST['ids_roles'] ?? [];

    $nombreUsuario = is_string($nombreUsuario)
        ? trim($nombreUsuario)
        : '';

    $errores = [];
    $idsRoles = [];

    // Validar los datos de la cuenta.
    if ($nombreUsuario === '') {
        $errores[] = t('usuarios.error_nombre_obligatorio');
    } elseif (strlen($nombreUsuario) < 3) {
        $errores[] = t('usuarios.error_nombre_corto');
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $nombreUsuario)) {
        $errores[] = t('usuarios.error_nombre_caracteres');
    } elseif (UsuarioModelo::existeNombreUsuario($nombreUsuario)) {
        $errores[] = t('usuarios.error_nombre_registrado');
    }

    if ($cedula === null) {
        $errores[] = t('usuarios.error_cedula_digito');
    } elseif (UsuarioModelo::existeCedula($cedula)) {
        $errores[] = t('usuarios.error_cedula_registrada');
    }
    if (!nombreCompletoValido($nombreCompleto)) {
        $errores[] = t('usuarios.error_nombre_completo');
    }

    if (!is_string($password) || !is_string($confirmarPassword)) {
        $errores[] = t('usuarios.error_contrasenas_invalidas');
    } else {
        if ($password === '') {
            $errores[] = t('usuarios.error_contrasena_obligatoria');
        } elseif (strlen($password) < 12) {
            $errores[] = t('usuarios.error_contrasena_corta');
        }

        if ($password !== $confirmarPassword) {
            $errores[] = t('usuarios.error_no_coinciden');
        }
    }

    // Validar la lista de roles.
    if (!is_array($rolesRecibidos)) {
        $errores[] = t('usuarios.error_roles');
    } else {
        foreach ($rolesRecibidos as $valor) {
            if (!is_string($valor)) {
                $errores[] = t('usuarios.error_roles');
                break;
            }

            $idRol = filter_var($valor, FILTER_VALIDATE_INT);

            if ($idRol === false || $idRol <= 0) {
                $errores[] = t('usuarios.error_rol_invalido');
                break;
            }

            $idsRoles[] = $idRol;
        }
    }

    $idsRoles = array_values(array_unique($idsRoles));
    sort($idsRoles);

    $rolesDisponibles = UsuarioModelo::listarRoles();

    $idsDisponibles = array_map(
        function ($rol) {
            return (int) $rol['id_rol'];
        },
        $rolesDisponibles
    );

    if (empty($idsRoles)) {
        $errores[] = t('usuarios.error_sin_rol');
    } elseif (!empty(array_diff($idsRoles, $idsDisponibles))) {
        $errores[] = t('usuarios.error_rol_inexistente');
    }
    // Conservar los datos del formulario, sin contraseñas.
    $_SESSION['datos_previos_usuario'] = [
        'nombre_usuario' => $nombreUsuario,
        'cedula' => $cedulaTexto,
        'nombre_completo' => $nombreCompleto,
        'ids_roles' => $idsRoles
    ];

    if (!empty($errores)) {
        $_SESSION['errores_usuario'] = $errores;
        header('Location: ../Vista/crear.php');
        exit;
    }

    $ok = UsuarioModelo::crear(
        $nombreUsuario,
        $password,
        $idsRoles,
        $cedula,
        $nombreCompleto
    );

    if (!$ok) {
        $_SESSION['errores_usuario'] = [
            t('usuarios.error_crear')
        ];

        header('Location: ../Vista/crear.php');
        exit;
    }

    unset(
        $_SESSION['datos_previos_usuario'],
        $_SESSION['errores_usuario']
    );

    $_SESSION['mensaje'] = t('usuarios.ok_creado', ['usuario' => $nombreUsuario]);

    header('Location: ../Vista/listado.php');
    exit;
}



// EDITAR

if ($accion === 'editar') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../Vista/listado.php');
        exit;
    }

    $idUsuario = filter_input(
        INPUT_POST,
        'id_usuario',
        FILTER_VALIDATE_INT
    );

    $nombreUsuario = $_POST['nombre_usuario'] ?? '';
    $cedulaTexto = is_string($_POST['cedula'] ?? null) ? trim($_POST['cedula']) : '';
    $nombreCompleto = is_string($_POST['nombre_completo'] ?? null) ? trim($_POST['nombre_completo']) : '';
    $cedula = normalizarCedula($cedulaTexto);
    $password = $_POST['password'] ?? '';

    $nombreUsuario = is_string($nombreUsuario)
        ? trim($nombreUsuario)
        : '';

    $activo = filter_input(
        INPUT_POST,
        'activo',
        FILTER_VALIDATE_INT
    );

    $rolesRecibidos = $_POST['ids_roles'] ?? [];
    $idsRoles = [];
    $errores = [];

    // Validar la lista enviada por el formulario.
    if (!is_array($rolesRecibidos)) {
        $errores[] = t('usuarios.error_roles');
    } else {
        foreach ($rolesRecibidos as $valor) {
            if (!is_string($valor)) {
                $errores[] = t('usuarios.error_roles');
                break;
            }

            $idRol = filter_var($valor, FILTER_VALIDATE_INT);

            if ($idRol === false || $idRol <= 0) {
                $errores[] = t('usuarios.error_rol_invalido');
                break;
            }

            $idsRoles[] = $idRol;
        }
    }

    $idsRoles = array_values(array_unique($idsRoles));
    sort($idsRoles);

    // Comprobar que el usuario exista.
    if (!$idUsuario || $idUsuario <= 0) {
        $_SESSION['error'] = t('usuarios.error_id');
        header('Location: ../Vista/listado.php');
        exit;
    }

    $usuarioActualizado = UsuarioModelo::obtenerPorId($idUsuario);

    if (!$usuarioActualizado) {
        $_SESSION['error'] = t('usuarios.no_existe');
        header('Location: ../Vista/listado.php');
        exit;
    }

        if ($nombreUsuario === '') {
        $errores[] = t('usuarios.error_nombre_obligatorio');
    } elseif (strlen($nombreUsuario) < 3 || strlen($nombreUsuario) > 50) {
        $errores[] = t('usuarios.error_nombre_largo');
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $nombreUsuario)) {
        $errores[] = t('usuarios.error_nombre_caracteres');
    } elseif (UsuarioModelo::existeNombreUsuario($nombreUsuario, $idUsuario)) {
        $errores[] = t('usuarios.error_nombre_en_uso');
    }

    if ($cedulaTexto === '') {
        if (!empty($usuarioActualizado['cedula'])) {
            $errores[] = t('usuarios.error_quitar_cedula');
        }
        $cedula = null; // Cuenta anterior: se completará luego.
    } elseif ($cedula === null) {
        $errores[] = t('usuarios.error_cedula');
    } elseif (UsuarioModelo::existeCedula($cedula, $idUsuario)) {
        $errores[] = t('usuarios.error_cedula_registrada');
    }
    if ($nombreCompleto === '') {
        if (!empty($usuarioActualizado['nombre_completo'])) {
            $errores[] = t('usuarios.error_quitar_nombre');
        }
        $nombreCompleto = null;
    } elseif (!nombreCompletoValido($nombreCompleto)) {
        $errores[] = t('usuarios.error_nombre_completo');
    }

    if (!is_string($password)) {
        $errores[] = t('usuarios.error_contrasena_invalida');
    } elseif ($password !== '' && strlen($password) < 12) {
        $errores[] = t('usuarios.error_nueva_corta');
    }

    if (!in_array($activo, [0, 1], true)) {
        $errores[] = t('usuarios.error_estado');
    }

    // Comprobar los roles contra la base de datos.
    $rolesDisponibles = UsuarioModelo::listarRoles();

    $idsDisponibles = array_map(
        function ($rol) {
            return (int) $rol['id_rol'];
        },
        $rolesDisponibles
    );

    if (empty($idsRoles)) {
        $errores[] = t('usuarios.error_sin_rol');
    } elseif (!empty(array_diff($idsRoles, $idsDisponibles))) {
        $errores[] = t('usuarios.error_rol_inexistente');
    }


    // Proteger la cuenta y el rol usados en esta sesion.
    if ($idUsuario === (int) ($_SESSION['id_usuario'] ?? 0)) {
        if ($activo === 0) {
            $errores[] = t('usuarios.error_autodesactivar');
        }

        $rolActivo = (int) ($_SESSION['id_rol'] ?? 0);

        if (!in_array($rolActivo, $idsRoles, true)) {
            $errores[] = t('usuarios.error_quitar_rol_actual');
        }
    }

    if (!empty($errores)) {
        $_SESSION['errores_usuario'] = $errores;
        header('Location: ../Vista/editar.php?id=' . $idUsuario);
        exit;
    }

    $ok = UsuarioModelo::editar(
        $idUsuario,
        $nombreUsuario,
        $password !== '' ? $password : null,
        $idsRoles,
        $activo,
        $cedula,
        $nombreCompleto
    );

    if ($ok) {
        if ($idUsuario === (int) ($_SESSION['id_usuario'] ?? 0)) {
            $_SESSION['nombre_usuario'] = $nombreUsuario;
        }

        $_SESSION['mensaje'] = t('usuarios.ok_editado', ['usuario' => $nombreUsuario]);
        header('Location: ../Vista/listado.php');
        exit;
    }

    $_SESSION['errores_usuario'] = [
        t('usuarios.error_editar')
    ];

    header('Location: ../Vista/editar.php?id=' . $idUsuario);
    exit;
}


// CAMBIAR ESTADO (Activar / Desactivar)

if ($accion === 'cambiar_estado') {
    $idUsuario = (int)($_POST['id_usuario'] ?? $_GET['id_usuario'] ?? 0);
    $activo = (int)($_POST['activo'] ?? $_GET['activo'] ?? 0);

    if ($idUsuario <= 0) {
        $_SESSION['error'] = t('usuarios.error_id');
        header('Location: ../Vista/listado.php');
        exit;
    }

    // Regla de seguridad: el usuario administrador actual no puede desactivarse a sí mismo
    if ($idUsuario === (int)$_SESSION['id_usuario'] && $activo === 0) {
        $_SESSION['error'] = t('usuarios.error_autodesactivar');
        header('Location: ../Vista/listado.php');
        exit;
    }

    $ok = UsuarioModelo::cambiarEstado($idUsuario, $activo);

    if ($ok) {
        $_SESSION['mensaje'] = $activo === 1
            ? t('usuarios.ok_activado')
            : t('usuarios.ok_desactivado');
    } else {
        $_SESSION['error'] = t('usuarios.error_cambiar_estado');
    }

    header('Location: ../Vista/listado.php');
    exit;
}

header('Location: ../Vista/listado.php');
exit;
