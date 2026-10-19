<?php
/**
 * Script CLI Idempotente para creación o reseteo del Superadministrador - S.I.G.S.M.
 * Uso:
 *   php bin/crear_admin.php
 *   php bin/crear_admin.php [nombre_usuario] [contraseña]
 */

if (php_sapi_name() !== 'cli') {
    die("Este comando solo puede ejecutarse por consola CLI.\n");
}

// 1. Cargar archivo .env del proyecto
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// 2. Determinar credenciales (argumentos CLI o variables .env)
$adminUser = $argv[1] ?? (getenv('SEED_ADMIN_USER') ?: ($_ENV['SEED_ADMIN_USER'] ?? 'admin'));
$adminPass = $argv[2] ?? (getenv('SEED_ADMIN_PASS') ?: ($_ENV['SEED_ADMIN_PASS'] ?? 'Admin123!'));

if (empty($adminUser) || empty($adminPass)) {
    fwrite(STDERR, "[ERROR] Debe especificar usuario y contraseña o definirlos en .env (SEED_ADMIN_USER, SEED_ADMIN_PASS).\n");
    exit(1);
}

// 3. Conectar a la base de datos
require_once dirname(__DIR__) . '/src/serviciosComunes/conexionBD/conexion.php';

try {
    $pdo = Conexion::conectar();
} catch (Exception $e) {
    fwrite(STDERR, "[ERROR] No se pudo conectar a la base de datos: " . $e->getMessage() . "\n");
    exit(1);
}

echo "=========================================================\n";
echo "      S.I.G.S.M. - CLI Seeder Superadministrador         \n";
echo "=========================================================\n";

// 4. Asegurar existencia del Rol 1 (Administrador de DTI)
$stmtRol = $pdo->prepare("SELECT id_rol, nombre_rol FROM rol WHERE id_rol = 1 LIMIT 1");
$stmtRol->execute();
$rolAdmin = $stmtRol->fetch(PDO::FETCH_ASSOC);

if (!$rolAdmin) {
    echo "[-] Rol ID 1 no encontrado. Creando rol 'Administrador de DTI'...\n";
    $stmtInsRol = $pdo->prepare("INSERT INTO rol (id_rol, nombre_rol, descripcion) VALUES (1, 'Administrador de DTI', 'Superadministrador de Sistemas')");
    $stmtInsRol->execute();
    echo "[+] Rol 'Administrador de DTI' (ID 1) creado exitosamente.\n";
} else {
    echo "[i] Rol de destino: '{$rolAdmin['nombre_rol']}' (ID 1).\n";
}

// 5. Verificar si el usuario ya existe
$stmtUser = $pdo->prepare("SELECT id_usuario, nombre_usuario, activo FROM usuario WHERE nombre_usuario = :user LIMIT 1");
$stmtUser->execute([':user' => $adminUser]);
$usuarioExistente = $stmtUser->fetch(PDO::FETCH_ASSOC);

$hash = password_hash($adminPass, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    if ($usuarioExistente) {
        $idUsuario = (int)$usuarioExistente['id_usuario'];
        echo "[i] El usuario '{$adminUser}' (ID {$idUsuario}) ya existe. Actualizando credenciales y estado activo...\n";

        $stmtUpd = $pdo->prepare("
            UPDATE usuario 
            SET contrasenha_hash = :hash, activo = 1 
            WHERE id_usuario = :id
        ");
        $stmtUpd->execute([
            ':hash' => $hash,
            ':id'   => $idUsuario
        ]);

        // Verificar o actualizar rol en usuario_rol
        $stmtCheckRol = $pdo->prepare("SELECT id_usuario, id_rol FROM usuario_rol WHERE id_usuario = :id LIMIT 1");
        $stmtCheckRol->execute([':id' => $idUsuario]);
        $rolAsignado = $stmtCheckRol->fetch(PDO::FETCH_ASSOC);

        if ($rolAsignado) {
            $stmtUpdRol = $pdo->prepare("UPDATE usuario_rol SET id_rol = 1 WHERE id_usuario = :id");
            $stmtUpdRol->execute([':id' => $idUsuario]);
        } else {
            $stmtInsRol = $pdo->prepare("INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion) VALUES (:id, 1, NOW())");
            $stmtInsRol->execute([':id' => $idUsuario]);
        }

        $pdo->commit();
        echo "[OK] Superadministrador '{$adminUser}' actualizado exitosamente con rol ID 1.\n";

    } else {
        echo "[-] Insertando nuevo usuario '{$adminUser}'...\n";

        $stmtIns = $pdo->prepare("
            INSERT INTO usuario (nombre_usuario, contrasenha_hash, activo) 
            VALUES (:user, :hash, 1)
        ");
        $stmtIns->execute([
            ':user' => $adminUser,
            ':hash' => $hash
        ]);

        $idUsuario = (int)$pdo->lastInsertId();

        $stmtInsRol = $pdo->prepare("
            INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion) 
            VALUES (:id_usuario, 1, NOW())
        ");
        $stmtInsRol->execute([':id_usuario' => $idUsuario]);

        $pdo->commit();
        echo "[OK] Superadministrador '{$adminUser}' (ID {$idUsuario}) creado exitosamente con rol ID 1.\n";
    }

    echo "---------------------------------------------------------\n";
    echo "  Usuario:     {$adminUser}\n";
    echo "  Contraseña:  {$adminPass}\n";
    echo "  Rol:         Administrador (ID 1)\n";
    echo "  Estado:      Activo (1)\n";
    echo "=========================================================\n";
    exit(0);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "[ERROR] Falló la operación en la base de datos: " . $e->getMessage() . "\n");
    exit(1);
}

