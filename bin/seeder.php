<?php
// Prepara encuestas, solicitudes y rutas. No modifica cuentas de usuario.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta por consola.');
}

$pdo = null;
try {
    $rutasConexion = [
        dirname(__DIR__) . '/src/serviciosComunes/conexionBD/conexion.php',
        dirname(__DIR__) . '/serviciosComunes/conexionBD/conexion.php'
    ];
    $encontrada = false;
    foreach ($rutasConexion as $ruta) {
        if (is_file($ruta)) {
            require_once $ruta;
            $encontrada = true;
            break;
        }
    }
    if (!$encontrada) {
        throw new RuntimeException('No se encontro conexion.php. Ejecutar desde el proyecto completo.');
    }
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // La estructura base debe estar importada antes de ejecutar este script.
    foreach (['categoria', 'encuesta', 'pregunta', 'solicitud_traslado', 'traslado', 'historial_estado', 'ubicacion', 'ruta'] as $tabla) {
        $pdo->query("SELECT 1 FROM `$tabla` LIMIT 1");
    }

    // MariaDB confirma los cambios de estructura fuera de la transaccion de datos.
    $pdo->exec("ALTER TABLE solicitud_traslado
        ADD COLUMN IF NOT EXISTS observaciones VARCHAR(500) NULL,
        ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(500) NULL,
        ADD COLUMN IF NOT EXISTS fecha_cancelacion DATETIME NULL,
        ADD COLUMN IF NOT EXISTS id_usuario_cancelacion INT NULL");
    echo "[OK] Columnas de solicitudes verificadas.\n";

    $pdo->beginTransaction();
    $categorias = [
        1 => ['Urología', 'QR-UROLOGIA'],
        2 => ['Cardiología', 'QR-CARDIOLOGIA'],
        3 => ['Traumatología', 'QR-TRAUMATOLOGIA'],
        4 => ['Gastroenterología', 'QR-GASTROENTEROLOGIA'],
        5 => ['Ginecobstetricia', 'QR-GINECOBSTETRICIA'],
        6 => ['Imagenología', 'QR-IMAGENOLOGIA'],
        7 => ['Medicina Nuclear', 'QR-MEDICINA-NUCLEAR']
    ];
    $buscarCategoria = $pdo->prepare('SELECT tipo_categoria FROM categoria WHERE id_categoria = ?');
    $crearCategoria = $pdo->prepare("INSERT INTO categoria (id_categoria, nombre, codigo_qr, tipo_categoria) VALUES (?, ?, ?, 'documental')");
    foreach ($categorias as $id => [$nombre, $qr]) {
        $buscarCategoria->execute([$id]);
        $existente = $buscarCategoria->fetch(PDO::FETCH_ASSOC);
        if (!$existente) {
            $crearCategoria->execute([$id, $nombre, $qr]);
        } elseif ($existente['tipo_categoria'] !== 'documental') {
            throw new RuntimeException("La categoria $id tiene otro tipo. Revisarla antes de continuar.");
        }
    }

    $buscarEncuesta = $pdo->prepare('SELECT id_categoria FROM encuesta WHERE id_encuesta = ?');
    $crearEncuesta = $pdo->prepare("INSERT INTO encuesta (id_encuesta, id_categoria, segmento) VALUES (?, ?, 'Institucional')");
    for ($id = 1; $id <= 7; $id++) {
        $buscarEncuesta->execute([$id]);
        $existente = $buscarEncuesta->fetch(PDO::FETCH_ASSOC);
        if (!$existente) {
            $crearEncuesta->execute([$id, $id]);
        } elseif ((int) $existente['id_categoria'] !== $id) {
            throw new RuntimeException("La encuesta $id pertenece a otra categoria. No se modificaron sus respuestas.");
        }
    }

    $buscarPregunta = $pdo->prepare('SELECT id_encuesta, tipo_pregunta FROM pregunta WHERE id_pregunta = ?');
    $crearPregunta = $pdo->prepare('INSERT INTO pregunta (id_pregunta, id_encuesta, tipo_pregunta) VALUES (?, 1, ?)');
    for ($id = 1; $id <= 9; $id++) {
        $tipo = $id === 9 ? 'abierta' : 'escala';
        $buscarPregunta->execute([$id]);
        $existente = $buscarPregunta->fetch(PDO::FETCH_ASSOC);
        if (!$existente) {
            $crearPregunta->execute([$id, $tipo]);
        } elseif ((int) $existente['id_encuesta'] !== 1 || $existente['tipo_pregunta'] !== $tipo) {
            throw new RuntimeException("La pregunta $id tiene otra configuracion. Revisarla antes de continuar.");
        }
    }

    // Completa las combinaciones entre los tres puntos actuales, sin duplicarlas.
    $ubicaciones = $pdo->query('SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion IN (1, 2, 3)')->fetchAll(PDO::FETCH_COLUMN);
    if (count($ubicaciones) !== 3) {
        throw new RuntimeException('Faltan ubicaciones iniciales (IDs 1, 2 y 3). Revisar la carga del SQL base.');
    }
    $pdo->exec('INSERT INTO ruta (id_origen, id_destino, distancia_km, tiempo_min)
        SELECT o.id_ubicacion, d.id_ubicacion, NULL, NULL
        FROM ubicacion o CROSS JOIN ubicacion d
        WHERE o.id_ubicacion IN (1, 2, 3) AND d.id_ubicacion IN (1, 2, 3)
          AND o.id_ubicacion <> d.id_ubicacion
          AND NOT EXISTS (SELECT 1 FROM ruta r
              WHERE r.id_origen = o.id_ubicacion AND r.id_destino = d.id_ubicacion)');
    $pdo->commit();
    echo "[OK] 7 encuestas y 9 preguntas verificadas.\n";
    echo "[OK] Rutas iniciales verificadas.\n";
    echo "Preparacion completada. Usuarios y contrasenas sin cambios.\n";
    exit(0);
} catch (Throwable $error) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, '[ERROR] ' . $error->getMessage() . "\n");
    fwrite(STDERR, "Los cambios de estructura ya aplicados se conservan. Corregir el error y repetir.\n");
    exit(1);
}
