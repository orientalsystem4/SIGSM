document.addEventListener("DOMContentLoaded", () => {

    // =========================================================
    // 1. NAVEGACIÓN
    // =========================================================

    const navButtons =
        document.querySelectorAll('.nav-btn[data-target]');

    const viewSections =
        document.querySelectorAll('.view-section');


    navButtons.forEach(btn => {

        btn.addEventListener('click', () => {

            navButtons.forEach(b => {
                b.classList.remove('active');
            });


            viewSections.forEach(section => {
                section.classList.add('hidden');
                section.classList.remove('active');
            });


            btn.classList.add('active');


            const targetId =
                btn.getAttribute('data-target');

            const targetSection =
                document.getElementById(targetId);


            if (targetSection) {
                targetSection.classList.remove('hidden');
                targetSection.classList.add('active');
            }


            if (window.innerWidth < 1024) {

                const sidebar =
                    document.getElementById('sidebar');

                const overlay =
                    document.getElementById('sidebarOverlay');


                if (sidebar) {
                    sidebar.classList.remove('active');
                }

                if (overlay) {
                    overlay.classList.remove('active');
                }
            }

        });

    });



    // =========================================================
    // 2. ELEMENTOS DEL FORMULARIO DE TRASLADO
    // =========================================================

    const formTraslado =
        document.getElementById('form-traslado');

    const mensajeExito =
        document.getElementById('mensaje-exito');

    const tipoElemento =
        document.getElementById('tipo-elemento');

    const datosPaciente =
        document.getElementById('datos-paciente');

    const ciPaciente =
        document.getElementById('ci-paciente');

    const estadoPaciente =
        document.getElementById('estado-paciente');

    const origen =
        document.getElementById('origen');

    const destino =
        document.getElementById('destino');

    const canal =
        document.getElementById('canal-solicitud');

    const prioridad =
        document.getElementById('prioridad');

    const mensajePrioridad =
        document.getElementById('mensaje-prioridad');

    const fechaHoraLimite =
        document.getElementById('fecha-hora-limite');

    const observaciones =
        document.getElementById('observaciones');



    // =========================================================
    // 3. PACIENTES DE PRUEBA
    // =========================================================

    /*
        Esto simula la tabla PACIENTE.

        Cuando exista backend, esta lista desaparece
        y la CI se verificará directamente contra MariaDB.
    */

    const pacientesRegistrados = [

        {
            ci: "11111111",
            nombre: "Paciente",
            apellido: "de prueba"
        }

    ];



    // =========================================================
    // 4. CAMBIO DEL TIPO DE ELEMENTO
    // =========================================================

    if (tipoElemento) {

        tipoElemento.addEventListener('change', () => {


            // -------------------------------------------------
            // PACIENTE
            // -------------------------------------------------

            if (tipoElemento.value === "1") {

                datosPaciente.classList.remove('hidden');

                ciPaciente.required = true;

                estadoPaciente.textContent =
                    "Ingrese la cédula para verificar que el paciente esté registrado.";

            } else {

                datosPaciente.classList.add('hidden');

                ciPaciente.required = false;

                ciPaciente.value = "";

                estadoPaciente.textContent =
                    "Ingrese la cédula para verificar que el paciente esté registrado.";

            }



            // -------------------------------------------------
            // ÓRGANO = PRIORIDAD MÁXIMA
            // -------------------------------------------------

            if (tipoElemento.value === "2") {

                prioridad.value = "maxima";

                prioridad.disabled = true;

                mensajePrioridad.textContent =
                    "Los traslados de órganos tienen prioridad máxima.";

            } else {

                prioridad.disabled = false;

                prioridad.value = "normal";

                mensajePrioridad.textContent =
                    "La prioridad se establece según las características y tiempos del traslado.";

            }

        });

    }



    // =========================================================
    // 5. BUSCAR PACIENTE POR CÉDULA
    // =========================================================

    if (ciPaciente) {

        ciPaciente.addEventListener('input', () => {


            // Sacar puntos, guiones, letras, etc.
            const ciIngresada =
                ciPaciente.value.replace(/\D/g, '');


            ciPaciente.value =
                ciIngresada;



            if (ciIngresada === "") {

                estadoPaciente.textContent =
                    "Ingrese la cédula para verificar que el paciente esté registrado.";

                return;
            }



            const paciente =
                pacientesRegistrados.find(
                    p => p.ci === ciIngresada
                );



            if (paciente) {

                estadoPaciente.textContent =
                    `Paciente encontrado: ${paciente.nombre} ${paciente.apellido}`;

            } else {

                estadoPaciente.textContent =
                    "Paciente no encontrado.";

            }

        });

    }



    // =========================================================
    // 6. ENVIAR SOLICITUD
    // =========================================================

    if (formTraslado) {

        formTraslado.addEventListener('submit', (e) => {

            e.preventDefault();



            // -------------------------------------------------
            // VALIDAR TIPO DE ELEMENTO
            // -------------------------------------------------

            if (tipoElemento.value === "") {

                alert(
                    "Debe seleccionar el tipo de elemento."
                );

                return;
            }



            // -------------------------------------------------
            // VALIDAR PACIENTE
            // -------------------------------------------------

            if (tipoElemento.value === "1") {

                const ciIngresada =
                    ciPaciente.value.replace(/\D/g, '');


                if (ciIngresada === "") {

                    alert(
                        "Debe ingresar la cédula del paciente."
                    );

                    return;
                }


                const pacienteExiste =
                    pacientesRegistrados.some(
                        p => p.ci === ciIngresada
                    );


                if (!pacienteExiste) {

                    alert(
                        "La cédula ingresada no corresponde a un paciente registrado."
                    );

                    return;
                }

            }



            // -------------------------------------------------
            // VALIDAR ORIGEN Y DESTINO
            // -------------------------------------------------

            if (
                origen.value === "" ||
                destino.value === ""
            ) {

                alert(
                    "Debe seleccionar el origen y el destino."
                );

                return;
            }


            if (origen.value === destino.value) {

                alert(
                    "El origen y el destino no pueden ser iguales."
                );

                return;
            }



            // -------------------------------------------------
            // VALIDAR CANAL
            // -------------------------------------------------

            if (canal.value === "") {

                alert(
                    "Debe seleccionar el canal de solicitud."
                );

                return;
            }



            // -------------------------------------------------
            // VALIDAR FECHA/HORA LÍMITE
            // -------------------------------------------------

            if (fechaHoraLimite.value !== "") {

                const limite =
                    new Date(fechaHoraLimite.value);

                const ahora =
                    new Date();


                if (limite <= ahora) {

                    alert(
                        "La fecha y hora límite debe ser posterior al momento actual."
                    );

                    return;
                }

            }



            // =================================================
            // 7. CREAR LA SOLICITUD
            // =================================================

            const solicitud = {

                id_solicitud: null,

                ci_paciente:
                    tipoElemento.value === "1"
                        ? ciPaciente.value
                        : null,

                id_tipo_elemento:
                    Number(tipoElemento.value),

                id_origen:
                    Number(origen.value),

                id_destino:
                    Number(destino.value),

                id_canal:
                    Number(canal.value),

                // Usuario de prueba: Administrador de Enfermería
                id_usuario_solicit:
                    2,

                fecha_solicitud:
                    new Date().toISOString(),

                estado_solicitud:
                    "Pendiente",

                prioridad:
                    tipoElemento.value === "2"
                        ? "maxima"
                        : prioridad.value,

                fecha_hora_limite:
                    fechaHoraLimite.value !== ""
                        ? fechaHoraLimite.value
                        : null,

                observaciones:
                    observaciones.value.trim() !== ""
                        ? observaciones.value.trim()
                        : null

            };



            // =================================================
            // 8. GUARDAR EN LOCALSTORAGE
            // =================================================

            /*
                Esto es solamente para la demostración.

                Permite que después Unidad de Enlace pueda
                leer las solicitudes creadas desde Enfermería.

                NO modifica MariaDB.
            */

            let solicitudesGuardadas = [];


            try {

                solicitudesGuardadas =
                    JSON.parse(
                        localStorage.getItem(
                            'sigsm_solicitudes'
                        )
                    ) || [];

            } catch (error) {

                solicitudesGuardadas = [];

            }



            /*
                Las solicitudes de prueba de nuestra BD
                llegan hasta el ID 5.

                Por eso, si todavía no existe ninguna solicitud
                creada en el navegador, la primera será la #6.
            */

            let ultimoId = 5;


            solicitudesGuardadas.forEach(item => {

                const id =
                    Number(item.id_solicitud);

                if (id > ultimoId) {
                    ultimoId = id;
                }

            });


            solicitud.id_solicitud =
                ultimoId + 1;



            solicitudesGuardadas.push(
                solicitud
            );


            localStorage.setItem(
                'sigsm_solicitudes',
                JSON.stringify(
                    solicitudesGuardadas
                )
            );



            console.log(
                "Solicitud creada:",
                solicitud
            );



            // =================================================
            // 9. MOSTRAR MENSAJE
            // =================================================

            if (mensajeExito) {

                mensajeExito.classList.remove(
                    'hidden'
                );

            }



            // =================================================
            // 10. LIMPIAR FORMULARIO
            // =================================================

            formTraslado.reset();


            datosPaciente.classList.add(
                'hidden'
            );


            ciPaciente.required = false;


            estadoPaciente.textContent =
                "Ingrese la cédula para verificar que el paciente esté registrado.";


            prioridad.disabled = false;

            prioridad.value = "normal";


            mensajePrioridad.textContent =
                "La prioridad se establece según las características y tiempos del traslado.";



            setTimeout(() => {

                if (mensajeExito) {

                    mensajeExito.classList.add(
                        'hidden'
                    );

                }

            }, 3000);

        });

    }



    // =========================================================
    // 11. ENCUESTAS
    // =========================================================

    const categoriaQR =
        document.getElementById('qr-categoria');

    const encuestaQR =
        document.getElementById('qr-encuesta');

    const btnGenerarQR =
        document.getElementById('btn-generar-qr');

    const qrVacio =
        document.getElementById('qr-vacio');

    const qrGenerado =
        document.getElementById('qr-generado');

    const qrImg =
        document.getElementById('qr-dinamico-img');

    const lblCategoria =
        document.getElementById('lbl-categoria');

    const lblEncuesta =
        document.getElementById('lbl-encuesta');


    /*
        Las encuestas definitivas todavía no fueron entregadas
        por el Hospital de Clínicas.

        Mientras tanto se utiliza UNA encuesta de demostración
        para mostrar el funcionamiento:

        Enfermería -> genera QR -> paciente abre encuesta -> responde.

        Cuando estén disponibles las encuestas reales,
        estos datos se reemplazarán por los datos reales.
    */

    const encuestasDemo = [

        {
            id_encuesta: 1,
            nombre: "Encuesta de demostración"
        }

    ];


    /*
        Dirección pública que tendrá la encuesta cuando
        esta versión sea publicada en GitHub Pages.
    */

    const URL_ENCUESTA_PUBLICA =
        "https://orientalsystem4.github.io/SIGSM/moduloDocumentacion/Vista/encuesta.html";



    // =========================================================
    // 12. CAMBIO DE CATEGORÍA
    // =========================================================

    if (categoriaQR && encuestaQR) {

        categoriaQR.addEventListener('change', () => {


            // Limpiar selector
            encuestaQR.innerHTML = `
                <option value="">
                    Seleccione la encuesta...
                </option>
            `;


            /*
                Por ahora mostramos la encuesta de demostración.

                Cuando estén las encuestas definitivas,
                acá se mostrarán solamente las correspondientes
                al área seleccionada.
            */

            if (categoriaQR.value !== "") {

                encuestasDemo.forEach(encuesta => {

                    const opcion =
                        document.createElement('option');


                    opcion.value =
                        encuesta.id_encuesta;


                    opcion.textContent =
                        encuesta.nombre;


                    encuestaQR.appendChild(
                        opcion
                    );

                });

            }


            // Si había un QR mostrado, lo ocultamos.
            if (qrGenerado) {

                qrGenerado.classList.add(
                    'hidden'
                );

            }


            if (qrVacio) {

                qrVacio.classList.remove(
                    'hidden'
                );

            }

        });

    }



    // =========================================================
    // 13. GENERAR QR
    // =========================================================

    if (btnGenerarQR) {

        btnGenerarQR.addEventListener('click', () => {


            // -------------------------------------------------
            // VALIDAR ÁREA
            // -------------------------------------------------

            if (
                !categoriaQR ||
                categoriaQR.value === ""
            ) {

                alert(
                    "Seleccione un área."
                );

                return;
            }



            // -------------------------------------------------
            // VALIDAR ENCUESTA
            // -------------------------------------------------

            if (
                !encuestaQR ||
                encuestaQR.value === ""
            ) {

                alert(
                    "Seleccione una encuesta."
                );

                return;
            }



            // -------------------------------------------------
            // NOMBRES PARA MOSTRAR EN PANTALLA
            // -------------------------------------------------

            const nombreCategoria =
                categoriaQR.options[
                    categoriaQR.selectedIndex
                ].text;


            const nombreEncuesta =
                encuestaQR.options[
                    encuestaQR.selectedIndex
                ].text;



            if (lblCategoria) {

                lblCategoria.textContent =
                    nombreCategoria;

            }


            if (lblEncuesta) {

                lblEncuesta.textContent =
                    nombreEncuesta;

            }



            // -------------------------------------------------
            // URL DE LA ENCUESTA
            // -------------------------------------------------

            /*
                El QR contiene la dirección de la encuesta.

                También enviamos:
                - id de encuesta
                - id de categoría

                No enviamos datos personales del paciente.
            */

            const urlEncuesta =
                URL_ENCUESTA_PUBLICA +
                "?id=" +
                encodeURIComponent(
                    encuestaQR.value
                ) +
                "&categoria=" +
                encodeURIComponent(
                    categoriaQR.value
                );



            // -------------------------------------------------
            // GENERAR IMAGEN DEL QR
            // -------------------------------------------------

            if (qrImg) {

                qrImg.src =
                    "https://api.qrserver.com/v1/create-qr-code/" +
                    "?size=200x200&data=" +
                    encodeURIComponent(
                        urlEncuesta
                    );

            }



            // -------------------------------------------------
            // MOSTRAR RESULTADO
            // -------------------------------------------------

            if (qrVacio) {

                qrVacio.classList.add(
                    'hidden'
                );

            }


            if (qrGenerado) {

                qrGenerado.classList.remove(
                    'hidden'
                );

            }

        });

    }



    // =========================================================
    // 14. ICONOS
    // =========================================================

    if (typeof lucide !== "undefined") {

        lucide.createIcons();

    }

});