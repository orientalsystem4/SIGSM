<?php

require_once __DIR__ . '/../../serviciosComunes/conexionBD/conexion.php';

class UsuarioModelo
{
    public static function listarTodos(): array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT 
                u.id_usuario,
                u.nombre_usuario,
                u.activo,
                ur.id_rol,
                r.nombre_rol
            FROM usuario u
            LEFT JOIN usuario_rol ur 
                ON u.id_usuario = ur.id_usuario
            LEFT JOIN rol r 
                ON ur.id_rol = r.id_rol
            ORDER BY u.id_usuario ASC
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPorId(int $idUsuario): ?array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT 
                u.id_usuario,
                u.nombre_usuario,
                u.activo,
                ur.id_rol,
                r.nombre_rol
            FROM usuario u
            LEFT JOIN usuario_rol ur 
                ON u.id_usuario = ur.id_usuario
            LEFT JOIN rol r 
                ON ur.id_rol = r.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([':id_usuario' => $idUsuario]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public static function listarRoles(): array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT 
                id_rol,
                nombre_rol,
                descripcion
            FROM rol
            ORDER BY id_rol ASC
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function existeNombreUsuario(string $nombreUsuario, ?int $excluirId = null): bool
    {
        $conexion = Conexion::conectar();

        $sql = "SELECT id_usuario FROM usuario WHERE nombre_usuario = :nombre";
        $params = [':nombre' => $nombreUsuario];

        if ($excluirId !== null) {
            $sql .= " AND id_usuario != :excluir_id";
            $params[':excluir_id'] = $excluirId;
        }

        $sql .= " LIMIT 1";

        $consulta = $conexion->prepare($sql);
        $consulta->execute($params);

        return (bool) $consulta->fetch();
    }

    public static function crear(string $nombreUsuario, string $password, int $idRol): bool
    {
        $conexion = Conexion::conectar();

        try {
            $conexion->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sqlUsuario = "
                INSERT INTO usuario (nombre_usuario, contrasenha_hash, activo)
                VALUES (:nombre_usuario, :contrasenha_hash, 1)
            ";
            $stmtUsuario = $conexion->prepare($sqlUsuario);
            $stmtUsuario->execute([
                ':nombre_usuario'    => $nombreUsuario,
                ':contrasenha_hash' => $hash
            ]);

            $idUsuario = (int) $conexion->lastInsertId();

            if ($idRol > 0) {
                $sqlRol = "
                    INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion)
                    VALUES (:id_usuario, :id_rol, NOW())
                ";
                $stmtRol = $conexion->prepare($sqlRol);
                $stmtRol->execute([
                    ':id_usuario' => $idUsuario,
                    ':id_rol'     => $idRol
                ]);
            }

            $conexion->commit();
            return true;

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }

    public static function editar(
        int $idUsuario,
        string $nombreUsuario,
        ?string $nuevaPassword,
        int $idRol,
        int $activo
    ): bool {
        $conexion = Conexion::conectar();

        try {
            $conexion->beginTransaction();

            if (!empty($nuevaPassword)) {
                $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
                $sqlUsuario = "
                    UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        contrasenha_hash = :contrasenha_hash,
                        activo = :activo
                    WHERE id_usuario = :id_usuario
                ";
                $paramsUsuario = [
                    ':nombre_usuario'    => $nombreUsuario,
                    ':contrasenha_hash' => $hash,
                    ':activo'            => $activo,
                    ':id_usuario'        => $idUsuario
                ];
            } else {
                $sqlUsuario = "
                    UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        activo = :activo
                    WHERE id_usuario = :id_usuario
                ";
                $paramsUsuario = [
                    ':nombre_usuario' => $nombreUsuario,
                    ':activo'         => $activo,
                    ':id_usuario'     => $idUsuario
                ];
            }

            $stmtUsuario = $conexion->prepare($sqlUsuario);
            $stmtUsuario->execute($paramsUsuario);

            // Actualizar o asignar rol
            $stmtCheck = $conexion->prepare("SELECT id_usuario FROM usuario_rol WHERE id_usuario = :id_usuario");
            $stmtCheck->execute([':id_usuario' => $idUsuario]);

            if ($stmtCheck->fetch()) {
                $sqlRol = "UPDATE usuario_rol SET id_rol = :id_rol WHERE id_usuario = :id_usuario";
            } else {
                $sqlRol = "INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion) VALUES (:id_usuario, :id_rol, NOW())";
            }

            $stmtRol = $conexion->prepare($sqlRol);
            $stmtRol->execute([
                ':id_rol'     => $idRol,
                ':id_usuario' => $idUsuario
            ]);

            $conexion->commit();
            return true;

        } catch (Exception $e) {
            $conexion->rollBack();
            error_log("Error al editar usuario: " . $e->getMessage());
            return false;
        }
    }

    public static function cambiarEstado(int $idUsuario, int $activo): bool
    {
        $conexion = Conexion::conectar();

        $sql = "
            UPDATE usuario
            SET activo = :activo
            WHERE id_usuario = :id_usuario
        ";

        $consulta = $conexion->prepare($sql);
        return $consulta->execute([
            ':activo'     => $activo,
            ':id_usuario' => $idUsuario
        ]);
    }
}

