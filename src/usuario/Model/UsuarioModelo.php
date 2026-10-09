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
                r.id_rol,
                r.nombre_rol
            FROM usuario u
            LEFT JOIN usuario_rol ur
                ON u.id_usuario = ur.id_usuario
            LEFT JOIN rol r
                ON ur.id_rol = r.id_rol
            ORDER BY u.id_usuario ASC, r.id_rol ASC
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute();

        $usuarios = [];

        // Agrupar las filas por usuario.
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $idUsuario = (int) $fila['id_usuario'];

            if (!isset($usuarios[$idUsuario])) {
                $usuarios[$idUsuario] = [
                    'id_usuario' => $idUsuario,
                    'nombre_usuario' => $fila['nombre_usuario'],
                    'activo' => (int) $fila['activo'],
                    'roles' => [],
                    'id_rol' => null,
                    'nombre_rol' => ''
                ];
            }

            if ($fila['id_rol'] !== null) {
                $usuarios[$idUsuario]['roles'][] = [
                    'id_rol' => (int) $fila['id_rol'],
                    'nombre_rol' => $fila['nombre_rol']
                ];
            }
        }

        foreach ($usuarios as &$usuario) {
            // Mantener los nombres para el listado actual.
            $usuario['nombre_rol'] = implode(
                ' / ',
                array_column($usuario['roles'], 'nombre_rol')
            );

            // Solo hay un id individual si tiene un unico rol.
            if (count($usuario['roles']) === 1) {
                $usuario['id_rol'] = $usuario['roles'][0]['id_rol'];
            }
        }

        unset($usuario);

        return array_values($usuarios);
    }

       public static function obtenerPorId(int $idUsuario): ?array
    {
        $conexion = Conexion::conectar();

        $sql = "
            SELECT id_usuario, nombre_usuario, activo
            FROM usuario
            WHERE id_usuario = :id_usuario
        ";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ':id_usuario' => $idUsuario
        ]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return null;
        }

        $roles = self::obtenerRolesUsuario($idUsuario);

        $usuario['roles'] = $roles;

        $usuario['ids_roles'] = array_map(
            function ($rol) {
                return (int) $rol['id_rol'];
            },
            $roles
        );

        $usuario['nombre_rol'] = implode(
            ' / ',
            array_column($roles, 'nombre_rol')
        );

        // Compatibilidad temporal con el formulario de un solo rol.
        $usuario['id_rol'] = count($roles) === 1
            ? (int) $roles[0]['id_rol']
            : null;

        return $usuario;
    }

    public static function obtenerRolesUsuario(int $idUsuario): array
{
    $conexion = Conexion::conectar();

    $sql = "
        SELECT r.id_rol, r.nombre_rol
        FROM usuario_rol ur
        INNER JOIN rol r ON r.id_rol = ur.id_rol
        WHERE ur.id_usuario = :id_usuario
        ORDER BY r.id_rol
    ";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([':id_usuario' => $idUsuario]);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
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

        public static function crear(
        string $nombreUsuario,
        string $password,
        array $idsRoles
    ): bool {
        $conexion = Conexion::conectar();

        try {
            if (empty($idsRoles)) {
                throw new InvalidArgumentException(
                    'Debe asignar al menos un rol.'
                );
            }

            foreach ($idsRoles as $idRol) {
                if (!is_int($idRol) || $idRol <= 0) {
                    throw new InvalidArgumentException('Rol invalido.');
                }
            }

            $idsRoles = array_values(array_unique($idsRoles));

            $conexion->beginTransaction();

            // Crear la cuenta.
            $consulta = $conexion->prepare(
                'INSERT INTO usuario
                    (nombre_usuario, contrasenha_hash, activo)
                 VALUES (:nombre_usuario, :contrasenha_hash, 1)'
            );

            $consulta->execute([
                ':nombre_usuario' => $nombreUsuario,
                ':contrasenha_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                )
            ]);

            $idUsuario = (int) $conexion->lastInsertId();

            // Asignar cada rol seleccionado.
            $asignarRol = $conexion->prepare(
                'INSERT INTO usuario_rol
                    (id_usuario, id_rol, fecha_asignacion)
                 VALUES (:id_usuario, :id_rol, NOW())'
            );

            foreach ($idsRoles as $idRol) {
                $asignarRol->execute([
                    ':id_usuario' => $idUsuario,
                    ':id_rol' => $idRol
                ]);
            }

            $conexion->commit();
            return true;

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log('Error al crear usuario: ' . $e->getMessage());
            return false;
        }
    }

        public static function editar(
        int $idUsuario,
        string $nombreUsuario,
        ?string $nuevaPassword,
        array $idsRoles,
        int $activo
    ): bool {
        $conexion = Conexion::conectar();

        try {
            if ($idUsuario <= 0 || !in_array($activo, [0, 1], true)) {
                throw new InvalidArgumentException('Datos invalidos.');
            }

            if (empty($idsRoles)) {
                throw new InvalidArgumentException('Debe asignar al menos un rol.');
            }

            foreach ($idsRoles as $idRol) {
                if (!is_int($idRol) || $idRol <= 0) {
                    throw new InvalidArgumentException('Rol invalido.');
                }
            }

            $idsRoles = array_values(array_unique($idsRoles));

            $conexion->beginTransaction();

            // Comprobar y bloquear el usuario durante la actualizacion.
            $consulta = $conexion->prepare(
                'SELECT id_usuario
                 FROM usuario
                 WHERE id_usuario = :id_usuario
                 FOR UPDATE'
            );

            $consulta->execute([
                ':id_usuario' => $idUsuario
            ]);

            if (!$consulta->fetch()) {
                throw new RuntimeException('El usuario no existe.');
            }

            // Actualizar los datos de la cuenta.
            $sql = "
                UPDATE usuario
                SET nombre_usuario = :nombre_usuario,
                    activo = :activo
            ";

            $parametros = [
                ':nombre_usuario' => $nombreUsuario,
                ':activo' => $activo,
                ':id_usuario' => $idUsuario
            ];

            if ($nuevaPassword !== null && $nuevaPassword !== '') {
                $sql .= ', contrasenha_hash = :contrasenha_hash';

                $parametros[':contrasenha_hash'] = password_hash(
                    $nuevaPassword,
                    PASSWORD_DEFAULT
                );
            }

            $sql .= ' WHERE id_usuario = :id_usuario';

            $consulta = $conexion->prepare($sql);
            $consulta->execute($parametros);

            // Consultar las asignaciones actuales.
            $consulta = $conexion->prepare(
                'SELECT id_rol
                 FROM usuario_rol
                 WHERE id_usuario = :id_usuario
                 FOR UPDATE'
            );

            $consulta->execute([
                ':id_usuario' => $idUsuario
            ]);

            $rolesActuales = array_map(
                'intval',
                $consulta->fetchAll(PDO::FETCH_COLUMN)
            );

            $rolesQuitar = array_diff($rolesActuales, $idsRoles);
            $rolesAgregar = array_diff($idsRoles, $rolesActuales);

            // Quitar solamente los roles desmarcados.
            $quitar = $conexion->prepare(
                'DELETE FROM usuario_rol
                 WHERE id_usuario = :id_usuario
                   AND id_rol = :id_rol'
            );

            foreach ($rolesQuitar as $idRol) {
                $quitar->execute([
                    ':id_usuario' => $idUsuario,
                    ':id_rol' => $idRol
                ]);
            }

            // Agregar solamente las nuevas asignaciones.
            $agregar = $conexion->prepare(
                'INSERT INTO usuario_rol
                    (id_usuario, id_rol, fecha_asignacion)
                 VALUES (:id_usuario, :id_rol, NOW())'
            );

            foreach ($rolesAgregar as $idRol) {
                $agregar->execute([
                    ':id_usuario' => $idUsuario,
                    ':id_rol' => $idRol
                ]);
            }

            $conexion->commit();
            return true;

        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log('Error al editar usuario: ' . $e->getMessage());
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

