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

    let pacienteEncontrado = null;
    let numeroBusqueda = 0;
    let temporizadorBusqueda = null;


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

                mensajePrioridad.hidden = false;
                mensajePrioridad.textContent =
                    "Los traslados de órganos tienen prioridad máxima.";

            } else {

                prioridad.disabled = false;

                prioridad.value = "normal";

                mensajePrioridad.hidden = true;
                mensajePrioridad.textContent = "";

            }

        });

    }



    // =========================================================
    // 5. BUSCAR PACIENTE POR CÉDULA
    // =========================================================

    // 5. BUSCAR PACIENTE POR CEDULA

    if (ciPaciente) {
        ciPaciente.addEventListener('input', () => {
            const cedula = ciPaciente.value.replace(/\D/g, '');
            ciPaciente.value = cedula;

            pacienteEncontrado = null;
            clearTimeout(temporizadorBusqueda);

            const busquedaActual = ++numeroBusqueda;

            if (!/^[0-9]{7,8}$/.test(cedula)) {
                estadoPaciente.textContent =
                    'Ingrese una cedula de 7 u 8 digitos.';
                return;
            }

            estadoPaciente.textContent = 'Buscando paciente...';

            temporizadorBusqueda = setTimeout(async () => {
                try {
                    const respuesta = await fetch(
                        '../../moduloAmbulancias/Controlador/BuscarPaciente.php?ci='
                        + encodeURIComponent(cedula),
                        {
                            cache: 'no-store',
                            headers: { Accept: 'application/json' }
                        }
                    );

                    if (respuesta.redirected) {
                        throw new Error('Sesion finalizada');
                    }

                    const datos = await respuesta.json();

                    // Ignorar respuestas de una cedula anterior.
                    if (
                        busquedaActual !== numeroBusqueda
                        || ciPaciente.value !== cedula
                    ) {
                        return;
                    }

                    if (!respuesta.ok || !datos.exito) {
                        estadoPaciente.textContent =
                            datos.mensaje || 'No se pudo consultar el paciente.';
                        return;
                    }

                    if (!datos.encontrado) {
                        estadoPaciente.textContent =
                            'No se encontro un paciente con esa cedula.';
                        return;
                    }

                    pacienteEncontrado = datos.paciente;

                    estadoPaciente.textContent =
                        `Paciente encontrado: ${datos.paciente.nombre} ${datos.paciente.apellido}`;

                } catch (error) {
                    if (busquedaActual !== numeroBusqueda) {
                        return;
                    }

                    pacienteEncontrado = null;
                    estadoPaciente.textContent =
                        'No se pudo consultar. Revise la conexion y su sesion.';
                }
            }, 350);
        });
    }


    // =========================================================
    // 6. ENVIAR SOLICITUD
    // =========================================================

    if (formTraslado) {

        formTraslado.addEventListener('submit', async (e) => {
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
                    pacienteEncontrado !== null
                    && pacienteEncontrado.ci === ciIngresada;


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



            // 7. ENVIAR LA SOLICITUD AL SERVIDOR

            if (formTraslado.dataset.enviando === '1') {
                return;
            }

            const token = document.getElementById('csrf-traslado');

            if (!token || !token.value) {
                alert('Recargue la pagina antes de enviar la solicitud.');
                return;
            }

            const datos = new FormData();

            datos.append('token', token.value);
            datos.append('id_tipo_elemento', tipoElemento.value);
            datos.append(
                'ci_paciente',
                tipoElemento.value === '1' ? ciPaciente.value : ''
            );
            datos.append('id_origen', origen.value);
            datos.append('id_destino', destino.value);
            datos.append('id_canal', canal.value);
            datos.append(
                'prioridad',
                tipoElemento.value === '2' ? 'maxima' : prioridad.value
            );
            datos.append('fecha_hora_limite', fechaHoraLimite.value);
            datos.append('observaciones', observaciones.value.trim());

            const botonEnviar = formTraslado.querySelector(
                'button[type="submit"], input[type="submit"]'
            );

            formTraslado.dataset.enviando = '1';

            if (botonEnviar) {
                botonEnviar.disabled = true;
            }

            if (mensajeExito) {
                mensajeExito.classList.add('hidden');
            }

            try {
                const respuesta = await fetch(
                    '../../moduloAmbulancias/Controlador/GuardarSolicitud.php',
                    {
                        method: 'POST',
                        body: datos,
                        headers: { Accept: 'application/json' }
                    }
                );

                if (respuesta.redirected) {
                    alert('La sesion finalizo. Vuelva a iniciar sesion.');
                    return;
                }

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.exito) {
                    alert(
                        resultado.mensaje
                        || 'No se pudo guardar la solicitud.'
                    );
                    return;
                }

                if (mensajeExito) {
                    mensajeExito.textContent = resultado.mensaje;
                }

            } catch (error) {
                alert(
                    'No se pudo confirmar el guardado. '
                    + 'Revise si la solicitud se registro antes de reenviarla.'
                );
                return;

            } finally {
                formTraslado.dataset.enviando = '0';

                if (botonEnviar) {
                    botonEnviar.disabled = false;
                }
            }


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
            pacienteEncontrado = null;
            numeroBusqueda++;
            clearTimeout(temporizadorBusqueda);


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

    /*
        Dirección local de la nueva encuesta (apuntamos a nuestro propio módulo local)
        Ajustado para que funcione en XAMPP con PHP embebido o Apache normal.
    */
    const URL_ENCUESTA_PUBLICA =
        window.location.origin + "/SIGSM/moduloEncuestas/Vista/responder.php"; // Si usas Apache local
    // O si usas `php -S`: window.location.origin + "/moduloEncuestas/Vista/responder.php"


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
            // NOMBRES PARA MOSTRAR EN PANTALLA
            // -------------------------------------------------

            const nombreCategoria =
                categoriaQR.options[
                    categoriaQR.selectedIndex
                ].text;

            if (lblCategoria) {
                lblCategoria.textContent = nombreCategoria;
            }

            // -------------------------------------------------
            // URL DE LA ENCUESTA
            // -------------------------------------------------
            // Solo pasamos la categoría, la encuesta es única (institucional)

            // Tratamos de armar una ruta dinámica basada en donde estamos parados
            let baseUrl = window.location.origin;
            if (window.location.protocol === 'file:' || window.location.port === '5500' || baseUrl === 'null' || !baseUrl.startsWith('http')) {
                baseUrl = 'http://localhost/SIGSM';
            } else if (window.location.pathname.includes('/SIGSM/')) {
                baseUrl += '/SIGSM';
            }
            const urlEncuesta = baseUrl + "/moduloEncuestas/Vista/responder.php?categoria=" + encodeURIComponent(categoriaQR.value);

            const btnAbrir = document.getElementById('btn-abrir-encuesta');
            if (btnAbrir) {
                btnAbrir.href = urlEncuesta;
            }

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