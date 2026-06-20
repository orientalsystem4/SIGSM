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

    // 2. Navegación Secundaria (En Curso / Pendientes / Historial)
    const subButtons = document.querySelectorAll('.sub-btn');
    const subContents = document.querySelectorAll('.sub-content');

    subButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            subButtons.forEach(b => b.classList.remove('active'));
            subContents.forEach(c => c.classList.add('hidden'));
            subContents.forEach(c => c.classList.remove('active'));

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