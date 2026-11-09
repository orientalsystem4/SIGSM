#!/usr/bin/env bash
# ==============================================================================
# S.I.G.S.M. - Respaldo de la instalación Docker (base de datos y documentos)
# Uso: sudo bash bin/respaldar.sh
# Lo ejecuta todos los días sigsm-respaldo.timer (lo crea bin/instalar.sh).
#
# Destino:  /var/backups/sigsm/databases   db_sigsm_FECHA.sql.gz
#           /var/backups/sigsm/documentos  documentos_FECHA.tar.gz
# Registro: /var/log/respaldos_sigsm.log
# Rotación: se borran las copias de más de 14 días, solo si el respaldo nuevo salió bien.
#
# Las rutas se pueden cambiar con SIGSM_DIR_RESPALDOS, SIGSM_DIR_DOCUMENTOS y
# SIGSM_LOG_RESPALDOS (por ejemplo, para probar sin tocar /var).
# ==============================================================================

set -Eeuo pipefail
umask 077

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIR_RESPALDOS="${SIGSM_DIR_RESPALDOS:-/var/backups/sigsm}"
DIR_DOCUMENTOS="${SIGSM_DIR_DOCUMENTOS:-/var/lib/sigsm/documentos}"
LOG_RESPALDOS="${SIGSM_LOG_RESPALDOS:-/var/log/respaldos_sigsm.log}"
DIAS_RETENCION=14

FECHA="$(date '+%Y%m%d_%H%M%S')"
ARCHIVO_DB="$DIR_RESPALDOS/databases/db_sigsm_${FECHA}.sql.gz"
ARCHIVO_DOCS="$DIR_RESPALDOS/documentos/documentos_${FECHA}.tar.gz"

registrar() {
    local nivel="$1"; shift
    local linea
    linea="$(date '+%Y-%m-%d %H:%M:%S') [$nivel] $*"
    printf '%s\n' "$linea"
    printf '%s\n' "$linea" >> "$LOG_RESPALDOS" 2>/dev/null || true
}

fallo() {
    registrar ERROR "$*"
    rm -f "$ARCHIVO_DB.tmp" "$ARCHIVO_DOCS.tmp"
    exit 1
}
trap 'fallo "Falló la línea $LINENO del respaldo."' ERR

# Lee una variable del .env sin interpretarlo como shell
env_get() { grep -E "^$1=" "$RAIZ/.env" | tail -n1 | cut -d= -f2- || true; }

# 1. Requisitos -----------------------------------------------------------------
touch "$LOG_RESPALDOS" 2>/dev/null || { printf '[ERROR] No se puede escribir %s. Ejecutar con sudo.\n' "$LOG_RESPALDOS" >&2; exit 1; }
install -d -m 700 "$DIR_RESPALDOS" "$DIR_RESPALDOS/databases" "$DIR_RESPALDOS/documentos" 2>/dev/null \
    || fallo "No se pudo crear $DIR_RESPALDOS. Ejecutar con sudo."

# Evita dos respaldos simultáneos (por ejemplo, el timer y uno manual)
exec 9> "$DIR_RESPALDOS/.respaldo.lock"
flock -n 9 || fallo "Ya hay un respaldo en curso."

cd "$RAIZ"
[[ -f .env ]] || fallo "No existe $RAIZ/.env."
command -v docker >/dev/null 2>&1 || fallo "Docker no está instalado."
[[ -n "$(docker compose ps -q db 2>/dev/null)" ]] || fallo "El contenedor de la base no está en ejecución (docker compose up -d)."
[[ -d "$DIR_DOCUMENTOS" && -r "$DIR_DOCUMENTOS" ]] || fallo "No se puede leer $DIR_DOCUMENTOS."

NOMBRE_DB="$(env_get DB_NAME)"
NOMBRE_DB="${NOMBRE_DB:-sigsm}"
[[ "$NOMBRE_DB" =~ ^[A-Za-z0-9_]+$ ]] || fallo "DB_NAME no es válido en .env."

# 2. Base de datos --------------------------------------------------------------
# Se respalda con el administrador de la base (DB_ADMIN_USER). Sin él (instalación
# anterior) se usa DB_USER, que en esas instalaciones es root.
# La clave viaja como variable de entorno (MYSQL_PWD), nunca como argumento:
# los argumentos de los procesos se ven con ps.
USUARIO_DB="$(env_get DB_ADMIN_USER)"
if [[ -n "$USUARIO_DB" ]]; then
    MYSQL_PWD="$(env_get DB_ADMIN_PASS)"
else
    USUARIO_DB="$(env_get DB_USER)"
    USUARIO_DB="${USUARIO_DB:-root}"
    MYSQL_PWD="$(env_get DB_PASS)"
fi
[[ "$USUARIO_DB" =~ ^[A-Za-z0-9_]+$ ]] || fallo "El usuario de la base no es válido en .env."
[[ -n "$MYSQL_PWD" ]] || fallo "La clave de $USUARIO_DB está vacía en .env."
export MYSQL_PWD

# Las vistas son SQL SECURITY INVOKER: se quita su DEFINER del volcado para que el
# respaldo se pueda restaurar con cualquier usuario con permisos sobre la base.
docker compose exec -T -e MYSQL_PWD db \
    mariadb-dump -u"$USUARIO_DB" --single-transaction --routines --events --triggers \
    --databases "$NOMBRE_DB" 2>> "$LOG_RESPALDOS" \
    | sed -E 's#^/\*!50013 DEFINER=`[^`]*`@`[^`]*` #/*!50013 #' \
    | gzip -c > "$ARCHIVO_DB.tmp" \
    || fallo "Falló el volcado de la base '$NOMBRE_DB' (detalle arriba en el registro)."
unset MYSQL_PWD

gzip -t "$ARCHIVO_DB.tmp" 2>/dev/null || fallo "El volcado de la base está dañado (gzip -t)."
tablas=$(gzip -dc "$ARCHIVO_DB.tmp" | grep -c '^CREATE TABLE' || true)
(( tablas > 0 )) || fallo "El volcado de la base quedó vacío (sin CREATE TABLE)."
vistas=$(gzip -dc "$ARCHIVO_DB.tmp" | grep -c '^/\*!50001 VIEW' || true)
mv "$ARCHIVO_DB.tmp" "$ARCHIVO_DB"

# 3. Documentos -----------------------------------------------------------------
tar -czf "$ARCHIVO_DOCS.tmp" -C "$(dirname "$DIR_DOCUMENTOS")" "$(basename "$DIR_DOCUMENTOS")" 2>> "$LOG_RESPALDOS" \
    || fallo "Falló el archivo de documentos."
gzip -t "$ARCHIVO_DOCS.tmp" 2>/dev/null || fallo "El archivo de documentos está dañado (gzip -t)."
mv "$ARCHIVO_DOCS.tmp" "$ARCHIVO_DOCS"

# 4. Rotación (solo después de un respaldo correcto) ----------------------------
minutos=$(( DIAS_RETENCION * 24 * 60 ))
borrados=$(find "$DIR_RESPALDOS/databases" "$DIR_RESPALDOS/documentos" -maxdepth 1 -type f \
    \( -name 'db_sigsm_*.sql.gz' -o -name 'documentos_*.tar.gz' \) -mmin "+$minutos" -print -delete | wc -l)

registrar OK "Respaldo correcto: $(basename "$ARCHIVO_DB") ($tablas tablas, $vistas vistas, $(du -h "$ARCHIVO_DB" | cut -f1)), $(basename "$ARCHIVO_DOCS") ($(du -h "$ARCHIVO_DOCS" | cut -f1)). Copias antiguas borradas: $borrados."
