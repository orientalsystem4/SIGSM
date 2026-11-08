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

El instalador verifica los requisitos, genera `.env` con una clave de base de datos aleatoria, crea la carpeta de documentos, levanta los contenedores, prepara la estructura con el seeder, crea el administrador (pide la clave o la genera) y configura Apache como proxy inverso. Al final muestra la URL, el usuario y la clave una sola vez. Se puede ejecutar de nuevo sin cambiar la clave de la base ni la del administrador existente.

### Instalación desde pendrive

Formatear el pendrive en ext4 (en FAT32/exFAT se pierden permisos y dueño) y copiar el proyecto desde Linux:

```sh
git archive --format=tar.gz -o sigsm.tar.gz HEAD   # no copiar el directorio de trabajo: contiene .env con claves reales
docker save php:8.4-apache mariadb:10.11 | gzip > imagenes.tar.gz
```

En el servidor: extraer `sigsm.tar.gz` y dejar `imagenes.tar.gz` en la carpeta del proyecto, en `bin/` o junto a la carpeta del proyecto; el instalador lo detecta y lo carga con `docker load`. Luego ejecutar `sudo bash bin/instalar.sh`.

## Instalación manual (respaldo y referencia)

1. Copiar `.env.example` a `.env` y completar `DB_PASS` con una clave propia. No subir `.env` a Git.
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

   En una instalación nueva, Docker importa `src/documentacion/sigsm.sql` al crear el volumen de datos. Los siguientes reinicios conservan la base.

4. Preparar la estructura y los datos iniciales (se puede repetir sin duplicar datos; no crea usuarios ni restablece contraseñas):

   ```sh
   docker compose exec -T web php bin/seeder.php
   ```

   Verifica las columnas de solicitudes (incluida `direccion_domicilio`) y usuarios, la tabla `intento_login` (límite de intentos de inicio de sesión), la ubicación genérica «Domicilio del paciente» con sus rutas hacia los hospitales 1, 2 y 3, las siete categorías y encuestas, las nueve preguntas y las rutas entre las ubicaciones 1, 2 y 3, y actualiza los nombres de los roles 2, 3 y 6. Las rutas iniciales no incluyen distancias ni tiempos oficiales. La búsqueda de pacientes por cédula consulta la tabla local `paciente`.

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

No importar nuevamente el SQL completo sobre una base en uso. Los PDF viven en `/var/lib/sigsm/documentos` y no se ven afectados por la actualización. Los cambios de estructura del seeder no se revierten si luego falla la carga de datos; el script informa el error y puede repetirse después de corregirlo.

## Respaldo y restauración

Base de datos y documentos:

```sh
docker compose exec -T db sh -c 'mariadb-dump -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > respaldo_sigsm.sql
sudo tar -czf documentos.tar.gz -C /var/lib/sigsm documentos
```

Restaurar (sobre una instalación con la base ya creada):

```sh
docker compose exec -T db sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < respaldo_sigsm.sql
sudo tar -xzf documentos.tar.gz -C /var/lib/sigsm
sudo chown -R 33:33 /var/lib/sigsm/documentos
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
| Respaldos | No se respaldan (datos de prueba) | Automáticos: `operador-sigsm --backup` todos los días a las 03:00 UTC (00:00 en Montevideo) en `/var/backups/sigsm/`; a los 30 días pasan a largo plazo y se borran a los 70 | Manuales con los comandos de *Respaldo y restauración*; los PDF están en `/var/lib/sigsm/documentos` |

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
| `docker stats` | CPU, memoria y red de cada contenedor, con su límite (`--no-stream` para una sola lectura) |

Ejemplo de `docker stats --no-stream` con los límites del compose (columna `MEM USAGE / LIMIT`):

```
NAME          CPU %     MEM USAGE / LIMIT   MEM %     NET I/O
sigsm-web-1   0.00%     14.21MiB / 512MiB   2.78%     3.27kB / 126B
sigsm-db-1    0.01%     131.7MiB / 768MiB   17.14%    6.01kB / 126B
```

Si en la columna del límite aparece la memoria total del equipo (por ejemplo `15.5GiB`), los límites no se aplicaron: recrear los contenedores con `docker compose up -d`.

**Cockpit** es un panel web del servidor (CPU, memoria, discos, servicios y registros), en el puerto 9090. Para que DTI tenga un acceso directo, definir en el `.env` `COCKPIT_URL=http://IP_DEL_SERVIDOR:9090` y aplicar con `docker compose up -d`: aparece la tarjeta *Monitoreo* en el panel de módulos, solo para el rol DTI. Sin la variable no se muestra. En el servidor de demostración Cockpit ya está instalado y solo se accede por túnel SSH (ver *Configuración manual del servidor*); su instalación no forma parte de este repositorio.

