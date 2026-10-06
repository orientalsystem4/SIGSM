<?php

require_once __DIR__ . '/../../serviciosComunes/seguridad/guardian.php';
requiereRol([1, 2]);

require_once __DIR__ . '/../Model/UsuarioModelo.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// ============================================================
// CREAR
// ============================================================
if ($accion === 'crear') {
    $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';
    $idRol = (int)($_POST['id_rol'] ?? 0);

    $errores = [];

    if (empty($nombreUsuario)) {
        $errores[] = 'El nombre de usuario es obligatorio.';
    } elseif (strlen($nombreUsuario) < 3) {
        $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $nombreUsuario)) {
        $errores[] = 'El nombre de usuario solo puede contener letras, números, puntos y guiones.';
    } elseif (UsuarioModelo::existeNombreUsuario($nombreUsuario)) {
        $errores[] = 'El nombre de usuario ya está registrado en el sistema.';
    }

    if (empty($password)) {
        $errores[] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 4) {
        $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
    }

    if ($password !== $confirmarPassword) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if ($idRol <= 0) {
        $errores[] = 'Debe seleccionar un rol válido para el usuario.';
    }

    if (!empty($errores)) {
        $_SESSION['errores_usuario'] = $errores;
        $_SESSION['datos_previos_usuario'] = [
            'nombre_usuario' => $nombreUsuario,
            'id_rol'         => $idRol
        ];
        header('Location: ../Vista/crear.php');
        exit;
    }

    $ok = UsuarioModelo::crear($nombreUsuario, $password, $idRol);

    if ($ok) {
        $_SESSION['mensaje'] = "Usuario '{$nombreUsuario}' creado con éxito y rol asignado.";
        header('Location: ../Vista/listado.php');
        exit;
    } else {
        $_SESSION['errores_usuario'] = ['Ocurrió un error en el servidor al intentar registrar el usuario.'];
        header('Location: ../Vista/crear.php');
        exit;
    }
}

// ============================================================
// EDITAR
// ============================================================
if ($accion === 'editar') {
    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
    $nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $idRol = (int)($_POST['id_rol'] ?? 0);
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

    $errores = [];

    if ($idUsuario <= 0) {
        $errores[] = 'Identificador de usuario inválido.';
    }

    if (empty($nombreUsuario)) {
        $errores[] = 'El nombre de usuario es obligatorio.';
    } elseif (UsuarioModelo::existeNombreUsuario($nombreUsuario, $idUsuario)) {
        $errores[] = 'El nombre de usuario ya está en uso por otro registro.';
    }

    if (!empty($password) && strlen($password) < 4) {
        $errores[] = 'Si ingresa una nueva contraseña, debe tener al menos 4 caracteres.';
    }

    if ($idRol <= 0) {
        $errores[] = 'Debe asignar un rol válido.';
    }

    if (!empty($errores)) {
        $_SESSION['errores_usuario'] = $errores;
        header("Location: ../Vista/editar.php?id={$idUsuario}");
        exit;
    }

    $ok = UsuarioModelo::editar($idUsuario, $nombreUsuario, !empty($password) ? $password : null, $idRol, $activo);

    if ($ok) {
        $_SESSION['mensaje'] = "Usuario '{$nombreUsuario}' actualizado correctamente.";
        header('Location: ../Vista/listado.php');
        exit;
    } else {
        $_SESSION['errores_usuario'] = ['Ocurrió un error al actualizar los datos del usuario.'];
        header("Location: ../Vista/editar.php?id={$idUsuario}");
        exit;
    }
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

