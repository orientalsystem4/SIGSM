<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexionBD/conexion.php';
require_once __DIR__ . '/roles.php';

// Este dato lo establecera el login tras verificar la contraseña.
$idUsuario = (int) ($_SESSION['id_usuario_pendiente'] ?? 0);

if ($idUsuario <= 0) {
    header('Location: sesion.php');
    exit;
}

$error = '';

try {
    $conexion = Conexion::conectar();

    $consulta = $conexion->prepare(
        'SELECT id_usuario, nombre_usuario, activo
         FROM usuario
         WHERE id_usuario = :id_usuario'
    );

    $consulta->execute([
        ':id_usuario' => $idUsuario
    ]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || (int) $usuario['activo'] !== 1) {
        unset(
            $_SESSION['id_usuario_pendiente'],
            $_SESSION['token_seleccion_rol']
        );

        $_SESSION['error'] = 'La cuenta no se encuentra disponible.';
        header('Location: sesion.php');
        exit;
    }

    $roles = consultarRolesUsuario($conexion, $idUsuario);

    if (empty($roles)) {
        unset(
            $_SESSION['id_usuario_pendiente'],
            $_SESSION['token_seleccion_rol']
        );

        $_SESSION['error'] = 'Su usuario no tiene roles asignados.';
        header('Location: sesion.php');
        exit;
    }

    if (empty($_SESSION['token_seleccion_rol'])) {
        $_SESSION['token_seleccion_rol'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'] ?? '';

        if (
            !is_string($token)
            || !hash_equals($_SESSION['token_seleccion_rol'], $token)
        ) {
            $error = 'La solicitud no es válida. Intente nuevamente.';
        } else {
            $idRol = filter_input(
                INPUT_POST,
                'id_rol',
                FILTER_VALIDATE_INT
            );

            $rolElegido = null;

            foreach ($roles as $rol) {
                if ((int) $rol['id_rol'] === $idRol) {
                    $rolElegido = $rol;
                    break;
                }
            }

            $destino = $rolElegido !== null
                ? destinoPorRol((int) $rolElegido['id_rol'])
                : null;

            if ($rolElegido === null || $destino === null) {
                $error = 'Seleccione un rol habilitado para su cuenta.';
            } else {
                session_regenerate_id(true);

                $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
                $_SESSION['id_rol'] = (int) $rolElegido['id_rol'];
                $_SESSION['nombre_rol'] = $rolElegido['nombre_rol'];

                unset(
                    $_SESSION['id_usuario_pendiente'],
                    $_SESSION['token_seleccion_rol']
                );

                header('Location: ' . $destino);
                exit;
            }
        }
    }
} catch (Exception $e) {
    error_log('Error al seleccionar rol: ' . $e->getMessage());

    $_SESSION['error'] = 'No se pudo completar el ingreso. Intente nuevamente.';

    unset(
        $_SESSION['id_usuario_pendiente'],
        $_SESSION['token_seleccion_rol']
    );

    header('Location: sesion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.I.G.S.M. | Seleccionar rol</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
        }

        .tarjeta {
            max-width: 500px;
            margin: 50px auto;
            padding: 25px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        h1 { font-size: 24px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        select, button {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
        }

        select {
            border: 1px solid #cbd5e1;
            margin-bottom: 20px;
        }

        button {
            border: none;
            background: #0284c7;
            color: white;
            cursor: pointer;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main class="tarjeta">
        <h1>Seleccionar rol</h1>

        <p>
            Hola,
            <strong><?= htmlspecialchars(
                $usuario['nombre_usuario'],
                ENT_QUOTES,
                'UTF-8'
            ) ?></strong>.
            Elegí con qué rol querés ingresar.
        </p>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="seleccionarRol.php">
            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars(
                    $_SESSION['token_seleccion_rol'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <label for="id_rol">Rol de trabajo</label>

            <select id="id_rol" name="id_rol" required>
                <option value="">Seleccione un rol...</option>

                <?php foreach ($roles as $rol): ?>
                    <?php if (destinoPorRol((int) $rol['id_rol']) !== null): ?>
                        <option value="<?= (int) $rol['id_rol'] ?>">
                            <?= htmlspecialchars(
                                $rol['nombre_rol'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>

            <button type="submit">Ingresar</button>
        </form>
    </main>
</body>
</html>