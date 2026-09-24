<?php
$titulo_pagina = "Módulo de Reportes";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Sucursal.php';
require_once __DIR__ . '/../models/Vehiculo.php';

$sucursales = Sucursal::obtenerTodas($conn);
$vehiculos = Vehiculo::obtenerTodos($conn);
?>

<div class="d-flex flex-column gap-4">
    <div>
        <h2 class="fw-bold mb-1">Centro de Reportes y Exportación</h2>
        <p class="text-secondary small mb-0">Genera reportes operativos, costos de mantenimiento y auditoría de flota con exportación a Excel y PDF.</p>
    </div>

    <div class="row g-4">
        <!-- 1. Reporte de Flota por Sucursal -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary mb-3">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h5 class="fw-bold text-light mb-2">Reporte de Flota Vehicular</h5>
                    <p class="text-secondary small">Inventario completo de unidades activas, asignadas por sucursal, kilometrajes y especificaciones mecánicas.</p>
                </div>

                <form action="<?= BASE_URL ?>/reportes/exportar.php" method="GET" target="_blank" class="mt-3 pt-3 border-top border-secondary border-opacity-25">
                    <input type="hidden" name="tipo_reporte" value="vehiculos">
                    <div class="mb-3">
                        <label class="form-label small">Filtrar por Sucursal</label>
                        <select name="sucursal_id" class="form-select form-select-sm">
                            <option value="">Todas las sucursales</option>
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="formato" value="excel" class="btn btn-success btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button type="submit" name="formato" value="print" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-printer"></i> Imprimir / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Reporte de Mantenimientos y Costos -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning mb-3">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h5 class="fw-bold text-light mb-2">Reporte de Mantenimientos y Costos</h5>
                    <p class="text-secondary small">Detalle cronológico de gastos en servicios preventivos y correctivos, talleres y repuestos por período.</p>
                </div>

                <form action="<?= BASE_URL ?>/reportes/exportar.php" method="GET" target="_blank" class="mt-3 pt-3 border-top border-secondary border-opacity-25">
                    <input type="hidden" name="tipo_reporte" value="mantenimientos">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= date('Y-01-01') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Tipo de Servicio</label>
                            <select name="tipo_servicio" class="form-select form-select-sm">
                                <option value="">Preventivo y Correctivo</option>
                                <option value="Preventivo">Solo Preventivos</option>
                                <option value="Correctivo">Solo Correctivos</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="formato" value="excel" class="btn btn-success btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button type="submit" name="formato" value="print" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-printer"></i> Imprimir / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Reporte de Desgaste de Llantas -->
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon-wrapper bg-info-subtle text-info mb-3">
                        <i class="bi bi-record-circle"></i>
                    </div>
                    <h5 class="fw-bold text-light mb-2">Auditoría de Neumáticos</h5>
                    <p class="text-secondary small">Seguimiento de llantas por vehículo y posición, cálculo de kilometraje recorrido y alertas de desgaste.</p>
                </div>

                <form action="<?= BASE_URL ?>/reportes/exportar.php" method="GET" target="_blank" class="mt-3 pt-3 border-top border-secondary border-opacity-25">
                    <input type="hidden" name="tipo_reporte" value="llantas">
                    <div class="mb-3">
                        <label class="form-label small">Filtrar por Vehículo</label>
                        <select name="vehiculo_id" class="form-select form-select-sm">
                            <option value="">Todos los vehículos</option>
                            <?php foreach ($vehiculos as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['placa'] . ' - ' . $v['marca']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="formato" value="excel" class="btn btn-success btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button type="submit" name="formato" value="print" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-printer"></i> Imprimir / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
