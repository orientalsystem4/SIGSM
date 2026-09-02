# S.I.G.S.M. - Sistema Informático de Gestión de Servicios Médicos 🚑

**Proyecto de Egreso - BT Tecnologías de la Información - 2026** *I.S.B.O. - Polo Educativo Tecnológico Vista Linda - ITS Paysandú - ESC Villa Muñoz*

S.I.G.S.M. es una plataforma web orientada a optimizar la logística de traslados médicos, la gestión de la documentación mediante códigos QR (estáticos y dinámicos) y la recopilación de métricas de satisfacción de los pacientes. 

🔗 **[Ver Prototipo en Vivo (GitHub Pages)](https://orientalsystem4.github.io/SIGSM/)**

---
## 🚀 Ejecución del proyecto

La versión pública del frontend puede visualizarse mediante GitHub Pages.

Para ejecutar las funcionalidades desarrolladas en PHP, especialmente el CRUD del módulo de Documentación, es necesario utilizar un servidor local con Apache y MariaDB/MySQL.

### Requisitos

- XAMPP
- Apache
- PHP
- MariaDB / MySQL
- phpMyAdmin
- Navegador web

### Instalación

1. Clonar este repositorio:

`git clone https://github.com/orientalsystem4/SIGSM.git`

2. Colocar la carpeta `SIGSM` dentro de:

`C:\xampp\htdocs\`

La ubicación del proyecto debería quedar:

`C:\xampp\htdocs\SIGSM`

3. Abrir XAMPP e iniciar los servicios **Apache** y **MySQL**.

4. Ingresar a phpMyAdmin desde:

`http://localhost/phpmyadmin/`

5. Crear una base de datos llamada `sigsm`.

6. Seleccionar la base de datos `sigsm` e importar el archivo:

`documentacion/sigsm.sql`

Este archivo contiene la estructura de la base de datos, sus relaciones y los datos de prueba utilizados en el proyecto.

7. Verificar la configuración de conexión ubicada en:

`serviciosComunes/conexionBD/conexion.php`

Si la configuración local de MariaDB/MySQL es diferente, se deberán modificar los datos de conexión correspondientes.

### CRUD del módulo de Documentación

El CRUD de Documentación utiliza PHP y MariaDB, por lo que debe ejecutarse mediante Apache.

Con Apache y MySQL iniciados, ingresar desde el navegador a:

`http://localhost/SIGSM/moduloDocumentacion/Vista/listado.php`

Desde esta vista se pueden realizar las siguientes operaciones:

- Registrar un documento PDF.
- Visualizar los documentos registrados.
- Consultar la información de un documento.
- Editar un documento existente.
- Dar de baja un documento.

Las operaciones realizadas desde este módulo se registran en la base de datos de S.I.G.S.M.

### Estado de la autenticación

El inicio de sesión se encuentra implementado actualmente a nivel de interfaz para permitir la demostración y navegación entre los diferentes módulos.

En esta etapa no se realiza una validación real de credenciales ni se mantiene una sesión mediante backend. La autenticación, gestión de sesiones y control de acceso por roles quedan previstas para una futura implementación.

## 🏗️ Arquitectura del Sistema

El proyecto está estructurado bajo el patrón de diseño **MVC (Modelo - Vista - Controlador)**, organizado por dominios de negocio (Módulos) en lugar de roles de usuario, para garantizar la escalabilidad y la separación de responsabilidades.

* **Módulo Ambulancias y Traslados:** Gestiona las rutas, estados de conducción de los choferes y la coordinación activa por parte de la Unidad de Enlace.
* **Módulo Documentación y Encuestas:** Gestiona el directorio de protocolos médicos (QR Estáticos) y el generador de encuestas/formularios para pacientes (QR Dinámicos).
* **Servicios Comunes:** Contiene la lógica transversal del sistema, como la autenticación, seguridad, conexión a bases de datos y la plantilla de diseño base (Layout).

### Estructura de Directorios

```text
📦 SIGSM/
├── 📂 moduloAmbulancias/
│   ├── 📂 Controlador/
│   ├── 📂 Modelo/
│   └── 📂 Vista/
│       ├── 📂 assets/ (css, js, img específicos)
│       ├── choferes.html
│       └── unidad_enlace.html
├── 📂 moduloDocumentacion/
│   ├── 📂 Controlador/
│   ├── 📂 Modelo/
│   └── 📂 Vista/
│       └── enfermeria.html
├── 📂 serviciosComunes/
│   ├── 📂 autenticacion/
│   └── 📂 vistaGeneral/
│       ├── 📂 assets/ (Layout global y paleta de colores)
│       ├── bifurcacion.html
│       └── layout_base.html
├── 📂 documento
│       ├── sigsm.sql
│   ├── 📂 Controlador
│   ├── 📂 Modelo
│   └── 📂 Vista/
│       ├── 📂 assets/ 
│            ├── 📂 css
│                 ├── chofer.css
│                 ├── unidadEnlace.css
│            ├── 📂 img 
│            └── 📂 js
│                 ├── choferes.html
│                 └── unidad_enlace.html
├── 📂 moduloDocumentacion/
│   ├── 📂 Controlador
│   ├── 📂 Modelo
│   └── 📂 Vista/
│       └── 📂 assets/ 
│            ├── 📂 css
│                 ├── documemtacion.css
│                 ├── enfermeria.css
│            └── 📂 img
│       └── documentacion.js
│       └── enfermeria.js
│       └── documentacion.html
│       └── enfermeria.html
├── 📂 moduloEncuesta/
│   ├── 📂 Controlador
│   ├── 📂 Modelo
│   └── 📂 Vista/
│       └── 📂 assets (css, img, js)
├── 📂 serviciosComunes/
│   ├── 📂 autenticacion
│       ├── login.css
│       ├── sesiones.html
│   ├── 📂 conexionBD
│   ├── 📂 permisos
│   ├── 📂 seguridad
│   ├── 📂 validaciones
│   └── 📂 vistaGeneral/
│       ├── 📂 assets/ (css, js, Layout global y paleta de colores)
│       ├── bifurcacion.html
│       └── layout_base.html
├── 📂 usuario/
│   ├── 📂 Controlador
│   ├── 📂 Modelo
│   └── 📂 Vista
│       ├── crear.php
│       ├── editar.php
│       ├── listado.php
│       └── ver.php
├── index.html (Redirección para el Deployment)
└── README.md
```

### 🛠️ Convenciones y Metodología de Trabajo

Para mantener el orden, la trazabilidad del código y el profesionalismo durante todo el ciclo de vida del proyecto, el equipo adoptó metodologías estándar de la industria para el control de versiones y la gestión del código fuente.

### . Control de Versiones (Feature Branch Workflow)
Todo el control de código fuente se realizó utilizando **Git** y alojado en **GitHub**. Se implementó un flujo de trabajo basado en la creación de ramas por funcionalidad (*Feature Branching*):
* **`main` (Rama Principal):** Contiene únicamente código funcional, estable y testeado. Refleja la versión de producción o entregables oficiales. Nunca se programa directamente sobre esta rama.
* **`test` (Rama de Desarrollo/Pruebas):** Se utilizó para desarrollar nuevas vistas, probar la compatibilidad de los estilos CSS compartidos (layout) y validar el código JavaScript antes de integrarlo. Una vez validado el funcionamiento cruzado, se realizaba la fusión (`merge`) hacia `main`.

### . Lógica de Commits (Conventional Commits)
Se estableció una convención estricta para los mensajes de guardado (*commits*), utilizando la metodología **Conventional Commits**. Esto permite que el historial del repositorio explique no solo *qué* cambió, sino *por qué* y *qué tipo* de cambio es. 
Los prefijos utilizados durante el proyecto fueron:
* `feat:` Implementación de una nueva característica o funcionalidad (Ej: *feat: implementa vista de choferes con layout base*).
* `fix:` Solución de errores o comportamientos no deseados (Ej: *fix: reutilización de clases CSS en tabs de documentos*).
* `refactor:` Reestructuración del código para mejorar su legibilidad o eficiencia sin alterar su comportamiento externo (Ej: *refactor: rediseño de bifurcación y limpieza de estilos*).
* `docs:` Cambios exclusivos en la documentación, como este archivo README.

### . Versionamiento Semántico (Semantic Versioning - SemVer)
El avance del proyecto se catalogó utilizando **SemVer** (`MAYOR.MENOR.PARCHE`). Según este estándar, todas las versiones `0.x.x` representan el proceso de desarrollo inicial, iteración y prototipado. La versión `1.0.0` marca el primer lanzamiento estable del prototipo.

A continuación, se detalla el registro de evolución del proyecto mediante etiquetas (*Tags*):

* **`v0.1.0` - Arquitectura Base:** Inicialización del repositorio y creación de la estructura de directorios separada por módulos funcionales bajo el patrón MVC.
* **`v0.2.0` - Refactorización UI:** Separación del código CSS del HTML, rediseño de la interfaz de inicio de sesión y bifurcación con soporte *Mobile First*.
* **`v0.3.0` - Layout Global:** Creación del `layout_base.html`, implementando el *sidebar* desplegable responsivo y perfiles de usuario.
* **`v0.6.0` - Portal Enfermería:** Desarrollo del módulo de documentación, incluyendo el formulario de solicitudes de traslado, directorio estático y generador de códigos QR dinámicos.
* **`v0.7.0` - Directorio y Métricas:** Implementación de la vista de Unidad de Enlace con el repositorio médico clasificado y paneles de métricas estadísticas (KPIs).
* **`v0.8.0` - Optimización de Código:** *Refactor* de la lógica JavaScript y CSS de la navegación secundaria (pestañas) para hacerla 100% modular y reutilizable.
* **`v0.9.0` - Modales Funcionales:** Integración de ventanas emergentes para la aprobación/denegación de traslados y subida de archivos, con inyección dinámica del nombre del responsable.
