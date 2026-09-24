
document.addEventListener('DOMContentLoaded', () => {
    if (!window.flotaData) return;

    const {
        costosLabels,
        costosValues,
        flotaActivos,
        flotaTaller,
        flotaInactivos,
        sucursalesLabels,
        sucursalesValues
    } = window.flotaData;

    // Colores y Opciones Globales de Chart.js
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

    // 1. Gráfico de Costos Mensuales (Barras con degradado)
    const ctxCostos = document.getElementById('chartCostosMensuales');
    if (ctxCostos) {
        new Chart(ctxCostos, {
            type: 'bar',
            data: {
                labels: costosLabels,
                datasets: [{
                    label: 'Inversión (Lps)',
                    data: costosValues,
                    backgroundColor: 'rgba(59, 130, 246, 0.65)',
                    borderColor: '#3b82f6',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' L. ' + Number(ctx.raw).toLocaleString('es-HN', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        grid: { color: 'rgba(51, 65, 85, 0.4)' },
                        ticks: {
                            callback: (val) => 'L. ' + val.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Estado de la Flota (Dona)
    const ctxEstado = document.getElementById('chartEstadoFlota');
    if (ctxEstado) {
        new Chart(ctxEstado, {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'En Taller', 'Inactivos'],
                datasets: [{
                    data: [flotaActivos, flotaTaller, flotaInactivos],
                    backgroundColor: [
                        '#10b981', // Verde esmeralda
                        '#f59e0b', // Ámbar
                        '#ef4444'  // Rojo
                    ],
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '72%'
            }
        });
    }

    // 3. Gráfico de Flota por Sucursal (Barras horizontales)
    const ctxSucursales = document.getElementById('chartSucursales');
    if (ctxSucursales) {
        new Chart(ctxSucursales, {
            type: 'bar',
            data: {
                labels: sucursalesLabels,
                datasets: [{
                    label: 'Unidades',
                    data: sucursalesValues,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1.5,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(51, 65, 85, 0.4)' },
                        ticks: { stepSize: 2 }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
