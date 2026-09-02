<?php

class Conexion
{
    public static function conectar(): PDO
    {
        try {

            $conexion = new PDO(
                "mysql:host=localhost;dbname=sigsm;charset=utf8mb4",
                "root",
                ""
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