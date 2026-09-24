<?php
$titulo_pagina = "Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Llanta.php';

$metricas = Dashboard::obtenerMetricas($conn);
$proximosMantenimientos = Mantenimiento::obtenerProximos($conn, 5);
$llantasAlerta = Llanta::obtenerProximasReemplazo($conn, 5000);
$costosMensuales = Dashboard::obtenerCostosMensuales($conn);
$flotaSucursal = Dashboard::obtenerFlotaPorSucursal($conn);
?>

<div class="d-flex flex-column gap-4">
    <!-- Encabezado y Acciones Rápidas -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="fw-bold mb-1">Panel de Control de Flota</h2>
            <p class="text-secondary small mb-0">Resumen operativo, estado de unidades e indicadores de mantenimiento en tiempo real.</p>
        </div>
        <?php if (puedeEditar()): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/vehiculos/crear.php" class="btn btn-primary d-flex align-items-center gap-2 btn-sm px-3 py-2 shadow-sm">
                <i class="bi bi-plus-circle"></i> Nuevo Vehículo
            </a>
            <a href="<?= BASE_URL ?>/mantenimientos/crear.php" class="btn btn-outline-light d-flex align-items-center gap-2 btn-sm px-3 py-2 shadow-sm">
                <i class="bi bi-tools"></i> Programar Servicio
            </a>
            <a href="<?= BASE_URL ?>/llantas/registrar.php" class="btn btn-outline-light d-flex align-items-center gap-2 btn-sm px-3 py-2 shadow-sm">
                <i class="bi bi-record-circle"></i> Registrar Llanta
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tarjetas de Estadísticas (KPIs) -->
    <div class="row g-3">
        <!-- Total Vehículos -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-secondary text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Flota Total</span>
                        <h3 class="fw-bold my-1"><?= number_format($metricas['vehiculos_total']) ?></h3>
                    </div>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="bi bi-truck"></i>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center small mt-auto pt-2 border-top border-secondary border-opacity-25">
                    <span class="badge bg-success-subtle text-success"><?= $metricas['vehiculos_activos'] ?> Activos</span>
                    <span class="badge bg-warning-subtle text-warning"><?= $metricas['vehiculos_taller'] ?> Taller</span>
                    <span class="badge bg-danger-subtle text-danger"><?= $metricas['vehiculos_inactivos'] ?> Inactivos</span>
                </div>
            </div>
        </div>

        <!-- Mantenimientos Pendientes -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-secondary text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Mantenimientos Pendientes</span>
                        <h3 class="fw-bold my-1 text-warning"><?= number_format($metricas['mantenimientos_pendientes']) ?></h3>
                    </div>
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                        <i class="bi bi-wrench-adjustable"></i>
                    </div>
                </div>
                <div class="small text-secondary mt-auto pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                    <span>Completados:</span>
                    <strong class="text-light"><?= $metricas['mantenimientos_completados'] ?> servicios</strong>
                </div>
            </div>
        </div>

        <!-- Gastos en Mantenimiento -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-secondary text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Costo Incurrido (Mes)</span>
                        <h3 class="fw-bold my-1 text-success">L. <?= number_format($metricas['costo_mes'], 2) ?></h3>
                    </div>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="small text-secondary mt-auto pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                    <span>Histórico Total:</span>
                    <strong class="text-light">L. <?= number_format($metricas['costo_total'], 2) ?></strong>
                </div>
            </div>
        </div>

        <!-- Llantas en Monitoreo -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-secondary text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Llantas Activas</span>
                        <h3 class="fw-bold my-1 text-info"><?= number_format($metricas['llantas_activas']) ?></h3>
                    </div>
                    <div class="stat-icon-wrapper bg-info-subtle text-info">
                        <i class="bi bi-disc"></i>
                    </div>
                </div>
                <div class="small text-secondary mt-auto pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                    <span>Monitoreo de desgaste:</span>
                    <strong class="text-info"><?= count($llantasAlerta) ?> alertas</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos Interactivos -->
    <div class="row g-3">
        <!-- Gráfica de Costos Mensuales -->
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-line-fill text-primary"></i>
                        Inversión en Mantenimiento por Mes (Lps.)
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary small">Últimos meses</span>
                </div>
                <div class="card-body">
                    <div style="height: 280px;">
                        <canvas id="chartCostosMensuales"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica de Estado de la Flota -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart-fill text-info"></i>
                        Estado Operativo
                    </span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div style="width: 220px; height: 220px;">
                        <canvas id="chartEstadoFlota"></canvas>
                    </div>
                    <div class="d-flex justify-content-around w-100 mt-3 pt-2 border-top border-secondary border-opacity-25 small text-center">
                        <div>
                            <div class="text-success fw-bold"><?= $metricas['vehiculos_activos'] ?></div>
                            <div class="text-secondary" style="font-size: 0.75rem;">Activos</div>
                        </div>
                        <div>
                            <div class="text-warning fw-bold"><?= $metricas['vehiculos_taller'] ?></div>
                            <div class="text-secondary" style="font-size: 0.75rem;">Taller</div>
                        </div>
                        <div>
                            <div class="text-danger fw-bold"><?= $metricas['vehiculos_inactivos'] ?></div>
                            <div class="text-secondary" style="font-size: 0.75rem;">Inactivos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablas de Resumen Operativo -->
    <div class="row g-3">
        <!-- Próximos Mantenimientos -->
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event text-warning"></i>
                        Próximos Mantenimientos Programados
                    </span>
                    <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-sm btn-outline-secondary border-0 text-primary small">Ver todos</a>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th>Servicio</th>
                                <th>Fecha Prog.</th>
                                <th>Estado</th>
                                <?php if (puedeEditar()): ?><th>Acción</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($proximosMantenimientos)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No hay mantenimientos pendientes.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($proximosMantenimientos as $m): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $m['vehiculo_id'] ?>" class="fw-bold text-decoration-none text-light">
                                            <?= htmlspecialchars($m['placa']) ?>
                                        </a>
                                        <div class="text-secondary small"><?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $m['tipo'] === 'Preventivo' ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?> me-1">
                                            <?= htmlspecialchars($m['tipo']) ?>
                                        </span>
                                        <span class="small"><?= htmlspecialchars($m['titulo']) ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><i class="bi bi-calendar3 me-1 text-secondary"></i><?= date('d/m/Y', strtotime($m['fecha_programada'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning">
                                            <?= htmlspecialchars($m['estado']) ?>
                                        </span>
                                    </td>
                                    <?php if (puedeEditar()): ?>
                                    <td>
                                        <a href="<?= BASE_URL ?>/controllers/MantenimientoController.php?accion=completar&id=<?= $m['id'] ?>" 
                                           class="btn btn-sm btn-outline-success py-0 px-2" 
                                           title="Marcar como Completado"
                                           data-confirm="¿Deseas marcar este servicio como Completado?">
                                            <i class="bi bi-check2"></i> Listo
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Distribución de Flota por Sucursal -->
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt-fill text-success"></i>
                        Vehículos por Sucursal
                    </span>
                    <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="btn btn-sm btn-outline-secondary border-0 text-primary small">Ver flota</a>
                </div>
                <div class="card-body">
                    <div style="height: 240px;">
                        <canvas id="chartSucursales"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuración de Datos para Gráficos en JavaScript -->
<script>
    window.flotaData = {
        costosLabels: <?= json_encode($costosMensuales['labels']) ?>,
        costosValues: <?= json_encode($costosMensuales['data']) ?>,
        flotaActivos: <?= (int)$metricas['vehiculos_activos'] ?>,
        flotaTaller: <?= (int)$metricas['vehiculos_taller'] ?>,
        flotaInactivos: <?= (int)$metricas['vehiculos_inactivos'] ?>,
        sucursalesLabels: <?= json_encode($flotaSucursal['labels']) ?>,
        sucursalesValues: <?= json_encode($flotaSucursal['data']) ?>
    };
</script>
<script src="<?= BASE_URL ?>/assets/js/dashboard-charts.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
