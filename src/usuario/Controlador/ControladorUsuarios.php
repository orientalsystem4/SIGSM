<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2]);

require_once __DIR__ . '/../Model/UsuarioModelo.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
// Limitar la administracion de usuarios por rol
$esAdminDTI = (int)($_SESSION['id_rol'] ?? 0) === 1;

if (!$esAdminDTI) {
    $permitido = true;


    if (in_array($accion, ['editar', 'cambiar_estado'], true)) {
        $idObjetivo = (int)(
            $_POST['id_usuario'] ?? $_GET['id_usuario'] ?? 0
        );

        $rolesObjetivo = UsuarioModelo::obtenerRolesUsuario($idObjetivo);

        $permitido = count($rolesObjetivo) === 1
            && (int)$rolesObjetivo[0]['id_rol'] === 6;

       
    }

    if (!$permitido) {
        $_SESSION['error'] =
            'Solo puede administrar cuentas con el rol exclusivo de Enfermero de Traslado.';

        header('Location: ../Vista/listado.php');
        exit;
    }
}

// ============================================================
// CREAR
// ============================================================
if ($accion === 'crear') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../Vista/listado.php');
        exit;
    }

    $nombreUsuario = $_POST['nombre_usuario'] ?? '';
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
        $errores[] = 'El nombre de usuario es obligatorio.';
    } elseif (strlen($nombreUsuario) < 3) {
        $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $nombreUsuario)) {
        $errores[] = 'El nombre de usuario solo puede contener letras sin tildes, números, puntos, guiones y guiones bajos.';
    } elseif (UsuarioModelo::existeNombreUsuario($nombreUsuario)) {
        $errores[] = 'El nombre de usuario ya está registrado.';
    }

    if (!is_string($password) || !is_string($confirmarPassword)) {
        $errores[] = 'Las contraseñas ingresadas no son válidas.';
    } else {
        if ($password === '') {
            $errores[] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 4) {
            $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
        }

        if ($password !== $confirmarPassword) {
            $errores[] = 'Las contraseñas no coinciden.';
        }
    }

    // Validar la lista de roles.
    if (!is_array($rolesRecibidos)) {
        $errores[] = 'La selección de roles no es válida.';
    } else {
        foreach ($rolesRecibidos as $valor) {
            if (!is_string($valor)) {
                $errores[] = 'La selección de roles no es válida.';
                break;
            }

            $idRol = filter_var($valor, FILTER_VALIDATE_INT);

            if ($idRol === false || $idRol <= 0) {
                $errores[] = 'La selección contiene un rol inválido.';
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
        $errores[] = 'Debe seleccionar al menos un rol.';
    } elseif (!empty(array_diff($idsRoles, $idsDisponibles))) {
        $errores[] = 'Uno de los roles seleccionados no existe.';
    }

    // Enfermeria solo puede crear cuentas de enfermeros.
    if (!$esAdminDTI && $idsRoles !== [6]) {
        $errores[] = 'Solo puede asignar el rol Enfermero de Traslado.';
    }

    // Conservar los datos del formulario, sin contraseñas.
    $_SESSION['datos_previos_usuario'] = [
        'nombre_usuario' => $nombreUsuario,
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
        $idsRoles
    );

    if (!$ok) {
        $_SESSION['errores_usuario'] = [
            'Ocurrió un error al registrar el usuario.'
        ];

        header('Location: ../Vista/crear.php');
        exit;
    }

    unset(
        $_SESSION['datos_previos_usuario'],
        $_SESSION['errores_usuario']
    );

    $_SESSION['mensaje'] = "Usuario '{$nombreUsuario}' creado con sus roles asignados.";

    header('Location: ../Vista/listado.php');
    exit;
}

// ============================================================
// EDITAR
// ============================================================
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
        $errores[] = 'La selección de roles no es válida.';
    } else {
        foreach ($rolesRecibidos as $valor) {
            if (!is_string($valor)) {
                $errores[] = 'La selección de roles no es válida.';
                break;
            }

            $idRol = filter_var($valor, FILTER_VALIDATE_INT);

            if ($idRol === false || $idRol <= 0) {
                $errores[] = 'La selección contiene un rol inválido.';
                break;
            }

            $idsRoles[] = $idRol;
        }
    }

    $idsRoles = array_values(array_unique($idsRoles));
    sort($idsRoles);

    // Comprobar que el usuario exista.
    if (!$idUsuario || $idUsuario <= 0) {
        $_SESSION['error'] = 'Identificador de usuario inválido.';
        header('Location: ../Vista/listado.php');
        exit;
    }

    $usuarioActualizado = UsuarioModelo::obtenerPorId($idUsuario);

    if (!$usuarioActualizado) {
        $_SESSION['error'] = 'El usuario solicitado no existe.';
        header('Location: ../Vista/listado.php');
        exit;
    }

    if ($nombreUsuario === '') {
        $errores[] = 'El nombre de usuario es obligatorio.';
    } elseif (
        UsuarioModelo::existeNombreUsuario($nombreUsuario, $idUsuario)
    ) {
        $errores[] = 'El nombre de usuario ya está en uso.';
    }

    if (!is_string($password)) {
        $errores[] = 'La contraseña ingresada no es válida.';
    } elseif ($password !== '' && strlen($password) < 4) {
        $errores[] = 'La nueva contraseña debe tener al menos 4 caracteres.';
    }

    if (!in_array($activo, [0, 1], true)) {
        $errores[] = 'El estado de la cuenta no es válido.';
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
        $errores[] = 'Debe seleccionar al menos un rol.';
    } elseif (!empty(array_diff($idsRoles, $idsDisponibles))) {
        $errores[] = 'Uno de los roles seleccionados no existe.';
    }

    // Enfermeria solo puede asignar el rol de enfermero.
    if (!$esAdminDTI && $idsRoles !== [6]) {
        $errores[] = 'Solo puede asignar el rol Enfermero de Traslado.';
    }

    // Proteger la cuenta y el rol usados en esta sesion.
    if ($idUsuario === (int) ($_SESSION['id_usuario'] ?? 0)) {
        if ($activo === 0) {
            $errores[] = 'No puede desactivar su propio usuario mientras tiene la sesión activa.';
        }

        $rolActivo = (int) ($_SESSION['id_rol'] ?? 0);

        if (!in_array($rolActivo, $idsRoles, true)) {
            $errores[] = 'No puede quitarse el rol con el que está trabajando.';
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
        $activo
    );

    if ($ok) {
        if ($idUsuario === (int) ($_SESSION['id_usuario'] ?? 0)) {
            $_SESSION['nombre_usuario'] = $nombreUsuario;
        }

        $_SESSION['mensaje'] = "Usuario '{$nombreUsuario}' actualizado correctamente.";
        header('Location: ../Vista/listado.php');
        exit;
    }

    $_SESSION['errores_usuario'] = [
        'Ocurrió un error al actualizar el usuario.'
    ];

    header('Location: ../Vista/editar.php?id=' . $idUsuario);
    exit;
}

