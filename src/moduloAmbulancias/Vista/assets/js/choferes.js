document.addEventListener("DOMContentLoaded", () => {
    function conectarNavegacion(selectorBotones, selectorPaneles, atributo) {
        const botones = document.querySelectorAll(selectorBotones);
        const paneles = document.querySelectorAll(selectorPaneles);

        botones.forEach(boton => {
            boton.addEventListener("click", () => {
                botones.forEach(b => b.classList.remove("active"));

                paneles.forEach(panel => {
                    panel.classList.add("hidden");
                    panel.classList.remove("active");
                });

                boton.classList.add("active");

                const destino = document.getElementById(
                    boton.getAttribute(atributo)
                );

                if (destino) {
                    destino.classList.remove("hidden");
                    destino.classList.add("active");
                }
            });
        });
    }

    conectarNavegacion(
        ".nav-btn[data-target]", ".view-section", "data-target"
    );

    conectarNavegacion(
        ".tab-btn[data-tab]", ".tab-content", "data-tab"
    );

    if (typeof lucide !== "undefined") {
        lucide.createIcons();
    }
});

window.toggleAccordion = function (cabecera) {
    const contenido = cabecera.nextElementSibling;
    const icono = cabecera.querySelector(".accordion-icon");

    if (!contenido) return;

    contenido.classList.toggle("open");

    if (icono) {
        icono.style.transform = contenido.classList.contains("open")
            ? "rotate(180deg)"
            : "rotate(0deg)";
    }
};