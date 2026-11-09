<?php
// Traduccion de la interfaz (español por defecto, inglés opcional).
// El idioma se guarda en la cookie "idioma" para que sobreviva al cierre de sesion.

const IDIOMA_COOKIE = 'idioma';
const IDIOMA_POR_DEFECTO = 'es';

if (!function_exists('idiomasDisponibles')) {
    // Lista cerrada: ningun valor recibido se usa para construir rutas.
    function idiomasDisponibles(): array
    {
        return [
            'es' => __DIR__ . '/lang/es.json',
            'en' => __DIR__ . '/lang/en.json'
        ];
    }

    function esIdiomaValido($idioma): bool
    {
        return is_string($idioma)
            && array_key_exists($idioma, idiomasDisponibles());
    }

    function idiomaActual(): string
    {
        $idioma = $_COOKIE[IDIOMA_COOKIE] ?? IDIOMA_POR_DEFECTO;
        return esIdiomaValido($idioma) ? $idioma : IDIOMA_POR_DEFECTO;
    }

    function cargarTraducciones(string $idioma): array
    {
        static $cache = [];

        if (!esIdiomaValido($idioma)) {
            return [];
        }

        if (!isset($cache[$idioma])) {
            $contenido = @file_get_contents(idiomasDisponibles()[$idioma]);
            $datos = $contenido !== false ? json_decode($contenido, true) : null;

            if (!is_array($datos)) {
                error_log("Archivo de idioma '$idioma' ausente o invalido.");
                $datos = [];
            }

            $cache[$idioma] = $datos;
        }

        return $cache[$idioma];
    }

    function buscarTraduccion(string $clave): ?string
    {
        $texto = cargarTraducciones(idiomaActual())[$clave]
            ?? cargarTraducciones(IDIOMA_POR_DEFECTO)[$clave]
            ?? null;

        return is_string($texto) ? $texto : null;
    }

    // Texto sin escapar: para mensajes que se guardan en sesion o se envian como JSON.
    // Los marcadores se escriben {nombre} en el JSON.
    function t(string $clave, array $marcadores = []): string
    {
        $texto = buscarTraduccion($clave);

        if ($texto === null) {
            // Nunca se muestra la clave en pantalla.
            error_log("Clave de idioma inexistente: $clave");
            return '';
        }

        if ($marcadores) {
            $reemplazos = [];
            foreach ($marcadores as $nombre => $valor) {
                $reemplazos['{' . $nombre . '}'] = (string) $valor;
            }
            $texto = strtr($texto, $reemplazos);
        }

        return $texto;
    }

    // Texto escapado para imprimir en HTML.
    function te(string $clave, array $marcadores = []): string
    {
        return htmlspecialchars(t($clave, $marcadores), ENT_QUOTES, 'UTF-8');
    }

    // Etiquetas que vienen de la base (roles, estados, tipos). La base sigue
    // siendo la fuente en español; en inglés se busca "bd.{tipo}.{id}".
    function etiquetaBd(string $tipo, $id, ?string $original): string
    {
        if (idiomaActual() !== IDIOMA_POR_DEFECTO) {
            $id = is_int($id) || ctype_digit((string) $id) ? (int) $id : (string) $id;
            $texto = cargarTraducciones(idiomaActual())['bd.' . $tipo . '.' . $id] ?? null;
            if (is_string($texto)) {
                return $texto;
            }
        }

        return (string) $original;
    }

    // Vehiculo de un traslado: la matricula, o "Tercerizado" si lo hace un proveedor externo (sin nombrar al proveedor).
    function textoVehiculoTraslado(?string $matricula, ?string $proveedor): string
    {
        if ($matricula !== null && $matricula !== '') {
            return $matricula;
        }

        return $proveedor !== null && $proveedor !== ''
            ? t('traslado.tercerizado')
            : t('traslado.sin_vehiculo');
    }

    // Chofer de un traslado: su nombre, o el proveedor externo, o "Sin asignar".
    function textoChoferTraslado(?string $chofer, ?string $proveedor): string
    {
        if ($chofer !== null && $chofer !== '') {
            return $chofer;
        }

        return $proveedor !== null && $proveedor !== ''
            ? t('traslado.tercerizado')
            : t('comun.sin_asignar');
    }

    // Prioridad de una solicitud (valores fijos de la columna prioridad).
    function textoPrioridad(?string $prioridad): string
    {
        return in_array($prioridad, ['normal', 'alta', 'maxima'], true)
            ? t('prioridad.' . $prioridad)
            : (string) $prioridad;
    }

    // Nombre de una ubicacion. La ubicacion generica de domicilio se traduce por
    // su tipo; los hospitales son nombres propios y se muestran tal cual.
    function nombreUbicacion(?string $nombre, ?string $tipo): string
    {
        return $tipo === 'Domicilio' ? t('ubicacion.domicilio') : (string) $nombre;
    }

    // Observaciones del historial: las filas nuevas guardan una clave "hist.*";
    // las antiguas se reconocen por su frase exacta. El texto libre no se toca.
    function textoObservacionHistorial(?string $observacion): string
    {
        $observacion = (string) $observacion;

        if (strpos($observacion, 'hist.') === 0) {
            return buscarTraduccion($observacion) !== null ? t($observacion) : $observacion;
        }

        $frasesAnteriores = [
            'Traslado solicitado' => 'hist.solicitado',
            'Vehículo y personal asignados' => 'hist.asignado',
            'Vehiculo y personal asignados.' => 'hist.asignado',
            'Se inició el traslado.' => 'hist.traslado_iniciado',
            'El vehículo llegó al destino.' => 'hist.llegada_destino',
            'Se inició el retorno.' => 'hist.retorno_iniciado',
            'Traslado finalizado.' => 'hist.traslado_finalizado'
        ];

        return isset($frasesAnteriores[$observacion])
            ? t($frasesAnteriores[$observacion])
            : $observacion;
    }

    // Traducciones para JavaScript: solo las claves con los prefijos pedidos.
    function traduccionesJs(array $prefijos): string
    {
        $resultado = [];
        $todas = cargarTraducciones(IDIOMA_POR_DEFECTO);

        foreach (array_keys($todas) as $clave) {
            foreach ($prefijos as $prefijo) {
                if (strpos($clave, $prefijo) === 0) {
                    $resultado[$clave] = t($clave);
                    break;
                }
            }
        }

        return json_encode(
            $resultado,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    // Bloque <script> con las traducciones de JavaScript.
    // tr() devuelve texto plano (para textContent/alert); trHtml() lo escapa para innerHTML.
    function scriptTraduccionesJs(array $prefijos): string
    {
        return '<script>'
            . 'window.I18N=' . traduccionesJs($prefijos) . ';'
            . 'window.tr=function(c,m){var s=window.I18N[c]||"";m=m||{};'
            . 'Object.keys(m).forEach(function(n){s=s.split("{"+n+"}").join(String(m[n]));});return s;};'
            . 'window.trHtml=function(c,m){var d=document.createElement("div");d.textContent=window.tr(c,m);return d.innerHTML;};'
            . '</script>';
    }
}
