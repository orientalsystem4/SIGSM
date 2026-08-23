document.addEventListener("DOMContentLoaded", () => {
    // 1. Navegación Principal (Sidebar)
    const navButtons = document.querySelectorAll('.nav-btn[data-target]');
    const viewSections = document.querySelectorAll('.view-section');

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            navButtons.forEach(b => b.classList.remove('active'));
            viewSections.forEach(v => {
                v.classList.add('hidden');
                v.classList.remove('active');
            });

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

    // 2. Sub-Navegación Inteligente (Pestañas internas de Categorías y Encuestas)
    const subButtons = document.querySelectorAll('.sub-btn[data-sub]');
    subButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Busca la sección padre para no afectar las pestañas de la otra vista
            const parentSection = btn.closest('.view-section');
            
            const sectionButtons = parentSection.querySelectorAll('.sub-btn');
            const sectionContents = parentSection.querySelectorAll('.sub-content');

            sectionButtons.forEach(b => b.classList.remove('active'));
            sectionContents.forEach(c => {
                c.classList.add('hidden');
                c.classList.remove('active');
            });

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-sub');
            const targetContent = document.getElementById(targetId);
            
            if (targetContent) {
                targetContent.classList.remove('hidden');
                targetContent.classList.add('active');
            }
        });
    });

    // 3. Funciones Globales (Acordeones y Modales)
    window.toggleAccordion = function (headerElement) {
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

    // Simulación de envío de formulario en modales
    const formulariosSimulados = document.querySelectorAll('.form-simulado');
    formulariosSimulados.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault(); 
            alert("Operación completada con éxito. (Simulación)");
            
            const modalActivo = form.closest('.modal-overlay');
            if (modalActivo) {
                modalActivo.classList.remove('active');
            }
            form.reset();
        });
    });
});