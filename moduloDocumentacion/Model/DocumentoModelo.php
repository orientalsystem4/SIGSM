<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class DocumentoModelo
{
    public static function listarTodos(): array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT
                d.id_documento,
                d.archivo_url,
                d.fecha_carga,
                d.id_categoria,
                d.id_usuario_carga,
                d.activo,
                c.nombre AS categoria,
                u.nombre_usuario AS usuario_carga
            FROM documento d
            INNER JOIN categoria c
                ON d.id_categoria = c.id_categoria
            INNER JOIN usuario u
                ON d.id_usuario_carga = u.id_usuario
            ORDER BY d.fecha_carga DESC
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function obtenerPorId(int $idDocumento): ?array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT
                d.id_documento,
                d.archivo_url,
                d.fecha_carga,
                d.id_categoria,
                d.id_usuario_carga,
                d.activo,
                c.nombre AS categoria,
                u.nombre_usuario AS usuario_carga
            FROM documento d
            INNER JOIN categoria c
                ON d.id_categoria = c.id_categoria
            INNER JOIN usuario u
                ON d.id_usuario_carga = u.id_usuario
            WHERE d.id_documento = :id_documento
            LIMIT 1
        ";

        $consulta = $conexion->prepare($sql);

        $consulta->execute([
            ':id_documento' => $idDocumento
        ]);

        $documento = $consulta->fetch(PDO::FETCH_ASSOC);

        return $documento ?: null;
    }


    public static function listarCategorias(): array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT
                id_categoria,
                nombre
            FROM categoria
            WHERE tipo_categoria = 'documental'
            ORDER BY nombre
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function crear(
        string $archivoUrl,
        int $idCategoria,
        int $idUsuarioCarga
    ): bool {
        $conexion = Conexion::conectar();

        $sql = "
            INSERT INTO documento
            (
                archivo_url,
                id_categoria,
                id_usuario_carga,
                activo
            )
            VALUES
            (
                :archivo_url,
                :id_categoria,
                :id_usuario_carga,
                1
            )
        ";

        $consulta = $conexion->prepare($sql);

        return $consulta->execute([
            ':archivo_url' => $archivoUrl,
            ':id_categoria' => $idCategoria,
            ':id_usuario_carga' => $idUsuarioCarga
        ]);
    }


    public static function editar(
        int $idDocumento,
        int $idCategoria,
        ?string $archivoUrl = null
    ): bool {
        $conexion = Conexion::conectar();

        if ($archivoUrl !== null) {

            $sql = "
                UPDATE documento
                SET
                    archivo_url = :archivo_url,
                    id_categoria = :id_categoria
                WHERE id_documento = :id_documento
            ";

            $consulta = $conexion->prepare($sql);

            return $consulta->execute([
                ':archivo_url' => $archivoUrl,
                ':id_categoria' => $idCategoria,
                ':id_documento' => $idDocumento
            ]);
        }

        $sql = "
            UPDATE documento
            SET id_categoria = :id_categoria
            WHERE id_documento = :id_documento
        ";

        $consulta = $conexion->prepare($sql);

        return $consulta->execute([
            ':id_categoria' => $idCategoria,
            ':id_documento' => $idDocumento
        ]);
    }


    public static function eliminar(int $idDocumento): bool
    {
        $conexion = Conexion::conectar();

        $sql = "
            UPDATE documento
            SET activo = 0
            WHERE id_documento = :id_documento
        ";

        $consulta = $conexion->prepare($sql);

        return $consulta->execute([
            ':id_documento' => $idDocumento
        ]);
    }
}