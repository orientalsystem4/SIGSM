<?php
/**
 * Script CLI Seeder Unificado - S.I.G.S.M.
 *
 * Ejecuta de forma limpia e idempotente la inicialización del sistema:
 *   1. Superadministrador y roles de usuario (admin / Admin123!)
 *   2. Las 7 Categorías y Encuestas institucionales
 *   3. Las 9 Preguntas base (8 de escala + 1 abierta para comentarios)
 *
 * Uso:
 *   docker compose exec web php bin/seeder.php
 *   o en local: php bin/seeder.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Este comando solo puede ejecutarse por consola CLI.\n");
}

echo "=========================================================\n";
echo "           S.I.G.S.M. - CLI Seeder General               \n";
echo "=========================================================\n";

// --- [1/2] Inicialización de Superadministrador y Roles ---
echo "\n>>> [1/2] Ejecutando sembrado de Administrador y Roles...\n";
require __DIR__ . '/crear_admin.php';

// --- [2/2] Inicialización de Encuestas y Preguntas Base ---
echo "\n>>> [2/2] Ejecutando sembrado de Encuestas Institucionales y Preguntas Base...\n";

// Asegurar conexión a base de datos
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $conexionPaths = [
        dirname(__DIR__) . '/src/serviciosComunes/conexionBD/conexion.php',
        dirname(__DIR__) . '/serviciosComunes/conexionBD/conexion.php',
        __DIR__ . '/../src/serviciosComunes/conexionBD/conexion.php',
        __DIR__ . '/../serviciosComunes/conexionBD/conexion.php',
    ];
    $conexionEncontrada = false;
    foreach ($conexionPaths as $ruta) {
        if (file_exists($ruta)) {
            require_once $ruta;
            $conexionEncontrada = true;
            break;
        }
    }
    if (!$conexionEncontrada) {
        fwrite(STDERR, "[ERROR] No se pudo encontrar el archivo conexion.php\n");
        exit(1);
    }
    $pdo = Conexion::conectar();
}

try {
    $pdo->beginTransaction();

    // 1. Asegurar existencia de las 7 categorías institucionales
    $categoriasBase = [
        ['id' => 1, 'nombre' => 'Urología',           'qr' => 'QR-UROLOGIA'],
        ['id' => 2, 'nombre' => 'Cardiología',        'qr' => 'QR-CARDIOLOGIA'],
        ['id' => 3, 'nombre' => 'Traumatología',      'qr' => 'QR-TRAUMATOLOGIA'],
        ['id' => 4, 'nombre' => 'Gastroenterología',  'qr' => 'QR-GASTROENTEROLOGIA'],
        ['id' => 5, 'nombre' => 'Ginecobstetricia',   'qr' => 'QR-GINECOBSTETRICIA'],
        ['id' => 6, 'nombre' => 'Imagenología',       'qr' => 'QR-IMAGENOLOGIA'],
        ['id' => 7, 'nombre' => 'Medicina Nuclear',   'qr' => 'QR-MEDICINA-NUCLEAR'],
    ];

    $stmtCat = $pdo->prepare("
        INSERT INTO categoria (id_categoria, nombre, codigo_qr, tipo_categoria)
        VALUES (:id, :nombre, :qr, 'documental')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)
    ");
    foreach ($categoriasBase as $cat) {
        $stmtCat->execute([
            ':id'     => $cat['id'],
            ':nombre' => $cat['nombre'],
            ':qr'     => $cat['qr']
        ]);
    }
    echo "  [+] 7 Categorías institucionales verificadas.\n";

    // 2. Asegurar existencia de las 7 encuestas institucionales vinculadas
    $stmtEnc = $pdo->prepare("
        INSERT INTO encuesta (id_encuesta, id_categoria, segmento)
        VALUES (:id_encuesta, :id_categoria, :segmento)
        ON DUPLICATE KEY UPDATE segmento = VALUES(segmento)
    ");
    for ($i = 1; $i <= 7; $i++) {
        $stmtEnc->execute([
            ':id_encuesta'  => $i,
            ':id_categoria' => $i,
            ':segmento'     => 'Institucional'
        ]);
    }
    echo "  [+] 7 Encuestas institucionales creadas/verificadas (IDs 1 al 7).\n";

    // 3. Asegurar existencia de las 9 preguntas base (8 escala + 1 abierta para comentarios)
    $preguntasBase = [
        ['id' => 1, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Amabilidad y respeto'],
        ['id' => 2, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Explicaciones claras'],
        ['id' => 3, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Tiempo de espera'],
        ['id' => 4, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Orientación y señalización'],
        ['id' => 5, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Limpieza e higiene'],
        ['id' => 6, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Confort y privacidad'],
        ['id' => 7, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Conformidad general'],
        ['id' => 8, 'encuesta' => 1, 'tipo' => 'escala',  'desc' => 'Recomendación institucional'],
        ['id' => 9, 'encuesta' => 1, 'tipo' => 'abierta', 'desc' => 'Comentarios libres'],
    ];

    $stmtPreg = $pdo->prepare("
        INSERT INTO pregunta (id_pregunta, id_encuesta, tipo_pregunta)
        VALUES (:id, :enc, :tipo)
        ON DUPLICATE KEY UPDATE tipo_pregunta = VALUES(tipo_pregunta)
    ");
    foreach ($preguntasBase as $preg) {
        $stmtPreg->execute([
            ':id'   => $preg['id'],
            ':enc'  => $preg['encuesta'],
            ':tipo' => $preg['tipo']
        ]);
    }
    echo "  [+] 9 Preguntas base creadas/verificadas (8 escala + 1 abierta).\n";

    $pdo->commit();

    echo "\n=========================================================\n";
    echo "  [OK] Sembrado general completado exitosamente.\n";
    echo "       - Administrador DTI configurado\n";
    echo "       - 7 Encuestas institucionales listas\n";
    echo "       - 9 Preguntas de satisfacción activas\n";
    echo "=========================================================\n";
    exit(0);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "\n[ERROR] Falló la inicialización de encuestas: " . $e->getMessage() . "\n");
    exit(1);
}
