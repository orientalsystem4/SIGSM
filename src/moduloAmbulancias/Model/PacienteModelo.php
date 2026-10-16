<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class PacienteModelo
{
    public static function buscarPorCedula(string $cedula): ?array
    {
        $conexion = Conexion::conectar();

        $consulta = $conexion->prepare(
            'SELECT ci, nombre, apellido
             FROM paciente
             WHERE ci = :ci
             LIMIT 1'
        );

        $consulta->execute([
            ':ci' => $cedula
        ]);

        $paciente = $consulta->fetch(PDO::FETCH_ASSOC);

        return $paciente ?: null;
    }
}