#!/usr/bin/env bash
# ==============================================================================
# S.I.G.S.M. - ORIENTAL SYSTEMS
# Script Maestro de Operador de Centro de Cómputos y Monitoreo Proactivo
# ==============================================================================
# Unifica:
#   1. Gestión de Usuarios, Grupos y Sudoers (admin_sys, operadores_db, auditores)
#   2. Gestión de Servicios Esenciales del Sistema (systemd / systemctl)
#   3. Gestión de Respaldos (Corto Plazo 30d, Largo Plazo 70d SHA-256 y Rsync)
#   4. Gestión de Base de Datos MariaDB/MySQL (PoLP y aislamiento de credenciales)
#   5. Gestión de Redes y Conectividad (IP estática, Netplan con rollback, DNS)
#   6. Gestión de Firewall Perimetral (UFW y auditoría de puertos en escucha)
#   7. Gestión de Logs y Auditoría (journalctl restrictivo y exportación)
#   8. Gestión de Contenedores Docker y Docker Compose
#   9. Monitoreo Automático de Umbrales (Disco 85%, RAM 80%, CPU 75%) y Cockpit
#
# Modos de uso:
#   sudo bash operador_sigsm.sh             -> Consola interactiva de operador
#   sudo bash operador_sigsm.sh --monitor   -> Ejecución desatendida (Cron/Timer)
#   sudo bash operador_sigsm.sh --backup    -> Respaldo automático desatendido
#   sudo bash operador_sigsm.sh --instalar  -> Instala comando global y timer 5m
# ==============================================================================

set -Eeuo pipefail
IFS=$'\n\t'
umask 077

# --- Rutas de Bitácoras y Auditoría ---
LOG_DIR="/var/log/sigsm"
LOG_GENERAL="/var/log/gestion_operador.log"
LOG_USUARIOS="/var/log/gestion_usuarios.log"
LOG_SERVICIOS="/var/log/gestion_servicios.log"
LOG_RESPALDOS="/var/log/gestion_respaldos.log"
LOG_REDES="/var/log/gestion_redes.log"
LOG_FIREWALL="/var/log/gestion_firewall.log"
LOG_BD="/var/log/gestion_bd.log"
LOG_LOGS="/var/log/gestion_logs.log"
LOG_DOCKER="/var/log/gestion_docker.log"
LOG_ALERTAS="/var/log/alertas_sigsm.log"
INCIDENT_DIR="/var/log/sigsm/incidentes"

# --- Parámetros Generales del Sistema ---
SUDOERS_DIR="/etc/sudoers.d"
BACKUP_DIR="/var/backups/sigsm"
BACKUP_DB_DIR="$BACKUP_DIR/databases"
BACKUP_APP_DIR="$BACKUP_DIR/aplicaciones"
BACKUP_SYSTEM_DIR="$BACKUP_DIR/sistema"
BACKUP_LONG_TERM_DIR="$BACKUP_DIR/largo_plazo"

RETENTION_DAYS=30
LONG_TERM_DAYS=70
SPACE_THRESHOLD=100

# Parámetros de Sincronización Externa (DTI)
REMOTE_USER="${REMOTE_USER:-backup_dti}"
REMOTE_HOST="${REMOTE_HOST:-10.10.20.50}"
REMOTE_DIR="${REMOTE_DIR:-/srv/backups_remotos/sigsm}"
SSH_KEY="${SSH_KEY:-/root/.ssh/id_ed25519_backup}"

# Parámetros de Red y Firewall
NETPLAN_DIR="/etc/netplan"
RESOLV_CONF="/etc/resolv.conf"
SSH_PORT="${SSH_PORT:-22}"

# Parámetros de Base de Datos y Aplicación
DB_ENV_FILE="/etc/orientalsistem/db.env"
DB_MODE="${DB_MODE:-auto}"
DB_CONTAINER="${DB_CONTAINER:-sigsm_mariadb}"
DEFAULT_DB="${DEFAULT_DB:-orientalsistem}"
DB_CLIENT=""
DB_DUMP=""
CONNECTION_MODE=""
CNF_FILE=""
DB_USER=""
DB_PASSWORD=""

# Detectar ruta de la aplicación automáticamente (Producción VPS vs Entorno DTI)
if [[ -d "/srv/app/SIGSM" ]]; then
    APP_DEFAULT_DIR="/srv/app/SIGSM"
    PROJECT_DIR="${PROJECT_DIR:-/srv/app/SIGSM}"
elif [[ -d "/opt/sigsm" ]]; then
    APP_DEFAULT_DIR="/opt/sigsm/public_html"
    PROJECT_DIR="${PROJECT_DIR:-/opt/sigsm}"
else
    APP_DEFAULT_DIR="/var/www/html"
    PROJECT_DIR="${PROJECT_DIR:-/opt/sigsm}"
fi
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_DIR/docker-compose.yml}"
MAX_RESULTS="${MAX_RESULTS:-200}"
MAX_LOG_LINES="${MAX_LOG_LINES:-200}"
COMPOSE_CMD=()

# Umbrales Críticos de Monitoreo
UMBRAL_DISCO=85
UMBRAL_RAM=80
UMBRAL_CPU=75

# Lista Blanca de Servicios Esenciales del Sistema
SERVICIOS_WHITELIST=(
    "caddy"
    "php8.4-fpm"
    "apache2"
    "mariadb"
    "mysql"
    "ssh"
    "sshd"
    "docker"
    "ufw"
    "cron"
    "cockpit.socket"
    "cockpit"
    "systemd-journald"
)

# ==============================================================================
# VALIDACIÓN INICIAL Y UTILIDADES COMPARTIDAS
# ==============================================================================

if [[ "${EUID}" -ne 0 ]]; then
    echo "ERROR: Este script debe ejecutarse como root. Usa: sudo bash $0" >&2
    exit 1
fi

inicializar_entorno() {
    mkdir -p "$LOG_DIR" "$INCIDENT_DIR" "$BACKUP_DB_DIR" "$BACKUP_APP_DIR" "$BACKUP_SYSTEM_DIR" "$BACKUP_LONG_TERM_DIR"
    chmod 700 "$LOG_DIR" "$INCIDENT_DIR" "$BACKUP_DIR" "$BACKUP_DB_DIR" "$BACKUP_APP_DIR" "$BACKUP_SYSTEM_DIR" "$BACKUP_LONG_TERM_DIR"
    local f
    for f in "$LOG_GENERAL" "$LOG_USUARIOS" "$LOG_SERVICIOS" "$LOG_RESPALDOS" \
             "$LOG_REDES" "$LOG_FIREWALL" "$LOG_BD" "$LOG_LOGS" "$LOG_DOCKER" "$LOG_ALERTAS"; do
        touch "$f"
        chmod 600 "$f"
    done
}

cleanup() {
    [[ -n "${CNF_FILE:-}" && -f "${CNF_FILE:-}" ]] && rm -f -- "$CNF_FILE" || true
}
trap cleanup EXIT

log_evento() {
    local archivo_destino="$1"
    local mensaje="$2"
    local ts
    ts="$(date '+%Y-%m-%d %H:%M:%S')"
    printf '%s - [%s] %s\n' "$ts" "${SUDO_USER:-root}" "$mensaje" >> "$archivo_destino"
    printf '%s - [%s] %s\n' "$ts" "${SUDO_USER:-root}" "$mensaje" >> "$LOG_GENERAL"
    logger -t "SIGSM_OPERADOR" -p user.info "$mensaje" 2>/dev/null || true
}

log_error_evento() {
    local archivo_destino="$1"
    local mensaje="$2"
    log_evento "$archivo_destino" "ERROR: $mensaje"
    logger -t "SIGSM_OPERADOR" -p user.err "ERROR: $mensaje" 2>/dev/null || true
    printf 'ERROR: %s\n' "$mensaje" >&2
}

pausa() {
    echo ""
    read -rp "Presione Enter para continuar..." _
}

limpiar_pantalla() {
    command -v clear >/dev/null 2>&1 && clear || true
}

calcular_uso_cpu_real() {
    local u1 n1 s1 i1 w1 q1 sq1 st1 u2 n2 s2 i2 w2 q2 sq2 st2 total1 total2 idle1 idle2 dt di
    read -r _ u1 n1 s1 i1 w1 q1 sq1 st1 _ < /proc/stat
    sleep 0.3
    read -r _ u2 n2 s2 i2 w2 q2 sq2 st2 _ < /proc/stat
    idle1=$(( i1 + ${w1:-0} ))
    idle2=$(( i2 + ${w2:-0} ))
    total1=$(( u1 + n1 + s1 + idle1 + ${q1:-0} + ${sq1:-0} + ${st1:-0} ))
    total2=$(( u2 + n2 + s2 + idle2 + ${q2:-0} + ${sq2:-0} + ${st2:-0} ))
    dt=$(( total2 - total1 ))
    di=$(( idle2 - idle1 ))
    if (( dt > 0 )); then
        printf '%d' $(( (100 * (dt - di)) / dt ))
    else
        printf '0'
    fi
}

obtener_metricas_rapidas() {
    local disco ram cpu
    disco=$(df -P /srv/app 2>/dev/null | awk 'NR==2 {gsub(/%/,"",$5); print $5}' || df -P / | awk 'NR==2 {gsub(/%/,"",$5); print $5}')
    ram=$(free | awk '/^Mem:/ {printf "%.0f", ($3/$2)*100}')
    cpu=$(calcular_uso_cpu_real 2>/dev/null || echo "0")
    printf 'Disco: %s%% (Máx %s%%) | RAM: %s%% (Máx %s%%) | CPU: %s%% (Máx %s%%)\n' \
        "$disco" "$UMBRAL_DISCO" "$ram" "$UMBRAL_RAM" "$cpu" "$UMBRAL_CPU"
}

estado_corto_servicio() {
    local srv="$1"
    if systemctl is-active --quiet "$srv" 2>/dev/null; then
        printf '[ACTIVO]'
    else
        printf '[INACTIVO]'
    fi
}

# ==============================================================================
# MÓDULO 1: GESTIÓN DE USUARIOS, GRUPOS Y SUDOERS
# ==============================================================================

usuario_valido() {
    local usuario="$1"
    if [[ ! "$usuario" =~ ^[a-z_][a-z0-9_-]{1,31}$ ]]; then
        echo "El nombre de usuario '$usuario' no es válido." >&2
        return 1
    fi
    return 0
}

