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
    // 4. ICONOS
    // ========================================================

    if (typeof lucide !== "undefined") {

        lucide.createIcons();

    }

});



// ============================================================
// CARGAR DOCUMENTOS EN LAS TABLAS
// ============================================================

function cargarDocumentos() {
    const documentos = window.documentosBD || [];

    const filtro = document.getElementById("filtro-estado-documentos");
    const estado = Number(filtro ? filtro.value : 1);

    for (let categoria = 1; categoria <= 7; categoria++) {
        const tabla = document.getElementById(
            "tabla-documentos-" + categoria
        );

        const aviso = document.getElementById("vacio-" + categoria);

        if (!tabla) {
            continue;
        }

        tabla.replaceChildren();

        const documentosCategoria = documentos.filter(function (documento) {
            return Number(documento.id_categoria) === categoria
                && Number(documento.activo) === estado;
        });

        documentosCategoria.forEach(function (documento) {
            const fila = document.createElement("tr");

            const nombre = documento.archivo_url.split("/").pop();

            const valores = [
                nombre,
                documento.fecha_carga || "Sin fecha",
                documento.usuario_carga
            ];

            valores.forEach(function (valor) {
                const celda = document.createElement("td");
                celda.textContent = valor;
                fila.appendChild(celda);
            });

            const acciones = document.createElement("td");

            const enlaces = [
                ["Ver", "ver.php"],
                ["Editar", "editar.php"]
            ];

            enlaces.forEach(function ([texto, pagina]) {
                const enlace = document.createElement("a");
                enlace.textContent = texto;
                enlace.href = pagina + "?id="
                    + encodeURIComponent(documento.id_documento);
                enlace.style.marginRight = "12px";
                acciones.appendChild(enlace);
            });
            const formularioBaja = document.createElement("form");
            formularioBaja.method = "POST";
            formularioBaja.action = "../Controlador/ControladorDocumentos.php";
            formularioBaja.style.display = "inline";

            const campoAccion = document.createElement("input");
            campoAccion.type = "hidden";
            campoAccion.name = "accion";
            campoAccion.value = "eliminar";

            const campoId = document.createElement("input");
            campoId.type = "hidden";
            campoId.name = "id_documento";
            campoId.value = documento.id_documento;

            const botonBaja = document.createElement("button");
            botonBaja.type = "submit";
            botonBaja.textContent = "Dar de baja";

            formularioBaja.addEventListener("submit", function (evento) {
                if (!confirm("¿Querés dar de baja este documento?")) {
                    evento.preventDefault();
                }
            });

            formularioBaja.append(campoAccion, campoId, botonBaja);
            if (Number(documento.activo) === 1) {
                acciones.appendChild(formularioBaja);
            }
            fila.appendChild(acciones);
            tabla.appendChild(fila);
        });

        if (aviso) {
            aviso.style.display = documentosCategoria.length === 0
                ? "block"
                : "none";
        }
    }
}


    document.addEventListener("DOMContentLoaded", function () {
    const filtro = document.getElementById("filtro-estado-documentos");

    if (filtro) {
        filtro.addEventListener("change", cargarDocumentos);
    }
});