/**
 * Gráficos del Dashboard con Chart.js (Tema Oscuro)
 */

window.renderDashboardCharts = function(flotaData) {
    if (!flotaData) return;

    const {
        costosLabels = [],
        costosValues = [],
        flotaActivos = 0,
        flotaTaller = 0,
        flotaInactivos = 0,
        sucursalesLabels = [],
        sucursalesValues = []
    } = flotaData;

    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

    // 1. Gráfico de Costos Mensuales
    const ctxCostos = document.getElementById('chartCostosMensuales');
    if (ctxCostos) {
        if (window.chartCostosInstance) window.chartCostosInstance.destroy();
        window.chartCostosInstance = new Chart(ctxCostos, {
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
                    x: { grid: { display: false } },
                    y: {
                        grid: { color: 'rgba(51, 65, 85, 0.4)' },
                        ticks: {
                            callback: (val) => 'L. ' + Number(val).toLocaleString()
                        }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Estado de la Flota (Dona)
    const ctxEstado = document.getElementById('chartEstadoFlota');
    if (ctxEstado) {
        if (window.chartEstadoInstance) window.chartEstadoInstance.destroy();
        window.chartEstadoInstance = new Chart(ctxEstado, {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'En Taller', 'Inactivos'],
                datasets: [{
                    data: [flotaActivos, flotaTaller, flotaInactivos],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderColor: '#1e293b',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 15, usePointStyle: true }
                    }
                }
            }
        });
    }

    // 3. Gráfico de Flota por Sucursal (Barras Horizontales)
    const ctxSucursales = document.getElementById('chartSucursales');
    if (ctxSucursales) {
        if (window.chartSucursalesInstance) window.chartSucursalesInstance.destroy();
        window.chartSucursalesInstance = new Chart(ctxSucursales, {
            type: 'bar',
            data: {
                labels: sucursalesLabels,
                datasets: [{
                    label: 'Unidades',
                    data: sucursalesValues,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: 'rgba(51, 65, 85, 0.4)' },
                        ticks: { stepSize: 1, precision: 0 }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.flotaData) {
        window.renderDashboardCharts(window.flotaData);
    }
});
