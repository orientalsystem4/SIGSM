let solicitudSeleccionada = null;

const tiposElemento = {
    1: "Paciente",
    2: "Órgano",
    3: "Muestra biológica",
    4: "Cadetería de trámites",
    5: "Insumo médico",
    6: "Equipamiento"
};

const ubicaciones = {
    1: "Hospital de Clínicas",
    2: "Hospital Maciel",
    3: "CHPR"
};

const canalesSolicitud = {
    1: "Gestión Salud",
    2: "Correo",
    3: "Papel"
};

const usuariosSolicitantes = {
    1: "admin_dti",
    2: "itanu_miniño",
    4: "registros_medicos"
};

const nombresPrioridad = {
    normal: "Normal",
    alta: "Alta",
    maxima: "Máxima"
};

const vehiculos = [
    {
        id_vehiculo: 1,
        id_tipo_vehiculo: 1,
        nombre: "Ambulancia - AMB001"
    },
    {
        id_vehiculo: 2,
        id_tipo_vehiculo: 2,
        nombre: "Auto - AUT001"
    },
    {
        id_vehiculo: 3,
        id_tipo_vehiculo: 3,
        nombre: "Camión - CAM001"
    }
];

const compatibilidadVehiculoElemento = {
    1: [1, 2, 3, 4, 5, 6],
    2: [1, 2, 3, 4, 5, 6],
    3: [5, 6]
};