// ============================================================
// CAMBIAR ESTADO (Activar / Desactivar)
// ============================================================
if ($accion === 'cambiar_estado') {
    $idUsuario = (int)($_POST['id_usuario'] ?? $_GET['id_usuario'] ?? 0);
    $activo = (int)($_POST['activo'] ?? $_GET['activo'] ?? 0);

    if ($idUsuario <= 0) {
        $_SESSION['error'] = 'Identificador de usuario inválido.';
        header('Location: ../Vista/listado.php');
        exit;
    }

    // Regla de seguridad: el usuario administrador actual no puede desactivarse a sí mismo
    if ($idUsuario === (int)$_SESSION['id_usuario'] && $activo === 0) {
        $_SESSION['error'] = 'No puede desactivar su propio usuario mientras tiene la sesión activa.';
        header('Location: ../Vista/listado.php');
        exit;
    }

    $ok = UsuarioModelo::cambiarEstado($idUsuario, $activo);

    if ($ok) {
        $estadoTexto = ($activo === 1) ? 'activado' : 'desactivado';
        $_SESSION['mensaje'] = "El usuario fue {$estadoTexto} exitosamente.";
    } else {
        $_SESSION['error'] = 'Error al cambiar el estado del usuario.';
    }

    header('Location: ../Vista/listado.php');
    exit;
}

header('Location: ../Vista/listado.php');
exit;
