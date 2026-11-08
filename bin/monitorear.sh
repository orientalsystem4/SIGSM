#!/usr/bin/env bash
# ==============================================================================
# S.I.G.S.M. - Monitor de la instalación Docker
# Uso: sudo bash bin/monitorear.sh
# Lo ejecuta cada 5 minutos sigsm-monitor-docker.timer (lo crea bin/instalar.sh).
#
# Revisa: contenedores web y db, respuesta de la aplicación, CPU y RAM de los
# contenedores (> 75 % / > 80 %), disco de / y /var/backups (> 85 %) y la
# antigüedad del último respaldo de la base (< 26 horas).
#
# Solo escribe cuando algo falla, en /var/log/alertas_sigsm.log (mismo formato
# que operador_sigsm.sh) y en el journal con la etiqueta SIGSM_MONITOR.
# Código de salida: 0 si todo está bien, 1 si hubo alguna alerta.
#
# Las rutas se pueden cambiar con SIGSM_LOG_ALERTAS, SIGSM_DIR_RESPALDOS y
# SIGSM_URL_MONITOR (por ejemplo, para probar sin tocar /var).
# ==============================================================================

set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_ALERTAS="${SIGSM_LOG_ALERTAS:-/var/log/alertas_sigsm.log}"
DIR_RESPALDOS="${SIGSM_DIR_RESPALDOS:-/var/backups/sigsm}"
URL_APLICACION="${SIGSM_URL_MONITOR:-http://127.0.0.1:8080/}"

UMBRAL_CPU=75
UMBRAL_RAM=80
UMBRAL_DISCO=85
HORAS_RESPALDO=26

ESTADO=0

# Mismo formato que registrar_alerta() de operador_sigsm.sh
registrar_alerta() {
    local tipo="$1" detalle="$2" mensaje
    mensaje="[$(date '+%Y-%m-%d %H:%M:%S')] [ALERTA CRÍTICA - $tipo] $detalle"
    printf '%s\n' "$mensaje"
    printf '%s\n' "$mensaje" >> "$LOG_ALERTAS" 2>/dev/null \
        || printf '[ERROR] No se puede escribir %s. Ejecutar con sudo.\n' "$LOG_ALERTAS" >&2
    logger -t "SIGSM_MONITOR" -p user.alert "$tipo: $detalle" 2>/dev/null || true
    ESTADO=1
}

# Compara un porcentaje decimal ("12.34%") con un umbral entero
supera() { awk -v v="${1%\%}" -v u="$2" 'BEGIN { exit !(v + 0 > u) }'; }

cd "$RAIZ" || { registrar_alerta "MONITOR" "No se encontró la carpeta del proyecto $RAIZ."; exit 1; }

# 1. Contenedores ---------------------------------------------------------------
if ! docker info >/dev/null 2>&1; then
    registrar_alerta "DOCKER" "Docker no responde."
else
    en_ejecucion="$(docker compose ps --status running --services 2>/dev/null)"
    for servicio in web db; do
        if ! grep -qx "$servicio" <<< "$en_ejecucion"; then
            registrar_alerta "CONTENEDOR" "El contenedor '$servicio' no está en ejecución."
        fi
    done

    # 2. CPU y RAM de los contenedores (porcentaje sobre su propio límite)
    mapfile -t contenedores < <(docker compose ps -q 2>/dev/null)
    if (( ${#contenedores[@]} > 0 )); then
        while IFS='|' read -r nombre cpu ram; do
            [[ -n "$nombre" ]] || continue
            if supera "$cpu" "$UMBRAL_CPU"; then
                registrar_alerta "CPU" "El contenedor $nombre usa ${cpu} de CPU (límite de alerta ${UMBRAL_CPU}%)."
            fi
            if supera "$ram" "$UMBRAL_RAM"; then
                registrar_alerta "MEMORIA_RAM" "El contenedor $nombre usa ${ram} de su memoria (límite de alerta ${UMBRAL_RAM}%)."
            fi
        done < <(docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemPerc}}' "${contenedores[@]}" 2>/dev/null)
    fi
fi

# 3. La aplicación responde -----------------------------------------------------
codigo="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$URL_APLICACION" 2>/dev/null)"
if [[ "$codigo" != "200" && "$codigo" != "302" ]]; then
    registrar_alerta "APLICACION" "La aplicación no responde en $URL_APLICACION (código HTTP: ${codigo:-sin respuesta})."
fi

# 4. Disco ----------------------------------------------------------------------
destino_respaldos="/var/backups"
[[ -d "$destino_respaldos" ]] || destino_respaldos="/var"
for punto in / "$destino_respaldos"; do
    uso="$(df -P "$punto" 2>/dev/null | awk 'NR==2 { gsub(/%/, "", $5); print $5 }')"
    if [[ "$uso" =~ ^[0-9]+$ ]] && (( uso > UMBRAL_DISCO )); then
        registrar_alerta "DISCO" "El disco de $punto está al ${uso}% (límite de alerta ${UMBRAL_DISCO}%)."
    fi
done

# 5. Último respaldo de la base -------------------------------------------------
ultimo="$(find "$DIR_RESPALDOS/databases" -maxdepth 1 -type f -name 'db_sigsm_*.sql.gz' \
    -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -n1)"
if [[ -z "$ultimo" ]]; then
    registrar_alerta "RESPALDO" "No hay respaldos de la base en $DIR_RESPALDOS/databases."
else
    edad_horas=$(( ( $(date +%s) - ${ultimo%%.*} ) / 3600 ))
    if (( edad_horas >= HORAS_RESPALDO )); then
        registrar_alerta "RESPALDO" "El último respaldo de la base tiene ${edad_horas} horas (máximo ${HORAS_RESPALDO})."
    fi
fi

exit "$ESTADO"
