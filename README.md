# S.I.G.S.M.

Sistema Informático de Gestión de Servicios Médicos. Proyecto de egreso 2026, Oriental System.

## Requisitos del servidor

- Debian con Apache (`apache2`), Docker y Docker Compose (plugin `docker compose`), `openssl` y `git`.
- Puertos: Apache escucha en el 80 (acceso de los usuarios) y el 22 (administración). Los puertos 8080 (aplicación), 3306 (base) y 8081 (phpMyAdmin) quedan ligados a `127.0.0.1` y no son accesibles desde la red.
- Carpeta de documentos `/var/lib/sigsm/documentos`: dueño `www-data` (uid/gid 33) y permisos `750`. El instalador la crea.
- Los archivos `.sh`, `.php` y `.sql` deben tener finales de línea LF (lo fuerza `.gitattributes`). No copiar el proyecto con el explorador de Windows.

## Instalación en un comando

```sh
git clone https://github.com/orientalsystem4/SIGSM.git
cd SIGSM
sudo bash bin/instalar.sh
```

Invocar siempre con `bash bin/instalar.sh`, no con `./bin/instalar.sh`, para no depender del bit de ejecución.

El instalador verifica los requisitos, genera `.env` con tres claves aleatorias (aplicación, administrador de la base y root; ver [Base de datos](#base-de-datos)), crea la carpeta de documentos, levanta los contenedores, prepara la estructura con el seeder, crea el administrador (pide la clave o la genera) configura Apache como proxy inverso, programa el respaldo diario y el monitoreo, y hace un primer respaldo (si falla, avisa y la instalación continúa). Al final muestra la URL, el usuario y la clave una sola vez, y los usuarios de la base (las claves generadas solo la vez que crea el `.env`). Se puede ejecutar de nuevo sin cambiar las claves de la base ni la del administrador existente.

### Instalación desde pendrive

Formatear el pendrive en ext4 (en FAT32/exFAT se pierden permisos y dueño) y copiar el proyecto desde Linux:

```sh
git archive --format=tar.gz -o sigsm.tar.gz HEAD   # no copiar el directorio de trabajo: contiene .env con claves reales
docker save php:8.4-apache mariadb:10.11 | gzip > imagenes.tar.gz
```

En el servidor: extraer `sigsm.tar.gz` y dejar `imagenes.tar.gz` en la carpeta del proyecto, en `bin/` o junto a la carpeta del proyecto; el instalador lo detecta y lo carga con `docker load`. Luego ejecutar `sudo bash bin/instalar.sh`.

## Instalación manual (respaldo y referencia)

1. Copiar `.env.example` a `.env` y completar `DB_PASS`, `DB_ADMIN_PASS` y `DB_ROOT_PASS` con tres claves distintas (por ejemplo `openssl rand -base64 24 | tr -d '/+='`). Dejarlo con `chmod 600 .env`. No subir `.env` a Git.
2. Crear la carpeta de documentos y copiar el `.htaccess` (que bloquea el acceso directo por URL):

   ```sh
   sudo install -d -o 33 -g 33 -m 750 /var/lib/sigsm/documentos
   sudo install -o 33 -g 33 -m 640 src/moduloDocumentacion/Vista/documentos/.htaccess /var/lib/sigsm/documentos/.htaccess
   ```

3. Iniciar y esperar a que `db` figure como `healthy`:

   ```sh
   docker compose up -d --build
   docker compose ps
   ```

   En una instalación nueva, al crear el volumen de datos la imagen de MariaDB ejecuta, en orden, `src/documentacion/sigsm.sql` (montado como `10-sigsm.sql`: estructura, datos de partida y vistas) y `bin/usuarios_bd.sh` (montado como `20-usuarios.sh`: crea `sigsm_admin` y `sigsm_app`). Los siguientes reinicios conservan la base y no repiten esos pasos.

4. Preparar la estructura y los datos iniciales (se puede repetir sin duplicar datos; no crea usuarios ni restablece contraseñas):

   ```sh
   docker compose exec -T web php bin/seeder.php
   ```

   Verifica las columnas de solicitudes (incluida `direccion_domicilio`) y usuarios, la tabla `intento_login` (límite de intentos de inicio de sesión), la ubicación genérica «Domicilio del paciente» con sus rutas hacia los hospitales 1, 2 y 3, las siete categorías y encuestas, las nueve preguntas, la tabla `encuesta_pregunta` (las 63 combinaciones de las 7 encuestas con las 9 preguntas) y las rutas entre las ubicaciones 1, 2 y 3, actualiza los nombres de los roles 2, 3 y 6, y crea o actualiza las vistas (`CREATE OR REPLACE`). Usa el administrador de la base (`DB_ADMIN_USER`). Si la base tenía la columna anterior `pregunta.id_encuesta`, el seeder la quita (junto con su clave foránea) solo después de confirmar las 63 combinaciones; en cualquier ejecución posterior no cambia nada. Las rutas iniciales no incluyen distancias ni tiempos oficiales. La búsqueda de pacientes por cédula consulta la tabla local `paciente`.

5. Crear el administrador. Si el usuario ya existe, este comando restablece su contraseña, lo activa y agrega el rol DTI:

   ```sh
   docker compose exec -T web php bin/crear_admin.php admin_local 'REEMPLAZAR_CON_TU_CLAVE'
   ```

6. Configurar Apache como proxy inverso (lo que hace el instalador):

   ```sh
   sudo a2enmod proxy proxy_http headers
   ```

   Crear `/etc/apache2/sites-available/sigsm.conf`:

   ```apache
   <VirtualHost *:80>
       ServerName sigsm.local
       ProxyPreserveHost On
       ProxyPass        / http://127.0.0.1:8080/
       ProxyPassReverse / http://127.0.0.1:8080/
       RequestHeader set X-Forwarded-Proto "http"
       LimitRequestBody 20971520
   </VirtualHost>
   ```

   Luego `sudo a2dissite 000-default && sudo a2ensite sigsm && sudo apachectl configtest` y, solo si la prueba es correcta, `sudo systemctl reload apache2`. `ProxyPreserveHost On` es obligatorio: los QR usan el dominio desde el que se abre el sistema; sin él apuntarían a `localhost:8080`.

7. Abrir `http://IP_DEL_SERVIDOR/`.

phpMyAdmin no se inicia por defecto. Para depurar: `docker compose --profile debug up -d phpmyadmin` y acceder por túnel SSH al puerto 8081 (`ssh -L 8081:127.0.0.1:8081 usuario@servidor`). Bajarlo después con `docker compose --profile debug stop phpmyadmin`.

Todos los servicios usan `restart: unless-stopped`: vuelven solos al reiniciar el servidor.

## Actualizar una instalación existente

Comprobar primero que no haya cambios locales pendientes con `git status --short`. Luego:

```sh
git pull --ff-only
docker compose up -d --build
docker compose exec -T web php bin/seeder.php
```

El seeder es obligatorio después de cada actualización: el código consulta vistas que crea el seeder en las bases existentes; sin él fallan Unidad de Enlace, el seguimiento y las métricas.

Si la instalación es anterior a los usuarios de base de datos (`.env` con `DB_USER=root`), sigue funcionando igual. Para pasar a `sigsm_app` y `sigsm_admin`, ver [Crear los usuarios en una base existente](#crear-los-usuarios-en-una-base-existente).

No importar nuevamente el SQL completo sobre una base en uso. Los PDF viven en `/var/lib/sigsm/documentos` y no se ven afectados por la actualización. Los cambios de estructura del seeder no se revierten si luego falla la carga de datos; el script informa el error y puede repetirse después de corregirlo.

## Respaldo y restauración

### Respaldo automático

`bin/instalar.sh` programa `sigsm-respaldo.timer`, que ejecuta `bin/respaldar.sh` todos los días a las 03:00 hora de Uruguay. Si el equipo estaba apagado a esa hora, el respaldo se hace al encenderlo (`Persistent=true`). Cada ejecución genera:

| Qué | Dónde |
| --- | --- |
| Base de datos (`mariadb-dump --single-transaction`, comprimido) | `/var/backups/sigsm/databases/db_sigsm_FECHA.sql.gz` |
| PDF subidos | `/var/backups/sigsm/documentos/documentos_FECHA.tar.gz` |
| Registro (fecha, `[OK]` o `[ERROR]` y el motivo) | `/var/log/respaldos_sigsm.log` |

Se conservan 14 días: las copias más antiguas se borran solo después de un respaldo correcto. Si el volcado queda vacío o el archivo está dañado (`gzip -t`), el script registra `[ERROR]` y termina con un código distinto de 0. El volcado se hace con `sigsm_admin` (o con `DB_USER` si no hay `DB_ADMIN_USER`); la clave se lee del `.env` y no aparece en la línea de comandos (no se ve con `ps`). El volcado incluye las vistas, sin su `DEFINER`, para que se pueda restaurar con cualquier usuario con permisos sobre la base; el registro indica cuántas tablas y vistas contiene.

Comandos útiles:

```sh
sudo bash bin/respaldar.sh                 # respaldo manual inmediato
systemctl list-timers sigsm-respaldo.timer # próxima ejecución
tail /var/log/respaldos_sigsm.log          # últimos resultados
```

Los respaldos quedan en el mismo equipo: si el disco falla, se pierden junto con el sistema. Conviene copiar periódicamente `/var/backups/sigsm` a otro medio.

### Comprobar un respaldo sin tocar la base real

Desde la carpeta del proyecto. El volcado incluye `CREATE DATABASE` y `USE`; se quitan para cargarlo en una base de prueba. Crear otra base requiere root (`sigsm_admin` solo tiene permisos sobre `sigsm`). En una instalación anterior sin `DB_ROOT_PASS`, la clave de root es `DB_PASS`:

```sh
export MYSQL_PWD="$(grep '^DB_ROOT_PASS=' .env | cut -d= -f2-)"
ULTIMO=$(ls -t /var/backups/sigsm/databases/db_sigsm_*.sql.gz | head -1)
docker compose exec -T -e MYSQL_PWD db mariadb -uroot -e "CREATE DATABASE sigsm_prueba"
gzip -dc "$ULTIMO" | grep -v -E '^(CREATE DATABASE|USE )' | docker compose exec -T -e MYSQL_PWD db mariadb -uroot sigsm_prueba
docker compose exec -T -e MYSQL_PWD db mariadb -uroot -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='sigsm_prueba'; SELECT COUNT(*) FROM sigsm_prueba.usuario"
docker compose exec -T -e MYSQL_PWD db mariadb -uroot -e "DROP DATABASE sigsm_prueba"
unset MYSQL_PWD
```

Los números deben coincidir con los de la base `sigsm`. Para comprobar también las vistas: `SELECT COUNT(*) FROM information_schema.views WHERE table_schema='sigsm_prueba'` (deben ser 3).

### Restaurar

Detener la aplicación para que nadie escriba durante la restauración. El volcado reemplaza las tablas y vistas de la base `sigsm`. Se restaura con root (en una instalación anterior sin `DB_ROOT_PASS`, la clave de root es `DB_PASS`), porque los respaldos hechos antes de este cambio todavía traen el `DEFINER` de las vistas:

```sh
docker compose stop web
export MYSQL_PWD="$(grep '^DB_ROOT_PASS=' .env | cut -d= -f2-)"
gzip -dc /var/backups/sigsm/databases/db_sigsm_FECHA.sql.gz | docker compose exec -T -e MYSQL_PWD db mariadb -uroot
unset MYSQL_PWD
sudo tar -xzf /var/backups/sigsm/documentos/documentos_FECHA.tar.gz -C /var/lib/sigsm
sudo chown -R 33:33 /var/lib/sigsm/documentos
sudo chmod 750 /var/lib/sigsm/documentos
docker compose start web
```

**Advertencia:** `docker compose down -v` elimina el volumen de la base de datos y borra todos los datos. Para detener los servicios usar `docker compose down` (sin `-v`).

## Entornos

SIGSM corre en tres entornos. **El código es el mismo en los tres**: lo que cambia es la configuración (el archivo `.env` y el software del servidor), nunca los archivos de `src/`.

| | Desarrollo | Servidor de demostración (Orientalsystem) | Producción (instituto) |
| --- | --- | --- | --- |
| Equipo | PC de cada programador | Servidor Linux del equipo | Servidor y PC del instituto |
| Servidor web | Apache del contenedor `web`, en `http://localhost:8080` | Caddy + PHP-FPM 8.4 instalados en el sistema | Apache del sistema como proxy inverso hacia el contenedor `web` (puerto 80) |
| Base de datos | MariaDB 10.11 en el contenedor `db` | MariaDB en un contenedor de Podman | MariaDB 10.11 en el contenedor `db` |
| Cómo se instala | `docker compose up -d --build` (ver *Instalación manual*) | Configuración manual del administrador | `sudo bash bin/instalar.sh` |
| Cómo se despliega | Trabajo diario en la rama `santi` | `git pull` en el servidor | `git pull` + `docker compose up -d --build` |
| Respaldos | No se respaldan (datos de prueba) | Automáticos: `operador-sigsm --backup` todos los días a las 03:00 UTC (00:00 en Montevideo) en `/var/backups/sigsm/`; a los 30 días pasan a largo plazo y se borran a los 70 | Automáticos: `sigsm-respaldo.timer` todos los días a las 03:00 (hora de Uruguay) en `/var/backups/sigsm/{databases,documentos}`; se conservan 14 días |

Qué cambia en el `.env` según el entorno (sin valores reales; las claves nunca se suben a Git):

| Variable | Desarrollo / producción (Docker) | Demostración |
| --- | --- | --- |
| `DB_HOST` | `db` (nombre del servicio en `docker-compose.yml`) | La dirección del contenedor de MariaDB en Podman (con `DB_PORT`), en `/srv/app/SIGSM/.env` |
| `DB_PASS` | La genera `bin/instalar.sh` (o se elige a mano en desarrollo) | La define el administrador |
| `COCKPIT_URL` | Opcional: `http://IP_DEL_SERVIDOR:9090` | Opcional |

El servidor de demostración no se puede reproducir solo con este repositorio: lo configuró el administrador a mano (Caddy, PHP-FPM, Podman, credenciales en `/etc/orientalsistem/db.env`). Su configuración se resume en *Configuración manual del servidor*.

## Despliegue

Hoy el despliegue es manual:

- **Servidor de demostración:** se entra por SSH y se ejecuta `git pull --ff-only` en la carpeta del proyecto. Como PHP-FPM lee los archivos del proyecto, el cambio normalmente queda activo sin reiniciar servicios. Si el cambio trae estructura nueva, se ejecuta además `php bin/seeder.php`.
- **Instituto:** la primera vez, `sudo bash bin/instalar.sh` (ver *Instalación en un comando*). Para actualizar, los pasos de *Actualizar una instalación existente*.

En los dos casos el código llega a `main` por un PR desde `santi` y recién después se baja al servidor.

**CI/CD** (integración y despliegue continuos) sería automatizar ese recorrido: que cada PR ejecute solo las comprobaciones (sintaxis de PHP, `bin/verificar_idiomas.php`, una instalación de prueba en contenedores) y que, al fusionar en `main`, el servidor se actualice sin que nadie entre por SSH. Todavía no lo usamos porque el servidor del instituto está en una red interna sin acceso desde GitHub, el equipo es chico y cada despliegue se verifica a mano con la *Prueba funcional de instalación*. Es el paso siguiente natural cuando haya un servidor de pruebas accesible.

## Monitoreo

Comandos básicos del servidor (Linux):

| Comando | Qué muestra |
| --- | --- |
| `top` | Procesos y uso de CPU y memoria en tiempo real (`q` para salir) |
| `free -h` | Memoria RAM usada y libre |
| `df -h` | Espacio en disco por partición |
| `docker stats` | CPU, memoria y red de cada contenedor, con su límite (`--no-stream` para una sola lectura). Solo en la instalación con Docker (instituto y desarrollo), no en el servidor de demostración |

Ejemplo de `docker stats --no-stream` con los límites del compose (columna `MEM USAGE / LIMIT`):

```
NAME          CPU %     MEM USAGE / LIMIT   MEM %     NET I/O
sigsm-web-1   0.00%     14.21MiB / 512MiB   2.78%     3.27kB / 126B
sigsm-db-1    0.01%     131.7MiB / 768MiB   17.14%    6.01kB / 126B
```

Si en la columna del límite aparece la memoria total del equipo (por ejemplo `15.5GiB`), los límites no se aplicaron: recrear los contenedores con `docker compose up -d`.

**Cockpit** es un panel web del servidor (CPU, memoria, discos, servicios y registros), en el puerto 9090. Para que DTI tenga un acceso directo, definir en el `.env` `COCKPIT_URL=http://IP_DEL_SERVIDOR:9090` y aplicar con `docker compose up -d`: aparece la tarjeta *Monitoreo* en el panel de módulos, solo para el rol DTI. Sin la variable no se muestra. En el servidor de demostración Cockpit ya está instalado y solo se accede por túnel SSH (ver *Configuración manual del servidor*); su instalación no forma parte de este repositorio.

**Cómo mostrar Cockpit en el servidor de demostración**

Cockpit no es público: solo se llega por un túnel SSH desde la PC del administrador.

1. **Abrir el túnel** (en una terminal; queda abierta, sin imprimir nada):

   ```sh
   ssh -i ~/.ssh/CLAVE_PRIVADA -N -L 9090:127.0.0.1:9090 USUARIO@IP_DEL_SERVIDOR
   ```

2. **Abrir Cockpit** en el navegador: `http://localhost:9090/cockpit/` (usar `localhost`, no `127.0.0.1`). Se entra con el usuario habilitado en el servidor; `root` está vetado. La pantalla *Overview* muestra los gráficos de CPU y memoria.

3. **Generar carga** desde otra terminal, contra la página pública del login (sin escrituras en la base):

   ```sh
   for i in $(seq 1 500); do curl -s -o /dev/null https://DOMINIO/SIGSM/serviciosComunes/autenticacion/sesion.php; done
   ```

   Mientras corre, el gráfico de CPU de Cockpit sube y vuelve a bajar al terminar. Si apenas se mueve, aumentar el número de peticiones o lanzar el bucle en varias terminales. Es producción: hacerlo con poca gente usando el sistema.

4. **Cerrar** el túnel con `Ctrl+C`.

Si algo falla: `Permission denied (publickey)` indica clave incorrecta o falta de `-i`; "no se puede conectar" indica que el túnel no está abierto; `address already in use` indica que quedó otro túnel en el puerto 9090.

En Cockpit del servidor la memoria total corresponde al equipo físico, no al límite real del contenedor: el límite lo aplica el proveedor por fuera.

`bin/prueba_carga.sh` y `docker stats` no sirven en el servidor de demostración, porque ahí no se usa Docker; son para la instalación del instituto.

**Monitor del operador** (`bin/operador_sigsm.sh`, solo en el servidor de demostración): con `sudo bash bin/operador_sigsm.sh --instalar` queda como comando global `operador-sigsm` y activa dos timers de systemd:

- `sigsm-monitor.timer`, cada 5 minutos: alerta si el disco supera el **85 %**, la RAM el **80 %** o la CPU el **75 %**, y revisa los servicios esenciales. Las alertas van a `/var/log/alertas_sigsm.log` y al journal con la etiqueta `SIGSM_MONITOR`, así que también se ven en Cockpit (*Registros*).
- `sigsm-backup.timer`, todos los días a las 03:00 UTC: respaldo de la base (resultado en `/var/log/gestion_respaldos.log`).

El servidor de demostración (Caddy + PHP-FPM) usa este operador. No se instala en el instituto.

**Monitor de la instalación Docker** (`bin/monitorear.sh`, en el instituto): `bin/instalar.sh` programa `sigsm-monitor-docker.timer`, que lo ejecuta cada 5 minutos. Revisa:

| Qué | Alerta cuando |
| --- | --- |
| Contenedores `web` y `db` (`docker compose ps`) | Alguno no está en ejecución, o Docker no responde |
| Aplicación (`curl` a `http://127.0.0.1:8080/`) | No responde 200 ni 302 |
| CPU y RAM de cada contenedor (`docker stats`, sobre su propio límite) | CPU > **75 %** o RAM > **80 %** |
| Disco de `/` y de `/var/backups` | Ocupación > **85 %** |
| Último respaldo de la base (`/var/backups/sigsm/databases`) | No hay, o tiene 26 horas o más |

Solo escribe cuando algo falla: en `/var/log/alertas_sigsm.log`, con el mismo formato que el operador, y en el journal con la etiqueta `SIGSM_MONITOR` (visible en Cockpit, *Registros*). Termina con código 0 si todo está bien y 1 si hubo alguna alerta. Para revisarlo a mano:

```sh
sudo bash bin/monitorear.sh; echo $?       # sin salida y 0 = todo bien
tail /var/log/alertas_sigsm.log            # alertas registradas
journalctl -t SIGSM_MONITOR --since today  # las mismas, en el journal
```

Las unidades de este monitor se llaman `sigsm-monitor-docker.*`, distintas de las `sigsm-monitor.*` del operador, así que no se pisan si alguien instala los dos.

**Prueba de carga** (`bin/prueba_carga.sh`, solo instalación con Docker; en el servidor de demostración ver el procedimiento con Cockpit): envía peticiones al login (página pública, sin escrituras en la base) y muestra `docker stats` antes, durante y después.

```sh
bash bin/prueba_carga.sh          # 500 peticiones al login
bash bin/prueba_carga.sh 1000     # 1000 peticiones
```

No usarla contra páginas con sesión ni que guarden datos. En una prueba local, 1000 peticiones tardaron unos 7 s y la CPU del contenedor `web` subió a cerca del 9 % (las peticiones van de a una, así que es una carga liviana).

## Escalabilidad

**Vertical (lo que se aplica hoy):** darle más recursos a la misma máquina. `docker-compose.yml` fija un tope por contenedor para que uno solo no agote el servidor:

| Servicio | CPU | Memoria |
| --- | --- | --- |
| `web` | 1 CPU | 512 MB |
| `db` | 1 CPU | 768 MB |

Para subirlos, editar `deploy.resources.limits` (`cpus` y `memory`) del servicio en `docker-compose.yml`, aplicar con `docker compose up -d` y comprobar con `docker stats --no-stream`. Antes de subirlos, verificar con `free -h` que el servidor tenga esa memoria libre. Si un proceso del contenedor supera la memoria asignada, el sistema lo termina (en `docker inspect` figura `OOMKilled`); si era el proceso principal, Docker reinicia el contenedor por `restart: unless-stopped`.

**Horizontal (hoy no):** sería correr varias copias del contenedor `web` detrás de un balanceador. SIGSM todavía no lo admite porque:

- **Sesiones en archivos locales:** cada contenedor guarda las sesiones de PHP en su propio disco; si una petición llega a otra copia, el usuario pierde la sesión.
- **PDF en disco local:** los documentos están en `/var/lib/sigsm/documentos` de un solo servidor.
- **Una sola base:** todas las copias dependen del mismo MariaDB, que sería el límite.
- **Puerto fijo:** el contenedor `web` publica `127.0.0.1:8080`, y dos copias no pueden usar el mismo puerto.

Para lograrlo habría que guardar las sesiones en la base o en Redis, mover los PDF a un almacenamiento compartido (una carpeta de red o un servicio de objetos), quitar el puerto fijo y poner un balanceador delante (el mismo Apache del servidor puede cumplir ese papel). Con el uso actual del instituto no hace falta: alcanza con la escalabilidad vertical.

## Configuración manual del servidor

Configuración del servidor de demostración, según el informe del administrador. No incluye claves ni direcciones reales.

| Elemento | Configuración |
| --- | --- |
| Carpeta `documentos/` | Dueño `www-data:www-data`, modo `750` |
| PHP-FPM | `/etc/php/8.4/fpm/conf.d/90-sigsm.ini` con `upload_max_filesize=15M`, `post_max_size=20M` y `date.timezone=America/Montevideo` |
| Variables de la aplicación | Archivo `/srv/app/SIGSM/.env` con `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` (no por el entorno de PHP-FPM). Queda fuera de la carpeta web |
| Variables de la base | `/etc/orientalsistem/db.env`, con `MARIADB_ROOT_PASSWORD`, `MARIADB_DATABASE`, `MARIADB_USER` y `MARIADB_PASSWORD` (los valores no se publican) |
| Respaldo diario | `sigsm-backup.timer`, 03:00 UTC. Destino `/var/backups/sigsm/{aplicaciones,databases,sistema,largo_plazo}`; a los 30 días pasan a largo plazo y se borran a los 70 |
| Monitoreo | `sigsm-monitor.timer`, cada 5 minutos |
| Cockpit | `/etc/cockpit/cockpit.conf` con `UrlRoot=/cockpit`, `ProtocolHeader` y `Origins`. Acceso solo por túnel SSH |
| Caddy | Una regla responde 404 a las rutas privadas (`/SIGSM/documentacion/` y la carpeta de PDF) y otra responde 404 a `/cockpit`, para que Cockpit nunca quede público (ver el Caddyfile abajo) |

Caddyfile del servidor de demostración (hosts genéricos):

```caddyfile
{
    admin off
}

:8080 {
    encode zstd gzip

    route {
        # Rutas privadas: nunca se sirven directamente (para todos los hosts)
        @privado path /SIGSM/documentacion/* /SIGSM/moduloDocumentacion/Vista/documentos/*
        respond @privado 404

        # Cockpit: solo por túnel SSH (127.0.0.1:9090), nunca público
        @cockpit path /cockpit /cockpit/*
        respond @cockpit 404

        # Web institucional de la empresa
        @empresa host ejemplo.com www.ejemplo.com
        handle @empresa {
            root * /srv/app/public/page
            file_server
        }

        # Plataforma médica SIGSM
        @sistema host sigsm.ejemplo.com
        handle @sistema {
            root * /srv/app/public
            redir / /SIGSM/serviciosComunes/autenticacion/sesion.php
            php_fastcgi unix//run/php/php8.4-fpm.sock
            file_server
        }

        # Fallback general
        handle {
            root * /srv/app/public
            php_fastcgi unix//run/php/php8.4-fpm.sock
            file_server
        }
    }
}
```

El VPS tiene además otro Caddy en el host, que funciona como proxy de borde con TLS. No forma parte de este repositorio.

## Encuestas

Hay 7 encuestas (una por área médica) y 9 preguntas comunes: las ocho primeras son de escala del 1 al 5 y la novena es el comentario libre. La tabla `encuesta_pregunta (id_encuesta, id_pregunta)` indica qué preguntas usa cada encuesta: 7 × 9 = 63 filas, con clave primaria compuesta y claves foráneas a `encuesta` y `pregunta`. Antes, cada pregunta figuraba en una sola encuesta (`pregunta.id_encuesta = 1`), con `ON DELETE CASCADE`: borrar esa encuesta habría borrado las nueve preguntas para todas las demás.

- **Instalación nueva:** `src/documentacion/sigsm.sql` ya crea `encuesta_pregunta` con las 63 filas y la tabla `pregunta` sin `id_encuesta`.
- **Base existente:** `bin/seeder.php` crea la tabla, inserta las combinaciones y quita la columna anterior. Es el mismo paso de *Actualizar una instalación existente*; antes de actualizar conviene respaldar con `bin/respaldar.sh`.
- **Al responder:** `GuardarEncuesta.php` solo acepta preguntas que pertenecen a esa encuesta según `encuesta_pregunta`; si la encuesta no tiene preguntas, responde «La encuesta no está disponible».
- **Instalador:** después del seeder comprueba que haya 63 filas; si no, muestra una `[ADVERTENCIA]` y sigue.

## Usuarios

- `cedula` es `VARCHAR(8)`, única y puede ser `NULL`.
- `nombre_completo` admite hasta 150 caracteres y puede ser `NULL`.
- Los usuarios anteriores quedan con ambas columnas vacías hasta completar sus fichas.
- Una base nueva ya trae estas columnas en `sigsm.sql`; en una base existente las agrega `bin/seeder.php` (`ADD COLUMN IF NOT EXISTS`).

## Roles

Roles de la aplicación (tabla `rol`). El acceso se controla en cada página con `requiereRol([...])` (`src/serviciosComunes/seguridad/guardian.php`); DTI (rol 1) figura en todas las listas. Todos los roles entran al panel de módulos (`serviciosComunes/vistaGeneral/bifurcacion.php`), que solo muestra las tarjetas de su rol.

| ID | Rol | Páginas y acciones |
| --- | --- | --- |
| 1 | Administrador de DTI | Todas. Exclusivo: gestión de usuarios (`usuario/Vista/listado.php`, `crear.php`, `editar.php`, `ver.php` y `usuario/Controlador/ControladorUsuarios.php`), tarjeta de Monitoreo y avance de estado de traslados tercerizados. |
| 2 | Administrador de Enfermería | Portal de Enfermería (`moduloDocumentacion/Vista/enfermeria.php`): crear solicitudes, buscar pacientes, editar y cancelar sus solicitudes (`editarSolicitud.php`, `EditarSolicitud.php`, `CancelarSolicitud.php`); directorio QR (`directorioQR.php`); consulta de Unidad de Enlace (`unidadEnlace.php`), sin la dirección de domicilio. |
| 3 | Coordinador de Traslados | Unidad de Enlace (`unidadEnlace.php`, con dirección de domicilio); asignación (`asignar.php`, `AsignarTraslado.php`); cancelar cualquier solicitud; panel y seguimiento de todos los traslados, solo consulta (`choferes.php`, `seguimientoChofer.php`). |
| 4 | Funcionario Administrativo de Registros Médicos | Documentos (`moduloDocumentacion/Vista/documentacion.php`, `crear.php`, `editar.php`, `ver.php`, `ControladorDocumentos.php`) y métricas de encuestas (`MetricasControlador.php`). |
| 5 | Chofer | Panel y seguimiento de sus traslados (`choferes.php`, `seguimientoChofer.php`); avance de estado de los traslados que tiene asignados (`CambiarEstadoTraslado.php`). |
| 6 | Enfermería | Lo mismo que el rol 2 en el portal de Enfermería y el directorio QR (solicitudes propias), sin Unidad de Enlace. |

Sin sesión (públicas): inicio de sesión (`serviciosComunes/autenticacion/sesion.php`), listado y archivo públicos de documentos (`documentosPublicos.php`, `archivoPublico.php`) y responder encuestas (`moduloEncuestas/Vista/responder.php`, `GuardarEncuesta.php`).

Si una cuenta tiene varios roles, elige el rol activo al ingresar. La aprobación especial de solicitudes por parte de Administración de Enfermería no tiene un flujo independiente implementado.

## Base de datos

### Usuarios de la base

| Usuario | Para qué | Permisos | Por qué |
| --- | --- | --- | --- |
| `sigsm_app` (`DB_USER`) | La aplicación web (PHP) | `SELECT` en toda la base `sigsm` (tablas y vistas). Escritura solo donde el código escribe: `INSERT, UPDATE` en `solicitud_traslado`, `usuario` y `documento`; `INSERT` en `traslado`, `historial_estado`, `respuesta_encuesta` y `respuesta_pregunta`; `INSERT, DELETE` en `usuario_rol` e `intento_login`. Sin permisos de estructura. | Menor privilegio: si alguien abusara de la aplicación, no podría cambiar la estructura, borrar tablas, leer otras bases ni las cuentas de MariaDB (`mysql.user`), ni borrar o modificar datos que el código no modifica (por ejemplo, traslados o historial). |
| `sigsm_admin` (`DB_ADMIN_USER`) | `bin/seeder.php`, `bin/crear_admin.php` y `bin/respaldar.sh` | `ALL PRIVILEGES` solo sobre la base `sigsm` | El seeder crea tablas, columnas y vistas, y el respaldo necesita leer todo; no necesita acceso a otras bases ni a la administración del servidor. |
| `root` (`DB_ROOT_PASS`) | Solo la administración interna del contenedor: crear los usuarios (`bin/usuarios_bd.sh`), bases de prueba y restauraciones | Todos | No lo usan ni la aplicación ni los scripts habituales. phpMyAdmin (perfil `debug`, no arranca solo) entra como root. |

La lista de permisos está en un solo lugar: `bin/usuarios_bd.sh`. Si el código empieza a escribir en otra tabla, hay que agregarla ahí y volver a ejecutarlo. Para ver los permisos: `SHOW GRANTS FOR 'sigsm_app'@'%';`.

**Compatibilidad:** si el `.env` no tiene `DB_ADMIN_USER` (instalaciones anteriores, con `DB_USER=root`), los scripts usan `DB_USER`/`DB_PASS` como antes y la clave de root sigue siendo `DB_PASS`. Nada cambia hasta migrar.

### Crear los usuarios en una base existente

La imagen de MariaDB crea los usuarios solo con el volumen vacío. En una base que ya existe (por ejemplo, una instalación con `DB_USER=root`):

1. Respaldar: `sudo bash bin/respaldar.sh`.
2. Actualizar la estructura (crea las tablas y vistas sobre las que se dan los permisos): `docker compose exec -T web php bin/seeder.php`.
3. Editar `.env` (dejarlo con `chmod 600`):
   - `DB_ROOT_PASS=` el valor que tenía `DB_PASS` (la clave actual de root).
   - `DB_USER=sigsm_app` y `DB_PASS=` una clave nueva.
   - Agregar `DB_ADMIN_USER=sigsm_admin` y `DB_ADMIN_PASS=` otra clave nueva.
4. Crear los usuarios (idempotente: `CREATE USER IF NOT EXISTS`, fija la clave del `.env` y vuelve a dar los permisos desde cero). Al final muestra los permisos:

   ```sh
   bash bin/usuarios_bd.sh
   ```

5. Recrear los contenedores para que la aplicación tome el usuario nuevo: `docker compose up -d`.
6. Comprobar: `docker compose exec -T web php bin/seeder.php`, `sudo bash bin/respaldar.sh` e ingresar a la aplicación.

Para volver atrás, restaurar el `.env` anterior y `docker compose up -d`: root no cambia.

En un entorno sin Docker Compose (por ejemplo, el servidor de demostración con Podman), `bash bin/usuarios_bd.sh --sql` solo imprime el SQL, para ejecutarlo como root. Por ejemplo: `export MYSQL_PWD=...; bash bin/usuarios_bd.sh --sql | podman exec -i -e MYSQL_PWD CONTENEDOR mariadb -uroot`.

### Vistas

Las tres son `SQL SECURITY INVOKER`: se ejecutan con los permisos de quien consulta, no dependen del usuario que las creó y no fallan al restaurar un respaldo en otro servidor. Están en `sigsm.sql` (bases nuevas) y en `bin/seeder.php` (bases existentes), con la misma definición.

| Vista | Reemplaza | La usa |
| --- | --- | --- |
| `v_traslado_detalle` | Los JOIN de traslado con solicitud, origen, destino, tipo de elemento, vehículo, chofer, enfermería, proveedor y último estado (`SeguimientoModelo::listar`) | Unidad de Enlace y Seguimiento de traslados |
| `v_respuestas_encuesta` | El JOIN de `respuesta_pregunta` con `respuesta_encuesta`, más encuesta, categoría, pregunta, valor y fecha (`MetricasControlador.php`) | Métricas por área (portal de Documentos) |
| `v_solicitud_pendiente` | La consulta de solicitudes pendientes sin traslado (`SolicitudModelo::listarPendientes`) | Lista de pendientes de Unidad de Enlace |

`v_traslado_detalle` no incluye la dirección del domicilio (dato personal): `SeguimientoModelo::listar` la agrega desde la solicitud solo si quien consulta puede verla: en Unidad de Enlace, DTI y Coordinador (Administración de Enfermería no); en Seguimiento, quien entra (el chofer solo ve sus propios traslados).

## Validación

Cada formulario se valida dos veces: en el navegador, por comodidad, y en el servidor (PHP), que es la autoridad. El JavaScript copia las reglas del controlador; no agrega reglas propias ni reemplaza las de PHP. Si el JavaScript no carga o se lo saltea (por ejemplo, con `curl`), el servidor rechaza los mismos datos con su propio mensaje.

**Cómo funciona.** Un único script, `src/serviciosComunes/vistaGeneral/assets/js/validacion.js`, sin librerías. Cada formulario se marca con `data-validar-form` y cada campo declara sus reglas con `data-validar="..."` (por ejemplo `requerido cedula`, `bytesmin:12`, `max:500`, `pdf tamano:15728640`). Los mensajes los imprime PHP con `te()` en atributos `data-msg-...`, así salen en el idioma activo (español o inglés); en general son los mismos textos que devuelve el servidor. Valida al salir de cada campo y al enviar; si hay errores no envía, muestra el mensaje debajo del campo (`aria-live`) y lleva el foco al primero. No valida campos opcionales vacíos ni muestra errores antes de que la persona toque el campo. Escucha en `document`, así que también cubre formularios que se recargan por `fetch` (Mis solicitudes) y se ejecuta antes que los manejadores propios del formulario (la solicitud de traslado se envía por `fetch`).

| Formulario | En el navegador | Regla del servidor |
| --- | --- | --- |
| Inicio de sesión (`sesion.php`) | Usuario y contraseña no vacíos. Sin reglas de largo ni formato, para no revelar nada. | `login.php` |
| Crear usuario (`usuario/Vista/crear.php`) | Cédula con el formato y dígito verificador de `normalizarCedula()`; nombre completo de 3 a 150 caracteres (letras, espacios, `.`, `'`, `-`); usuario de 3 a 50 bytes con `[a-zA-Z0-9_.-]`; contraseña de al menos 12 bytes (se mide como `strlen`); confirmación igual; al menos un rol. | `ControladorUsuarios.php` (crear), `ValidacionIdentidad.php` |
| Editar usuario (`usuario/Vista/editar.php`) | Igual que crear, pero cédula y nombre opcionales (cuentas antiguas), salvo que la cuenta ya los tenga: entonces no se pueden borrar. Contraseña opcional; si se escribe, al menos 12 bytes. | `ControladorUsuarios.php` (editar) |
| Seleccionar rol (`seleccionarRol.php`) | Un rol elegido entre los de la cuenta. | `seleccionarRol.php` |
| Solicitud de traslado (`form-traslado`, `enfermeria.php`) | Tipo, origen, destino y canal obligatorios; origen distinto de destino; si es paciente, cédula de 7 u 8 dígitos (admite puntos, guiones y espacios); prioridad `normal`, `alta` o `maxima`; fecha límite futura si se completa; observaciones de hasta 500 caracteres. Que el paciente exista lo sigue comprobando `enfermeria.js` contra el servidor. | `GuardarSolicitud.php` |
| Editar solicitud (`editarSolicitud.php`) | Origen y destino obligatorios y distintos; prioridad válida; fecha límite futura si se completa; observaciones de hasta 500 caracteres. | `EditarSolicitud.php`, `EdicionSolicitudModelo::validar` |
| Cancelar solicitud (Mis solicitudes) | Motivo obligatorio, de hasta 500 caracteres. | `CancelarSolicitud.php` |
| Asignar traslado (`asignar.php`) | Ruta obligatoria; sin proveedor, vehículo y chofer obligatorios (con proveedor quedan vacíos y deshabilitados, `asignar.js`); enfermería obligatoria si es paciente y distinta del chofer; dirección de domicilio obligatoria si el origen o el destino es Domicilio, de hasta 255 caracteres y sin caracteres de control. | `AsignarTraslado.php` |
| Crear documento (`moduloDocumentacion/Vista/crear.php`) | Categoría obligatoria (una de la lista); archivo obligatorio, con extensión `.pdf` y de hasta 15 MB (15.728.640 bytes). | `ControladorDocumentos.php` (crear) |
| Editar documento (`moduloDocumentacion/Vista/editar.php`) | Categoría obligatoria; el archivo es opcional y, si se elige, `.pdf` de hasta 15 MB. | `ControladorDocumentos.php` (editar) |
| Encuesta pública (`responder.php`) | Las 8 preguntas respondidas (1 a 5), con un mensaje por cada pregunta que falta; comentario de hasta 500 caracteres, con contador. | `ValidacionEncuesta.php` |
| Generador de QR (`form-generador`, `enfermeria.php`) | Área obligatoria, de 1 a 7. No se envía al servidor: arma el enlace a la encuesta. | `ValidacionEncuesta.php` (categoría de 1 a 7, al responder) |

Quedan solo en el servidor las reglas que necesitan la base o el contenido del archivo: usuario o cédula ya registrados, paciente inexistente, «Domicilio» solo para pacientes, vehículo o personal ocupados, que el archivo sea realmente un PDF (`finfo`) y los permisos de cada rol. El tipo de archivo que informa el navegador no se usa: sale de la misma extensión y rechazaría archivos que el servidor acepta.

**Pruebas.** Cada formulario se probó en Chromium (Playwright) en español y en inglés: un dato inválido no se envía y muestra el mensaje en el idioma activo; un dato válido se envía y el servidor lo acepta; sin errores de JavaScript en la consola. Para usuarios, solicitudes, asignación, documentos, encuesta y selección de rol se enviaron además datos inválidos directo al servidor (`curl`, sin JavaScript) y el servidor los rechazó con su propio mensaje. No se probó en Firefox ni Safari.

## Prueba funcional de instalación

1. Ingresar como DTI y crear cuentas de prueba de Enfermería, Coordinador, Registros y Chofer.
2. Crear una solicitud con una cédula que exista en la base local; comprobar también el mensaje para una inexistente. Para elementos que no sean pacientes, verificar el formulario correspondiente.
3. Editar una solicitud propia pendiente. Cancelar otra indicando un motivo y comprobar el registro en Unidad de Enlace.
4. Asignar vehículo compatible, chofer, enfermería cuando corresponde y ruta. Intentar reutilizar recursos ocupados y comprobar el rechazo.
5. Con el chofer asignado, registrar inicio, llegada, retorno y finalización. Revisar el historial y sus filtros por cédula y vehículo.
6. Desde Registros, cargar un PDF, editarlo y darlo de baja. Comprobar el listado público de su categoría antes y después de la baja.
7. Responder una encuesta completa y comprobar el total y las métricas de esa categoría.
8. Comprobar que los otros roles no puedan gestionar usuarios ni modificar documentos mediante sus enlaces directos.

## QR y archivos

Los QR de documentación llevan al listado público de documentos activos de una categoría. El enlace usa el dominio desde el cual se abre el sistema; por eso el proxy debe conservar el `Host` (`ProxyPreserveHost On`). Un QR generado en `localhost` no funciona desde otro dispositivo: abrir el sistema por la IP o el nombre del servidor.

Los PDF cargados y los datos creados localmente no se sincronizan con GitHub. Los documentos públicos deben contener información destinada a consulta pública. Los QR, gráficos e iconos actuales usan servicios externos y necesitan acceso a Internet.

## Notas pendientes

- **Traslados tercerizados (limitación conocida):** al asignar, Unidad de Enlace puede elegir un *proveedor externo* (por ejemplo SAME) en lugar de vehículo y chofer propios; se muestra solo como «Tercerizado», sin el nombre del proveedor. Un traslado tercerizado no tiene chofer, y solo DTI (rol 1) o el chofer asignado (rol 5) pueden avanzar su estado, así que **solo DTI puede marcarlo** como en camino, llegado o finalizado. El coordinador (rol 3) no puede hacerlo.
- `bin/operador_sigsm.sh` es la herramienta del servidor de demostración (Caddy + `php8.4-fpm`). La instalación Docker del instituto usa `bin/monitorear.sh` y `bin/respaldar.sh`.
- **Encuesta y teclado:** los botones del 1 al 5 de `responder.php` están ocultos por CSS (`display: none`) y se eligen con el clic sobre el número; con el teclado no se pueden marcar. Si falta una respuesta, la validación lleva la pantalla a esa pregunta.
- **Documento de más de 15 MB sin JavaScript:** PHP lo corta por `upload_max_filesize` antes del controlador, y el mensaje es «Debe seleccionar un archivo PDF» en lugar de «no puede superar los 15 MB». En el navegador el mensaje es el correcto.
- No hay HTTPS (red interna, solo HTTP). Si se publica fuera, revisar las cookies de sesión (`secure`, `HttpOnly`).
- La aplicación usa el usuario `sigsm_app`, con privilegios limitados (ver [Base de datos](#base-de-datos)). Pendientes: el contenedor web recibe todo el `.env` (`env_file`), incluidas `DB_ADMIN_PASS` y `DB_ROOT_PASS`, porque el seeder y `crear_admin.php` corren dentro de él; y la imagen crea `root@'%'`, accesible desde la red interna de Docker (con `MARIADB_ROOT_HOST=localhost` quedaría solo dentro del contenedor, pero phpMyAdmin dejaría de entrar). Las instalaciones anteriores con `DB_USER=root` siguen usando root hasta migrarlas.

## Organización

- `bin/instalar.sh`: instalador completo (incluye el respaldo diario y el monitoreo).
- `bin/respaldar.sh`: respaldo de la base y de los documentos.
- `bin/monitorear.sh`: monitoreo de la instalación Docker.
- `bin/prueba_carga.sh`: prueba de carga sobre el login.
- `bin/crear_admin.php`: creación o restablecimiento del administrador.
- `bin/seeder.php`: preparación de estructura, vistas y datos iniciales (bases existentes).
- `bin/usuarios_bd.sh`: usuarios `sigsm_admin` y `sigsm_app` y sus permisos.
- `src/documentacion/sigsm.sql`: estructura, datos de partida y vistas; alcanza por sí solo para crear una base nueva.
- `src/moduloAmbulancias`: solicitudes, asignación y seguimiento.
- `src/moduloDocumentacion`: documentos, QR y portal de Enfermería.
- `src/moduloEncuestas`: respuestas y métricas.
- `src/usuario`: gestión de cuentas, exclusiva de DTI.
- `src/serviciosComunes`: autenticación, permisos, conexión y estilos compartidos.
- `src/serviciosComunes/vistaGeneral/assets/js/validacion.js`: validación en el navegador, común a todos los formularios (ver [Validación](#validación)).

La configuración incluida ejecuta PHP 8.4 y MariaDB 10.11 en contenedores, con zona horaria `America/Montevideo`; no requiere PHP ni MariaDB instalados en el servidor. Una base creada con MariaDB 10.4 se actualiza sola al primer arranque (`MARIADB_AUTO_UPGRADE`); respaldarla antes.
