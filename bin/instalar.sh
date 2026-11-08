#!/usr/bin/env bash
# ==============================================================================
# S.I.G.S.M. - Instalador (Debian + Apache + Docker)
# Uso: sudo bash bin/instalar.sh
# Es idempotente: se puede ejecutar varias veces sin cambiar la clave de la base
# ni la del administrador ya creado.
# ==============================================================================

set -Eeuo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIR_DOCUMENTOS="/var/lib/sigsm/documentos"
VHOST="/etc/apache2/sites-available/sigsm.conf"
ESPERA_DB=180

info()  { printf '[i] %s\n' "$*"; }
ok()    { printf '[OK] %s\n' "$*"; }
fallo() { printf '[ERROR] %s\n' "$*" >&2; exit 1; }
trap 'fallo "Falló la línea $LINENO. Corregir el problema y volver a ejecutar el instalador."' ERR

# Lee una variable del .env sin interpretarlo como shell
env_get() { grep -E "^$1=" "$RAIZ/.env" | tail -n1 | cut -d= -f2- || true; }

cd "$RAIZ"

# 1. Requisitos -----------------------------------------------------------------
[[ $EUID -eq 0 ]] || fallo "Ejecutar como root: sudo bash bin/instalar.sh"

faltan=()
command -v docker   >/dev/null 2>&1 || faltan+=("docker")
if command -v docker >/dev/null 2>&1; then
    docker compose version >/dev/null 2>&1 || faltan+=("docker compose (plugin)")
