document.addEventListener("DOMContentLoaded", () => {
    const ID_TRASLADO = 1;
    const CLAVE_SALIDA = `sigsm_traslado_${ID_TRASLADO}_salida`;
    const CLAVE_LLEGADA = `sigsm_traslado_${ID_TRASLADO}_llegada`;
    const CLAVE_HISTORIAL = "sigsm_historial_traslados";

    const navButtons = document.querySelectorAll('.nav-btn[data-target]');
    const viewSections = document.querySelectorAll('.view-section');

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            navButtons.forEach(b => b.classList.remove('active'));

            viewSections.forEach(section => {
                section.classList.add('hidden');
                section.classList.remove('active');
            });

            btn.classList.add('active');

            const targetId = btn.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);

            if (targetSection) {
                targetSection.classList.remove('hidden');
                targetSection.classList.add('active');
            }

            if (window.innerWidth < 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');

                if (sidebar) sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        });
    });

    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            tabContents.forEach(content => content.classList.add('hidden'));

            btn.classList.add('active');

            const targetId = btn.getAttribute('data-tab');
            const target = document.getElementById(targetId);

            if (target) target.classList.remove('hidden');
        });
    });

    const botonesEstado = document.querySelectorAll(
        '.acciones-conduccion button'
    );

    botonesEstado.forEach(boton => {
        boton.addEventListener('click', () => {
            if (boton.disabled) return;

            const pasoActual = boton.closest('.timeline-step');
            if (!pasoActual) return;

            const tituloActual = pasoActual.querySelector('h4');

            const estadoActual = tituloActual
                ? tituloActual.textContent.split(" - ")[0].trim()
                : "";

            let fechaCambio = new Date();

            // =================================================
            // RNE3 - REGISTRAR SALIDA
            // =================================================

            if (estadoActual === "Asignado") {
                localStorage.setItem(
                    CLAVE_SALIDA,
                    fechaCambio.toISOString()
                );
            }

            // =================================================
            // RNE3 - VALIDAR LLEGADA
            // =================================================

            if (estadoActual === "En curso") {
                const salidaGuardada =
                    localStorage.getItem(CLAVE_SALIDA);

                if (!salidaGuardada) {
                    alert(
                        "No se puede registrar la llegada porque no existe una fecha/hora de salida."
                    );
                    return;
                }

                const fechaSalida = new Date(salidaGuardada);
                const fechaLlegada = new Date();

                if (fechaLlegada <= fechaSalida) {
                    alert(
                        "La fecha/hora de llegada debe ser posterior a la fecha/hora de salida."
                    );
                    return;
                }

                fechaCambio = fechaLlegada;

                localStorage.setItem(
                    CLAVE_LLEGADA,
                    fechaLlegada.toISOString()
                );
            }

            const siguientePaso = pasoActual.nextElementSibling;

            pasoActual.classList.remove('active');
            pasoActual.classList.add('completed');

            boton.disabled = true;

            if (
                siguientePaso &&
                siguientePaso.classList.contains('timeline-step')
            ) {
                siguientePaso.classList.remove('pending');
                siguientePaso.classList.add('active');

                const siguienteBoton =
                    siguientePaso.querySelector(
                        '.acciones-conduccion button'
                    );

                if (siguienteBoton) {
                    siguienteBoton.disabled = false;
                }

                const tituloEstado =
                    siguientePaso.querySelector('h4');

                const badgeEstado =
                    document.querySelector(
                        '#tab-proceso .badge.urgencia-media'
                    );

                if (tituloEstado) {
                    const estado =
                        tituloEstado.textContent
                            .split(" - ")[0]
                            .trim();

                    if (badgeEstado) {
                        badgeEstado.textContent = estado;
                    }

                    let fechaEstado = fechaCambio;

                    if (estado === "En curso") {
                        const salida =
                            localStorage.getItem(CLAVE_SALIDA);

                        if (salida) {
                            fechaEstado = new Date(salida);
                        }
                    }

                    if (estado === "En destino") {
                        const llegada =
                            localStorage.getItem(CLAVE_LLEGADA);

                        if (llegada) {
                            fechaEstado = new Date(llegada);
                        }
                    }

                    const observacion =
                        obtenerObservacion(estado);

                    guardarHistorialCompartido(
                        estado,
                        fechaEstado,
                        "Pedro Miniño",
                        observacion
                    );

                    agregarAlHistorialChofer(
                        estado,
                        fechaEstado,
                        observacion
                    );
                }
            }
        });
    });

    function obtenerObservacion(estado) {
        if (estado === "En curso") {
            return "El chofer inició el traslado.";
        }

        if (estado === "En destino") {
            return "El vehículo llegó al destino.";
        }

        if (estado === "En retorno") {
            return "El vehículo inició el retorno.";
        }

        if (estado === "Finalizado") {
            return "Traslado finalizado.";
        }

        return "Estado del traslado actualizado.";
    }

    function guardarHistorialCompartido(
        estado,
        fecha,
        usuario,
        observacion
    ) {
        let historial = [];

        try {
            historial =
                JSON.parse(
                    localStorage.getItem(CLAVE_HISTORIAL)
                ) || [];
        } catch (error) {
            historial = [];
        }

        const registro = {
            id: `${ID_TRASLADO}-${estado}-${fecha.getTime()}`,
            id_traslado: ID_TRASLADO,
            fecha_hora: fecha.toISOString(),
            estado,
            usuario,
            observacion
        };

        historial.push(registro);

        localStorage.setItem(
            CLAVE_HISTORIAL,
            JSON.stringify(historial)
        );
    }

    function agregarAlHistorialChofer(
        estado,
        fecha,
        observacion
    ) {
        const timelineHistorial =
            document.querySelector(
                '#tab-historial .timeline'
            );

        if (!timelineHistorial) return;

        const paso =
            document.createElement('div');

        paso.className =
            'timeline-step completed';

        const fechaHora =
            fecha.toLocaleString(
                'es-UY',
                {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            );

        paso.innerHTML = `
            <div class="step-marker">
                <i data-lucide="check"></i>
            </div>

            <div class="step-content">
                <h4>${estado}</h4>

                <p class="text-muted">
                    ${fechaHora}
                </p>

                <div class="chat-message system">
                    <span>${observacion}</span>
                </div>
            </div>
        `;

        timelineHistorial.appendChild(paso);

        const badgeHistorial =
            document.querySelector(
                '#tab-historial .accordion-header .badge'
            );

        if (badgeHistorial) {
            badgeHistorial.textContent = estado;
        }

        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    }
});

window.toggleAccordion = function (headerElement) {
    const content =
        headerElement.nextElementSibling;

    const icon =
        headerElement.querySelector(
            '.accordion-icon'
        );

    if (!content) return;

    content.classList.toggle('open');

    if (icon) {
        icon.style.transform =
            content.classList.contains('open')
                ? "rotate(180deg)"
                : "rotate(0deg)";
    }
};