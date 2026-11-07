<?php
/**
 * Guardián de Seguridad y Autenticación Centralizado - S.I.G.S.M.
 * Actúa como "portero" para verificar sesiones activas y proteger controladores y vistas.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../idioma/idioma.php';

if (!function_exists('resolverUrlLogin')) {
    /**
     * Determina la URL adecuada de sesion.php dinámicamente según el entorno (Docker, XAMPP, etc.)
     */
    function resolverUrlLogin(): string {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        $pos = stripos($script, '/SIGSM/');
        if ($pos !== false) {
            return substr($script, 0, $pos + strlen('/SIGSM/')) . 'serviciosComunes/autenticacion/sesion.php';
        }

        foreach (['/modulo', '/serviciosComunes', '/usuario', '/documentacion'] as $folder) {
            $p = strpos($script, $folder);
            if ($p !== false) {
                return substr($script, 0, $p) . '/serviciosComunes/autenticacion/sesion.php';
            }
        }

        return '/SIGSM/serviciosComunes/autenticacion/sesion.php';
    }
}

if (!function_exists('estaAutenticado')) {
    function estaAutenticado(): bool {
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }
}

if (!function_exists('obtenerUsuarioActual')) {
    function obtenerUsuarioActual(): ?array {
        if (!estaAutenticado()) {
            return null;
        }

        $idRol = $_SESSION['id_rol'] ?? null;
        $nombreRolUI = $_SESSION['nombre_rol'] ?? '';

        $mapaRoles = [
            1 => 'Administrador de DTI',
            2 => 'Administrador de Enfermería',
            3 => 'Coordinador de Traslados',
            4 => 'Funcionario Administrativo de Registros Médicos',
            5 => 'Chofer',
            6 => 'Enfermería'
        ];

        if ($idRol && isset($mapaRoles[$idRol])) {
            $nombreRolUI = $mapaRoles[$idRol];
        }
        $nombreRolUI = etiquetaBd('rol', $idRol, $nombreRolUI);

        return [
            'id_usuario'     => $_SESSION['id_usuario'],
            'nombre_usuario' => $_SESSION['nombre_usuario'] ?? '',
            'id_rol'         => $idRol,
            'nombre_rol'     => $nombreRolUI
        ];
    }
}

if (!function_exists('tieneRol')) {
    function tieneRol($roles): bool {
        if (!estaAutenticado()) {
            return false;
        }
        $idRol = $_SESSION['id_rol'] ?? null;
        $nombreRol = strtolower($_SESSION['nombre_rol'] ?? '');

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $rol) {
            if (is_int($rol) && (int)$idRol === $rol) {
                return true;
            }
            if (is_string($rol)) {
                $rolLower = strtolower($rol);
                if ($rolLower === 'admin' || $rolLower === 'administrador') {
                    if ((int)$idRol === 1) {
                        return true;
                    }
                } elseif (strpos($nombreRol, $rolLower) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('requiereRol')) {
    function requiereRol($roles): void {
        if (!tieneRol($roles)) {
            $_SESSION['error'] = t('guardian.sin_permisos');
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $pos = stripos($script, '/SIGSM/');
            $base = ($pos !== false) ? substr($script, 0, $pos + strlen('/SIGSM/')) : '/SIGSM/';
            header('Location: ' . $base . 'serviciosComunes/vistaGeneral/bifurcacion.php');
            exit;
        }
    }
}

// Verificación inmediata al ser incluido: Intercepta la navegación si no hay sesión válida
if (!estaAutenticado()) {
    $_SESSION['error'] = t('guardian.debe_iniciar_sesion');
    header('Location: ' . resolverUrlLogin());
    exit;
}
// Comprueba que la cuenta y el rol sigan habilitados.
require_once __DIR__ . '/../conexionBD/conexion.php';

try {
    $conexion = Conexion::conectar();

    $consulta = $conexion->prepare(
        'SELECT u.nombre_usuario, r.nombre_rol
         FROM usuario u
         INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         INNER JOIN rol r ON r.id_rol = ur.id_rol
         WHERE u.id_usuario = :usuario
           AND u.activo = 1
           AND ur.id_rol = :rol
         LIMIT 1'
    );

    $consulta->execute([
        ':usuario' => (int) $_SESSION['id_usuario'],
        ':rol' => (int) ($_SESSION['id_rol'] ?? 0)
    ]);

    $cuentaVigente = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$cuentaVigente) {
        $_SESSION = [];
        session_regenerate_id(true);

        $_SESSION['error'] =
            t('guardian.permisos_cambiaron');

        header('Location: ' . resolverUrlLogin());
        exit;
    }

    $_SESSION['nombre_usuario'] = $cuentaVigente['nombre_usuario'];
    $_SESSION['nombre_rol'] = $cuentaVigente['nombre_rol'];

} catch (Throwable $error) {
    error_log('Error al verificar la sesión: ' . $error->getMessage());
    http_response_code(503);
    exit(htmlspecialchars(t('guardian.error_verificacion'), ENT_QUOTES, 'UTF-8'));
}


