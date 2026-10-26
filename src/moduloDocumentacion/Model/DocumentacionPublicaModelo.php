<?php
require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class DocumentacionPublicaModelo
{
    public static function categorias(): array
    {
        return Conexion::conectar()->query("SELECT id_categoria, nombre FROM categoria
            WHERE tipo_categoria = 'documental' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function categoria(int $id): ?array
    {
        $q = Conexion::conectar()->prepare("SELECT id_categoria, nombre FROM categoria
            WHERE id_categoria = :id AND tipo_categoria = 'documental'");
        $q->execute([':id' => $id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function documentos(int $categoria): array
    {
        $q = Conexion::conectar()->prepare("SELECT d.id_documento, d.archivo_url, d.fecha_carga
            FROM documento d JOIN categoria c ON c.id_categoria = d.id_categoria
            WHERE d.id_categoria = :categoria AND d.activo = 1 AND c.tipo_categoria = 'documental'
            ORDER BY d.fecha_carga DESC, d.id_documento DESC");
        $q->execute([':categoria' => $categoria]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function documento(int $id): ?array
    {
        $q = Conexion::conectar()->prepare("SELECT d.id_documento, d.archivo_url
            FROM documento d JOIN categoria c ON c.id_categoria = d.id_categoria
            WHERE d.id_documento = :id AND d.activo = 1 AND c.tipo_categoria = 'documental'");
        $q->execute([':id' => $id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function rutaPDF(string $url): ?string
    {
        // Acepta solo archivos PDF dentro de la carpeta de documentos.
        if (strpos($url, 'documentos/') !== 0 || strpos($url, '\\') !== false) return null;
        $nombre = substr($url, strlen('documentos/'));
        if ($nombre === '' || basename($nombre) !== $nombre || strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) !== 'pdf') return null;
        $raiz = realpath(__DIR__ . '/../Vista/documentos');
        $archivo = realpath(__DIR__ . '/../Vista/' . $url);
        if (!$raiz || !$archivo || strpos($archivo, $raiz . DIRECTORY_SEPARATOR) !== 0 || !is_file($archivo) || !is_readable($archivo)) return null;
        return $archivo;
    }

    public static function titulo(string $url): string
    {
        $nombre = pathinfo(basename($url), PATHINFO_FILENAME);
        $nombre = preg_replace('/^\d{10,}_/', '', $nombre);
        return str_replace('_', ' ', $nombre);
    }
}
