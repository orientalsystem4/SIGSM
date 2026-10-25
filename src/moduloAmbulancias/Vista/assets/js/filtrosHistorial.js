// Combina los filtros sin modificar los datos ni los permisos del historial.
function normalizarTextoHistorial(valor) {
    return String(valor).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}
function normalizarCedulaHistorial(valor) {
    return String(valor).replace(/[.\-\s]/g, '');
}
function coincideFilaHistorial(fila, filtros) {
    const cedula = normalizarCedulaHistorial(filtros.cedula);
    return (!cedula || (fila.cedula !== '' && normalizarCedulaHistorial(fila.cedula) === cedula))
        && (!filtros.vehiculo || fila.vehiculo === filtros.vehiculo)
        && normalizarTextoHistorial(fila.texto).includes(normalizarTextoHistorial(filtros.texto));
}
document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tabla-estados');
    const cedula = document.getElementById('historial-cedula');
    const vehiculo = document.getElementById('historial-vehiculo');
    const texto = document.getElementById('buscar-historial');
    const limpiar = document.getElementById('limpiar-historial');
    const resultado = document.getElementById('historial-resultados');
    if (!tabla || !cedula || !vehiculo || !texto || !limpiar || !resultado) return;
    const filas = Array.from(tabla.querySelectorAll('tbody tr[data-fila-busqueda]'));
    const aplicar = () => {
        let eventos = 0;
        const traslados = new Set();
        const filtros = { cedula: cedula.value, vehiculo: vehiculo.value, texto: texto.value };
        filas.forEach(fila => {
            const coincide = coincideFilaHistorial({
                cedula: fila.dataset.ci, vehiculo: fila.dataset.vehiculo, texto: fila.textContent
            }, filtros);
            fila.classList.toggle('hidden', !coincide);
            if (coincide) { eventos++; traslados.add(fila.dataset.traslado); }
        });
        resultado.textContent = eventos
            ? `${traslados.size} traslado(s), ${eventos} cambio(s) de estado.`
            : 'No hay registros que coincidan con los filtros.';
        if (!filas.length) resultado.textContent = 'No hay historial de traslados.';
    };
    cedula.addEventListener('input', aplicar);
    texto.addEventListener('input', aplicar);
    vehiculo.addEventListener('change', aplicar);
    limpiar.addEventListener('click', () => {
        cedula.value = ''; vehiculo.value = ''; texto.value = '';
        aplicar(); cedula.focus();
    });
    aplicar();
});
