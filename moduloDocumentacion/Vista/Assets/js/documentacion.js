// ============================================================
// S.I.G.S.M.
// PORTAL DE DOCUMENTACIÓN Y ENCUESTAS
// ============================================================


document.addEventListener("DOMContentLoaded", function () {


    // ========================================================
    // 1. NAVEGACIÓN PRINCIPAL
    // ========================================================

    const navButtons =
        document.querySelectorAll(".nav-btn[data-target]");

    const viewSections =
        document.querySelectorAll(".view-section");


    navButtons.forEach(function (button) {

        button.addEventListener("click", function () {


            navButtons.forEach(function (btn) {

                btn.classList.remove("active");

            });


            viewSections.forEach(function (section) {

                section.classList.add("hidden");

                section.classList.remove("active");

            });


            button.classList.add("active");


            const targetId =
                button.getAttribute("data-target");


            const target =
                document.getElementById(targetId);


            if (target) {

                target.classList.remove("hidden");

                target.classList.add("active");

            }

        });

    });



    // ========================================================
    // 2. SUBNAVEGACIÓN
    // ========================================================

    const subButtons =
        document.querySelectorAll(".sub-btn[data-sub]");


    subButtons.forEach(function (button) {


        button.addEventListener("click", function () {


            const parent =
                button.closest(".view-section");


            if (!parent) {

                return;

            }


            const buttons =
                parent.querySelectorAll(".sub-btn[data-sub]");


            const contents =
                parent.querySelectorAll(".sub-content");


            buttons.forEach(function (btn) {

                btn.classList.remove("active");

            });


            contents.forEach(function (content) {

                content.classList.add("hidden");

                content.classList.remove("active");

            });


            button.classList.add("active");


            const targetId =
                button.getAttribute("data-sub");


            const target =
                document.getElementById(targetId);


            if (target) {

                target.classList.remove("hidden");

                target.classList.add("active");

            }

        });

    });



    // ========================================================
    // 3. DOCUMENTOS
    // ========================================================

    cargarDocumentos();



    // ========================================================
    // 4. FORMULARIO SUBIR DOCUMENTO
    // ========================================================

    const formDocumento =
        document.getElementById("form-subir-documento");


    if (formDocumento) {


        formDocumento.addEventListener("submit", function (event) {


            event.preventDefault();


            const categoria =
                document.getElementById("categoriaDocumento");


            const archivo =
                document.getElementById("archivoDocumento");



            if (
                !categoria ||
                categoria.value === ""
            ) {

                alert(
                    "Debe seleccionar una categoría."
                );

                return;

            }



            if (
                !archivo ||
                !archivo.files ||
                archivo.files.length === 0
            ) {

                alert(
                    "Debe seleccionar un archivo PDF."
                );

                return;

            }



            const archivoSeleccionado =
                archivo.files[0];


            const nombreArchivo =
                archivoSeleccionado.name;


            const esPDF =
                archivoSeleccionado.type === "application/pdf" ||
                nombreArchivo
                    .toLowerCase()
                    .endsWith(".pdf");



            if (!esPDF) {

                alert(
                    "El archivo seleccionado debe ser un PDF."
                );

                return;

            }



            const documentosLocales =
                obtenerDocumentosLocales();



            // ------------------------------------------------
            // IDS TEMPORALES
            // ------------------------------------------------

            /*
                En nuestra base de datos el documento de prueba
                existente tiene id_documento = 2.

                Para la demostración, los documentos nuevos
                creados en el navegador comienzan después.
            */

            let ultimoId = 2;


            documentosLocales.forEach(function (documento) {


                const id =
                    Number(documento.id_documento);


                if (id > ultimoId) {

                    ultimoId = id;

                }

            });



            // ------------------------------------------------
            // OBJETO QUE REPRESENTA DOCUMENTO
            // ------------------------------------------------

            const nuevoDocumento = {


                id_documento:
                    ultimoId + 1,


                archivo_url:
                    nombreArchivo,


                fecha_carga:
                    new Date().toISOString(),


                id_categoria:
                    Number(categoria.value),


                id_usuario_carga:
                    1,


                usuario:
                    "admin_dti",


                activo:
                    true,


                origen:
                    "local"

            };



            documentosLocales.push(
                nuevoDocumento
            );


            guardarDocumentosLocales(
                documentosLocales
            );


            cargarDocumentos();


            formDocumento.reset();


            cerrarModal(
                "modal-subir-doc"
            );


            seleccionarCategoria(
                nuevoDocumento.id_categoria
            );


            alert(
                "Documento guardado correctamente."
            );

        });

    }



    // ========================================================
    // 5. ICONOS
    // ========================================================

    if (typeof lucide !== "undefined") {

        lucide.createIcons();

    }

});



