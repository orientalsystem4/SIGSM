<?php

class Conexion
{
    public static function conectar(): PDO
    {
        try {
            // Cargar variables del .env si existe y no están definidas en el entorno
            $envPath = __DIR__ . '/../../../.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if (!getenv($name)) {
                        putenv(sprintf('%s=%s', $name, $value));
                    }
                }
            }

            $host = getenv('DB_HOST') ?: 'db';
            $dbname = getenv('DB_NAME') ?: 'sigsm';
            $user = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root_password';

            $conexion = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $user,
                $password
            );

            $conexion->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            return $conexion;

        } catch (PDOException $e) {
            die(
                "Error de conexión a la base de datos: " .
                $e->getMessage()
            );
        }
    }
}