const CLAVE_HISTORIAL_COMPARTIDO =
    "sigsm_historial_traslados";

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const navButtons =
            document.querySelectorAll(
                '.nav-btn[data-target]'
            );

        const viewSections =
            document.querySelectorAll(
                '.view-section'
            );

        navButtons.forEach(
            function (boton) {

                boton.addEventListener(
                    'click',
                    function () {

                        navButtons.forEach(
                            function (b) {
                                b.classList.remove(
                                    'active'
                                );
                            }
                        );

                        viewSections.forEach(
                            function (seccion) {

                                seccion.classList.add(
                                    'hidden'
                                );

                                seccion.classList.remove(
                                    'active'
                                );
                            }
                        );

                        boton.classList.add(
                            'active'
                        );

                        const target =
                            document.getElementById(
                                boton.getAttribute(
                                    'data-target'
                                )
                            );

                        if (target) {
                            target.classList.remove(
                                'hidden'
                            );

                            target.classList.add(
                                'active'
                            );
                        }
                    }
                );
            }
        );


        document
            .querySelectorAll('.sub-btn')
            .forEach(
                function (boton) {

                    boton.addEventListener(
                        'click',
                        function () {

                            const seccionPrincipal =
                                boton.closest(
                                    '.view-section'
                                );

                            if (!seccionPrincipal) {
                                return;
                            }

                            seccionPrincipal
                                .querySelectorAll(
                                    '.sub-btn'
                                )
                                .forEach(
                                    function (b) {
                                        b.classList.remove(
                                            'active'
                                        );
                                    }
                                );

                            seccionPrincipal
                                .querySelectorAll(
                                    '.sub-content'
                                )
                                .forEach(
                                    function (contenido) {

                                        contenido.classList.add(
                                            'hidden'
                                        );

                                        contenido.classList.remove(
                                            'active'
                                        );
                                    }
                                );

                            boton.classList.add(
                                'active'
                            );

                            const target =
                                document.getElementById(
                                    boton.getAttribute(
                                        'data-sub'
                                    )
                                );

                            if (target) {
                                target.classList.remove(
                                    'hidden'
                                );

                                target.classList.add(
                                    'active'
                                );
                            }

                            if (
                                boton.getAttribute(
                                    'data-sub'
                                ) ===
                                'sub-historial'
                            ) {
                                cargarHistorialCompartido();
                            }
                        }
                    );
                }
            );


        cargarSolicitudesEnfermeria();
        cargarHistorialCompartido();


        const formularioAprobar =
            document.getElementById(
                'formAprobar'
            );

        if (formularioAprobar) {

            formularioAprobar.addEventListener(
                'submit',
                function (evento) {

                    evento.preventDefault();

                    if (!solicitudSeleccionada) {
                        alert(
                            "No se pudo identificar la solicitud."
                        );

                        return;
                    }


                    const vehiculoSelect =
                        document.getElementById(
                            'vehiculoAsignado'
                        );

                    const choferSelect =
                        document.getElementById(
                            'choferAsignado'
                        );

                    const enfermeroSelect =
                        document.getElementById(
                            'enfermeroAsignado'
                        );

                    const proveedorSelect =
                        document.getElementById(
                            'proveedorAsignado'
                        );

                    const rutaInput =
                        document.getElementById(
                            'rutaSeleccionada'
                        );

                    const idRutaInput =
                        document.getElementById(
                            'idRutaSeleccionada'
                        );


                    if (!vehiculoSelect.value) {
                        alert(
                            "Debe seleccionar un vehículo."
                        );

                        return;
                    }


                    // RNE4
                    const idTipoElemento =
                        obtenerIdTipoElementoSolicitud(
                            solicitudSeleccionada
                        );

                    const vehiculoSeleccionado =
                        vehiculos.find(
                            function (vehiculo) {

                                return String(
                                    vehiculo.id_vehiculo
                                ) ===
                                String(
                                    vehiculoSelect.value
                                );
                            }
                        );

                    if (
                        !vehiculoSeleccionado ||
                        !esVehiculoCompatible(
                            vehiculoSeleccionado
                                .id_tipo_vehiculo,
                            idTipoElemento
                        )
                    ) {

                        alert(
                            "El vehículo seleccionado no es compatible con el tipo de elemento de la solicitud."
                        );

                        cargarVehiculosCompatibles(
                            solicitudSeleccionada
                        );

                        return;
                    }


                    if (!rutaInput.value) {

                        alert(
                            "No se pudo determinar la ruta."
                        );

                        return;
                    }


                    if (!idRutaInput.value) {

                        alert(
                            "La ruta no se encuentra registrada."
                        );

                        return;
                    }


                    if (!choferSelect.value) {

                        alert(
                            "Debe seleccionar un chofer."
                        );

                        return;
                    }


                    if (!enfermeroSelect.value) {

                        alert(
                            "Debe seleccionar un enfermero."
                        );

                        return;
                    }


                    const tituloElemento =
                        solicitudSeleccionada
                            .querySelector(
                                '.card-title'
                            );

                    const tituloSolicitud =
                        tituloElemento
                            ? tituloElemento
                                .textContent
                                .trim()
                            : "Solicitud";

                    const numeroSolicitud =
                        tituloSolicitud
                            .replace(
                                "Solicitud #",
                                ""
                            )
                            .trim();


                    const tipoElemento =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Tipo de elemento:"
                        );

                    const ciPaciente =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Cédula del paciente:"
                        );

                    const origen =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Origen:"
                        );

                    const destino =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Destino:"
                        );

                    const canal =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Canal:"
                        );

                    const solicitante =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Solicitante:"
                        );

                    const prioridad =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Prioridad:"
                        );

                    const fechaLimite =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Debe llegar antes de:"
                        );

                    const observaciones =
                        obtenerDetalle(
                            solicitudSeleccionada,
                            "Observaciones:"
                        );


                    const vehiculo =
                        vehiculoSelect.options[
                            vehiculoSelect
                                .selectedIndex
                        ].text.trim();

                    const chofer =
                        choferSelect.options[
                            choferSelect
                                .selectedIndex
                        ].text.trim();

                    const enfermero =
                        enfermeroSelect.options[
                            enfermeroSelect
                                .selectedIndex
                        ].text.trim();

                    const proveedor =
                        proveedorSelect.options[
                            proveedorSelect
                                .selectedIndex
                        ].text.trim();

                    const ruta =
                        rutaInput.value;


                    let matricula =
                        vehiculo;

                    if (
                        vehiculo.includes(" - ")
                    ) {

                        const partes =
                            vehiculo.split(" - ");

                        matricula =
                            partes[
                                partes.length - 1
                            ];
                    }


                    const activos =
                        document.getElementById(
                            'sub-curso'
                        );

                    if (!activos) {

                        alert(
                            "No se encontró la sección de Traslados activos."
                        );

                        return;
                    }


                    const nuevaTarjeta =
                        document.createElement(
                            'div'
                        );

                    nuevaTarjeta.className =
                        'card-traslado';


                    const detallePaciente =
                        ciPaciente &&
                        ciPaciente !== "-"
                            ? crearDetalle(
                                "Cédula del paciente:",
                                ciPaciente
                            )
                            : "";


                    const detallePrioridad =
                        prioridad
                            ? crearDetalle(
                                "Prioridad:",
                                prioridad
                            )
                            : "";


                    const detalleLimite =
                        fechaLimite &&
                        fechaLimite !==
                            "Sin límite"
                            ? crearDetalle(
                                "Debe llegar antes de:",
                                fechaLimite
                            )
                            : "";


                    const detalleObservaciones =
                        observaciones &&
                        observaciones !==
                            "Sin observaciones"
                            ? crearDetalle(
                                "Observaciones:",
                                observaciones
                            )
                            : "";


                    nuevaTarjeta.innerHTML = `
                        <div
                            class="accordion-header"
                            onclick="toggleAccordion(this)"
                        >

                            <div class="header-info">

                                <h3>
                                    Traslado - ${tituloSolicitud} - ${matricula}
                                </h3>

                                <span class="badge badge-warning">
                                    Asignado
                                </span>

                            </div>

                            <i
                                data-lucide="chevron-down"
                                class="accordion-icon"
                                style="transform: rotate(180deg);"
                            ></i>

                        </div>


                        <div class="accordion-content open">

                            <div style="padding: 15px;">

                                ${crearDetalle(
                                    "Tipo de elemento:",
                                    tipoElemento
                                )}

                                ${detallePaciente}

                                ${crearDetalle(
                                    "Origen:",
                                    origen
                                )}

                                ${crearDetalle(
                                    "Destino:",
                                    destino
                                )}

                                ${crearDetalle(
                                    "Ruta:",
                                    ruta
                                )}

                                ${crearDetalle(
                                    "Vehículo:",
                                    vehiculo
                                )}

                                ${crearDetalle(
                                    "Chofer:",
                                    chofer
                                )}

                                ${crearDetalle(
                                    "Enfermero:",
                                    enfermero
                                )}

                                ${crearDetalle(
                                    "Proveedor externo:",
                                    proveedor
                                )}

                                ${crearDetalle(
                                    "Canal:",
                                    canal
                                )}

                                ${crearDetalle(
                                    "Solicitante:",
                                    solicitante
                                )}

                                ${detallePrioridad}

                                ${detalleLimite}

                                ${detalleObservaciones}

                            </div>


                            <div class="timeline">

                                <div class="timeline-step completed">

                                    <div class="step-marker">
                                        <i data-lucide="check"></i>
                                    </div>

                                    <div class="step-content">

                                        <h4>
                                            Solicitado
                                        </h4>

                                        <div class="chat-message received mb-1">
                                            Traslado solicitado.
                                        </div>

                                    </div>

                                </div>


                                <div class="timeline-step active">

                                    <div class="step-marker">
                                        <i data-lucide="car"></i>
                                    </div>

                                    <div class="step-content">

                                        <h4>
                                            Asignado
                                        </h4>

                                        <div class="chat-message system">

                                            <i
                                                data-lucide="check-circle"
                                                class="icon-sm"
                                            ></i>

                                            <span>
                                                Vehículo y personal asignados.
                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <div class="timeline-step pending">

                                    <div class="step-marker">
                                        3
                                    </div>

                                    <div class="step-content">

                                        <h4>
                                            En curso - Destino: ${destino}
                                        </h4>

                                    </div>

                                </div>

                            </div>

                        </div>
                    `;


                    activos.appendChild(
                        nuevaTarjeta
                    );


                    agregarHistorial(
                        tituloSolicitud,
                        "Asignado",
                        "admin_transporte",
                        "Vehículo y personal asignados.",
                        "badge-warning"
                    );


                    eliminarSolicitudLocalStorage(
                        numeroSolicitud
                    );


                    solicitudSeleccionada.remove();


                    cerrarModal(
                        'modalAprobar'
                    );


                    mostrarSubSeccion(
                        'sub-curso'
                    );


                    if (
                        typeof lucide !==
                        "undefined"
                    ) {
                        lucide.createIcons();
                    }


                    alert(
                        tituloSolicitud +
                        " fue aprobada y agregada a Traslados activos."
                    );


                    formularioAprobar.reset();

                    solicitudSeleccionada =
                        null;
                }
            );
        }


        const formularioCancelar =
            document.getElementById(
                'formCancelar'
            );


        if (formularioCancelar) {

            formularioCancelar.addEventListener(
                'submit',
                function (evento) {

                    evento.preventDefault();


                    if (!solicitudSeleccionada) {

                        alert(
                            "No se pudo identificar la solicitud."
                        );

                        return;
                    }


                    const motivo =
                        document.getElementById(
                            'motivoCancelacion'
                        );


                    if (
                        !motivo ||
                        !motivo.value.trim()
                    ) {

                        alert(
                            "Debe indicar el motivo de la cancelación."
                        );

                        return;
                    }


                    const titulo =
                        solicitudSeleccionada
                            .querySelector(
                                '.card-title'
                            );


                    const nombreSolicitud =
                        titulo
                            ? titulo
                                .textContent
                                .trim()
                            : "Solicitud";


                    const numeroSolicitud =
                        nombreSolicitud
                            .replace(
                                "Solicitud #",
                                ""
                            )
                            .trim();


                    eliminarSolicitudLocalStorage(
                        numeroSolicitud
                    );


                    solicitudSeleccionada.remove();


                    cerrarModal(
                        'modalDenegar'
                    );


                    alert(
                        nombreSolicitud +
                        " cancelada correctamente."
                    );


                    formularioCancelar.reset();

                    solicitudSeleccionada =
                        null;
                }
            );
        }


        if (
            typeof lucide !==
            "undefined"
        ) {
            lucide.createIcons();
        }

    }
);