fi
command -v apache2  >/dev/null 2>&1 || faltan+=("apache2")
command -v a2enmod  >/dev/null 2>&1 || faltan+=("a2enmod (apache2)")
command -v openssl  >/dev/null 2>&1 || faltan+=("openssl")
if (( ${#faltan[@]} > 0 )); then
    fallo "Falta instalar: ${faltan[*]}"
fi
ok "Requisitos verificados."

# 2. Archivo .env ---------------------------------------------------------------
if [[ ! -f .env ]]; then
    cp .env.example .env
    clave_db="$(openssl rand -base64 24 | tr -d '/+=')"
    sed -i "s|^DB_PASS=.*|DB_PASS=${clave_db}|" .env
    chown --reference=. .env
    ok "Archivo .env creado con una clave de base de datos generada."
else
    info "El archivo .env ya existe; no se modifica su contenido."
fi
# Contiene la clave root de la base: solo lectura/escritura del dueño
chmod 600 .env
[[ -n "$(env_get DB_PASS)" ]] || fallo "DB_PASS está vacío en .env. Definirlo antes de continuar."

# 3. Carpeta de documentos ------------------------------------------------------
install -d -o 33 -g 33 -m 750 "$DIR_DOCUMENTOS"
# El volumen tapa el .htaccess del repositorio, por eso se copia a la carpeta nueva
if [[ ! -f "$DIR_DOCUMENTOS/.htaccess" ]]; then
    install -o 33 -g 33 -m 640 src/moduloDocumentacion/Vista/documentos/.htaccess "$DIR_DOCUMENTOS/.htaccess"
fi
ok "Carpeta de documentos lista en $DIR_DOCUMENTOS."

# 3b. Imágenes sin red (pendrive) -----------------------------------------------
for candidato in "$RAIZ/bin/imagenes.tar.gz" "$RAIZ/imagenes.tar.gz" "$RAIZ/../imagenes.tar.gz"; do
    if [[ -f "$candidato" ]]; then
        info "Cargando imágenes de Docker desde $candidato ..."
        docker load -i "$candidato"
        break
    fi
done

# 4. Contenedores ---------------------------------------------------------------
info "Construyendo e iniciando los contenedores..."
docker compose up -d --build

info "Esperando a que la base de datos esté lista (máx. ${ESPERA_DB}s)..."
id_db="$(docker compose ps -q db)"
[[ -n "$id_db" ]] || fallo "No se encontró el contenedor de la base de datos."
inicio=$SECONDS
until [[ "$(docker inspect -f '{{.State.Health.Status}}' "$id_db")" == "healthy" ]]; do
    (( SECONDS - inicio < ESPERA_DB )) || fallo "La base de datos no quedó lista. Revisar: docker compose logs db"
    sleep 3
done
ok "Base de datos lista."

# 5. Estructura y datos iniciales -----------------------------------------------
docker compose exec -T web php bin/seeder.php

# 6. Administrador --------------------------------------------------------------
usuario_admin="$(env_get SEED_ADMIN_USER)"
usuario_admin="${usuario_admin:-admin}"
[[ "$usuario_admin" =~ ^[A-Za-z0-9_.-]+$ ]] || fallo "SEED_ADMIN_USER solo admite letras, números, punto, guion y guion bajo."

existe="$(docker compose exec -T db sh -c \
    "mariadb -uroot -p\"\$MYSQL_ROOT_PASSWORD\" -N -D \"\$MYSQL_DATABASE\" -e \"SELECT COUNT(*) FROM usuario WHERE nombre_usuario='${usuario_admin}'\"")"
existe="${existe//[$'\r\n ']/}"

clave_admin=""
if [[ "$existe" != "0" ]]; then
    info "El administrador '$usuario_admin' ya existe; no se modifica su clave."
else
    if [[ -t 0 ]]; then
        read -rsp "Clave para '$usuario_admin' (Enter para generar una): " clave_admin; echo
        if [[ -n "$clave_admin" && ${#clave_admin} -lt 8 ]]; then
            fallo "La clave debe tener al menos 8 caracteres."
        fi
    fi
    if [[ -z "$clave_admin" ]]; then
        clave_admin="$(openssl rand -base64 18 | tr -d '/+=')"
    fi
    docker compose exec -T -e SEED_ADMIN_PASS="$clave_admin" web php bin/crear_admin.php "$usuario_admin" >/dev/null
    ok "Administrador '$usuario_admin' creado."
fi

# 7. Apache como proxy inverso ----------------------------------------------------
a2enmod proxy proxy_http headers >/dev/null
cat > "$VHOST" <<'CONF'
<VirtualHost *:80>
    ServerName sigsm.local
    ProxyPreserveHost On
    ProxyPass        / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/
    RequestHeader set X-Forwarded-Proto "http"
    LimitRequestBody 20971520
</VirtualHost>
CONF
info "Se desactiva el sitio por defecto de Apache (000-default)."
a2dissite 000-default >/dev/null 2>&1 || true
a2ensite sigsm >/dev/null
# Si la configuración es inválida, el trap aborta antes de recargar
apachectl configtest
systemctl enable --now apache2
systemctl reload apache2
ok "Apache configurado."

# 7b. Respaldo diario -------------------------------------------------------------
# Se reescriben las unidades en cada ejecución: si el proyecto cambia de carpeta,
# volver a ejecutar el instalador actualiza la ruta del script.
cat > /etc/systemd/system/sigsm-respaldo.service <<UNIDAD
[Unit]
Description=Respaldo diario de S.I.G.S.M. (base de datos y documentos)
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
ExecStart=/bin/bash $RAIZ/bin/respaldar.sh
UNIDAD

cat > /etc/systemd/system/sigsm-respaldo.timer <<'UNIDAD'
[Unit]
Description=Ejecuta el respaldo de S.I.G.S.M. todos los días a las 03:00 (hora de Uruguay)

[Timer]
OnCalendar=*-*-* 03:00:00 America/Montevideo
Persistent=true
Unit=sigsm-respaldo.service

[Install]
WantedBy=timers.target
UNIDAD

chmod 0644 /etc/systemd/system/sigsm-respaldo.service /etc/systemd/system/sigsm-respaldo.timer
systemctl daemon-reload
systemctl enable --now sigsm-respaldo.timer >/dev/null
ok "Respaldo diario programado (sigsm-respaldo.timer, 03:00)."

# 7c. Monitoreo cada 5 minutos ----------------------------------------------------
cat > /etc/systemd/system/sigsm-monitor-docker.service <<UNIDAD
[Unit]
Description=Monitoreo de S.I.G.S.M. (contenedores, aplicación, recursos y respaldo)
After=docker.service

[Service]
Type=oneshot
ExecStart=/bin/bash $RAIZ/bin/monitorear.sh
UNIDAD

cat > /etc/systemd/system/sigsm-monitor-docker.timer <<'UNIDAD'
[Unit]
Description=Ejecuta el monitoreo de S.I.G.S.M. cada 5 minutos

[Timer]
OnBootSec=2min
OnCalendar=*:0/5
Unit=sigsm-monitor-docker.service

[Install]
WantedBy=timers.target
UNIDAD

chmod 0644 /etc/systemd/system/sigsm-monitor-docker.service /etc/systemd/system/sigsm-monitor-docker.timer
systemctl daemon-reload
systemctl enable --now sigsm-monitor-docker.timer >/dev/null
ok "Monitoreo programado (sigsm-monitor-docker.timer, cada 5 minutos)."

# 7d. Primer respaldo ------------------------------------------------------------
# La base ya está sana (paso 4). Así el monitor no avisa "no hay respaldos" hasta
# las 03:00. Si falla, solo se advierte: la instalación sigue.
info "Haciendo el primer respaldo..."
if salida_respaldo="$(bash "$RAIZ/bin/respaldar.sh" 2>&1)"; then
    ok "Primer respaldo hecho: ${salida_respaldo##*$'\n'}"
else
    printf '[ADVERTENCIA] El primer respaldo falló: %s\n' "${salida_respaldo##*$'\n'}" >&2
    printf '[ADVERTENCIA] La instalación continúa. Revisar /var/log/respaldos_sigsm.log y repetir con: sudo bash bin/respaldar.sh\n' >&2
fi

# 8. Resumen --------------------------------------------------------------------
ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
echo
echo "========================================================="
echo " S.I.G.S.M. instalado"
echo "  URL:      http://${ip:-IP_DEL_SERVIDOR}/"
echo "  Usuario:  $usuario_admin"
if [[ -n "$clave_admin" ]]; then
    echo "  Clave:    $clave_admin"
    echo "  (se muestra una sola vez; guardarla ahora)"
else
    echo "  Clave:    sin cambios (ya existía)"
fi
echo "========================================================="
