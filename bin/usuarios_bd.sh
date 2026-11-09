#!/usr/bin/env bash
# ==============================================================================
# S.I.G.S.M. - Usuarios de la base de datos
#   DB_ADMIN_USER (sigsm_admin): todos los permisos, solo sobre la base DB_NAME.
#                 Lo usan seeder.php, crear_admin.php y respaldar.sh.
#   DB_USER       (sigsm_app):   la aplicación web. SELECT en la base y escritura
#                 solo en las tablas que el código modifica. Sin permisos de estructura.
#
# Uso:
#   bash bin/usuarios_bd.sh         Base existente con Docker: crea o actualiza los
#                                   usuarios conectando como root (DB_ROOT_PASS del .env).
#                                   Antes, correr el seeder (crea las tablas y vistas).
#   bash bin/usuarios_bd.sh --sql   Solo imprime el SQL, para otros entornos. Ejemplo, con
#                                   la clave de root en la variable MYSQL_PWD (export):
#                                   bash bin/usuarios_bd.sh --sql | podman exec -i -e MYSQL_PWD CONTENEDOR mariadb -uroot
#
# En una base nueva lo ejecuta la imagen de MariaDB, montado como
# /docker-entrypoint-initdb.d/20-usuarios.sh (después de 10-sigsm.sql).
#
# Idempotente: CREATE USER IF NOT EXISTS, fija la clave que dice el .env y vuelve a
# dar los permisos desde cero. Si DB_USER es root (instalación anterior), no toca a root.
# ==============================================================================

# Escribe un texto como literal SQL entre comillas simples
sigsm_literal() {
    local valor="${1//\\/\\\\}"
    printf "'%s'" "${valor//\'/\'\'}"
}