// ============================================================
// DETALLES
// ============================================================

function obtenerDetalle(
    tarjeta,
    etiqueta
) {

    const detalles =
        tarjeta.querySelectorAll(
            '.detail-item'
        );


    for (
        const detalle of detalles
    ) {

        const texto =
            detalle.textContent
                .replace(
                    /\s+/g,
                    " "
                )
                .trim();


        if (
            texto.startsWith(
                etiqueta
            )
        ) {

            return texto
                .replace(
                    etiqueta,
                    ""
                )
                .trim();
        }
    }


    return "";
}


function crearDetalle(
    etiqueta,
    valor
) {

    return `
        <p class="detail-item">

            <strong>
                ${etiqueta}
            </strong>

            ${valor}

        </p>
    `;
}


// ============================================================
// SOLICITUDES DE ENFERMERÍA
// ============================================================

function cargarSolicitudesEnfermeria() {

    const contenedor =
        document.getElementById(
            'sub-pendientes'
        );


    if (!contenedor) {
        return;
    }


    let solicitudes =
        [];


    try {

        solicitudes =
            JSON.parse(
                localStorage.getItem(
                    'sigsm_solicitudes'
                )
            ) || [];

    } catch (error) {

        solicitudes =
            [];
    }


    solicitudes.forEach(
        function (solicitud) {

            const existente =
                contenedor.querySelector(
                    `[data-solicitud-id="${solicitud.id_solicitud}"]`
                );


            if (existente) {
                return;
            }


            contenedor.appendChild(
                crearTarjetaSolicitud(
                    solicitud
                )
            );
        }
    );


    if (
        typeof lucide !==
        "undefined"
    ) {
        lucide.createIcons();
    }
}