**Monitor del operador** (`bin/operador_sigsm.sh`): con `sudo bash bin/operador_sigsm.sh --instalar` queda como comando global `operador-sigsm` y activa dos timers de systemd:

- `sigsm-monitor.timer`, cada 5 minutos: alerta si el disco supera el **85 %**, la RAM el **80 %** o la CPU el **75 %**, y revisa los servicios esenciales. Las alertas van a `/var/log/alertas_sigsm.log` y al journal con la etiqueta `SIGSM_MONITOR`, así que también se ven en Cockpit (*Registros*).
- `sigsm-backup.timer`, todos los días a las 03:00 UTC: respaldo de la base (resultado en `/var/log/gestion_respaldos.log`).

Este script es la herramienta de operación del servidor de demostración (Caddy + PHP-FPM). Está en adaptación para Docker, para cubrir también la instalación del instituto (Apache + Docker).

**Prueba de carga** (`bin/prueba_carga.sh`): envía peticiones al login (página pública, sin escrituras en la base) y muestra `docker stats` antes, durante y después.

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

## Usuarios

- `cedula` es `VARCHAR(8)`, única y puede ser `NULL`.
- `nombre_completo` admite hasta 150 caracteres y puede ser `NULL`.
- Los usuarios anteriores quedan con ambas columnas vacías hasta completar sus fichas.
- Estas columnas no están en el SQL base: las agrega `bin/seeder.php` (`ADD COLUMN IF NOT EXISTS`), por lo que hay que ejecutarlo en toda instalación.

## Roles

| ID | Rol | Acceso |
| --- | --- | --- |
| 1 | Administrador de DTI | Todos los portales y gestión de usuarios |
| 2 | Administrador de Enfermería | Enfermería y consulta de Unidad de Enlace |
| 3 | Coordinador de Traslados | Unidad de Enlace, asignación y consulta de seguimiento |
| 4 | Funcionario Administrativo de Registros Médicos | Documentos y métricas |
| 5 | Chofer | Seguimiento de sus traslados |
| 6 | Enfermería | Solicitudes, documentos por categoría y encuestas |

Si una cuenta tiene varios roles, elige el rol activo al ingresar. La aprobación especial de solicitudes por parte de Administración de Enfermería no tiene un flujo independiente implementado.

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

- `bin/operador_sigsm.sh` está en adaptación para Docker: hoy cubre el servidor de demostración (Caddy + `php8.4-fpm`) y se extenderá a la instalación del instituto (Apache + Docker).
- No hay HTTPS (red interna, solo HTTP). Si se publica fuera, revisar las cookies de sesión (`secure`, `HttpOnly`).
- La aplicación usa el usuario `root` de la base; a mediano plazo conviene un usuario con privilegios limitados.

## Organización

- `bin/crear_admin.php`: creación o restablecimiento del administrador.
- `bin/seeder.php`: preparación de estructura y datos iniciales.
- `src/documentacion/sigsm.sql`: estructura y datos de partida.
- `src/moduloAmbulancias`: solicitudes, asignación y seguimiento.
- `src/moduloDocumentacion`: documentos, QR y portal de Enfermería.
- `src/moduloEncuestas`: respuestas y métricas.
- `src/usuario`: gestión de cuentas, exclusiva de DTI.
- `src/serviciosComunes`: autenticación, permisos, conexión y estilos compartidos.

La configuración incluida ejecuta PHP 8.4 y MariaDB 10.11 en contenedores, con zona horaria `America/Montevideo`; no requiere PHP ni MariaDB instalados en el servidor. Una base creada con MariaDB 10.4 se actualiza sola al primer arranque (`MARIADB_AUTO_UPGRADE`); respaldarla antes.
