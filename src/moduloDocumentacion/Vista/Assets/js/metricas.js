document.addEventListener('DOMContentLoaded', () => {
    const filtroCategoria = document.getElementById('filtro-categoria');
    const graficosContainer = document.getElementById('graficos-container');
    const comentariosContainer = document.getElementById('comentarios-container');
    const lblTotal = document.getElementById('lbl-total-encuestas');
    const lblPromedio = document.getElementById('lbl-promedio-general');
    
    // Almacenará las instancias de Chart.js para destruirlas al actualizar
    let charts = [];

    // URL dinámica al controlador
    const API_URL = '../../moduloEncuestas/Controlador/MetricasControlador.php';

    // Colores para los gráficos (estilo Google Forms)
    const chartColors = [
        '#fbbc04', // Amarillo/Naranja Forms
        '#4285f4', // Azul Forms
        '#ea4335', // Rojo Forms
        '#34a853', // Verde Forms
        '#8ab4f8'  // Celeste
    ];

    function cargarMetricas(categoria) {
        // Mostrar cargando
        graficosContainer.innerHTML = '<p class="text-center text-muted">Cargando métricas...</p>';
        comentariosContainer.innerHTML = '';
        lblTotal.textContent = '-';
        lblPromedio.textContent = '-';
        
        // Destruir gráficos anteriores
        charts.forEach(c => c.destroy());
        charts = [];

        fetch(`${API_URL}?categoria=${categoria}`)
            .then(res => res.json())
            .then(data => {
                if (!data.exito) {
                    graficosContainer.innerHTML = `<p style="color:red">Error: ${data.error}</p>`;
                    return;
                }

                renderizarMetricas(data);
            })
            .catch(err => {
                console.error(err);
                graficosContainer.innerHTML = `<p style="color:red">Error de conexión al cargar métricas.</p>`;
            });
    }

    function renderizarMetricas(data) {
        graficosContainer.innerHTML = '';
        lblTotal.textContent = data.total;

        let sumaPromedios = 0;
        let cantidadPreguntasValidas = 0;

        if (data.total === 0) {
            graficosContainer.innerHTML = '<div class="card-formulario"><p class="text-center text-muted" style="margin:0;">No hay encuestas registradas para esta categoría.</p></div>';
            lblPromedio.textContent = '0.0 / 5';
            comentariosContainer.innerHTML = '<p class="text-muted">Sin comentarios.</p>';
            return;
        }

        data.preguntas.forEach((pregunta, index) => {
            if (pregunta.total_respuestas > 0) {
                sumaPromedios += pregunta.promedio;
                cantidadPreguntasValidas++;
            }

            // Crear tarjeta de pregunta
            const card = document.createElement('div');
            card.className = 'card-formulario';
            card.style.marginBottom = '0'; // manejado por el gap del container

            // Título de la pregunta y su promedio
            const header = document.createElement('div');
            header.style.display = 'flex';
            header.style.justifyContent = 'space-between';
            header.style.alignItems = 'flex-start';
            header.style.marginBottom = '20px';
            header.innerHTML = `
                <h4 style="margin:0; font-size: 16px; font-weight: bold; color: #1e293b;">${pregunta.texto}</h4>
                <div style="background: #eef2f6; padding: 5px 12px; border-radius: 12px; font-size: 14px; font-weight: bold; color: #0284c7;">
                    ★ ${pregunta.promedio}
                </div>
            `;
            card.appendChild(header);

            // Contenedor del canvas
            const canvasContainer = document.createElement('div');
            canvasContainer.style.position = 'relative';
            canvasContainer.style.height = '200px';
            canvasContainer.style.width = '100%';
            
            const canvas = document.createElement('canvas');
            canvasContainer.appendChild(canvas);
            card.appendChild(canvasContainer);
            graficosContainer.appendChild(card);

            // Preparar datos para Chart.js
            // Mapeamos los valores de 1 a 5
            const valores = [1, 2, 3, 4, 5];
            const frecuencias = valores.map(v => pregunta.distribucion[v] || 0);

            // Elegir color base para esta pregunta basado en su índice
            const colorBase = chartColors[index % chartColors.length];

            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['1 (Malo)', '2', '3 (Regular)', '4', '5 (Excelente)'],
                    datasets: [{
                        label: 'Votos',
                        data: frecuencias,
                        backgroundColor: colorBase,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw;
                                    const perc = Math.round((val / (pregunta.total_respuestas || 1)) * 100);
                                    return ` ${val} votos (${perc}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 } // no decimales en cantidad de votos
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            charts.push(chart);
        });

        // Promedio general
        if (cantidadPreguntasValidas > 0) {
            const promGen = (sumaPromedios / cantidadPreguntasValidas).toFixed(1);
            lblPromedio.textContent = `${promGen} / 5`;
        } else {
            lblPromedio.textContent = '0.0 / 5';
        }

        // Renderizar comentarios
        if (data.comentarios.length === 0) {
            comentariosContainer.innerHTML = '<p class="text-muted">Aún no hay comentarios adicionales registrados.</p>';
        } else {
            data.comentarios.forEach(comentario => {
                const cDiv = document.createElement('div');
                cDiv.style.background = 'white';
                cDiv.style.border = '1px solid #e2e8f0';
                cDiv.style.padding = '15px';
                cDiv.style.borderRadius = '8px';
                cDiv.style.color = '#334155';
                cDiv.style.fontSize = '14px';
                cDiv.style.fontStyle = 'italic';
                cDiv.innerHTML = `"${comentario}"`;
                comentariosContainer.appendChild(cDiv);
            });
        }
    }

    // Inicializar evento y primer carga
    if (filtroCategoria) {
        filtroCategoria.addEventListener('change', (e) => {
            cargarMetricas(e.target.value);
        });
    }
    
    // Cargar si la sección está visible al inicio o al cambiar pestaña
    cargarMetricas(filtroCategoria ? filtroCategoria.value : 0);
});