function crearTarjetaSolicitud(
    solicitud
) {

    const tarjeta =
        document.createElement(
            'div'
        );


    tarjeta.className =
        'card-pendiente';


    tarjeta.dataset.solicitudId =
        solicitud.id_solicitud;


    tarjeta.dataset.tipoElemento =
        solicitud.id_tipo_elemento;


    const tipo =
        tiposElemento[
            solicitud.id_tipo_elemento
        ] ||
        "Sin especificar";


    const origen =
        ubicaciones[
            solicitud.id_origen
        ] ||
        "Sin especificar";


    const destino =
        ubicaciones[
            solicitud.id_destino
        ] ||
        "Sin especificar";


    const canal =
        canalesSolicitud[
            solicitud.id_canal
        ] ||
        "Sin especificar";


    const solicitante =
        usuariosSolicitantes[
            solicitud.id_usuario_solicit
        ] ||
        "Usuario";


    const prioridad =
        nombresPrioridad[
            solicitud.prioridad
        ] ||
        "Normal";


    const detallePaciente =
        solicitud.ci_paciente
            ? crearDetalle(
                "Cédula del paciente:",
                solicitud.ci_paciente
            )
            : "";


    let detalleLimite =
        "";


    if (
        solicitud.fecha_hora_limite
    ) {

        const fecha =
            new Date(
                solicitud.fecha_hora_limite
            );


        const fechaFormateada =
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


        detalleLimite =
            crearDetalle(
                "Debe llegar antes de:",
                fechaFormateada
            );
    }


    const detalleObservaciones =
        solicitud.observaciones
            ? crearDetalle(
                "Observaciones:",
                solicitud.observaciones
            )
            : "";


    tarjeta.innerHTML = `

        <div class="item-header">

            <h3 class="card-title">
                Solicitud #${solicitud.id_solicitud}
            </h3>

            <span class="badge badge-gray">
                Pendiente
            </span>

        </div>


        ${crearDetalle(
            "Tipo de elemento:",
            tipo
        )}

        ${detallePaciente}

        ${crearDetalle(
            "Origen:",
            origen
        )}

        ${crearDetalle(
            "Destino:",
            destino
        )}

        ${crearDetalle(
            "Canal:",
            canal
        )}

        ${crearDetalle(
            "Solicitante:",
            solicitante
        )}

        ${crearDetalle(
            "Prioridad:",
            prioridad
        )}

        ${detalleLimite}

        ${detalleObservaciones}


        <div class="acciones-pendientes">

            <button
                type="button"
                class="btn-action btn-success"
                onclick="
                    seleccionarSolicitud(this);
                    abrirModal('modalAprobar');
                "
            >

                <i data-lucide="check"></i>

                Aprobar y asignar

            </button>


            <button
                type="button"
                class="btn-action btn-danger"
                onclick="
                    seleccionarSolicitud(this);
                    abrirModal('modalDenegar');
                "
            >

                <i data-lucide="x"></i>

                Cancelar solicitud

            </button>

        </div>
    `;


    return tarjeta;
}


