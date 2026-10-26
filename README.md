# S.I.G.S.M.

Sistema Informático de Gestión de Servicios Médicos. Proyecto de egreso 2026, Oriental System.

## Instalación con Docker

Requiere Git, Docker con Compose y un navegador. En Windows se utiliza Docker Desktop con WSL 2. Visual Studio Code es opcional para editar el proyecto.

### 1. Descargar

```sh
git clone https://github.com/orientalsystem4/SIGSM.git
cd SIGSM
```

### 2. Configurar

Copiar `.env.example` a `.env` (en PowerShell: `Copy-Item .env.example .env`; en Linux: `cp .env.example .env`).

Configurar `DB_HOST=db`, `DB_NAME=sigsm`, `DB_USER=root` y elegir `DB_PASS`. Las variables `SEED_ADMIN_USER` y `SEED_ADMIN_PASS` se utilizan solamente al ejecutar `crear_admin.php` sin argumentos. No subir `.env` a Git.

### 3. Iniciar

```sh
docker compose up -d --build
docker compose ps
docker compose logs --tail=30 db
```

En una instalación nueva, Docker importa `src/documentacion/sigsm.sql` al crear el volumen de datos. Esperar a que finalice la inicialización y MariaDB indique `ready for connections` en el puerto 3306 antes de continuar. Los siguientes reinicios conservan la base y no vuelven a importar el SQL.

### 4. Preparar datos y estructura

```sh
docker compose exec web php bin/seeder.php
```

Verifica las columnas de solicitudes, las siete categorías y encuestas, las nueve preguntas y las rutas entre las ubicaciones 1, 2 y 3. También actualiza los nombres de los roles 2, 3 y 6, conservando sus IDs y permisos. Se puede repetir sin duplicar esos datos. No crea usuarios ni restablece contraseñas. Necesita las tablas del SQL base.

Las rutas iniciales son combinaciones entre los tres puntos de prueba; no incluyen distancias ni tiempos oficiales. La API de pacientes todavía no está integrada: la búsqueda por cédula consulta la tabla local `paciente`.

### 5. Crear el administrador

Elegir un usuario y una contraseña, reemplazando los ejemplos:

```sh
docker compose exec web php bin/crear_admin.php admin_local 'REEMPLAZAR_CON_TU_CLAVE'
```

Si el usuario ya existe, este comando restablece su contraseña, lo activa y agrega el rol DTI. Se ejecuta por separado del seeder y no es parte de una actualización habitual.

### 6. Abrir

- Sistema: http://localhost:8080/SIGSM/serviciosComunes/autenticacion/sesion.php
- phpMyAdmin: http://localhost:8081/

Iniciar sesión con el usuario preparado en el paso anterior. Desde Gestión de Usuarios, DTI puede crear las cuentas necesarias para probar cada rol.

## Actualizar una instalación existente

Comprobar primero que no haya cambios locales pendientes con `git status --short`. Luego, desde la rama que utiliza la instalación:

```sh
git pull --ff-only
docker compose up -d --build
docker compose exec web php bin/seeder.php
```

No importar nuevamente el SQL completo sobre una base en uso. No usar `docker compose down -v` para actualizar: elimina el volumen de la base. Antes de actualizar una instalación compartida, respaldar MariaDB y los archivos de `src/moduloDocumentacion/Vista/documentos`.

Los cambios de estructura del seeder no se revierten si luego falla la carga de datos; el script informa el error y puede repetirse después de corregirlo.

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

Los QR de documentación llevan al listado público de documentos activos de una categoría. El enlace usa el dominio desde el cual se abre el sistema. Un QR generado en `localhost` no funciona desde otro dispositivo: para una prueba con teléfono usar el servidor publicado o una dirección de la laptop accesible por la red local.

Los PDF cargados y los datos creados localmente no se sincronizan automáticamente con GitHub ni con el servidor. Los documentos públicos deben contener información destinada a consulta pública. Los QR, gráficos e iconos actuales usan servicios externos y necesitan acceso a Internet.

## Organización

- `bin/crear_admin.php`: creación o restablecimiento del administrador.
- `bin/seeder.php`: preparación de estructura y datos iniciales.
- `src/documentacion/sigsm.sql`: estructura y datos de partida.
- `src/moduloAmbulancias`: solicitudes, asignación y seguimiento.
- `src/moduloDocumentacion`: documentos, QR y portal de Enfermería.
- `src/moduloEncuestas`: respuestas y métricas.
- `src/usuario`: gestión de cuentas, exclusiva de DTI.
- `src/serviciosComunes`: autenticación, permisos, conexión y estilos compartidos.

La configuración incluida ejecuta PHP 8.0 y MariaDB 10.4. La instalación y las migraciones se realizan con Docker; no requieren PHP ni MariaDB instalados por separado en la computadora.
