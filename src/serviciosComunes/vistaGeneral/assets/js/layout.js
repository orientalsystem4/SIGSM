document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const openMenuBtn = document.getElementById("openMenuBtn");
    const closeMenuBtn = document.getElementById("closeMenuBtn");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    // Función para abrir el menú
    const openMenu = () => {
        sidebar.classList.add("active");
        sidebarOverlay.classList.add("active");
    };

    // Función para cerrar el menú
    const closeMenu = () => {
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
    };

    if (openMenuBtn) openMenuBtn.addEventListener("click", openMenu);
    if (closeMenuBtn) closeMenuBtn.addEventListener("click", closeMenu);
    if (sidebarOverlay) sidebarOverlay.addEventListener("click", closeMenu);
});