// ============================================================
// RNE4 - COMPATIBILIDAD VEHÍCULO / ELEMENTO
// ============================================================

function obtenerIdTipoElementoSolicitud(
    tarjetaSolicitud
) {

    if (!tarjetaSolicitud) {
        return null;
    }


    if (
        tarjetaSolicitud.dataset
            .tipoElemento
    ) {

        return Number(
            tarjetaSolicitud.dataset
                .tipoElemento
        );
    }


    const nombreTipo =
        obtenerDetalle(
            tarjetaSolicitud,
            "Tipo de elemento:"
        );


    const entrada =
        Object.entries(
            tiposElemento
        ).find(
            function (par) {

                return par[1] ===
                    nombreTipo;
            }
        );


    return entrada
        ? Number(
            entrada[0]
        )
        : null;
}


function esVehiculoCompatible(
    idTipoVehiculo,
    idTipoElemento
) {

    const permitidos =
        compatibilidadVehiculoElemento[
            idTipoVehiculo
        ] || [];


    return permitidos.includes(
        Number(
            idTipoElemento
        )
    );
}


function cargarVehiculosCompatibles(
    tarjetaSolicitud
) {

    const selectVehiculo =
        document.getElementById(
            'vehiculoAsignado'
        );


    if (!selectVehiculo) {
        return;
    }


    const idTipoElemento =
        obtenerIdTipoElementoSolicitud(
            tarjetaSolicitud
        );


    selectVehiculo.innerHTML = `

        <option value="">
            Seleccione vehículo...
        </option>

    `;


    const compatibles =
        vehiculos.filter(
            function (vehiculo) {

                return esVehiculoCompatible(
                    vehiculo.id_tipo_vehiculo,
                    idTipoElemento
                );
            }
        );


    compatibles.forEach(
        function (vehiculo) {

            const opcion =
                document.createElement(
                    'option'
                );


            opcion.value =
                vehiculo.id_vehiculo;


            opcion.textContent =
                vehiculo.nombre;


            selectVehiculo.appendChild(
                opcion
            );
        }
    );


    if (
        compatibles.length ===
        0
    ) {

        const opcion =
            document.createElement(
                'option'
            );


        opcion.value =
            "";


        opcion.disabled =
            true;


        opcion.textContent =
            "No hay vehículos compatibles";


        selectVehiculo.appendChild(
            opcion
        );
    }
}


