<?php

class Conexion
{
    // Cargar variables del .env si existe y no están definidas en el entorno.
    // En Docker las variables ya llegan al contenedor por env_file.
    public static function cargarEntorno(): void
    {
        $envPath = __DIR__ . '/../../../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!getenv($name)) {
                    putenv(sprintf('%s=%s', $name, $value));
                }
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function conectar(): PDO
    {
        try {
            self::cargarEntorno();

            $host = getenv('DB_HOST') ?: 'db';
            // Si se ejecuta en CLI desde el host fuera del contenedor y 'db' no resuelve, usar 127.0.0.1
            if ($host === 'db' && php_sapi_name() === 'cli' && gethostbyname('db') === 'db') {
                $host = '127.0.0.1';
            }

            $dbname = getenv('DB_NAME') ?: 'sigsm';
            $user = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
            $port = getenv('DB_PORT') ?: '3306';

            $conexion = new PDO(
                "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
                $user,
                $password
            );

            $conexion->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            // NOW() y CURRENT_TIMESTAMP en hora de Uruguay, igual que PHP.
            // Se fija por conexion: no depende de la zona horaria del servidor de la base.
            $conexion->exec("SET time_zone = '-03:00'");

            return $conexion;

        } catch (PDOException $e) {
            die(
                "Error de conexión a la base de datos: " .
                $e->getMessage()
            );
        }
    }
}