// ============================================================
// DOCUMENTO REAL DE PRUEBA DE LA BASE DE DATOS
// ============================================================

/*
    Actualmente tenemos cargado en MariaDB:

    id_documento = 2
    id_categoria = 1 -> Urología
    id_usuario_carga = 1 -> admin_dti
    activo = 1

    Como todavía no tenemos backend PHP conectado,
    lo representamos visualmente acá.
*/

const documentoBaseDatos = {


    id_documento:
        2,


    archivo_url:
        "/documentos/urologia/prostatectomia_radical.pdf",


    fecha_carga:
        null,


    id_categoria:
        1,


    id_usuario_carga:
        1,


    usuario:
        "admin_dti",


    activo:
        true,


    origen:
        "base_datos"

};



// ============================================================
// LOCALSTORAGE
// ============================================================

function obtenerDocumentosLocales() {


    try {


        const datos =
            JSON.parse(
                localStorage.getItem(
                    "sigsm_documentos"
                )
            );


        if (Array.isArray(datos)) {

            return datos;

        }


        return [];


    } catch (error) {


        console.error(
            "Error leyendo documentos:",
            error
        );


        return [];

    }

}



function guardarDocumentosLocales(documentos) {


    localStorage.setItem(

        "sigsm_documentos",

        JSON.stringify(documentos)

    );

}



// ============================================================
// OBTENER TODOS LOS DOCUMENTOS
// ============================================================

function obtenerTodosLosDocumentos() {


    const locales =
        obtenerDocumentosLocales();


    return [

        documentoBaseDatos,

        ...locales

    ];

}



// ============================================================
// CARGAR DOCUMENTOS EN LAS TABLAS
// ============================================================

function cargarDocumentos() {


    // Limpiar las 7 tablas.

    for (
        let categoria = 1;
        categoria <= 7;
        categoria++
    ) {


        const tbody =
            document.getElementById(
                "tabla-documentos-" + categoria
            );


        if (tbody) {

            tbody.innerHTML = "";

        }

    }



    const documentos =
        obtenerTodosLosDocumentos();



    documentos.forEach(function (documento) {


        if (!documento.activo) {

            return;

        }



        const tbody =
            document.getElementById(
                "tabla-documentos-" +
                documento.id_categoria
            );


        if (!tbody) {

            return;

        }



        const fila =
            document.createElement("tr");



        const nombre =
            obtenerNombreDocumento(
                documento.archivo_url
            );



        const fecha =
            obtenerFechaDocumento(
                documento.fecha_carga
            );



        fila.innerHTML = `

            <td>

                <strong>
                    ${nombre}
                </strong>

            </td>


            <td>
                ${fecha}
            </td>


            <td>
                ${documento.usuario}
            </td>


            <td>

                <button
                    class="btn-icon text-danger"
                    title="Dar de baja"
                    onclick="darDeBajaDocumento(
                        ${documento.id_documento},
                        '${documento.origen}'
                    )">

                    <i data-lucide="trash-2"></i>

                </button>

            </td>

        `;



        tbody.appendChild(
            fila
        );

    });



    actualizarMensajesVacios();



    if (typeof lucide !== "undefined") {

        lucide.createIcons();

    }

}



// ============================================================
// NOMBRE DEL DOCUMENTO
// ============================================================

function obtenerNombreDocumento(ruta) {


    /*
        Como la tabla DOCUMENTO no tiene un atributo "titulo",
        mostramos el nombre del archivo.
    */


    if (!ruta) {

        return "Documento";

    }



    const partes =
        ruta.split("/");


    let nombre =
        partes[partes.length - 1];



    // Quitar extensión .pdf.

    nombre =
        nombre.replace(
            /\.pdf$/i,
            ""
        );



    // Reemplazar guiones bajos.

    nombre =
        nombre.replace(
            /_/g,
            " "
        );



    // Primera letra mayúscula.

    if (nombre.length > 0) {

        nombre =
            nombre.charAt(0).toUpperCase() +
            nombre.slice(1);

    }



    return nombre;

}



// ============================================================
// FECHA
// ============================================================

