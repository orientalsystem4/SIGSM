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
        ADD COLUMN IF NOT EXISTS id_usuario_cancelacion INT NULL,
        ADD COLUMN IF NOT EXISTS direccion_domicilio VARCHAR(255) NULL");
    echo "[OK] Columnas de solicitudes verificadas.\n";

        // agrega las columnas de usuarios que falten, conservando los datos existentes
    $pdo->exec("ALTER TABLE usuario
        ADD COLUMN IF NOT EXISTS cedula VARCHAR(8) NULL UNIQUE,
        ADD COLUMN IF NOT EXISTS nombre_completo VARCHAR(150) NULL");

    echo "[OK] Columnas de usuarios verificadas.\n";

    // Registro de intentos fallidos de inicio de sesion (limite por usuario).
    $pdo->exec("CREATE TABLE IF NOT EXISTS intento_login (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nombre_usuario VARCHAR(50) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip VARCHAR(45) NULL,
        KEY idx_intento_usuario_fecha (nombre_usuario, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "[OK] Tabla de intentos de inicio de sesion verificada.\n";

    // Preguntas de cada encuesta: las 9 preguntas se comparten entre las 7 encuestas.
    $pdo->exec("CREATE TABLE IF NOT EXISTS encuesta_pregunta (
        id_encuesta INT NOT NULL,
        id_pregunta INT NOT NULL,
        PRIMARY KEY (id_encuesta, id_pregunta),
        KEY fk_encuesta_pregunta_pregunta (id_pregunta),
        CONSTRAINT fk_encuesta_pregunta_encuesta FOREIGN KEY (id_encuesta)
            REFERENCES encuesta (id_encuesta) ON DELETE CASCADE,
        CONSTRAINT fk_encuesta_pregunta_pregunta FOREIGN KEY (id_pregunta)
            REFERENCES pregunta (id_pregunta) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "[OK] Tabla encuesta_pregunta verificada.\n";

    $pdo->beginTransaction();
    // Conserva los IDs y permisos; actualiza los nombres acordados.
    $nombreRol = $pdo->prepare('UPDATE rol SET nombre_rol = ? WHERE id_rol = ?');
    foreach ([2 => 'Administrador de Enfermería', 3 => 'Coordinador de Traslados', 6 => 'Enfermería'] as $idRol => $nombre) {
        $nombreRol->execute([$nombre, $idRol]);
    }
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

    // Una base anterior todavia tiene pregunta.id_encuesta (NOT NULL): se completa
    // mientras la columna exista; la limpieza se hace al final.
    $columnaVieja = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'pregunta' AND column_name = 'id_encuesta'")->fetchColumn() > 0;
    $buscarPregunta = $pdo->prepare('SELECT tipo_pregunta FROM pregunta WHERE id_pregunta = ?');
    $crearPregunta = $pdo->prepare($columnaVieja
        ? 'INSERT INTO pregunta (id_pregunta, id_encuesta, tipo_pregunta) VALUES (?, 1, ?)'
        : 'INSERT INTO pregunta (id_pregunta, tipo_pregunta) VALUES (?, ?)');
    for ($id = 1; $id <= 9; $id++) {
        $tipo = $id === 9 ? 'abierta' : 'escala';
        $buscarPregunta->execute([$id]);
        $existente = $buscarPregunta->fetch(PDO::FETCH_ASSOC);
        if (!$existente) {
            $crearPregunta->execute([$id, $tipo]);
        } elseif ($existente['tipo_pregunta'] !== $tipo) {
            throw new RuntimeException("La pregunta $id tiene otra configuracion. Revisarla antes de continuar.");
        }
    }

    // Las 63 combinaciones (encuestas 1 a 7 x preguntas 1 a 9), sin duplicarlas.
    $vincular = $pdo->prepare('INSERT IGNORE INTO encuesta_pregunta (id_encuesta, id_pregunta) VALUES (?, ?)');
    for ($idEncuesta = 1; $idEncuesta <= 7; $idEncuesta++) {
        for ($idPregunta = 1; $idPregunta <= 9; $idPregunta++) {
            $vincular->execute([$idEncuesta, $idPregunta]);
        }
    }
    $vinculadas = (int) $pdo->query('SELECT COUNT(*) FROM encuesta_pregunta
        WHERE id_encuesta BETWEEN 1 AND 7 AND id_pregunta BETWEEN 1 AND 9')->fetchColumn();
    if ($vinculadas !== 63) {
        throw new RuntimeException("encuesta_pregunta tiene $vinculadas de 63 combinaciones. No se elimino la columna anterior.");
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

    // Ubicacion generica para traslados al domicilio del paciente. La direccion
    // se guarda en la solicitud; no se crea una ubicacion por cada casa.
    $pdo->exec("INSERT INTO ubicacion (nombre, direccion, tipo_ubicacion)
        SELECT 'Domicilio del paciente', NULL, 'Domicilio' FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM ubicacion WHERE tipo_ubicacion = 'Domicilio')");
    // Rutas Hospital 1/2/3 <-> Domicilio, sin distancia ni tiempo inventados.
    $pdo->exec("INSERT INTO ruta (id_origen, id_destino, distancia_km, tiempo_min)
        SELECT a.id_ubicacion, b.id_ubicacion, NULL, NULL
        FROM ubicacion a JOIN ubicacion b
          ON (a.id_ubicacion IN (1, 2, 3) AND b.tipo_ubicacion = 'Domicilio')
          OR (b.id_ubicacion IN (1, 2, 3) AND a.tipo_ubicacion = 'Domicilio')
        WHERE NOT EXISTS (SELECT 1 FROM ruta r
              WHERE r.id_origen = a.id_ubicacion AND r.id_destino = b.id_ubicacion)");
    $pdo->commit();
    echo "[OK] Ubicacion y rutas de domicilio verificadas.\n";

    // Con las 63 combinaciones confirmadas, se quita la relacion anterior (1 encuesta por pregunta,
    // con ON DELETE CASCADE). Cada paso se omite si ya no existe, asi que se puede repetir.
    $pdo->exec('ALTER TABLE pregunta
        DROP FOREIGN KEY IF EXISTS fk_pregunta_encuesta,
        DROP INDEX IF EXISTS fk_pregunta_encuesta,
        DROP COLUMN IF EXISTS id_encuesta');
    echo "[OK] Relacion encuesta-pregunta verificada (63 combinaciones).\n";

    // Vistas usadas por el sistema: mismas definiciones que en sigsm.sql.
    // CREATE OR REPLACE las deja al dia en cada ejecucion. SQL SECURITY INVOKER: se
    // consultan con los permisos de quien las usa, no de quien las creo.
    $vistas = [
        // Sin la direccion de domicilio: la agrega SeguimientoModelo solo a quien puede verla.
        'v_traslado_detalle' => "SELECT
                t.id_traslado, t.id_solicitud, t.id_chofer, t.id_vehiculo, t.id_proveedor,
                s.ci_paciente, s.id_tipo_elemento, v.matricula, pe.nombre AS proveedor,
                ch.nombre_usuario AS chofer, en.nombre_usuario AS enfermeria,
                o.nombre AS origen, o.tipo_ubicacion AS origen_tipo,
                d.nombre AS destino, d.tipo_ubicacion AS destino_tipo,
                te.nombre AS elemento, h.id_estado, e.nombre AS estado
            FROM traslado t
            JOIN solicitud_traslado s ON s.id_solicitud = t.id_solicitud
            JOIN ubicacion o ON o.id_ubicacion = s.id_origen
            JOIN ubicacion d ON d.id_ubicacion = s.id_destino
            JOIN tipo_elemento te ON te.id_tipo_elemento = s.id_tipo_elemento
            LEFT JOIN vehiculo v ON v.id_vehiculo = t.id_vehiculo
            LEFT JOIN proveedor_externo pe ON pe.id_proveedor = t.id_proveedor
            LEFT JOIN usuario ch ON ch.id_usuario = t.id_chofer
            LEFT JOIN usuario en ON en.id_usuario = t.id_enfermero
            LEFT JOIN historial_estado h ON h.id_historial = (
                SELECT hx.id_historial FROM historial_estado hx
                WHERE hx.id_traslado = t.id_traslado
                ORDER BY hx.fecha_hora DESC, hx.id_historial DESC
                LIMIT 1
            )
            LEFT JOIN estado_traslado e ON e.id_estado = h.id_estado",
        'v_respuestas_encuesta' => "SELECT
                rp.id_resp_pregunta, rp.id_resp_encuesta, re.id_encuesta, en.id_categoria,
                rp.id_pregunta, p.tipo_pregunta, rp.texto_libre AS valor, re.fecha_envio
            FROM respuesta_pregunta rp
            JOIN respuesta_encuesta re ON re.id_resp_encuesta = rp.id_resp_encuesta
            JOIN encuesta en ON en.id_encuesta = re.id_encuesta
            JOIN pregunta p ON p.id_pregunta = rp.id_pregunta",
        'v_solicitud_pendiente' => "SELECT
                s.id_solicitud, s.ci_paciente, s.id_tipo_elemento, s.fecha_solicitud,
                s.estado_solicitud, s.prioridad, s.fecha_hora_limite, s.observaciones,
                te.nombre AS tipo_elemento,
                origen.nombre AS origen, origen.tipo_ubicacion AS origen_tipo,
                destino.nombre AS destino, destino.tipo_ubicacion AS destino_tipo,
                s.id_canal, c.nombre AS canal, u.nombre_usuario AS solicitante,
                p.nombre AS nombre_paciente, p.apellido AS apellido_paciente
            FROM solicitud_traslado s
            JOIN tipo_elemento te ON te.id_tipo_elemento = s.id_tipo_elemento
            JOIN ubicacion origen ON origen.id_ubicacion = s.id_origen
            JOIN ubicacion destino ON destino.id_ubicacion = s.id_destino
            JOIN canal_solicitud c ON c.id_canal = s.id_canal
            JOIN usuario u ON u.id_usuario = s.id_usuario_solicit
            LEFT JOIN paciente p ON p.ci = s.ci_paciente
            WHERE s.estado_solicitud = 'Pendiente'
              AND NOT EXISTS (SELECT 1 FROM traslado t WHERE t.id_solicitud = s.id_solicitud)"
    ];
    foreach ($vistas as $nombre => $consulta) {
        $pdo->exec("CREATE OR REPLACE SQL SECURITY INVOKER VIEW `$nombre` AS $consulta");
        $pdo->query("SELECT 1 FROM `$nombre` LIMIT 1");
    }
    echo "[OK] Vistas verificadas (" . implode(', ', array_keys($vistas)) . ").\n";
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
