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

- `bin/operador_sigsm.sh` asume Caddy y `php8.4-fpm`; no coincide con el stack instalado (Apache + Docker). Decidir si se adapta o se retira de la entrega.
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
