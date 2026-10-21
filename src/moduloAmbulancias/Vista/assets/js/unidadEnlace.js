// Navegacion y filtros. Los datos se consultan y guardan en MariaDB.
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".sub-btn[data-sub]").forEach(boton => {
        boton.addEventListener("click", () => {
            const seccion = boton.closest(".view-section");
            seccion.querySelectorAll(".sub-btn").forEach(b => b.classList.remove("active"));
            seccion.querySelectorAll(".sub-content").forEach(panel => {
                panel.classList.add("hidden");
                panel.classList.remove("active");
            });
            const panel = document.getElementById(boton.dataset.sub);
            if (panel) {
                boton.classList.add("active");
                panel.classList.remove("hidden");
                panel.classList.add("active");
            }
        });
    });
    const normalizar = texto => texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    document.querySelectorAll("[data-filtrar]").forEach(input => {
        input.addEventListener("input", () => {
            const tabla = document.getElementById(input.dataset.filtrar);
            if (!tabla) return;
            let visibles = 0;
            tabla.querySelectorAll("tbody tr[data-fila-busqueda]").forEach(fila => {
                const coincide = normalizar(fila.textContent).includes(normalizar(input.value.trim()));
                fila.classList.toggle("hidden", !coincide);
                if (coincide) visibles++;
            });
            const aviso = document.querySelector(`[data-sin-resultados="${input.dataset.filtrar}"]`);
            if (aviso) aviso.classList.toggle("hidden", visibles > 0 || input.value.trim() === "");
        });
    });
    if (typeof lucide !== "undefined") lucide.createIcons();
});
window.toggleAccordion = cabecera => {
    const contenido = cabecera.nextElementSibling;
    if (!contenido) return;
    const abierto = contenido.classList.toggle("open");
    const icono = cabecera.querySelector(".accordion-icon");
    if (icono) icono.style.transform = abierto ? "rotate(180deg)" : "rotate(0deg)";
};
