#!/usr/bin/env bash
# ==============================================================================
# S.I.G.S.M. - Prueba de carga simple
# Uso: bash bin/prueba_carga.sh [cantidad] [url]
#   cantidad: peticiones a enviar (por defecto 500)
#   url:      página a consultar (por defecto http://localhost:8080/)
# Ejemplo: bash bin/prueba_carga.sh 1000 http://localhost:8080/SIGSM/serviciosComunes/autenticacion/sesion.php
#
# Usar solo páginas públicas (por ejemplo el login): nunca rutas con sesión
# ni que escriban en la base de datos.
# ==============================================================================

set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CANTIDAD="${1:-500}"
URL="${2:-http://localhost:8080/}"

info()  { printf '[i] %s\n' "$*"; }
fallo() { printf '[ERROR] %s\n' "$*" >&2; exit 1; }

# 1. Requisitos -----------------------------------------------------------------
[[ "$CANTIDAD" =~ ^[1-9][0-9]*$ ]] || fallo "La cantidad debe ser un entero positivo (recibido: '$CANTIDAD')."
[[ "$URL" =~ ^https?:// ]] || fallo "La URL debe empezar con http:// o https:// (recibido: '$URL')."
command -v curl >/dev/null 2>&1 || fallo "Falta instalar curl."
command -v docker >/dev/null 2>&1 || fallo "Docker no está instalado."
docker info >/dev/null 2>&1 || fallo "Docker no responde: verificar que el servicio esté iniciado y que el usuario tenga permiso (grupo docker o sudo)."

cd "$RAIZ"
mapfile -t contenedores < <(docker compose ps -q 2>/dev/null)
(( ${#contenedores[@]} > 0 )) || fallo "Los contenedores de SIGSM no están en ejecución. Iniciar con: docker compose up -d"

mostrar_stats() {
    docker stats --no-stream "${contenedores[@]}"
}

# 2. Estado antes ---------------------------------------------------------------
info "Uso de recursos ANTES:"
mostrar_stats

# 3. Carga ----------------------------------------------------------------------
# Sin -v ni salida de la respuesta: solo se cuenta el código HTTP.
info "Enviando $CANTIDAD peticiones a $URL ..."
mitad=$(( (CANTIDAD + 1) / 2 ))
muestra_durante="$(mktemp)"
trap 'rm -f "$muestra_durante"' EXIT

correctas=0
fallidas=0
inicio=$(date +%s.%N)

for (( i = 1; i <= CANTIDAD; i++ )); do
    # A mitad de la prueba se toma una muestra en segundo plano, con la carga activa.
    if (( i == mitad )); then
        mostrar_stats > "$muestra_durante" 2>&1 &
    fi

    codigo=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$URL" || true)
    if [[ "$codigo" =~ ^[23] ]]; then
        correctas=$(( correctas + 1 ))
    else
        fallidas=$(( fallidas + 1 ))
    fi
done

fin=$(date +%s.%N)
wait

# 4. Resultados -----------------------------------------------------------------
info "Uso de recursos DURANTE (muestra tomada a mitad de la prueba):"
cat "$muestra_durante"

info "Uso de recursos DESPUÉS:"
mostrar_stats

duracion=$(awk -v a="$inicio" -v b="$fin" 'BEGIN { printf "%.2f", b - a }')
por_segundo=$(awk -v n="$CANTIDAD" -v d="$duracion" 'BEGIN { printf "%.1f", (d > 0 ? n / d : 0) }')

echo
echo "========================================================="
echo " Prueba de carga terminada"
echo "  URL:          $URL"
echo "  Peticiones:   $CANTIDAD (correctas: $correctas, fallidas: $fallidas)"
echo "  Duración:     ${duracion} s (${por_segundo} peticiones/s)"
echo "========================================================="
