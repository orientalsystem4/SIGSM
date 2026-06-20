document.addEventListener("DOMContentLoaded", () => {
    // 1. Navegación del Sidebar (Secciones principales)
    const navButtons = document.querySelectorAll('.nav-btn[data-target]');
    const viewSections = document.querySelectorAll('.view-section');

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Quitar active de todos
            navButtons.forEach(b => b.classList.remove('active'));
            viewSections.forEach(v => v.classList.add('hidden'));
            viewSections.forEach(v => v.classList.remove('active'));

            // Activar el clickeado
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                targetSection.classList.remove('hidden');
                targetSection.classList.add('active');
            }

            // Cerrar menú en móviles al hacer clic
            if(window.innerWidth < 1024) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebarOverlay').classList.remove('active');
            }
        });
    });

    // 2. Navegación de Pestañas Internas (Proceso vs Historial)
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.add('hidden'));

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-tab');
            document.getElementById(targetId).classList.remove('hidden');
        });
    });
});
// Función Global para abrir/cerrar Acordeones
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