<?php
$titulo_pagina = "Registrar Mantenimiento";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Sucursal.php';

if (!puedeEditar()) {
    setAlerta('danger', 'No tienes permisos para programar mantenimientos.');
    header("Location: " . BASE_URL . "/mantenimientos/lista.php");
    exit;
}

$vehiculos = Vehiculo::obtenerTodos($conn);
$sucursales = Sucursal::obtenerTodas($conn);
$preseleccionId = (int)($_GET['vehiculo_id'] ?? 0);
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Programar Mantenimiento</h2>
                <p class="text-secondary small mb-0">Registra una orden de servicio preventivo o correctivo para la flota vehicular.</p>
            </div>
            <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/controllers/MantenimientoController.php?accion=guardar" method="POST">
                    
                    <h5 class="fw-semibold text-warning mb-3"><i class="bi bi-truck me-2"></i>Asignación de Unidad y Sucursal</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Vehículo <span class="text-danger">*</span></label>
                            <select name="vehiculo_id" id="vehiculo_id" class="form-select" required>
                                <option value="">-- Selecciona el camión / vehículo --</option>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= $v['id'] ?>" data-sucursal="<?= $v['sucursal_id'] ?>" <?= $preseleccionId === (int)$v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['placa'] . ' | ' . $v['marca'] . ' ' . $v['modelo'] . ' (' . $v['sucursal'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Sucursal de Mantenimiento <span class="text-danger">*</span></label>
                            <select name="sucursal_id" id="sucursal_id" class="form-select" required>
                                <option value="">-- Selecciona la sucursal --</option>
                                <?php foreach ($sucursales as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-secondary" style="font-size: 0.72rem;">Lugar o sucursal donde se ejecuta el servicio.</small>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Tipo de Servicio <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="Preventivo">Preventivo (Rutina)</option>
                                <option value="Correctivo">Correctivo (Falla)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Estado Inicial</label>
                            <select name="estado" class="form-select">
                                <option value="Programado" selected>Programado</option>
                                <option value="En proceso">En proceso</option>
                                <option value="Completado">Completado</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-warning mb-3"><i class="bi bi-tools me-2"></i>Detalle de la Reparación / Mantenimiento</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-8">
                            <label class="form-label">Título del Servicio <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Ej. Cambio de Aceite 15W40, Filtros y Engrase General">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Costo (Lempiras) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">L.</span>
                                <input type="number" step="0.01" name="costo" class="form-control" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción Detallada / Repuestos Requeridos</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Especificar trabajos a realizar, piezas a cambiar, diagnóstico inicial..."></textarea>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-warning mb-3"><i class="bi bi-calendar-check me-2"></i>Tiempos, Kilometraje y Taller</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha Programada <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_programada" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha Realizada (si ya concluyó)</label>
                            <input type="date" name="fecha_realizada" class="form-control">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Kilometraje al Realizar Servicio</label>
                            <div class="input-group">
                                <input type="number" name="kilometraje_servicio" class="form-control" placeholder="Ej. 12500">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">km</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Taller / Proveedor del Servicio</label>
                            <input type="text" name="proveedor" class="form-control" placeholder="Ej. Taller Isuzu Central, Llantilandia, Taller Propio">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Observaciones Finales / Garantía</label>
                            <input type="text" name="observaciones" class="form-control" placeholder="Ej. Factura #4589, garantía 3 meses en repuestos">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-warning px-4 fw-semibold text-dark shadow-sm">
                            <i class="bi bi-printer me-1"></i> Guardar e Imprimir PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const vehiculoSelect = document.getElementById('vehiculo_id');
    const sucursalSelect = document.getElementById('sucursal_id');

    function syncSucursal() {
        const opt = vehiculoSelect.options[vehiculoSelect.selectedIndex];
        const sucId = opt ? opt.getAttribute('data-sucursal') : null;
        if (sucId && !sucursalSelect.value) {
            sucursalSelect.value = sucId;
        }
    }

    vehiculoSelect.addEventListener('change', syncSucursal);
    syncSucursal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
