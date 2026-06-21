document.addEventListener("DOMContentLoaded", () => {
    // 1. Navegación Principal (Sidebar)
    const navButtons = document.querySelectorAll('.nav-btn[data-target]');
    const viewSections = document.querySelectorAll('.view-section');

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            navButtons.forEach(b => b.classList.remove('active'));
            viewSections.forEach(v => v.classList.add('hidden'));
            viewSections.forEach(v => v.classList.remove('active'));

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                targetSection.classList.remove('hidden');
                targetSection.classList.add('active');
            }

            if(window.innerWidth < 1024) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebarOverlay').classList.remove('active');
            }
        });
    });

    // 2. Navegación Secundaria Inteligente (Reutilizable para cualquier módulo)
    const subButtons = document.querySelectorAll('.sub-btn');

    subButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // A. Encontrar la sección principal actual (para no romper otras pestañas ocultas)
            const parentSection = btn.closest('.view-section');
            
            // B. Encontrar solo los botones y contenidos de ESTA sección
            const sectionButtons = parentSection.querySelectorAll('.sub-btn');
            const sectionContents = parentSection.querySelectorAll('.sub-content');

            // C. Limpiar estado 'active' y ocultar contenidos solo de esta sección
            sectionButtons.forEach(b => b.classList.remove('active'));
            sectionContents.forEach(c => {
                c.classList.add('hidden');
                c.classList.remove('active');
            });

            // D. Activar el botón presionado y mostrar su contenido
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-sub');
            const targetContent = document.getElementById(targetId);
            
            if (targetContent) {
                targetContent.classList.remove('hidden');
                targetContent.classList.add('active');
            }
        });
    });
});

// 3. Funciones Globales para Acordeones y Modales
window.toggleAccordion = function(headerElement) {
    const content = headerElement.nextElementSibling;
    const icon = headerElement.querySelector('.accordion-icon');
    
    content.classList.toggle('open');
    if(content.classList.contains('open')) {
        icon.style.transform = "rotate(180deg)";
    } else {
        icon.style.transform = "rotate(0deg)";
    }
};

window.abrirModal = function(modalId) {
    // 1. Obtener el nombre del usuario desde el perfil del Sidebar
    const nombreUsuario = document.querySelector('.profile-name').textContent;
    
    // 2. Inyectar ese nombre en todos los campos 'responsable-nombre' del sistema
    document.querySelectorAll('.responsable-nombre').forEach(span => {
        span.textContent = nombreUsuario;
    });

    // 3. Mostrar el Modal
    document.getElementById(modalId).classList.add('active');
};

window.cerrarModal = function(modalId) {
    document.getElementById(modalId).classList.remove('active');
};

// 4. Simulación de Envío de Formularios en Modales
const formulariosSimulados = document.querySelectorAll('.form-simulado');
formulariosSimulados.forEach(form => {
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Evita que la página se recargue
        
        // Simular un guardado exitoso
        alert("Acción registrada correctamente en el sistema.");
        
        // Cerrar el modal activo
        const modalActivo = form.closest('.modal-overlay');
        if (modalActivo) {
            modalActivo.classList.remove('active');
        }
        
        // Limpiar los campos del formulario
        form.reset();
    });
});