// ============================================================
// ELIMINAR SOLICITUD LOCALSTORAGE
// ============================================================

function eliminarSolicitudLocalStorage(
    numeroSolicitud
) {

    let solicitudes =
        [];


    try {

        solicitudes =
            JSON.parse(
                localStorage.getItem(
                    'sigsm_solicitudes'
                )
            ) || [];

    } catch (error) {

        solicitudes =
            [];
    }


    const nuevasSolicitudes =
        solicitudes.filter(
            function (solicitud) {

                return String(
                    solicitud.id_solicitud
                ) !==
                String(
                    numeroSolicitud
                );
            }
        );


    localStorage.setItem(
        'sigsm_solicitudes',
        JSON.stringify(
            nuevasSolicitudes
        )
    );
}


// ============================================================
// HISTORIAL
// ============================================================

function agregarHistorial(
    solicitud,
    estado,
    usuario,
    observacion,
    claseBadge,
    fechaPersonalizada = null,
    idRegistro = null
) {

    const tablaHistorial =
        document.querySelector(
            '#sub-historial .tabla-historial tbody'
        );


    if (!tablaHistorial) {
        return;
    }


    if (
        idRegistro &&
        tablaHistorial.querySelector(
            `[data-registro-id="${idRegistro}"]`
        )
    ) {

        return;
    }


    const nuevaFila =
        document.createElement(
            'tr'
        );


    if (
        idRegistro
    ) {

        nuevaFila.dataset.registroId =
            idRegistro;
    }


    const fecha =
        fechaPersonalizada
            ? new Date(
                fechaPersonalizada
            )
            : new Date();


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


    nuevaFila.innerHTML = `

        <td>

            <strong>
                ${solicitud}
            </strong>

        </td>


        <td>
            ${fechaHora}
        </td>


        <td>

            <span class="badge ${claseBadge}">
                ${estado}
            </span>

        </td>


        <td>
            ${usuario}
        </td>


        <td>
            ${observacion}
        </td>
    `;


    tablaHistorial.appendChild(
        nuevaFila
    );
}


// ============================================================
// HISTORIAL COMPARTIDO CON CHOFER
// ============================================================

function claseBadgePorEstado(
    estado
) {

    if (
        estado === "Asignado"
    ) {

        return "badge-warning";
    }


    if (
        estado === "En curso" ||
        estado === "En retorno"
    ) {

        return "badge-warning";
    }


    if (
        estado === "En destino" ||
        estado === "Finalizado"
    ) {

        return "badge-success";
    }


    return "badge-gray";
}


function cargarHistorialCompartido() {

    let historial =
        [];


    try {

        historial =
            JSON.parse(
                localStorage.getItem(
                    CLAVE_HISTORIAL_COMPARTIDO
                )
            ) || [];

    } catch (error) {

        historial =
            [];
    }


    historial.forEach(
        function (registro) {

            agregarHistorial(
                `Traslado #${registro.id_traslado}`,
                registro.estado,
                registro.usuario,
                registro.observacion,
                claseBadgePorEstado(
                    registro.estado
                ),
                registro.fecha_hora,
                registro.id
            );
        }
    );
}


// Si Chofer y Unidad de Enlace están abiertos
// en dos pestañas, se actualiza automáticamente.

window.addEventListener(
    'storage',
    function (evento) {

        if (
            evento.key ===
            CLAVE_HISTORIAL_COMPARTIDO
        ) {

            cargarHistorialCompartido();
        }
    }
);


