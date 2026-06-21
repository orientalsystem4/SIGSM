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
    document.getElementById(modalId).classList.add('active');
};

window.cerrarModal = function(modalId) {
    document.getElementById(modalId).classList.remove('active');
};