grupo_valido() {
    local grupo="$1"
    if [[ ! "$grupo" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]; then
        echo "El nombre del grupo '$grupo' no es válido." >&2
        return 1
    fi
    return 0
}

contrasena_valida() {
    local contrasena="$1"
    local minima_longitud=8
    if [[ ${#contrasena} -lt $minima_longitud ]]; then
        echo "La contraseña debe tener mínimo $minima_longitud caracteres." >&2
        return 1
    fi
    if ! [[ "$contrasena" =~ [A-Z] ]]; then
        echo "La contraseña debe contener al menos una mayúscula." >&2
        return 1
    fi
    if ! [[ "$contrasena" =~ [a-z] ]]; then
        echo "La contraseña debe contener al menos una minúscula." >&2
        return 1
    fi
    if ! [[ "$contrasena" =~ [0-9] ]]; then
        echo "La contraseña debe contener al menos un número." >&2
        return 1
    fi
    if ! [[ "$contrasena" =~ [^a-zA-Z0-9] ]]; then
        echo "La contraseña debe contener al menos un carácter especial (!@#\$%^&*)." >&2
        return 1
    fi
    return 0
}

grupo_existe() {
    getent group "$1" >/dev/null 2>&1
}

usuario_existe() {
    id -u "$1" >/dev/null 2>&1
}

usuario_bloqueado() {
    [[ $(passwd -S "$1" 2>/dev/null | awk '{print $2}') == "L" ]]
}

configurar_sudoers() {
    local grupo="$1"
    local sudoers_file="$SUDOERS_DIR/$grupo"
    mkdir -p "$SUDOERS_DIR"

    case "$grupo" in
        admin_sys)
            cat > "$sudoers_file" << 'EOF'
# Grupo Admin - Acceso administrativo completo
%admin_sys ALL=(ALL:ALL) ALL
EOF
            chmod 0440 "$sudoers_file"
            visudo -c -f "$sudoers_file" >/dev/null 2>&1
            echo "Configuración sudoers para admin_sys establecida."
            log_evento "$LOG_USUARIOS" "Configuración sudoers para admin_sys: acceso completo."
            ;;
        operadores_db)
            cat > "$sudoers_file" << 'EOF'
# Grupo Operadores - Acceso restringido a BD, respaldos y servicios esenciales
%operadores_db ALL=(ALL) /usr/bin/systemctl restart mysql, \
                         /usr/bin/systemctl restart mariadb, \
                         /usr/bin/systemctl restart caddy, \
                         /usr/bin/systemctl reload caddy, \
                         /usr/bin/systemctl restart php8.4-fpm, \
                         /usr/bin/systemctl reload php8.4-fpm, \
                         /usr/bin/systemctl restart apache2, \
                         /usr/bin/mysqldump, \
                         /usr/bin/mariadb-dump, \
                         /usr/bin/mysql, \
                         /usr/bin/mariadb, \
                         /usr/local/bin/operador-sigsm, \
                         /usr/local/bin/respaldo*.sh
EOF
            chmod 0440 "$sudoers_file"
            visudo -c -f "$sudoers_file" >/dev/null 2>&1
            echo "Configuración sudoers para operadores_db establecida."
            log_evento "$LOG_USUARIOS" "Configuración sudoers para operadores_db establecida."
            ;;
        auditores)
            cat > "$sudoers_file" << 'EOF'
# Grupo Auditores - Acceso solo lectura (NOEXEC previene !bash en less)
%auditores ALL=(ALL) /usr/bin/cat /var/log/*, \
                     /usr/bin/tail /var/log/*, \
                     /bin/grep *, \
                     NOEXEC: /usr/bin/less /var/log/*, \
                     /usr/bin/head /var/log/*, \
                     /usr/bin/journalctl --no-pager *
EOF
            chmod 0440 "$sudoers_file"
            visudo -c -f "$sudoers_file" >/dev/null 2>&1
            echo "Configuración sudoers para auditores establecida."
            log_evento "$LOG_USUARIOS" "Configuración sudoers para auditores: solo lectura de logs."
            ;;
        *)
            echo "Grupo personalizado '$grupo' (sin plantilla sudoers predefinida)."
            ;;
    esac
}

crear_grupos_base() {
    local -a grupos=("admin_sys" "operadores_db" "auditores")
    echo "Verificando y creando grupos base del Centro de Cómputos..."
    local grupo
    for grupo in "${grupos[@]}"; do
        if grupo_existe "$grupo"; then
            echo "El grupo '$grupo' ya existe."
            configurar_sudoers "$grupo"
        else
            if groupadd "$grupo"; then
                echo "Grupo '$grupo' creado."
                log_evento "$LOG_USUARIOS" "Grupo '$grupo' creado."
                configurar_sudoers "$grupo"
            else
                log_error_evento "$LOG_USUARIOS" "No se pudo crear el grupo '$grupo'."
            fi
        fi
    done
    pausa
}

crear_usuario_sistema() {
    local nombre_usuario password1 password2
    read -rp "Ingrese el nombre del nuevo usuario: " nombre_usuario
    if [[ -z "$nombre_usuario" ]] || ! usuario_valido "$nombre_usuario"; then
        log_error_evento "$LOG_USUARIOS" "Intento de crear usuario inválido: ${nombre_usuario:-vacío}"
        pausa
        return
    fi
    if usuario_existe "$nombre_usuario"; then
        echo "El usuario '$nombre_usuario' ya existe."
        pausa
        return
    fi
    if useradd -m -s /bin/bash "$nombre_usuario"; then
        echo "Usuario '$nombre_usuario' creado."
        echo "Ingrese contraseña (mín 8 caracteres, mayús, minús, número y especial):"
        while true; do
            read -rsp "Contraseña: " password1
            echo ""
            if ! contrasena_valida "$password1"; then
                continue
            fi
            read -rsp "Repetir contraseña: " password2
            echo ""
            if [[ "$password1" == "$password2" ]]; then
                echo "$nombre_usuario:$password1" | chpasswd
                passwd -e "$nombre_usuario" >/dev/null 2>&1
                echo "Contraseña establecida. El usuario deberá cambiarla en su próximo inicio de sesión."
                log_evento "$LOG_USUARIOS" "Usuario '$nombre_usuario' creado con política de contraseña segura."
                break
            else
                echo "Las contraseñas no coinciden. Intente nuevamente."
            fi
        done
    else
        log_error_evento "$LOG_USUARIOS" "Fallo al ejecutar useradd para '$nombre_usuario'."
    fi
    pausa
}

anadir_usuario_a_grupo() {
    local nombre_usuario nombre_grupo
    read -rp "Ingrese el nombre del usuario: " nombre_usuario
    read -rp "Ingrese el nombre del grupo (admin_sys, operadores_db, auditores): " nombre_grupo
    if ! usuario_existe "$nombre_usuario"; then
        log_error_evento "$LOG_USUARIOS" "El usuario '$nombre_usuario' no existe."
        pausa
        return
    fi
    if ! grupo_valido "$nombre_grupo" || ! grupo_existe "$nombre_grupo"; then
        log_error_evento "$LOG_USUARIOS" "El grupo '$nombre_grupo' no es válido o no existe."
        pausa
        return
    fi
    if usermod -aG "$nombre_grupo" "$nombre_usuario"; then
        echo "Usuario '$nombre_usuario' añadido al grupo '$nombre_grupo'."
        log_evento "$LOG_USUARIOS" "Usuario '$nombre_usuario' añadido a '$nombre_grupo'."
        configurar_sudoers "$nombre_grupo"
    else
        log_error_evento "$LOG_USUARIOS" "Error al añadir '$nombre_usuario' a '$nombre_grupo'."
    fi
    pausa
}

bloquear_usuario_sistema() {
    local nombre_usuario
    read -rp "Ingrese el nombre del usuario a bloquear: " nombre_usuario
    if ! usuario_existe "$nombre_usuario"; then
        echo "El usuario '$nombre_usuario' no existe."
        pausa
        return
    fi
    if [[ "$nombre_usuario" == "root" || "$nombre_usuario" == "orientalsistem" ]]; then
        log_error_evento "$LOG_USUARIOS" "Protección activa: no se permite bloquear la cuenta crítica '$nombre_usuario'."
        pausa
        return
    fi
    if usuario_bloqueado "$nombre_usuario"; then
        echo "El usuario '$nombre_usuario' ya está bloqueado."
        pausa
        return
    fi
    if passwd -l "$nombre_usuario" >/dev/null 2>&1; then
        echo "Usuario '$nombre_usuario' bloqueado correctamente."
        log_evento "$LOG_USUARIOS" "Usuario '$nombre_usuario' bloqueado."
    else
        log_error_evento "$LOG_USUARIOS" "Fallo al bloquear '$nombre_usuario'."
    fi
    pausa
}

desbloquear_usuario_sistema() {
    local nombre_usuario
    read -rp "Ingrese el nombre del usuario a desbloquear: " nombre_usuario
    if ! usuario_existe "$nombre_usuario"; then
        echo "El usuario '$nombre_usuario' no existe."
        pausa
        return
    fi
    if ! usuario_bloqueado "$nombre_usuario"; then
        echo "El usuario '$nombre_usuario' no está bloqueado."
        pausa
        return
    fi
    if passwd -u "$nombre_usuario" >/dev/null 2>&1; then
        echo "Usuario '$nombre_usuario' desbloqueado correctamente."
        log_evento "$LOG_USUARIOS" "Usuario '$nombre_usuario' desbloqueado."
    else
        log_error_evento "$LOG_USUARIOS" "Fallo al desbloquear '$nombre_usuario'."
    fi
    pausa
}

eliminar_usuario_sistema() {
    local nombre_usuario confirmacion1 confirmacion2
    read -rp "Ingrese el nombre del usuario a eliminar: " nombre_usuario
    if ! usuario_existe "$nombre_usuario"; then
        echo "El usuario '$nombre_usuario' no existe."
        pausa
        return
    fi
    if [[ "$nombre_usuario" == "root" || "$nombre_usuario" == "orientalsistem" ]]; then
        log_error_evento "$LOG_USUARIOS" "Protección activa: no se permite eliminar la cuenta '$nombre_usuario'."
        pausa
        return
    fi
    read -rp "¿Seguro que deseas ELIMINAR a '$nombre_usuario'? [s/N]: " confirmacion1
    if [[ "${confirmacion1,,}" =~ ^(s|si|y|yes)$ ]]; then
        read -rp "CONFIRMAR: ¿Eliminar PERMANENTEMENTE '$nombre_usuario' y su directorio home? [s/N]: " confirmacion2
        if [[ "${confirmacion2,,}" =~ ^(s|si|y|yes)$ ]]; then
            if userdel -r "$nombre_usuario" >/dev/null 2>&1; then
                echo "Usuario '$nombre_usuario' eliminado permanentemente."
                log_evento "$LOG_USUARIOS" "Usuario '$nombre_usuario' eliminado permanentemente con su home."
            else
                log_error_evento "$LOG_USUARIOS" "Fallo al eliminar usuario '$nombre_usuario'."
            fi
        else
            echo "Eliminación cancelada en segunda confirmación."
        fi
    else
        echo "Eliminación cancelada."
    fi
    pausa
}

listar_usuarios_sistema() {
    limpiar_pantalla
    echo "=========================================="
    echo "          USUARIOS DEL SISTEMA"
    echo "=========================================="
    getent passwd | grep -E ":[0-9]{4,}:" | awk -F: '{print $1, "(" $6 ")"}' | nl || true
    echo "=========================================="
    pausa
}

listar_grupos_sistema() {
    limpiar_pantalla
    echo "=========================================="
    echo "           GRUPOS DEL SISTEMA"
    echo "=========================================="
    getent group | awk -F: '{print $1, "GID:" $3}' | nl
    echo "=========================================="
    pausa
}

listar_miembros_grupo_sistema() {
    local nombre_grupo members
    read -rp "Ingrese el nombre del grupo: " nombre_grupo
    if ! grupo_valido "$nombre_grupo" || ! grupo_existe "$nombre_grupo"; then
        echo "El grupo '$nombre_grupo' no es válido o no existe."
        pausa
        return
    fi
    limpiar_pantalla
    echo "=========================================="
    echo "   MIEMBROS DEL GRUPO: $nombre_grupo"
    echo "=========================================="
    members=$(getent group "$nombre_grupo" | cut -d: -f4)
    if [[ -z "$members" ]]; then
        echo "El grupo no tiene miembros secundarios asignados."
    else
        echo "$members" | tr ',' '\n' | nl
    fi
    log_evento "$LOG_USUARIOS" "Listado de miembros del grupo consultado: $nombre_grupo"
    pausa
}

submenu_usuarios() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 1: GESTIÓN DE USUARIOS, GRUPOS Y SUDOERS"
        echo "=========================================================="
        echo "1. Crear grupos base (admin_sys, operadores_db, auditores)"
        echo "2. Crear nuevo usuario (con validación estricta)"
        echo "3. Añadir usuario a un grupo"
        echo "4. Bloquear usuario"
        echo "5. Desbloquear usuario"
        echo "6. Eliminar usuario (doble confirmación)"
        echo "7. Listar usuarios del sistema"
        echo "8. Listar grupos del sistema"
        echo "9. Ver miembros de un grupo"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) crear_grupos_base ;;
            2) crear_usuario_sistema ;;
            3) anadir_usuario_a_grupo ;;
            4) bloquear_usuario_sistema ;;
            5) desbloquear_usuario_sistema ;;
            6) eliminar_usuario_sistema ;;
            7) listar_usuarios_sistema ;;
            8) listar_grupos_sistema ;;
            9) listar_miembros_grupo_sistema ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 2: GESTIÓN DE SERVICIOS ESENCIALES DEL SISTEMA (SYSTEMD)
# ==============================================================================

servicio_en_whitelist() {
    local candidato="${1%.service}"
    local item
    for item in "${SERVICIOS_WHITELIST[@]}"; do
        if [[ "$candidato" == "$item" ]]; then
            return 0
        fi
    done
    return 1
}

panel_servicios_esenciales() {
    limpiar_pantalla
    echo "=========================================================================="
    echo "          ESTADO DE SERVICIOS ESENCIALES DEL SISTEMA (SYSTEMD)"
    echo "=========================================================================="
    printf "%-20s %-14s %-14s %-10s %-12s\n" "SERVICIO" "ESTADO" "ARRANQUE" "PID" "MEMORIA"
    echo "--------------------------------------------------------------------------"
    local srv estado habilitado pid mem_bytes mem_mb
    for srv in "caddy" "php8.4-fpm" "apache2" "mariadb" "ssh" "cron" "ufw" "docker" "cockpit.socket" "sigsm-monitor.timer"; do
        estado=$(systemctl is-active "$srv" 2>/dev/null || true)
        [[ -z "$estado" ]] && estado="no-instalado"
        habilitado=$(systemctl is-enabled "$srv" 2>/dev/null || true)
        [[ -z "$habilitado" ]] && habilitado="-"
        pid=$(systemctl show -p MainPID --value "$srv" 2>/dev/null || echo "0")
        mem_bytes=$(systemctl show -p MemoryCurrent --value "$srv" 2>/dev/null || echo "0")
        if [[ "$mem_bytes" =~ ^[0-9]+$ ]] && (( mem_bytes > 0 && mem_bytes < 18446744073709551615 )); then
            mem_mb="$(( mem_bytes / 1024 / 1024 )) MB"
        else
            mem_mb="-"
        fi
        printf "%-20s %-14s %-14s %-10s %-12s\n" "$srv" "$estado" "$habilitado" "${pid:-0}" "$mem_mb"
    done
    echo "=========================================================================="
    log_evento "$LOG_SERVICIOS" "Panel de servicios esenciales consultado."
    pausa
}

seleccionar_servicio_esencial() {
    echo "Servicios esenciales autorizados:"
    echo "  caddy | php8.4-fpm | apache2 | mariadb | mysql | ssh | cron | ufw | docker | cockpit.socket"
    echo ""
    local srv
    read -rp "Ingrese el nombre del servicio esencial: " srv
    srv="${srv%.service}"
    if ! servicio_en_whitelist "$srv"; then
        log_error_evento "$LOG_SERVICIOS" "Servicio '$srv' fuera de la lista blanca de servicios esenciales."
        return 1
    fi
    printf '%s' "$srv"
}

controlar_servicio_esencial() {
    local accion="$1"
    local srv confirmar
    limpiar_pantalla
    echo "=========================================================="
    echo "   ACCIÓN SOBRE SERVICIO ESENCIAL: ${accion^^}"
    echo "=========================================================="
    echo "Servicios autorizados: caddy, php8.4-fpm, apache2, mariadb, mysql, ssh, cron, ufw, docker, cockpit.socket"
    read -rp "Nombre del servicio: " srv
    srv="${srv%.service}"
    if ! servicio_en_whitelist "$srv"; then
        log_error_evento "$LOG_SERVICIOS" "Intento de ejecutar '$accion' sobre servicio no autorizado: '${srv:-vacío}'."
        pausa
        return
    fi

    # Protección anti-aislamiento remoto para SSH
    if [[ ( "$srv" == "ssh" || "$srv" == "sshd" ) && ( "$accion" == "stop" || "$accion" == "disable" ) ]]; then
        log_error_evento "$LOG_SERVICIOS" "BLOQUEO DE SEGURIDAD: No se permite detener ni deshabilitar SSH para evitar aislar el servidor."
        pausa
        return
    fi

    read -rp "¿Confirma ejecutar 'systemctl $accion $srv'? [s/N]: " confirmar
    if [[ ! "${confirmar,,}" =~ ^(s|si|y|yes)$ ]]; then
        echo "Operación cancelada."
        pausa
        return
    fi

    if systemctl "$accion" "$srv"; then
        echo "Acción '$accion' aplicada correctamente sobre '$srv'."
        systemctl status "$srv" --no-pager -n 5 || true
        log_evento "$LOG_SERVICIOS" "Acción systemctl '$accion' ejecutada con éxito sobre '$srv'."
    else
        log_error_evento "$LOG_SERVICIOS" "Fallo al ejecutar 'systemctl $accion $srv'."
    fi
    pausa
}

diagnostico_servicio_esencial() {
    local srv
    limpiar_pantalla
    echo "=========================================================="
    echo "   DIAGNÓSTICO PROFUNDO DE SERVICIO ESENCIAL"
    echo "=========================================================="
    echo "Servicios autorizados: caddy, php8.4-fpm, apache2, mariadb, mysql, ssh, cron, ufw, docker, cockpit.socket"
    read -rp "Nombre del servicio a diagnosticar: " srv
    srv="${srv%.service}"
    if ! servicio_en_whitelist "$srv"; then
        log_error_evento "$LOG_SERVICIOS" "Servicio '$srv' no autorizado para diagnóstico."
        pausa
        return
    fi
    echo ""
    echo "--- 1. Estado del Demonio (systemctl status) ---"
    systemctl status "$srv" --no-pager -n 12 || true
    echo ""
    echo "--- 2. Últimos 20 Eventos en Journald (journalctl -u $srv) ---"
    journalctl -u "$srv" -n 20 --no-pager -o short-iso || true
    log_evento "$LOG_SERVICIOS" "Diagnóstico profundo ejecutado sobre '$srv'."
    pausa
}

recargar_stack_web() {
    limpiar_pantalla
    echo "=========================================================="
    echo "   RECARGA EN CALIENTE DEL STACK WEB (CERO CAÍDA)"
    echo "=========================================================="
    echo "Purgando OPcache de PHP-FPM y recargando configuración de Caddy/Apache..."
    if systemctl is-active --quiet php8.4-fpm 2>/dev/null; then
        systemctl reload php8.4-fpm
        echo " [OK] php8.4-fpm recargado (OPcache actualizado sin cortar sesiones)."
        log_evento "$LOG_SERVICIOS" "Recarga en caliente de php8.4-fpm ejecutada."
    fi
    if systemctl is-active --quiet caddy 2>/dev/null; then
        systemctl reload caddy
        echo " [OK] caddy recargado."
        log_evento "$LOG_SERVICIOS" "Recarga en caliente de caddy ejecutada."
    fi
    if systemctl is-active --quiet apache2 2>/dev/null; then
        systemctl reload apache2
        echo " [OK] apache2 recargado."
        log_evento "$LOG_SERVICIOS" "Recarga en caliente de apache2 ejecutada."
    fi
    pausa
}

submenu_servicios() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 2: GESTIÓN DE SERVICIOS ESENCIALES DEL SISTEMA"
        echo "=========================================================="
        echo "1. Ver tablero de estado de servicios esenciales (PID y RAM)"
        echo "2. Iniciar un servicio esencial (start)"
        echo "3. Reiniciar un servicio esencial (restart)"
        echo "4. Recargar servicio sin corte de sesiones (reload)"
        echo "5. Detener un servicio esencial (stop - con bloqueo anti-corte SSH)"
        echo "6. Habilitar servicio en el arranque del sistema (enable)"
        echo "7. Deshabilitar servicio en el arranque (disable)"
        echo "8. Diagnóstico profundo de un servicio (status + últimos logs)"
        echo "9. Recarga rápida de Stack Web (PHP 8.4 OPcache + Caddy/Apache)"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) panel_servicios_esenciales ;;
            2) controlar_servicio_esencial "start" ;;
            3) controlar_servicio_esencial "restart" ;;
            4) controlar_servicio_esencial "reload" ;;
            5) controlar_servicio_esencial "stop" ;;
            6) controlar_servicio_esencial "enable" ;;
            7) controlar_servicio_esencial "disable" ;;
            8) diagnostico_servicio_esencial ;;
            9) recargar_stack_web ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 3: GESTIÓN DE RESPALDOS (CORTO 30D, LARGO PLAZO 70D Y EXTERNO)
# ==============================================================================

validar_espacio_disco() {
    local espacio_libre
    espacio_libre=$(df -P "$BACKUP_DIR" | awk 'NR==2 {print $4}')
    if [[ "$espacio_libre" -lt $((SPACE_THRESHOLD * 1024)) ]]; then
        log_error_evento "$LOG_RESPALDOS" "Espacio insuficiente en disco. Disponible: ${espacio_libre}KB, Requerido: $((SPACE_THRESHOLD * 1024))KB"
        return 1
    fi
    return 0
}

cargar_credenciales_env_produccion() {
    if [[ -f "$DB_ENV_FILE" ]]; then
        # shellcheck disable=SC1090
        source "$DB_ENV_FILE"
        DB_USER="${DB_USER:-${MYSQL_USER:-orientalsistem}}"
        DB_PASSWORD="${DB_PASS:-${DB_PASSWORD:-${MYSQL_PASSWORD:-}}}"
        DEFAULT_DB="${DB_NAME:-${MYSQL_DATABASE:-orientalsistem}}"
        return 0
    fi
    return 1
}

backup_bd_respaldos() {
    local modo_auto="${1:-interactivo}"
    [[ "$modo_auto" == "interactivo" ]] && limpiar_pantalla
    echo "=========================================="
    echo "        RESPALDO DE BASE DE DATOS"
    echo "=========================================="

    if ! validar_espacio_disco; then
        [[ "$modo_auto" == "interactivo" ]] && pausa
        return 1
    fi

    local fecha archivo tamano dump_bin
    fecha=$(date +"%Y%m%d_%H%M%S")
    archivo="$BACKUP_DB_DIR/db_sigsm_${fecha}.sql.gz"
    dump_bin="mysqldump"
    command -v mariadb-dump >/dev/null 2>&1 && dump_bin="mariadb-dump"

    # Si existe /etc/orientalsistem/db.env o estamos en modo automático, usar credenciales del entorno de forma segura
    if cargar_credenciales_env_produccion && [[ -n "${DB_PASSWORD:-}" ]]; then
        echo "Usando configuración segura del servidor ($DB_ENV_FILE) para base '$DEFAULT_DB'..."
        crear_archivo_credenciales "$DB_USER" "$DB_PASSWORD"
        if "$dump_bin" "--defaults-extra-file=$CNF_FILE" --single-transaction --routines --events --triggers --databases "$DEFAULT_DB" 2>/dev/null | gzip -c > "$archivo" && gzip -t "$archivo"; then
            chmod 600 "$archivo"
            tamano=$(du -h "$archivo" | cut -f1)
            echo "Respaldo de BD exitoso: $archivo (Tamaño: $tamano)"
            log_evento "$LOG_RESPALDOS" "Respaldo de BD exitoso. Archivo: $archivo, Tamaño: $tamano"
            cleanup
            [[ "$modo_auto" == "interactivo" ]] && pausa
            return 0
        fi
        cleanup
    fi

    if [[ "$modo_auto" == "auto" ]]; then
        log_error_evento "$LOG_RESPALDOS" "Fallo en respaldo automático de BD."
        rm -f "$archivo"
        return 1
    fi

    local db_user db_pass
    read -rp "Ingrese usuario de MariaDB/MySQL [orientalsistem]: " db_user
    db_user="${db_user:-orientalsistem}"
    read -rsp "Ingrese contraseña: " db_pass
    echo ""
    crear_archivo_credenciales "$db_user" "$db_pass"

    echo "Realizando respaldo de base de datos..."
    if "$dump_bin" "--defaults-extra-file=$CNF_FILE" --all-databases --single-transaction 2>/dev/null | gzip -c > "$archivo" && gzip -t "$archivo"; then
        chmod 600 "$archivo"
        tamano=$(du -h "$archivo" | cut -f1)
        echo "Respaldo exitoso: $archivo (Tamaño: $tamano)"
        log_evento "$LOG_RESPALDOS" "Respaldo manual de BD exitoso. Archivo: $archivo, Tamaño: $tamano"
    else
        log_error_evento "$LOG_RESPALDOS" "Fallo al realizar respaldo de BD con usuario '$db_user'."
        rm -f "$archivo"
    fi
    cleanup
    pausa
}

backup_aplicacion_respaldos() {
    local modo_auto="${1:-interactivo}"
    [[ "$modo_auto" == "interactivo" ]] && limpiar_pantalla
    echo "=========================================="
    echo "     RESPALDO DE APLICACIÓN Y CÓDIGO"
    echo "=========================================="
    local ruta_app="$APP_DEFAULT_DIR"
    if [[ "$modo_auto" == "interactivo" ]]; then
        read -rp "Ruta de la aplicación a respaldar [$APP_DEFAULT_DIR]: " input_ruta
        ruta_app="${input_ruta:-$APP_DEFAULT_DIR}"
    fi

    if ! validar_espacio_disco; then
        [[ "$modo_auto" == "interactivo" ]] && pausa
        return 1
    fi
    if [[ ! -d "$ruta_app" ]]; then
        log_error_evento "$LOG_RESPALDOS" "Ruta de aplicación no existe: $ruta_app"
        [[ "$modo_auto" == "interactivo" ]] && pausa
        return 1
    fi

    local fecha archivo tamano
    fecha=$(date +"%Y%m%d_%H%M%S")
    archivo="$BACKUP_APP_DIR/app_sigsm_${fecha}.tar.gz"
    echo "Realizando respaldo de aplicación desde: $ruta_app"

    if tar -czf "$archivo" -C "$(dirname "$ruta_app")" "$(basename "$ruta_app")" 2>/dev/null; then
        chmod 600 "$archivo"
        tamano=$(du -h "$archivo" | cut -f1)
        echo "Respaldo exitoso: $archivo (Tamaño: $tamano)"
        log_evento "$LOG_RESPALDOS" "Respaldo de aplicación exitoso. Archivo: $archivo, Tamaño: $tamano"
    else
        log_error_evento "$LOG_RESPALDOS" "Fallo al realizar respaldo de aplicación desde '$ruta_app'."
        rm -f "$archivo"
    fi
    [[ "$modo_auto" == "interactivo" ]] && pausa
}

backup_sistema_respaldos() {
    local modo_auto="${1:-interactivo}"
    [[ "$modo_auto" == "interactivo" ]] && limpiar_pantalla
    echo "=========================================="
    echo "   RESPALDO DE CONFIGURACIÓN DEL SISTEMA"
    echo "=========================================="
    local fecha archivo tamano
    fecha=$(date +"%Y%m%d_%H%M%S")
    archivo="$BACKUP_SYSTEM_DIR/sistema_${fecha}.tar.gz"

    local -a rutas_existentes=()
    local r
    for r in /etc/hostname /etc/hosts /etc/resolv.conf /etc/sudoers.d /etc/caddy /etc/php /etc/orientalsistem /etc/mysql /etc/apache2; do
        [[ -e "$r" ]] && rutas_existentes+=("$r")
    done

    echo "Empaquetando rutas de configuración: ${rutas_existentes[*]}"
    if tar -czf "$archivo" "${rutas_existentes[@]}" 2>/dev/null; then
        chmod 600 "$archivo"
        tamano=$(du -h "$archivo" | cut -f1)
        echo "Respaldo exitoso: $archivo (Tamaño: $tamano)"
        log_evento "$LOG_RESPALDOS" "Respaldo de configuración del sistema exitoso. Archivo: $archivo, Tamaño: $tamano"
    else
        log_error_evento "$LOG_RESPALDOS" "Fallo al realizar respaldo de configuración del sistema."
        rm -f "$archivo"
    fi
    [[ "$modo_auto" == "interactivo" ]] && pausa
}

listar_respaldos_disponibles() {
    limpiar_pantalla
    echo "=========================================================="
    echo "     RESPALDOS DISPONIBLES (CORTO 30D Y LARGO PLAZO 70D)"
    echo "=========================================================="

    echo -e "\n--- Respaldos Operativos de Base de Datos (0-$RETENTION_DAYS días) ---"
    if [[ -d "$BACKUP_DB_DIR" ]] && [[ -n "$(ls -A "$BACKUP_DB_DIR" 2>/dev/null)" ]]; then
        ls -lh "$BACKUP_DB_DIR" | tail -n +2 | awk '{print "  " $9, "(" $5 ")"}'
    else
        echo "  No hay respaldos operativos de BD."
    fi

    echo -e "\n--- Respaldos Operativos de Aplicación (0-$RETENTION_DAYS días) ---"
    if [[ -d "$BACKUP_APP_DIR" ]] && [[ -n "$(ls -A "$BACKUP_APP_DIR" 2>/dev/null)" ]]; then
        ls -lh "$BACKUP_APP_DIR" | tail -n +2 | awk '{print "  " $9, "(" $5 ")"}'
    else
        echo "  No hay respaldos operativos de aplicación."
    fi

    echo -e "\n--- Respaldos Operativos de Sistema (0-$RETENTION_DAYS días) ---"
    if [[ -d "$BACKUP_SYSTEM_DIR" ]] && [[ -n "$(ls -A "$BACKUP_SYSTEM_DIR" 2>/dev/null)" ]]; then
        ls -lh "$BACKUP_SYSTEM_DIR" | tail -n +2 | awk '{print "  " $9, "(" $5 ")"}'
    else
        echo "  No hay respaldos operativos de sistema."
    fi

    echo -e "\n--- Repositorio de Largo Plazo ($RETENTION_DAYS a $LONG_TERM_DAYS días + SHA-256) ---"
    if [[ -d "$BACKUP_LONG_TERM_DIR" ]] && [[ -n "$(ls -A "$BACKUP_LONG_TERM_DIR" 2>/dev/null)" ]]; then
        ls -lh "$BACKUP_LONG_TERM_DIR" | grep -v "\.sha256$" | tail -n +2 | awk '{print "  " $9, "(" $5 ")"}' || echo "  Sin archivos principales."
    else
        echo "  No hay respaldos archivados en largo plazo."
    fi

    echo -e "\n--- Espacio Total Utilizado en $BACKUP_DIR ---"
    du -sh "$BACKUP_DIR" 2>/dev/null || echo "No se pudo calcular"
    log_evento "$LOG_RESPALDOS" "Listado de respaldos consultado."
    pausa
}

limpiar_y_rotar_respaldos() {
    local modo_auto="${1:-interactivo}"
    [[ "$modo_auto" == "interactivo" ]] && limpiar_pantalla
    echo "=========================================================="
    echo "  ROTACIÓN (> $RETENTION_DAYS DÍAS) Y DEPURACIÓN (> $LONG_TERM_DAYS DÍAS)"
    echo "=========================================================="
    local cantidad_archivados=0
    local cantidad_eliminados=0
    local archivo nombre_archivo destino archivo_antiguo tamano

    echo "Paso 1: Promoviendo respaldos con más de $RETENTION_DAYS días a Largo Plazo ($BACKUP_LONG_TERM_DIR)..."
    while IFS= read -r archivo; do
        if [[ -f "$archivo" ]]; then
            nombre_archivo=$(basename "$archivo")
            destino="$BACKUP_LONG_TERM_DIR/$nombre_archivo"
            mv "$archivo" "$destino"
            sha256sum "$destino" | awk '{print $1}' > "${destino}.sha256"
            chmod 600 "$destino" "${destino}.sha256"
            echo "  [ARCHIVADO] $nombre_archivo (+ firma .sha256)"
            log_evento "$LOG_RESPALDOS" "Respaldo promovido a largo plazo: $destino"
            cantidad_archivados=$((cantidad_archivados + 1))
        fi
    done < <(find "$BACKUP_DB_DIR" "$BACKUP_APP_DIR" "$BACKUP_SYSTEM_DIR" -type f \( -name "*.gz" -o -name "*.sql" \) -mtime +"$RETENTION_DAYS" 2>/dev/null)

    echo ""
    echo "Paso 2: Eliminando respaldos de largo plazo que superan los $LONG_TERM_DAYS días..."
    while IFS= read -r archivo_antiguo; do
        if [[ -f "$archivo_antiguo" ]]; then
            tamano=$(du -h "$archivo_antiguo" | cut -f1)
            echo "  [ELIMINADO] $(basename "$archivo_antiguo") ($tamano)"
            rm -f "$archivo_antiguo" "${archivo_antiguo}.sha256"
            cantidad_eliminados=$((cantidad_eliminados + 1))
        fi
    done < <(find "$BACKUP_LONG_TERM_DIR" -type f \( -name "*.gz" -o -name "*.sql" \) -mtime +"$LONG_TERM_DAYS" 2>/dev/null)

    echo "----------------------------------------------------------"
    echo "Resumen: $cantidad_archivados promovidos a largo plazo | $cantidad_eliminados eliminados (> $LONG_TERM_DAYS días)."
    log_evento "$LOG_RESPALDOS" "Rotación completada: $cantidad_archivados archivados, $cantidad_eliminados eliminados."
    [[ "$modo_auto" == "interactivo" ]] && pausa
}

sincronizar_respaldo_externo() {
    limpiar_pantalla
    echo "=========================================================="
    echo "       SINCRONIZACIÓN A SERVIDOR EXTERNO (OFF-SITE)"
    echo "=========================================================="
    echo "Servidor destino configurado: ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}"
    if ! ping -c 2 -W 2 "$REMOTE_HOST" &>/dev/null; then
        log_error_evento "$LOG_RESPALDOS" "No se pudo establecer conexión con el nodo de respaldo externo: $REMOTE_HOST"
        pausa
        return 1
    fi
    echo "Transfiriendo respaldos locales y de largo plazo vía rsync/SSH..."
    if rsync -avz --delete -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=accept-new" "$BACKUP_DIR/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/"; then
        echo "Respaldo externo sincronizado exitosamente."
        log_evento "$LOG_RESPALDOS" "Sincronización externa exitosa hacia ${REMOTE_HOST}:${REMOTE_DIR}"
    else
        log_error_evento "$LOG_RESPALDOS" "Error al sincronizar con el servidor de respaldo externo."
    fi
    pausa
}

ejecutar_backup_completo_desatendido() {
    log_evento "$LOG_RESPALDOS" "Iniciando rutina de respaldo automático completo..."
    backup_bd_respaldos "auto" || true
    backup_aplicacion_respaldos "auto" || true
    backup_sistema_respaldos "auto" || true
    limpiar_y_rotar_respaldos "auto" || true
    log_evento "$LOG_RESPALDOS" "Rutina de respaldo automático finalizada."
}

submenu_respaldos() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 3: GESTIÓN DE RESPALDOS (BACKUPS 30D / 70D)"
        echo "=========================================================="
        echo "1. Respaldo de Base de Datos (Auto-detecta $DB_ENV_FILE)"
        echo "2. Respaldo de Aplicación y Código ($APP_DEFAULT_DIR)"
        echo "3. Respaldo de Configuración del Sistema (/etc/caddy, /etc/php...)"
        echo "4. Ejecutar Respaldo Integral Completo (BD + App + Sistema)"
        echo "5. Listar respaldos disponibles (Operativos 30d / Largo Plazo 70d)"
        echo "6. Rotar a Largo Plazo (>$RETENTION_DAYS días) y Depurar (>$LONG_TERM_DAYS días)"
        echo "7. Sincronizar con Servidor de Respaldo Externo (rsync/SSH)"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) backup_bd_respaldos "interactivo" ;;
            2) backup_aplicacion_respaldos "interactivo" ;;
            3) backup_sistema_respaldos "interactivo" ;;
            4)
                backup_bd_respaldos "auto" || true
                backup_aplicacion_respaldos "auto" || true
                backup_sistema_respaldos "auto" || true
                pausa
                ;;
            5) listar_respaldos_disponibles ;;
            6) limpiar_y_rotar_respaldos "interactivo" ;;
            7) sincronizar_respaldo_externo ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 4: GESTIÓN DE BASE DE DATOS (MARIADB / MYSQL)
# ==============================================================================

nombre_bd_valido() {
    [[ "$1" =~ ^[A-Za-z0-9_]{1,64}$ ]]
}

nombre_usuario_bd_valido() {
    [[ "$1" =~ ^[A-Za-z0-9_]{1,32}$ ]]
}

escapar_sql() {
    local value="$1"
    value="${value//\\/\\\\}"
    value="${value//\'/\'\'}"
    printf '%s' "$value"
}

crear_archivo_credenciales() {
    local usuario="$1" contrasena="$2"
    cleanup
    CNF_FILE=$(mktemp /tmp/gestion_bd.XXXXXX.cnf)
    chmod 600 "$CNF_FILE"
    {
        printf '[client]\n'
        printf 'user=%s\n' "$usuario"
        printf 'password=%s\n' "$contrasena"
    } > "$CNF_FILE"
}

detectar_conexion_bd() {
    if [[ "$DB_MODE" != "local" ]] && command -v docker >/dev/null 2>&1 \
        && docker inspect -f '{{.State.Running}}' "$DB_CONTAINER" 2>/dev/null | grep -qx true; then
        CONNECTION_MODE="docker"
        if docker exec "$DB_CONTAINER" sh -c 'command -v mariadb >/dev/null 2>&1' 2>/dev/null; then
            DB_CLIENT="mariadb"
        else
            DB_CLIENT="mysql"
        fi
        if docker exec "$DB_CONTAINER" sh -c 'command -v mariadb-dump >/dev/null 2>&1' 2>/dev/null; then
            DB_DUMP="mariadb-dump"
        else
            DB_DUMP="mysqldump"
        fi
        return 0
    fi

    CONNECTION_MODE="local"
    if command -v mariadb >/dev/null 2>&1; then
        DB_CLIENT="mariadb"
    elif command -v mysql >/dev/null 2>&1; then
        DB_CLIENT="mysql"
    else
        log_error_evento "$LOG_BD" "No se encontró cliente MariaDB/MySQL instalado."
        return 1
    fi

    if command -v mariadb-dump >/dev/null 2>&1; then
        DB_DUMP="mariadb-dump"
    else
        DB_DUMP="mysqldump"
    fi
    return 0
}

ejecutar_sql() {
    local consulta="$1"
    if [[ "$CONNECTION_MODE" == "docker" ]]; then
        printf '%s\n%s\n' "$DB_USER" "$DB_PASSWORD" | docker exec -i "$DB_CONTAINER" sh -c '
            IFS= read -r db_user
            IFS= read -r db_password
            MYSQL_PWD="$db_password" exec "$0" --user="$db_user" --batch --skip-column-names -e "$1"
        ' "$DB_CLIENT" "$consulta"
    else
        "$DB_CLIENT" "--defaults-extra-file=$CNF_FILE" --batch --skip-column-names -e "$consulta"
    fi
}

ejecutar_dump_bd() {
    local base="$1"
    if [[ "$CONNECTION_MODE" == "docker" ]]; then
        printf '%s\n%s\n' "$DB_USER" "$DB_PASSWORD" | docker exec -i "$DB_CONTAINER" sh -c '
            IFS= read -r db_user
            IFS= read -r db_password
            MYSQL_PWD="$db_password" exec "$0" --user="$db_user" --single-transaction --routines --events --triggers --databases "$1"
        ' "$DB_DUMP" "$base"
    else
        "$DB_DUMP" "--defaults-extra-file=$CNF_FILE" --single-transaction --routines --events --triggers --databases "$base"
    fi
}

autenticar_bd() {
    if [[ -n "${CNF_FILE:-}" && -f "${CNF_FILE:-}" ]] && ejecutar_sql "SELECT 1" >/dev/null 2>&1; then
        return 0
    fi
    if cargar_credenciales_env_produccion && [[ -n "${DB_PASSWORD:-}" ]]; then
        crear_archivo_credenciales "$DB_USER" "$DB_PASSWORD"
        if ejecutar_sql "SELECT 1" >/dev/null 2>&1; then
            log_evento "$LOG_BD" "Autenticación automática de BD exitosa desde $DB_ENV_FILE ($DB_USER)."
            return 0
        fi
    fi
    local usuario contrasena
    read -rp "Usuario administrador de MariaDB/MySQL [orientalsistem]: " usuario
    usuario="${usuario:-orientalsistem}"
    read -rsp "Contraseña de $usuario: " contrasena
    echo ""
    DB_USER="$usuario"
    DB_PASSWORD="$contrasena"
    crear_archivo_credenciales "$usuario" "$contrasena"
    if ! ejecutar_sql "SELECT 1" >/dev/null 2>&1; then
        log_error_evento "$LOG_BD" "No se pudo autenticar el usuario '$usuario'."
        cleanup
        return 1
    fi
    log_evento "$LOG_BD" "Autenticación manual exitosa para '$usuario'."
    return 0
}

mostrar_entorno_bd() {
    limpiar_pantalla
    echo "=========================================================="
    echo "          ESTADO DEL MOTOR DE BASE DE DATOS"
    echo "=========================================================="
    echo "Modo de conexión: $CONNECTION_MODE (Cliente: $DB_CLIENT)"
    echo ""
    ejecutar_sql "SELECT CONCAT('Versión del Motor: ', VERSION());"
    ejecutar_sql "SHOW GLOBAL STATUS WHERE Variable_name IN ('Uptime','Threads_connected','Max_used_connections','Aborted_connects');" \
        | awk -F $'\t' '{printf "%-26s %s\n", $1 ":", $2}'
    echo ""
    echo "Bases de datos y tamaño aproximado:"
    ejecutar_sql "SELECT table_schema, CONCAT(ROUND(SUM(data_length + index_length) / 1024 / 1024, 2), ' MB') FROM information_schema.tables WHERE table_schema NOT IN ('information_schema','mysql','performance_schema','sys') GROUP BY table_schema ORDER BY table_schema;" \
        | awk -F $'\t' '{printf "  %-30s %s\n", $1, $2}' || true
    log_evento "$LOG_BD" "Estado del motor MariaDB consultado."
    pausa
}

listar_bases_bd() {
    limpiar_pantalla
    echo "=========================================================="
    echo "               BASES DE DATOS DISPONIBLES"
    echo "=========================================================="
    ejecutar_sql "SHOW DATABASES;" | nl -w2 -s'. '
    log_evento "$LOG_BD" "Listado de bases de datos consultado."
    pausa
}

crear_base_y_usuario_polp() {
    local base usuario contrasena confirmar usuario_sql contrasena_sql
    limpiar_pantalla
    echo "=========================================================="
    echo "    CREAR BASE Y USUARIO DE APLICACIÓN (MENOR PRIVILEGIO)"
    echo "=========================================================="
    read -rp "Nombre de la base [$DEFAULT_DB]: " base
    base="${base:-$DEFAULT_DB}"
    read -rp "Usuario de aplicación (sin privilegios globales): " usuario
    if ! nombre_bd_valido "$base" || ! nombre_usuario_bd_valido "$usuario"; then
        log_error_evento "$LOG_BD" "Nombre de base o usuario inválido."
        pausa
        return
    fi
    read -rsp "Contraseña para '$usuario' (mín. 12 caracteres, Enter para autogenerar): " contrasena
    echo ""
    if [[ -z "$contrasena" ]]; then
        contrasena=$(openssl rand -base64 24 | tr -d '\n')
    elif [[ ${#contrasena} -lt 12 ]]; then
        log_error_evento "$LOG_BD" "La contraseña debe contener al menos 12 caracteres."
        pausa
        return
    fi
    read -rp "¿Confirma crear '$base' y asignar permisos acotados a '$usuario'? [s/N]: " confirmar
    if [[ ! "${confirmar,,}" =~ ^(s|si)$ ]]; then
        echo "Operación cancelada."
        pausa
        return
    fi
    usuario_sql=$(escapar_sql "$usuario")
    contrasena_sql=$(escapar_sql "$contrasena")
    if ejecutar_sql "CREATE DATABASE IF NOT EXISTS \`$base\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '$usuario_sql'@'%' IDENTIFIED BY '$contrasena_sql'; GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE ON \`$base\`.* TO '$usuario_sql'@'%'; FLUSH PRIVILEGES;"; then
        echo "Base '$base' y usuario '$usuario' configurados bajo Principio de Menor Privilegio."
        echo "Contraseña asignada: $contrasena"
        log_evento "$LOG_BD" "Base '$base' y usuario PoLP '$usuario' configurados."
    else
        log_error_evento "$LOG_BD" "No se pudo crear la base o asignar privilegios (requiere cuenta root de BD)."
    fi
    pausa
}

restaurar_respaldo_bd() {
    local archivo base confirmacion
    limpiar_pantalla
    echo "=========================================================="
    echo "          RESTAURAR RESPALDO DE BASE DE DATOS"
    echo "=========================================================="
    echo "Respaldos disponibles en $BACKUP_DB_DIR:"
    ls -1 "$BACKUP_DB_DIR"/*.sql.gz 2>/dev/null || { echo "No hay archivos .sql.gz disponibles."; pausa; return; }
    echo ""
    read -rp "Nombre del archivo .sql.gz a restaurar: " archivo
    archivo="$BACKUP_DB_DIR/$(basename "$archivo")"
    if [[ ! -f "$archivo" || "$archivo" != *.sql.gz ]] || ! gzip -t "$archivo"; then
        log_error_evento "$LOG_BD" "Archivo inexistente o corrupto: $archivo"
        pausa
        return
    fi
    read -rp "Base destino [$DEFAULT_DB]: " base
    base="${base:-$DEFAULT_DB}"
    if ! nombre_bd_valido "$base"; then
        log_error_evento "$LOG_BD" "Nombre de base destino inválido."
        pausa
        return
    fi
    read -rp "Escriba RESTAURAR-$base para confirmar la importación: " confirmacion
    if [[ "$confirmacion" != "RESTAURAR-$base" ]]; then
        echo "Restauración cancelada."
        pausa
        return
    fi
    if gzip -cd "$archivo" | "$DB_CLIENT" "--defaults-extra-file=$CNF_FILE" "$base"; then
        echo "Restauración completada exitosamente sobre '$base'."
        log_evento "$LOG_BD" "Base '$base' restaurada desde '$archivo'."
    else
        log_error_evento "$LOG_BD" "Fallo durante la restauración de '$base' desde '$archivo'."
    fi
    pausa
}

submenu_bd() {
    if ! detectar_conexion_bd || ! autenticar_bd; then
        pausa
        return
    fi
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 4: GESTIÓN DE BASE DE DATOS (MARIADB)"
        echo "=========================================================="
        echo "1. Ver estado del motor, conexiones y tamaño de tablas"
        echo "2. Listar bases de datos disponibles"
        echo "3. Crear base y usuario de aplicación (Menor Privilegio PoLP)"
        echo "4. Crear respaldo lógico comprimido (.sql.gz)"
        echo "5. Listar respaldos de base de datos"
        echo "6. Restaurar un respaldo de base de datos"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) mostrar_entorno_bd ;;
            2) listar_bases_bd ;;
            3) crear_base_y_usuario_polp ;;
            4) backup_bd_respaldos "interactivo" ;;
            5) listar_respaldos_disponibles ;;
            6) restaurar_respaldo_bd ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 5: GESTIÓN DE REDES Y CONECTIVIDAD
# ==============================================================================

mostrar_estado_red() {
    limpiar_pantalla
    echo "=========================================================="
    echo "              ESTADO DE INTERFACES DE RED"
    echo "=========================================================="
    echo -e "\n--- Interfaces Disponibles ---"
    ip -br link show || ip link show
    echo -e "\n--- Direcciones IP Asignadas ---"
    ip -br addr show || ip addr show | grep -E "inet |inet6 "
    echo -e "\n--- Tabla de Enrutamiento Activa ---"
    ip route show
    echo -e "\n--- Servidores DNS ($RESOLV_CONF) ---"
    [[ -f "$RESOLV_CONF" ]] && grep "^nameserver" "$RESOLV_CONF" || echo "Sin servidores DNS explícitos."
    log_evento "$LOG_REDES" "Estado de interfaces de red consultado."
    pausa
}

configurar_ip_estatica_netplan() {
    local interfaz ip_address gateway dns1 dns2 config_file
    limpiar_pantalla
    echo "=========================================================="
    echo "        CONFIGURAR IP ESTÁTICA (NETPLAN + ROLLBACK)"
    echo "=========================================================="
    read -rp "Nombre de la interfaz (ej. eth0, ens3): " interfaz
    if ! ip link show "$interfaz" &>/dev/null; then
        log_error_evento "$LOG_REDES" "La interfaz '$interfaz' no existe."
        pausa
        return
    fi
    read -rp "Dirección IP con máscara CIDR (ej. 192.168.1.100/24): " ip_address
    read -rp "Gateway (ej. 192.168.1.1): " gateway
    read -rp "DNS primario [8.8.8.8]: " dns1
    dns1="${dns1:-8.8.8.8}"
    read -rp "DNS secundario [8.8.4.4]: " dns2
    dns2="${dns2:-8.8.4.4}"

    if ! [[ "$ip_address" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}/[0-9]{1,2}$ ]]; then
        log_error_evento "$LOG_REDES" "Formato de IP CIDR inválido: $ip_address"
        pausa
        return
    fi

    mkdir -p "$NETPLAN_DIR"
    config_file="$NETPLAN_DIR/01-config-statik.yaml"
    cat > "$config_file" << EOF
network:
  version: 2
  ethernets:
    $interfaz:
      dhcp4: no
      addresses:
        - $ip_address
      routes:
        - to: default
          via: $gateway
      nameservers:
        addresses:
          - $dns1
          - $dns2
EOF
    chmod 600 "$config_file"
    if command -v netplan >/dev/null 2>&1 && netplan validate; then
        netplan apply
        log_evento "$LOG_REDES" "IP estática configurada en $interfaz: $ip_address (GW: $gateway)"
        echo "IP estática aplicada y validada."
    else
        rm -f "$config_file"
        log_error_evento "$LOG_REDES" "Validación de Netplan falló o netplan no está instalado. Se revirtió el archivo (rollback)."
    fi
    pausa
}

configurar_dns_sistema() {
    local dns1 dns2
    limpiar_pantalla
    echo "=========================================================="
    echo "              CONFIGURAR SERVIDORES DNS"
    echo "=========================================================="
    read -rp "DNS primario [1.1.1.1]: " dns1
    dns1="${dns1:-1.1.1.1}"
    read -rp "DNS secundario [8.8.8.8]: " dns2
    dns2="${dns2:-8.8.8.8}"
    [[ -f "$RESOLV_CONF" ]] && cp "$RESOLV_CONF" "${RESOLV_CONF}.bak"
    {
        echo "# DNS configurados por Operador SIGSM - $(date '+%Y-%m-%d %H:%M:%S')"
        echo "nameserver $dns1"
        echo "nameserver $dns2"
    } > "$RESOLV_CONF"
    chmod 644 "$RESOLV_CONF"
    echo "Servidores DNS actualizados:"
    cat "$RESOLV_CONF"
    log_evento "$LOG_REDES" "DNS actualizados: $dns1, $dns2"
    pausa
}

verificar_conectividad_red() {
    local objetivo
    limpiar_pantalla
    echo "=========================================================="
    echo "            VERIFICAR CONECTIVIDAD Y DNS"
    echo "=========================================================="
    read -rp "Host o IP a verificar [sigsm.orientalsystems.xyz]: " objetivo
    objetivo="${objetivo:-sigsm.orientalsystems.xyz}"
    echo "1. Probando alcance ICMP (ping) a $objetivo..."
    if ping -c 3 -W 2 "$objetivo"; then
        log_evento "$LOG_REDES" "Ping exitoso hacia $objetivo"
    else
        log_error_evento "$LOG_REDES" "Ping sin respuesta hacia $objetivo"
    fi
    echo ""
    echo "2. Probando resolución de nombres (DNS)..."
    if command -v dig >/dev/null 2>&1; then
        dig +short "$objetivo" || true
    else
        getent hosts "$objetivo" || true
    fi
    pausa
}

submenu_redes() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 5: GESTIÓN DE REDES Y CONECTIVIDAD"
        echo "=========================================================="
        echo "1. Ver estado de interfaces, IPs, rutas y DNS"
        echo "2. Configurar IPv4 estática (Netplan con rollback automático)"
        echo "3. Configurar servidores DNS (/etc/resolv.conf con respaldo)"
        echo "4. Verificar conectividad ICMP y resolución DNS"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) mostrar_estado_red ;;
            2) configurar_ip_estatica_netplan ;;
            3) configurar_dns_sistema ;;
            4) verificar_conectividad_red ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 6: GESTIÓN DE FIREWALL (UFW) Y AUDITORÍA DE PUERTOS
# ==============================================================================

validar_puerto() {
    [[ "$1" =~ ^[0-9]{1,5}$ ]] && (( $1 >= 1 && $1 <= 65535 ))
}

mostrar_puertos_escucha() {
    limpiar_pantalla
    echo "=========================================================="
    echo "            PUERTOS LOCALES EN ESCUCHA (SS)"
    echo "=========================================================="
    ss -lntup || true
    echo ""
    echo "Nota de seguridad: Verifique que MariaDB (3306) escuche únicamente en 127.0.0.1 o socket Unix."
    log_evento "$LOG_FIREWALL" "Auditoría de puertos en escucha realizada."
    pausa
}

estado_ufw() {
    limpiar_pantalla
    echo "=========================================================="
    echo "              ESTADO DEL FIREWALL UFW"
    echo "=========================================================="
    if ! command -v ufw >/dev/null 2>&1; then
        echo "UFW no está instalado en este contenedor/host (el filtrado se gestiona en el Host VPS)."
    else
        ufw status verbose || true
        echo ""
        ufw status numbered || true
    fi
    log_evento "$LOG_FIREWALL" "Estado de UFW consultado."
    pausa
}

aplicar_politica_ufw_sigsm() {
    limpiar_pantalla
    echo "=========================================================="
    echo "          POLÍTICA BASE PERIMETRAL S.I.G.S.M."
    echo "=========================================================="
    if ! command -v ufw >/dev/null 2>&1; then
        log_error_evento "$LOG_FIREWALL" "UFW no está instalado. Instálelo con: apt install ufw"
        pausa
        return
    fi
    local red_admin confirmar
    read -rp "Red/IP administrativa para SSH (ej. 10.10.0.0/24 o IP): " red_admin
    read -rp "¿Confirma aplicar política base (Deny Incoming, Allow 80/443 y SSH desde $red_admin)? [s/N]: " confirmar
    if [[ ! "${confirmar,,}" =~ ^(s|si)$ ]]; then
        echo "Operación cancelada."
        pausa
        return
    fi
    ufw allow from "$red_admin" to any port "$SSH_PORT" proto tcp comment 'SIGSM SSH administracion'
    ufw allow 80/tcp comment 'SIGSM HTTP'
    ufw allow 443/tcp comment 'SIGSM HTTPS'
    ufw default deny incoming
    ufw default allow outgoing
    ufw logging medium
    ufw --force enable
    log_evento "$LOG_FIREWALL" "Política base UFW aplicada (SSH: $red_admin, Web: 80/443)."
    pausa
}

submenu_firewall() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 6: GESTIÓN DE FIREWALL Y AUDITORÍA DE PUERTOS"
        echo "=========================================================="
        echo "1. Ver puertos y demonios locales en escucha (ss -lntup)"
        echo "2. Ver estado y reglas numeradas de UFW"
        echo "3. Aplicar política perimetral segura S.I.G.S.M."
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) mostrar_puertos_escucha ;;
            2) estado_ufw ;;
            3) aplicar_politica_ufw_sigsm ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 7: GESTIÓN DE LOGS Y AUDITORÍA (JOURNALCTL RESTRICTIVO)
# ==============================================================================

validar_unidad_systemd() {
    [[ "$1" =~ ^[A-Za-z0-9@_.-]+(\.service|\.socket|\.timer)?$ ]]
}

resumen_logs_sistema() {
    limpiar_pantalla
    echo "=========================================================="
    echo "             RESUMEN DE REGISTROS Y AUDITORÍA"
    echo "=========================================================="
    printf 'Servicio Journald: '
    systemctl is-active systemd-journald 2>/dev/null || echo "no disponible"
    journalctl --disk-usage 2>/dev/null || true
    printf 'Tamaño de /var/log: '
    du -sh /var/log 2>/dev/null || true
    echo ""
    echo "Cantidad de eventos críticos (err..alert) en las últimas 24 horas:"
    journalctl -p err..alert --since '24 hours ago' --no-pager -q 2>/dev/null | wc -l || echo "0"
    echo ""
    echo "Últimas 10 acciones registradas por Operadores S.I.G.S.M.:"
    tail -n 10 "$LOG_GENERAL" 2>/dev/null || echo "Sin registros aún."
    log_evento "$LOG_LOGS" "Resumen de registros consultado."
    pausa
}

ver_errores_journal() {
    local periodo
    limpiar_pantalla
    echo "=========================================================="
    echo "          ERRORES RECIENTES DEL SISTEMA (ERR..ALERT)"
    echo "=========================================================="
    read -rp "Período a consultar [24 hours ago]: " periodo
    periodo="${periodo:-24 hours ago}"
    journalctl -p err..alert --since "$periodo" -n "$MAX_RESULTS" --no-pager -o short-iso || true
    log_evento "$LOG_LOGS" "Errores del sistema consultados desde: $periodo"
    pausa
}

ver_log_unidad() {
    local servicio periodo
    limpiar_pantalla
    echo "=========================================================="
    echo "              CONSULTAR LOG DE UN SERVICIO"
    echo "=========================================================="
    read -rp "Unidad systemd (ej. caddy, php8.4-fpm, mariadb, ssh): " servicio
    if ! validar_unidad_systemd "$servicio"; then
        log_error_evento "$LOG_LOGS" "Nombre de unidad inválido: $servicio"
        pausa
        return
    fi
    read -rp "Período [24 hours ago]: " periodo
    periodo="${periodo:-24 hours ago}"
    journalctl -u "$servicio" --since "$periodo" -n "$MAX_RESULTS" --no-pager -o short-iso || true
    log_evento "$LOG_LOGS" "Log de unidad '$servicio' consultado desde '$periodo'."
    pausa
}

exportar_evidencia_incidente() {
    local servicio periodo confirmacion archivo
    limpiar_pantalla
    echo "=========================================================="
    echo "           EXPORTAR EVIDENCIA FORENSE DE INCIDENTE"
    echo "=========================================================="
    read -rp "Unidad systemd (Enter para todos los servicios): " servicio
    if [[ -n "$servicio" ]] && ! validar_unidad_systemd "$servicio"; then
        log_error_evento "$LOG_LOGS" "Unidad inválida: $servicio"
        pausa
        return
    fi
    read -rp "Período [24 hours ago]: " periodo
    periodo="${periodo:-24 hours ago}"
    read -rp "Escriba EXPORTAR para generar el archivo protegido en $INCIDENT_DIR: " confirmacion
    if [[ "$confirmacion" != "EXPORTAR" ]]; then
        echo "Exportación cancelada."
        pausa
        return
    fi
    archivo="$INCIDENT_DIR/incidente_$(date '+%Y%m%d_%H%M%S').log"
    {
        printf 'S.I.G.S.M. - Evidencia Forense de Incidente\n'
        printf 'Generado: %s\n' "$(date --iso-8601=seconds)"
        printf 'Período: %s\n' "$periodo"
        printf 'Unidad: %s\n\n' "${servicio:-todas}"
        if [[ -n "$servicio" ]]; then
            journalctl -u "$servicio" --since "$periodo" --no-pager -o short-iso
        else
            journalctl --since "$periodo" -n "$MAX_RESULTS" --no-pager -o short-iso
        fi
    } > "$archivo"
    chmod 600 "$archivo"
    sha256sum "$archivo" > "${archivo}.sha256"
    chmod 600 "${archivo}.sha256"
    echo "Evidencia exportada con firma SHA-256: $archivo"
    log_evento "$LOG_LOGS" "Evidencia de incidente exportada: $archivo"
    pausa
}

limpiar_journal_retencion() {
    local dias confirmacion
    limpiar_pantalla
    echo "=========================================================="
    echo "            DEPURAR JOURNAL POR RETENCIÓN"
    echo "=========================================================="
    journalctl --disk-usage
    read -rp "Conservar registros de los últimos días (1-365) [30]: " dias
    dias="${dias:-30}"
    if ! [[ "$dias" =~ ^[0-9]{1,3}$ ]] || (( dias < 1 || dias > 365 )); then
        log_error_evento "$LOG_LOGS" "Cantidad de días inválida: $dias"
        pausa
        return
    fi
    read -rp "Escriba LIMPIAR-$dias para confirmar: " confirmacion
    if [[ "$confirmacion" != "LIMPIAR-$dias" ]]; then
        echo "Operación cancelada."
        pausa
        return
    fi
    journalctl --vacuum-time="${dias}d"
    log_evento "$LOG_LOGS" "Limpieza de journal ejecutada con retención de ${dias} días."
    pausa
}

submenu_logs() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 7: GESTIÓN DE LOGS Y AUDITORÍA (JOURNALCTL)"
        echo "=========================================================="
        echo "1. Resumen general de registros y bitácora de operadores"
        echo "2. Ver errores recientes del sistema (prioridad err..alert)"
        echo "3. Consultar registros de un servicio específico"
        echo "4. Exportar evidencia forense de incidente (con SHA-256)"
        echo "5. Depurar journal antiguo por política de retención"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) resumen_logs_sistema ;;
            2) ver_errores_journal ;;
            3) ver_log_unidad ;;
            4) exportar_evidencia_incidente ;;
            5) limpiar_journal_retencion ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MÓDULO 8: GESTIÓN DE CONTENEDORES DOCKER
# ==============================================================================

submenu_docker() {
    limpiar_pantalla
    echo "=========================================================="
    echo "   MÓDULO 8: GESTIÓN DE DOCKER Y CONTENEDORES"
    echo "=========================================================="
    if ! command -v docker >/dev/null 2>&1; then
        echo "Nota de Arquitectura: Este entorno opera dentro del contenedor aislado 'os-dev'."
        echo "Los servicios web (Caddy + PHP 8.4-FPM) se administran de forma nativa desde"
        echo "el Módulo 2 (Servicios Esenciales del Sistema)."
        log_evento "$LOG_DOCKER" "Consulta de módulo Docker en contenedor os-dev."
        pausa
        return
    fi
    docker ps -a --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}' || true
    echo ""
    docker system df || true
    log_evento "$LOG_DOCKER" "Estado de contenedores Docker consultado."
    pausa
}

# ==============================================================================
# MÓDULO 9: MONITOREO ACTIVO DE UMBRALES, ALERTAS Y COCKPIT
# ==============================================================================

ESTADO_GLOBAL=0
ALERTAS_DETECTADAS=()

registrar_alerta() {
    local tipo="$1"
    local detalle="$2"
    local fecha mensaje
    fecha="$(date '+%Y-%m-%d %H:%M:%S')"
    mensaje="[$fecha] [ALERTA CRÍTICA - $tipo] $detalle"

    # 1. Guardar en bitácora dedicada de alertas
    printf '%s\n' "$mensaje" >> "$LOG_ALERTAS"

    # 2. Enviar a systemd-journald con etiqueta SIGSM_MONITOR y severidad ALERT (Visible en Cockpit)
    logger -t "SIGSM_MONITOR" -p user.alert "$tipo: $detalle" 2>/dev/null || true

    # 3. Acumular para reporte y código de salida
    ALERTAS_DETECTADAS+=("$mensaje")
    ESTADO_GLOBAL=2
}

ejecutar_monitoreo_umbrales() {
    local modo="${1:-interactivo}"
    ESTADO_GLOBAL=0
    ALERTAS_DETECTADAS=()

    echo "=========================================================="
    echo " AUDITORÍA DE UMBRALES Y MONITOREO DE ESTADO - S.I.G.S.M."
    echo " Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "=========================================================="

    # 1. Disco (Umbral 85%)
    local uso_disco
    uso_disco=$(df -P /srv/app 2>/dev/null | awk 'NR==2 {gsub(/%/,"",$5); print $5}' || df -P / | awk 'NR==2 {gsub(/%/,"",$5); print $5}')
    echo "-> 1. Almacenamiento en Disco: ${uso_disco}% (Umbral crítico: ${UMBRAL_DISCO}%)"
    if (( uso_disco >= UMBRAL_DISCO )); then
        registrar_alerta "DISCO" "Ocupación de disco en ${uso_disco}% (supera límite del ${UMBRAL_DISCO}%)."
    fi

    # 2. Memoria RAM (Umbral 80%)
    local uso_ram
    uso_ram=$(free | awk '/^Mem:/ {printf "%.0f", ($3/$2)*100}')
    echo "-> 2. Consumo de Memoria RAM:  ${uso_ram}% (Umbral crítico: ${UMBRAL_RAM}%)"
    if (( uso_ram >= UMBRAL_RAM )); then
        registrar_alerta "MEMORIA_RAM" "Consumo de RAM en ${uso_ram}% (supera límite del ${UMBRAL_RAM}%)."
    fi

    # 3. Carga de CPU (Umbral 75% - cálculo exacto vía delta en /proc/stat)
    local uso_cpu
    uso_cpu=$(calcular_uso_cpu_real 2>/dev/null || echo "0")
    [[ "$uso_cpu" =~ ^[0-9]+$ ]] || uso_cpu=0
    echo "-> 3. Carga de CPU:            ${uso_cpu}% (Umbral crítico: ${UMBRAL_CPU}%)"
    if (( uso_cpu >= UMBRAL_CPU )); then
        registrar_alerta "CPU" "Carga de procesamiento CPU en ${uso_cpu}% (supera límite del ${UMBRAL_CPU}%)."
    fi

    # 4. Demonios Esenciales Activos (Adaptable a Producción Caddy/PHP-FPM o Apache)
    echo "-> 4. Verificando Demonios Críticos y Base de Datos..."
    local -a servicios_criticos=()
    if systemctl list-unit-files 2>/dev/null | grep -q '^caddy\.service'; then
        servicios_criticos+=("caddy" "php8.4-fpm")
    elif systemctl list-unit-files 2>/dev/null | grep -q '^apache2\.service'; then
        servicios_criticos+=("apache2")
    fi

    local srv
    for srv in "${servicios_criticos[@]}"; do
        if ! systemctl is-active --quiet "$srv"; then
            registrar_alerta "SERVICIO_CAIDO" "El servicio esencial '$srv' NO se encuentra activo."
            # Autorrecuperación proactiva
            if systemctl start "$srv" 2>/dev/null; then
                logger -t "SIGSM_MONITOR" -p user.notice "AUTORRECUPERACION: Servicio '$srv' reiniciado automáticamente." 2>/dev/null || true
                echo "     [AUTORRECUPERACIÓN] Se levantó automáticamente el servicio '$srv'."
            fi
        else
            echo "     [OK] Servicio esencial '$srv': active (running)"
        fi
    done

    # Verificar alcanzabilidad del socket o puerto de MariaDB
    if [[ -S "/run/mysqld/mysqld.sock" ]] || ss -lnt 2>/dev/null | grep -q ':3306 '; then
        echo "     [OK] Motor MariaDB: socket/puerto activo"
    else
        registrar_alerta "BD_INACCESIBLE" "No se detecta socket ni puerto 3306 de MariaDB activo."
    fi

    echo "----------------------------------------------------------"
    if (( ESTADO_GLOBAL == 0 )); then
        echo "ESTADO DEL SISTEMA: SALUDABLE (OK). Ningún umbral superado."
        logger -t "SIGSM_MONITOR" -p user.info "Chequeo periódico OK: Disco=${uso_disco}% RAM=${uso_ram}% CPU=${uso_cpu}%" 2>/dev/null || true
        [[ "$modo" == "interactivo" ]] && pausa
        return 0
    else
        echo "ESTADO DEL SISTEMA: EN ALERTA (${#ALERTAS_DETECTADAS[@]} incidente(s) detectado(s))."
        local alerta
        for alerta in "${ALERTAS_DETECTADAS[@]}"; do
            echo " * $alerta" >&2
        done
        printf 'ATENCIÓN DTI - ALERTA CRÍTICA EN SERVIDOR S.I.G.S.M.:\n%s\n' "${ALERTAS_DETECTADAS[*]}" | wall 2>/dev/null || true
        [[ "$modo" == "interactivo" ]] && pausa
        return 2
    fi
}

instalar_monitoreo_automatico() {
    local ruta_script
    ruta_script="$(readlink -f "$0")"
    ln -sf "$ruta_script" /usr/local/bin/operador-sigsm
    chmod 755 "$ruta_script" /usr/local/bin/operador-sigsm

    # 1. Crear unidad systemd y timer cada 5 minutos (visible desde Cockpit -> Servicios)
    cat > /etc/systemd/system/sigsm-monitor.service << 'EOF'
[Unit]
Description=Monitoreo Proactivo de Umbrales y Servicios Esenciales S.I.G.S.M.
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/operador-sigsm --monitor
StandardOutput=journal
StandardError=journal
SyslogIdentifier=SIGSM_MONITOR
EOF

    cat > /etc/systemd/system/sigsm-monitor.timer << 'EOF'
[Unit]
Description=Ejecución cada 5 minutos del Monitoreo Proactivo S.I.G.S.M.

[Timer]
OnBootSec=1min
OnCalendar=*:0/5
AccuracySec=15s
Unit=sigsm-monitor.service
Persistent=true

[Install]
WantedBy=timers.target
EOF

    # 2. Crear timer de Respaldo Automático Diario a las 03:00 AM
    cat > /etc/systemd/system/sigsm-backup.service << 'EOF'
[Unit]
Description=Respaldo Diario Automatizado y Rotación 30/70 días S.I.G.S.M.
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/operador-sigsm --backup
StandardOutput=journal
StandardError=journal
SyslogIdentifier=SIGSM_BACKUP
EOF

    cat > /etc/systemd/system/sigsm-backup.timer << 'EOF'
[Unit]
Description=Ejecución diaria 03:00 AM del Respaldo Automatizado S.I.G.S.M.

[Timer]
OnCalendar=*-*-* 03:00:00
Persistent=true
Unit=sigsm-backup.service

[Install]
WantedBy=timers.target
EOF

    chmod 0644 /etc/systemd/system/sigsm-monitor.service \
               /etc/systemd/system/sigsm-monitor.timer \
               /etc/systemd/system/sigsm-backup.service \
               /etc/systemd/system/sigsm-backup.timer

    systemctl daemon-reload
    systemctl enable --now sigsm-monitor.timer
    systemctl enable --now sigsm-backup.timer

    log_evento "$LOG_GENERAL" "Comando global '/usr/local/bin/operador-sigsm' y timers systemd (monitoreo 5m y backup 03:00) activados."
    echo "=========================================================="
    echo " [OK] Comando instalado: operador-sigsm"
    echo " [OK] Timer activo cada 5 min: sigsm-monitor.timer"
    echo " [OK] Timer backup diario 03:00: sigsm-backup.timer"
    echo " [OK] Integración con Cockpit lista (etiqueta SIGSM_MONITOR)"
    echo "=========================================================="
}

gestionar_cockpit() {
    limpiar_pantalla
    echo "=========================================================="
    echo "     GESTIÓN DEL PANEL VISUAL COCKPIT (OBSERVABILIDAD)"
    echo "=========================================================="
    if ! systemctl list-unit-files 2>/dev/null | grep -q '^cockpit\.socket'; then
        echo "Cockpit no está instalado actualmente en este entorno."
        local confirmar
        read -rp "¿Desea instalar y habilitar 'cockpit' ahora mismo? [s/N]: " confirmar
        if [[ "${confirmar,,}" =~ ^(s|si)$ ]]; then
            apt-get update && apt-get install -y cockpit
            systemctl enable --now cockpit.socket
            log_evento "$LOG_SERVICIOS" "Cockpit instalado y habilitado (cockpit.socket)."
        fi
    else
        systemctl enable --now cockpit.socket
        echo " [OK] cockpit.socket está activo y escuchando en el puerto 9090."
        systemctl status cockpit.socket --no-pager -n 5 || true
    fi
    echo ""
    echo "Cómo visualizar las alertas de este script en Cockpit:"
    echo "  1. Ingrese al panel web de Cockpit (https://sigsm.orientalsystems.xyz/cockpit)."
    echo "  2. En 'Servicios' -> 'Temporizadores' verá 'sigsm-monitor.timer' y 'sigsm-backup.timer'."
    echo "  3. En 'Registros' (Logs) filtre por identificador: SIGSM_MONITOR o SIGSM_OPERADOR."
    pausa
}

submenu_monitoreo() {
    local op
    while true; do
        limpiar_pantalla
        echo "=========================================================="
        echo "   MÓDULO 9: MONITOREO AUTOMÁTICO, ALERTAS Y COCKPIT"
        echo "=========================================================="
        printf "Estado Timer Monitoreo (5m): %s\n" "$(estado_corto_servicio sigsm-monitor.timer)"
        printf "Estado Timer Respaldo (3am): %s\n" "$(estado_corto_servicio sigsm-backup.timer)"
        printf "Estado Panel Web Cockpit:    %s\n" "$(estado_corto_servicio cockpit.socket)"
        echo "----------------------------------------------------------"
        echo "1. Ejecutar auditoría de umbrales ahora (Disco 85%, RAM 80%, CPU 75%)"
        echo "2. Activar/Instalar Monitoreo Automático (5m) y Backup Diario (3am)"
        echo "3. Detener Monitoreo Automático en segundo plano"
        echo "4. Ver historial de alertas críticas ($LOG_ALERTAS)"
        echo "5. Instalar / Activar Panel Web Cockpit (puerto 9090)"
        echo "0. Volver al Menú Principal"
        echo "=========================================================="
        read -rp "Seleccione una opción: " op
        case "$op" in
            1) ejecutar_monitoreo_umbrales "interactivo" || true ;;
            2) instalar_monitoreo_automatico; pausa ;;
            3)
                systemctl disable --now sigsm-monitor.timer 2>/dev/null || true
                echo "Timer de monitoreo automático detenido."
                log_evento "$LOG_GENERAL" "Timer sigsm-monitor.timer detenido por operador."
                pausa
                ;;
            4)
                limpiar_pantalla
                echo "=== HISTORIAL DE ALERTAS CRÍTICAS ($LOG_ALERTAS) ==="
                if [[ -s "$LOG_ALERTAS" ]]; then
                    tail -n 30 "$LOG_ALERTAS"
                else
                    echo "No se han registrado alertas críticas hasta el momento."
                fi
                pausa
                ;;
            5) gestionar_cockpit ;;
            0) return ;;
            *) echo "Opción no válida."; pausa ;;
        esac
    done
}

# ==============================================================================
# MENÚ PRINCIPAL DEL OPERADOR DE CENTRO DE CÓMPUTOS
# ==============================================================================

mostrar_menu_principal() {
    limpiar_pantalla
    echo "=========================================================================="
    echo "          CENTRO DE CÓMPUTOS S.I.G.S.M. - ORIENTAL SYSTEMS"
    echo "=========================================================================="
    echo " $(obtener_metricas_rapidas)"
    printf " Servicios: Caddy %s | PHP-FPM %s | MonitorAuto %s | Cockpit %s\n" \
        "$(estado_corto_servicio caddy)" \
        "$(estado_corto_servicio php8.4-fpm)" \
        "$(estado_corto_servicio sigsm-monitor.timer)" \
        "$(estado_corto_servicio cockpit.socket)"
    echo "=========================================================================="
    echo "  1) Gestión de Usuarios, Grupos y Sudoers (ABM + PoLP)"
    echo "  2) Gestión de Servicios Esenciales del Sistema (Systemd / Stack Web)"
    echo "  3) Gestión de Respaldos (Corto 30d, Largo Plazo 70d SHA-256 y Rsync)"
    echo "  4) Gestión de Base de Datos MariaDB (Estado, Usuarios, Dump y Restore)"
    echo "  5) Gestión de Redes y Conectividad (IP Estática, Netplan, DNS, Ping)"
    echo "  6) Gestión de Firewall Perimetral y Puertos en Escucha (UFW / SS)"
    echo "  7) Gestión de Logs y Auditoría Forense (Journalctl / Incidentes)"
    echo "  8) Gestión de Contenedores Docker / Compose"
    echo "  9) Monitoreo Automático de Umbrales (Disco/RAM/CPU) y Cockpit"
    echo "  0) Salir"
    echo "=========================================================================="
}

main() {
    inicializar_entorno

    # Procesar banderas de ejecución desatendida (Cron / Systemd Timer / CLI)
    case "${1:-}" in
        --monitor|--auto)
            ejecutar_monitoreo_umbrales "auto"
            exit $?
            ;;
        --backup|--auto-backup)
            ejecutar_backup_completo_desatendido
            exit 0
            ;;
        --instalar)
            instalar_monitoreo_automatico
            exit 0
            ;;
    esac

    log_evento "$LOG_GENERAL" "Consola de Operador de Centro de Cómputos iniciada."
    local opcion
    while true; do
        mostrar_menu_principal
        read -rp "Seleccione un módulo [0-9]: " opcion
        case "$opcion" in
            1) submenu_usuarios ;;
            2) submenu_servicios ;;
            3) submenu_respaldos ;;
            4) submenu_bd ;;
            5) submenu_redes ;;
            6) submenu_firewall ;;
            7) submenu_logs ;;
            8) submenu_docker ;;
            9) submenu_monitoreo ;;
            0)
                log_evento "$LOG_GENERAL" "Consola de Operador finalizada por usuario."
                echo "Saliendo del Centro de Cómputos S.I.G.S.M..."
                exit 0
                ;;
            *)
                echo "Opción inválida."
                pausa
                ;;
        esac
    done
}

main "$@"

