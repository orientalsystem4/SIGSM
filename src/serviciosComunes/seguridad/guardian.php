<?php
/**
 * Guardián de Seguridad y Autenticación Centralizado - S.I.G.S.M.
 * Actúa como "portero" para verificar sesiones activas y proteger controladores y vistas.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            1 => 'Superadministrador (DTI)',
            2 => 'Gestor de Personal Asistencial',
            3 => 'Coordinador de Traslados (Unidad de Enlace)',
            4 => 'Técnico de Registros Médicos (Archivo Clínico)',
            5 => 'Chofer de Ambulancia',
            6 => 'Personal Asistencial (Médico / Enfermero)'
        ];

        if ($idRol && isset($mapaRoles[$idRol])) {
            $nombreRolUI = $mapaRoles[$idRol];
        }

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
            $_SESSION['error'] = 'No tiene permisos suficientes para acceder a este módulo.';
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
    $_SESSION['error'] = 'Debe iniciar sesión para acceder al sistema.';
    header('Location: ' . resolverUrlLogin());
    exit;
}