// ============================================================
// MOSTRAR SUBSECCIÓN
// ============================================================

function mostrarSubSeccion(
    idSeccion
) {

    const seccionTraslados =
        document.getElementById(
            'sec-traslados'
        );


    if (!seccionTraslados) {
        return;
    }


    seccionTraslados
        .querySelectorAll(
            '.sub-btn'
        )
        .forEach(
            function (boton) {

                boton.classList.remove(
                    'active'
                );
            }
        );


    seccionTraslados
        .querySelectorAll(
            '.sub-content'
        )
        .forEach(
            function (contenido) {

                contenido.classList.add(
                    'hidden'
                );

                contenido.classList.remove(
                    'active'
                );
            }
        );


    const botonObjetivo =
        seccionTraslados
            .querySelector(
                `[data-sub="${idSeccion}"]`
            );


    const contenidoObjetivo =
        document.getElementById(
            idSeccion
        );


    if (
        botonObjetivo
    ) {

        botonObjetivo.classList.add(
            'active'
        );
    }


    if (
        contenidoObjetivo
    ) {

        contenidoObjetivo
            .classList.remove(
                'hidden'
            );

        contenidoObjetivo
            .classList.add(
                'active'
            );
    }
}


// ============================================================
// SELECCIONAR SOLICITUD
// ============================================================

window.seleccionarSolicitud =
    function (boton) {

        solicitudSeleccionada =
            boton.closest(
                '.card-pendiente'
            );
    };


// ============================================================
// ABRIR MODAL
// ============================================================

window.abrirModal =
    function (modalId) {

        const modal =
            document.getElementById(
                modalId
            );


        if (!modal) {
            return;
        }


        const perfil =
            document.querySelector(
                '.profile-name'
            );


        const nombreUsuario =
            perfil
                ? perfil.textContent.trim()
                : "Usuario";


        document
            .querySelectorAll(
                '.responsable-nombre'
            )
            .forEach(
                function (span) {

                    span.textContent =
                        nombreUsuario;
                }
            );


        if (
            modalId ===
                'modalAprobar' &&
            solicitudSeleccionada
        ) {

            cargarVehiculosCompatibles(
                solicitudSeleccionada
            );


            const origen =
                obtenerDetalle(
                    solicitudSeleccionada,
                    "Origen:"
                );


            const destino =
                obtenerDetalle(
                    solicitudSeleccionada,
                    "Destino:"
                );


            const campoRuta =
                document.getElementById(
                    'rutaSeleccionada'
                );


            const campoIdRuta =
                document.getElementById(
                    'idRutaSeleccionada'
                );


            if (
                campoRuta &&
                campoIdRuta
            ) {

                if (
                    origen ===
                        "Hospital de Clínicas" &&
                    destino ===
                        "Hospital Maciel"
                ) {

                    campoRuta.value =
                        "Hospital de Clínicas → Hospital Maciel";


                    campoIdRuta.value =
                        "1";
                }


                else if (
                    origen ===
                        "Hospital de Clínicas" &&
                    destino ===
                        "CHPR"
                ) {

                    campoRuta.value =
                        "Hospital de Clínicas → CHPR";


                    campoIdRuta.value =
                        "2";
                }


                else {

                    campoRuta.value =
                        "Ruta no registrada";


                    campoIdRuta.value =
                        "";
                }
            }
        }


        modal.classList.add(
            'active'
        );
    };


// ============================================================
// CERRAR MODAL
// ============================================================

window.cerrarModal =
    function (modalId) {

        const modal =
            document.getElementById(
                modalId
            );


        if (
            modal
        ) {

            modal.classList.remove(
                'active'
            );
        }
    };


// ============================================================
// ACORDEONES
// ============================================================

window.toggleAccordion =
    function (headerElement) {

        const content =
            headerElement
                .nextElementSibling;


        const icon =
            headerElement
                .querySelector(
                    '.accordion-icon'
                );


        if (!content) {
            return;
        }


        content.classList.toggle(
            'open'
        );


        if (
            icon
        ) {

            icon.style.transform =
                content.classList.contains(
                    'open'
                )
                    ? "rotate(180deg)"
                    : "rotate(0deg)";
        }
    };