# Imprime el SQL. Argumentos: base, usuario app, clave app, usuario admin, clave admin
sigsm_sql_usuarios() {
    local base="$1" app="$2" clave_app="$3" admin="$4" clave_admin="$5"

    if [[ -n "$admin" && "$admin" != "root" ]]; then
        cat <<SQL
CREATE USER IF NOT EXISTS '$admin'@'%' IDENTIFIED BY $(sigsm_literal "$clave_admin");
ALTER USER '$admin'@'%' IDENTIFIED BY $(sigsm_literal "$clave_admin");
REVOKE ALL PRIVILEGES, GRANT OPTION FROM '$admin'@'%';
GRANT ALL PRIVILEGES ON \`$base\`.* TO '$admin'@'%';
SQL
    fi

    if [[ -n "$app" && "$app" != "root" ]]; then
        cat <<SQL
CREATE USER IF NOT EXISTS '$app'@'%' IDENTIFIED BY $(sigsm_literal "$clave_app");
ALTER USER '$app'@'%' IDENTIFIED BY $(sigsm_literal "$clave_app");
REVOKE ALL PRIVILEGES, GRANT OPTION FROM '$app'@'%';
GRANT SELECT ON \`$base\`.* TO '$app'@'%';
GRANT INSERT, UPDATE ON \`$base\`.\`solicitud_traslado\` TO '$app'@'%';
GRANT INSERT ON \`$base\`.\`traslado\` TO '$app'@'%';
GRANT INSERT ON \`$base\`.\`historial_estado\` TO '$app'@'%';
GRANT INSERT, UPDATE ON \`$base\`.\`usuario\` TO '$app'@'%';
GRANT INSERT, DELETE ON \`$base\`.\`usuario_rol\` TO '$app'@'%';
GRANT INSERT, UPDATE ON \`$base\`.\`documento\` TO '$app'@'%';
GRANT INSERT ON \`$base\`.\`respuesta_encuesta\` TO '$app'@'%';
GRANT INSERT ON \`$base\`.\`respuesta_pregunta\` TO '$app'@'%';
GRANT INSERT, DELETE ON \`$base\`.\`intento_login\` TO '$app'@'%';
SQL
    fi
}

# Valida nombres de base y usuarios (van dentro del SQL sin comillas de valor)
sigsm_validar_nombres() {
    local nombre
    for nombre in "$@"; do
        [[ -z "$nombre" || "$nombre" =~ ^[A-Za-z0-9_]+$ ]] || {
            printf '[ERROR] Nombre no válido para la base o un usuario: %s\n' "$nombre" >&2
            return 1
        }
    done
    if [[ -n "$4" && "$2" == "$4" ]]; then
        printf '[ERROR] DB_USER y DB_ADMIN_USER deben ser usuarios distintos.\n' >&2
        return 1
    fi
}

# 1. Base nueva: lo ejecuta la imagen de MariaDB ----------------------------------
# Las variables llegan del .env (env_file). Se usa return y no exit: la imagen
# puede cargar el script con "source".
if [[ "${BASH_SOURCE[0]}" == /docker-entrypoint-initdb.d/* ]]; then
    sigsm_validar_nombres "${DB_NAME:-sigsm}" "${DB_USER:-}" "" "${DB_ADMIN_USER:-}" || return 1 2>/dev/null || exit 1
    if [[ "${DB_USER:-root}" == "root" ]]; then
        echo "[i] SIGSM: DB_USER es root (configuración anterior); no se crea el usuario de la aplicación."
    fi
    sigsm_sql_usuarios "${DB_NAME:-sigsm}" "${DB_USER:-}" "${DB_PASS:-}" "${DB_ADMIN_USER:-}" "${DB_ADMIN_PASS:-}" \
        | MYSQL_PWD="${MARIADB_ROOT_PASSWORD:-${MYSQL_ROOT_PASSWORD:-}}" mariadb --protocol=socket -uroot \
        || return 1 2>/dev/null || exit 1
    echo "[OK] SIGSM: usuarios de la base verificados (aplicación: ${DB_USER:-root}, administrador: ${DB_ADMIN_USER:-no definido})."
    return 0 2>/dev/null || exit 0
fi

# 2. Base existente: ejecución manual ------------------------------------------
set -Eeuo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fallo() { printf '[ERROR] %s\n' "$*" >&2; exit 1; }

# Lee una variable del .env sin interpretarlo como shell
env_get() { grep -E "^$1=" "$RAIZ/.env" | tail -n1 | cut -d= -f2- || true; }

[[ -r "$RAIZ/.env" ]] || fallo "No se puede leer $RAIZ/.env."

base="$(env_get DB_NAME)"; base="${base:-sigsm}"
app="$(env_get DB_USER)"
clave_app="$(env_get DB_PASS)"
admin="$(env_get DB_ADMIN_USER)"
clave_admin="$(env_get DB_ADMIN_PASS)"

sigsm_validar_nombres "$base" "$app" "" "$admin" || exit 1
[[ -n "$admin" ]] || fallo "Falta DB_ADMIN_USER en .env (ver .env.example)."
[[ -n "$clave_admin" ]] || fallo "DB_ADMIN_PASS está vacío en .env."
[[ -n "$app" && "$app" != "root" ]] || fallo "DB_USER debe ser el usuario de la aplicación (por ejemplo sigsm_app), no root."
[[ -n "$clave_app" ]] || fallo "DB_PASS está vacío en .env."

if [[ "${1:-}" == "--sql" ]]; then
    sigsm_sql_usuarios "$base" "$app" "$clave_app" "$admin" "$clave_admin"
    exit 0
fi

MYSQL_PWD="$(env_get DB_ROOT_PASS)"
[[ -n "$MYSQL_PWD" ]] || fallo "Falta DB_ROOT_PASS en .env (en una instalación anterior es el valor que tenía DB_PASS)."
export MYSQL_PWD

cd "$RAIZ"
command -v docker >/dev/null 2>&1 || fallo "Docker no está instalado. En otros entornos usar: bash bin/usuarios_bd.sh --sql"
[[ -n "$(docker compose ps -q db 2>/dev/null)" ]] || fallo "El contenedor de la base no está en ejecución (docker compose up -d db)."

# La clave de root viaja como variable de entorno (MYSQL_PWD), nunca como argumento.
sigsm_sql_usuarios "$base" "$app" "$clave_app" "$admin" "$clave_admin" \
    | docker compose exec -T -e MYSQL_PWD db mariadb -uroot \
    || fallo "No se pudieron crear los usuarios. Si falta una tabla, correr antes: docker compose exec web php bin/seeder.php"

printf '[OK] Usuarios %s y %s listos. Permisos:\n' "$admin" "$app"
docker compose exec -T -e MYSQL_PWD db mariadb -uroot -N \
    -e "SHOW GRANTS FOR '$admin'@'%'; SHOW GRANTS FOR '$app'@'%';" \
    | sed -E "s/IDENTIFIED BY PASSWORD '[^']*'/IDENTIFIED BY PASSWORD '***'/"
unset MYSQL_PWD
echo "[i] Para que la aplicación use el usuario nuevo: docker compose up -d"
