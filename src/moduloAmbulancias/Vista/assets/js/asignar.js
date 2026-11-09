// Asignar traslado: con proveedor externo no hay vehículo ni chofer propios.
// Evita enviar ambos a la vez. El servidor valida igual (AsignarTraslado.php).
(function () {
    var proveedor = document.getElementById('proveedor');
    var propios = [document.getElementById('vehiculo'), document.getElementById('chofer')];
    if (!proveedor || !propios[0] || !propios[1]) {
        return;
    }

    function sincronizar() {
        var tercerizado = proveedor.value !== '';
        propios.forEach(function (campo) {
            if (tercerizado) {
                campo.value = '';
            }
            campo.disabled = tercerizado;
        });
    }

    proveedor.addEventListener('change', sincronizar);
    // Al cargar, y al volver con "atrás": el navegador puede haber restaurado valores.
    sincronizar();
    window.addEventListener('pageshow', sincronizar);
})();
