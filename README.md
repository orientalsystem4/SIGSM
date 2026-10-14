# S.I.G.S.M. - Sistema Informático de Gestión de Servicios Médicos 🚑

**Proyecto de Egreso - BT Tecnologías de la Información - 2026**  
*I.S.B.O. - Polo Educativo Tecnológico Vista Linda - ITS Paysandú - ESC Villa Muñoz*

S.I.G.S.M. es una plataforma web integral orientada a optimizar la logística de traslados médicos de emergencia, la gestión centralizada de documentación clínica mediante códigos QR (estáticos y dinámicos), el control de acceso de usuarios y la recolección/análisis estadístico de la satisfacción de los pacientes.

🔗 **[Ver Maqueta Estática en Vivo (GitHub Pages)](https://orientalsystem4.github.io/SIGSM/)** *(Demostración exclusivamente de interfaz gráfica frontend)*

---

## 🚀 Requisitos del Sistema

El entorno de desarrollo y despliegue del proyecto está completamente containerizado mediante **Docker**, garantizando paridad de entorno en cualquier sistema operativo.

### Requisitos Oficiales (Docker)
- **Docker Engine** (versión 20.10 o superior)
- **Docker Compose** (versión 2.0 o superior)
- **Navegador web moderno** (Chrome, Firefox, Edge, Safari)

### Alternativa sin Docker (LAMP / XAMPP)
- **PHP** 8.0 o superior (con extensiones `pdo`, `pdo_mysql`, `mysqli`)
- **MariaDB** 10.4+ o **MySQL** 8.0+
- **Servidor Apache** con módulo `mod_rewrite` habilitado
- **phpMyAdmin** (opcional, para inspección visual de la base de datos)

---

## 🐳 Puesta en Marcha con Docker (Recomendado)

El repositorio incluye la configuración de Docker Compose con los servicios de **Apache/PHP 8.0 (`web`)**, **MariaDB 10.4 (`db`)** y **phpMyAdmin (`phpmyadmin`)**.

### 1. Clonar el repositorio
```bash
git clone https://github.com/orientalsystem4/SIGSM.git
cd SIGSM
```

### 2. Configurar variables de entorno
El proyecto incluye un archivo `.env` precargado para desarrollo. Si no existe, créelo a partir de `.env.example`:
```bash
cp .env.example .env
```

### 3. Levantar los contenedores
Inicie los servicios en segundo plano:
```bash
docker compose up -d
```

> **Nota:** La base de datos cargará automáticamente la estructura inicial a partir de `src/documentacion/sigsm.sql` durante el primer inicio.

### 4. Inicializar y sembrar la base de datos (CLI Seeder)
Ejecute el script unificado de inicialización dentro del contenedor web:
```bash
docker compose exec web php bin/seeder.php
```

Este comando idempotente se encarga de:
1. Crear/actualizar el **Superadministrador** con el Rol 1 (`Administrador de DTI`).
2. Verificar las **7 categorías médicas institucionales**.
3. Vincular las **7 encuestas institucionales** a sus respectivas especialidades.
4. Inicializar las **9 preguntas base** del sistema de satisfacción (8 de escala + 1 abierta para sugerencias).

**Credenciales iniciales generadas:**
- **Usuario:** `admin`
- **Contraseña:** `Admin123!`
- **Rol:** `Administrador de DTI` (Acceso total)

### 5. Acceso a las aplicaciones
- **Plataforma S.I.G.S.M:** [http://localhost:8080/SIGSM/](http://localhost:8080/SIGSM/)
- **Gestor phpMyAdmin:** [http://localhost:8081/](http://localhost:8081/)  
  *(Servidor: `db`, Usuario: `root`, Contraseña: la configurada en `DB_PASS` o `root_password`)*
- **Puerto MySQL/MariaDB:** `3306`

---

## 💻 Alternativa: Instalación Local con XAMPP / LAMP

Si desea ejecutar el sistema directamente en su servidor local sin utilizar Docker:

1. **Ubicación:** Clone o copie el proyecto dentro del directorio raíz de su servidor web (por ejemplo `C:\xampp\htdocs\SIGSM` o `/var/www/html/SIGSM`).
2. **Servicios:** Inicie Apache y MySQL desde el panel de control de XAMPP.
3. **Base de Datos:**
   - Ingrese a phpMyAdmin (`http://localhost/phpmyadmin/`).
   - Cree una base de datos denominada `sigsm` con cotejamiento `utf8mb4_unicode_ci`.
   - Importe el volcado base: `src/documentacion/sigsm.sql`.
4. **Sembrado de Datos:**
   Abra una terminal en la raíz del proyecto y ejecute:
   ```bash
   php bin/seeder.php
   ```
5. **Configuración de Conexión:**
   Verifique que los valores de conexión en `.env` o en `src/serviciosComunes/conexionBD/conexion.php` coincidan con sus credenciales locales de MySQL.
6. **Navegación:** Ingrese a [http://localhost/SIGSM/](http://localhost/SIGSM/).

---

## ⌨️ Herramientas de Consola (CLI)

En el directorio [`bin/`](file:///home/nika/Documentos/SIGSM/bin) se disponen herramientas de línea de comandos para tareas administrativas y de mantenimiento:

| Comando | Descripción |
| :--- | :--- |
| `php bin/seeder.php` | **Seeder Unificado:** Ejecuta de manera segura y transaccional la carga de roles, superadministrador, categorías, encuestas y preguntas modelo. |
| `php bin/crear_admin.php [user] [pass]` | **Gestor de Superadmin:** Crea o restablece la contraseña de un usuario con permisos de Administrador de DTI. Permite pasar credenciales personalizadas como argumentos. |

*Para ejecutarlos dentro de Docker, anteponga `docker compose exec web` (ejemplo: `docker compose exec web php bin/seeder.php`).*

---

## 🧩 Módulos Funcionales del Sistema

El sistema implementa una arquitectura modular donde cada dominio de negocio gestiona su lógica y persistencia:

### 1. Autenticación y Control de Acceso (RBAC)
- **Inicio de sesión centralizado:** Validación real contra base de datos utilizando contraseñas cifradas mediante `password_hash()` con algoritmo Bcrypt.
- **Control Perimetral (`guardian.php`):** Middleware que intercepta solicitudes, verifica el estado de sesión activa y valida permisos según el rol asignado.
- **Cierre de sesión seguro:** Destrucción y limpieza de sesiones en `logout.php`.

### 2. Módulo de Gestión de Usuarios (`src/usuario/`)
- **CRUD Administrativo:** Listado paginado/filtrable, creación, edición y visualización detallada de usuarios.
- **Asignación de Roles:** Roles configurables (Administrador de DTI, Chofer, Enfermería, Unidad de Enlace).
- **Control de Estado:** Activación y desactivación lógica de accesos.

### 3. Módulo de Documentación Clínica (`src/moduloDocumentacion/`)
- **Directorio de Protocolos:** Clasificación por especialidad médica (Urología, Cardiología, Traumatología, etc.).
- **CRUD de Documentos:** Carga de archivos PDF, visualización en visor integrado, edición de metadatos y baja lógica.
- **Acceso por QR Estático:** Códigos QR asignados por servicio médico para consulta rápida de protocolos en salas.

### 4. Módulo de Encuestas y Métricas (`src/moduloEncuestas/`)
- **Formulario Dinámico (`responder.php`):** Interfaz para pacientes con 8 preguntas evaluativas en escala (1 a 5 estrellas) y campo para comentarios libres.
- **Registro Transaccional (`GuardarEncuesta.php`):** Persistencia relacional de cabecera y detalle de respuestas con control de integridad referencial.
- **Panel de Métricas (`MetricasControlador.php`):** API de analítica para visualización de KPIs, promedios de satisfacción por pregunta y distribución estadística.
- **Seguridad en Endpoints:** Desactivación de ejecución web de inicializadores con respuesta estricta **HTTP 403 Forbidden**.

### 5. Módulo de Ambulancias y Traslados (`src/moduloAmbulancias/`)
- **Panel de Choferes:** Gestión operativa de rutas asignadas y bitácora de estado del traslado.
- **Unidad de Enlace:** Coordinación, supervisión y despacho activo de solicitudes de traslado interhospitalario.

---

## 🏗️ Arquitectura y Estructura del Proyecto

El proyecto está diseñado bajo el patrón **MVC (Modelo - Vista - Controlador)**, complementado con una capa de **Servicios Comunes** para la lógica transversal y herramientas CLI:

```text
📦 SIGSM/
├── 📂 bin/                               # Herramientas de consola y scripts administrativos CLI
│   ├── crear_admin.php                   # Gestor CLI para Superadministrador
│   └── seeder.php                        # Seeder general e inicializador del sistema
├── 📂 src/                               # Código fuente principal de la aplicación
│   ├── 📂 documentacion/                 # Volcados y esquemas de base de datos
│   │   └── sigsm.sql                     # Script SQL con estructura relacional y datos base
│   ├── 📂 moduloAmbulancias/             # Módulo de traslados médicos y choferes
│   │   ├── 📂 Controlador/
│   │   ├── 📂 Model/
│   │   └── 📂 Vista/                     # Vistas operativas (choferes.php, unidadEnlace.php)
│   ├── 📂 moduloDocumentacion/           # Módulo de repositorio documental médico
│   │   ├── 📂 Controlador/               # ControladorDocumentos.php
│   │   ├── 📂 Model/                     # DocumentoModelo.php
│   │   └── 📂 Vista/                     # CRUD de documentos y portal de enfermería
│   ├── 📂 moduloEncuestas/               # Módulo de evaluación de satisfacción y métricas
│   │   ├── 📂 Controlador/               # GuardarEncuesta.php, MetricasControlador.php
│   │   └── 📂 Vista/                     # Interfaz de respuesta de encuestas (responder.php)
│   ├── 📂 serviciosComunes/              # Capa transversal y utilidades compartidas
│   │   ├── 📂 autenticacion/             # Controladores de login, logout y manejo de sesión
│   │   ├── 📂 conexionBD/                # Conector PDO Singleton con soporte para Docker y CLI
│   │   ├── 📂 permisos/                  # Verificadores de permisos por rol
│   │   ├── 📂 seguridad/                 # Guardian perimetral y middleware de sesión
│   │   └── 📂 vistaGeneral/              # Layout base compartido y bifurcación
│   ├── 📂 usuario/                       # Módulo de administración de usuarios y roles
│   │   ├── 📂 Controlador/               # ControladorUsuarios.php
│   │   ├── 📂 Model/                     # UsuarioModelo.php
│   │   └── 📂 Vista/                     # Vistas CRUD (listado, crear, editar, ver)
│   └── index.html                        # Redirección al punto de entrada web
├── 📂 tests/                             # Suites de pruebas y verificación
├── .env                                  # Variables de entorno locales
├── .env.example                          # Plantilla de variables de entorno
├── docker-compose.yml                    # Orquestación de contenedores (web, db, phpmyadmin)
├── Dockerfile                            # Imagen personalizada de Apache + PHP 8.0
└── README.md                             # Documentación técnica general
```

---

## 🛠️ Metodología y Convenciones de Desarrollo

Para asegurar la calidad, orden y trazabilidad del código fuente, el equipo sigue estándares consolidados de desarrollo:

### Control de Versiones (Feature Branch Workflow)
- **`main`:** Código funcional, estable y testeado correspondiente a las versiones de entrega oficial.
- **Ramas de funcionalidad (`feat/...`, `fix/...`, `test`):** Desarrollo aislado de nuevas capacidades antes de su integración a la rama principal mediante Pull Requests.

### Convención de Commits (Conventional Commits)
Los mensajes de confirmación utilizan los prefijos estándar:
- `feat:` Implementación de una nueva funcionalidad.
- `fix:` Corrección de errores o inconsistencias.
- `refactor:` Reestructuración o limpieza de código sin alterar su comportamiento externo.
- `docs:` Modificaciones exclusivas en la documentación.
- `test:` Adición o ajuste de pruebas.

### Versionamiento Semántico (SemVer)
- **`v0.1.0` - `v0.9.0`:** Proceso iterativo de diseño arquitectónico, maquetación de interfaces, módulos clínicos y modales funcionales.
- **`v1.0.0`:** Consolidación de la arquitectura MVC, implementación de entorno Dockerizado, login real con control de acceso RBAC, gestión integral de usuarios, módulo de métricas analíticas y herramientas de sembrado CLI.
