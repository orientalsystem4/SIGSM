document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Navegación Principal (Sidebar) - Lógica reutilizada de los otros módulos
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

    // 2. Simular Envío de Solicitud de Traslado
    const formTraslado = document.getElementById('form-traslado');
    const mensajeExito = document.getElementById('mensaje-exito');

    if (formTraslado) {
        formTraslado.addEventListener('submit', (e) => {
            e.preventDefault(); // Evita recargar la página
            
            // Ocultar formulario y mostrar mensaje de éxito
            mensajeExito.classList.remove('hidden');
            
            // Resetear el formulario y volver a la normalidad después de 3 segundos
            setTimeout(() => {
                formTraslado.reset();
                mensajeExito.classList.add('hidden');
            }, 3000);
        });
    }

    // 3. Generador de QR Dinámico para Pacientes
    const btnGenerarQR = document.getElementById('btn-generar-qr');
    const qrVacio = document.getElementById('qr-vacio');
    const qrGenerado = document.getElementById('qr-generado');
    const qrImg = document.getElementById('qr-dinamico-img');
    const lblPaciente = document.getElementById('lbl-paciente');
    const lblFormulario = document.getElementById('lbl-formulario');

    if (btnGenerarQR) {
        btnGenerarQR.addEventListener('click', () => {
            const pacienteInput = document.getElementById('qr-paciente');
            const tipoInput = document.getElementById('qr-tipo');

            // Validar que no estén vacíos
            if (pacienteInput.value.trim() === "" || tipoInput.value === "") {
                alert("Por favor, ingrese el nombre del paciente y seleccione un tipo de formulario.");
                return;
            }

            // Actualizar etiquetas visuales
            lblPaciente.textContent = pacienteInput.value;
            lblFormulario.textContent = tipoInput.options[tipoInput.selectedIndex].text;

            // Generar la URL de la imagen QR (Usando una API gratuita para prototipar)
            // En un sistema real, aquí iría el ID único del paciente en la base de datos
            const qrData = encodeURIComponent(`Paciente:${pacienteInput.value}|Formulario:${tipoInput.value}`);
            qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrData}`;

            // Cambiar las vistas
            qrVacio.classList.add('hidden');
            qrGenerado.classList.remove('hidden');
        });
    }
});