function obtenerFechaDocumento(fechaOriginal) {


    /*
        El documento real cargado en la BD puede mostrarse
        sin inventar una fecha si no tenemos el valor exacto
        disponible en esta pantalla estática.
    */


    if (!fechaOriginal) {

        return "—";

    }



    const fecha =
        new Date(fechaOriginal);



    if (
        Number.isNaN(
            fecha.getTime()
        )
    ) {

        return "—";

    }



    return fecha.toLocaleDateString(
        "es-UY",
        {

            day:
                "2-digit",

            month:
                "2-digit",

            year:
                "numeric"

        }
    );

}



// ============================================================
// MENSAJES PARA CATEGORÍAS VACÍAS
// ============================================================

function actualizarMensajesVacios() {


    for (
        let categoria = 1;
        categoria <= 7;
        categoria++
    ) {


        const tbody =
            document.getElementById(
                "tabla-documentos-" + categoria
            );


        const mensaje =
            document.getElementById(
                "vacio-" + categoria
            );



        if (!tbody || !mensaje) {

            continue;

        }



        if (tbody.children.length === 0) {

            mensaje.style.display =
                "block";

        } else {

            mensaje.style.display =
                "none";

        }

    }

}



// ============================================================
// DAR DE BAJA
// ============================================================

window.darDeBajaDocumento =
    function (
        idDocumento,
        origen
    ) {


        // ----------------------------------------------------
        // DOCUMENTO DE LA BD
        // ----------------------------------------------------

        if (origen === "base_datos") {


            alert(
                "Este documento pertenece a la base de datos. " +
                "La baja real se realizará desde el backend."
            );


            return;

        }



        // ----------------------------------------------------
        // DOCUMENTO SIMULADO LOCAL
        // ----------------------------------------------------

        const confirmar =
            confirm(
                "¿Desea dar de baja este documento?"
            );


        if (!confirmar) {

            return;

        }



        const documentos =
            obtenerDocumentosLocales();



        const documento =
            documentos.find(
                function (doc) {


                    return Number(
                        doc.id_documento
                    ) === Number(
                        idDocumento
                    );

                }
            );



        if (!documento) {


            alert(
                "No se encontró el documento."
            );


            return;

        }



        documento.activo =
            false;



        guardarDocumentosLocales(
            documentos
        );



        cargarDocumentos();



        alert(
            "Documento dado de baja correctamente."
        );

    };



// ============================================================
// SELECCIONAR CATEGORÍA
// ============================================================

function seleccionarCategoria(idCategoria) {


    const idsCategorias = {


        1:
            "cat-urologia",


        2:
            "cat-cardiologia",


        3:
            "cat-traumatologia",


        4:
            "cat-gastroenterologia",


        5:
            "cat-ginecobstetricia",


        6:
            "cat-imagenologia",


        7:
            "cat-medicina-nuclear"

    };



    const targetId =
        idsCategorias[idCategoria];



    if (!targetId) {

        return;

    }



    const secDocumentos =
        document.getElementById(
            "sec-documentos"
        );



    if (!secDocumentos) {

        return;

    }



    const botones =
        secDocumentos.querySelectorAll(
            ".sub-btn[data-sub]"
        );


    const contenidos =
        secDocumentos.querySelectorAll(
            ".sub-content"
        );



    botones.forEach(function (button) {

        button.classList.remove("active");

    });



    contenidos.forEach(function (content) {

        content.classList.add("hidden");

        content.classList.remove("active");

    });



    const boton =
        secDocumentos.querySelector(
            `[data-sub="${targetId}"]`
        );



    const contenido =
        document.getElementById(
            targetId
        );



    if (boton) {

        boton.classList.add("active");

    }



    if (contenido) {

        contenido.classList.remove("hidden");

        contenido.classList.add("active");

    }

}



// ============================================================
// MODALES
// ============================================================

window.abrirModal =
    function (idModal) {


        const modal =
            document.getElementById(
                idModal
            );


        if (modal) {

            modal.classList.add("active");

        }

    };



window.cerrarModal =
    function (idModal) {


        const modal =
            document.getElementById(
                idModal
            );


        if (modal) {

            modal.classList.remove("active");

        }

    };



// ============================================================
// ACORDEÓN
// ============================================================

window.toggleAccordion =
    function (header) {


        const content =
            header.nextElementSibling;


        if (!content) {

            return;

        }


        content.classList.toggle(
            "active"
        );

    };