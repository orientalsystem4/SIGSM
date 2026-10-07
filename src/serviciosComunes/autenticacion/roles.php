<?php

// Destino de inicio para cada rol.
// Las rutas son relativas a la carpeta autenticacion.
function destinoPorRol(int $idRol): ?string
{
       $destinos = [
        1 => '../vistaGeneral/bifurcacion.php',
        2 => '../vistaGeneral/bifurcacion.php',
        3 => '../vistaGeneral/bifurcacion.php',
        4 => '../../moduloDocumentacion/Vista/documentacion.php',
        5 => '../../moduloAmbulancias/Vista/choferes.php',
        6 => '../../moduloDocumentacion/Vista/enfermeria.php'
    ];
    return $destinos[$idRol] ?? null;
}

// Consulta los roles actualmente asignados al usuario.
function consultarRolesUsuario(PDO $conexion, int $idUsuario): array
{
    $sql = "
        SELECT r.id_rol, r.nombre_rol
        FROM usuario_rol ur
        INNER JOIN rol r ON r.id_rol = ur.id_rol
        WHERE ur.id_usuario = :id_usuario
        ORDER BY r.id_rol
    ";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([
        ':id_usuario' => $idUsuario
    ]);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}