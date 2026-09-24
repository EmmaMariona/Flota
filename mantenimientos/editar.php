<?php
$titulo_pagina = "Editar Mantenimiento";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Sucursal.php';

if (!puedeEditar()) {
    setAlerta('danger', 'No tienes permisos para editar mantenimientos.');
    header("Location: " . BASE_URL . "/mantenimientos/lista.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$mantenimiento = Mantenimiento::obtenerPorId($conn, $id);

if (!$mantenimiento) {
    setAlerta('danger', 'Registro de mantenimiento no encontrado.');
    header("Location: " . BASE_URL . "/mantenimientos/lista.php");
    exit;
}

$vehiculos = Vehiculo::obtenerTodos($conn);
$sucursales = Sucursal::obtenerTodas($conn);
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Editar Mantenimiento #<?= $mantenimiento['id'] ?></h2>
                <p class="text-secondary small mb-0">Actualiza los costos, fechas o estado de la orden de trabajo.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/mantenimientos/imprimir.php?id=<?= $mantenimiento['id'] ?>" target="_blank" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-printer-fill"></i> Imprimir Orden (PDF)
                </a>
                <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Volver a la Lista
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/controllers/MantenimientoController.php?accion=actualizar" method="POST">
                    <input type="hidden" name="id" value="<?= $mantenimiento['id'] ?>">

                    <h5 class="fw-semibold text-warning mb-3"><i class="bi bi-truck me-2"></i>Asignación de Unidad y Sucursal</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Vehículo <span class="text-danger">*</span></label>
                            <select name="vehiculo_id" class="form-select" required>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= (int)$mantenimiento['vehiculo_id'] === (int)$v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['placa'] . ' | ' . $v['marca'] . ' ' . $v['modelo'] . ' (' . $v['sucursal'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Sucursal de Mantenimiento</label>
                            <select name="sucursal_id" class="form-select">
                                <option value="">-- Misma sucursal del vehículo --</option>
                                <?php foreach ($sucursales as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (int)($mantenimiento['sucursal_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-secondary" style="font-size: 0.72rem;">Lugar donde se atendió la unidad.</small>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Tipo de Servicio <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select" required>
                                <option value="Preventivo" <?= $mantenimiento['tipo'] === 'Preventivo' ? 'selected' : '' ?>>Preventivo</option>
                                <option value="Correctivo" <?= $mantenimiento['tipo'] === 'Correctivo' ? 'selected' : '' ?>>Correctivo</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="Programado" <?= $mantenimiento['estado'] === 'Programado' ? 'selected' : '' ?>>Programado</option>
                                <option value="En proceso" <?= $mantenimiento['estado'] === 'En proceso' ? 'selected' : '' ?>>En proceso</option>
                                <option value="Completado" <?= $mantenimiento['estado'] === 'Completado' ? 'selected' : '' ?>>Completado</option>
                                <option value="Cancelado" <?= $mantenimiento['estado'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-warning mb-3"><i class="bi bi-tools me-2"></i>Detalle de la Reparación / Mantenimiento</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-8">
                            <label class="form-label">Título del Servicio <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($mantenimiento['titulo']) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Costo (Lempiras) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">L.</span>
                                <input type="number" step="0.01" name="costo" class="form-control" required value="<?= htmlspecialchars($mantenimiento['costo']) ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción Detallada / Repuestos Requeridos</label>
                            <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($mantenimiento['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-warning mb-3"><i class="bi bi-calendar-check me-2"></i>Tiempos, Kilometraje y Taller</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha Programada <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_programada" class="form-control" required value="<?= htmlspecialchars($mantenimiento['fecha_programada']) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha Realizada</label>
                            <input type="date" name="fecha_realizada" class="form-control" value="<?= htmlspecialchars($mantenimiento['fecha_realizada'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Kilometraje del Servicio</label>
                            <div class="input-group">
                                <input type="number" name="kilometraje_servicio" class="form-control" value="<?= htmlspecialchars($mantenimiento['kilometraje_servicio'] ?? '') ?>">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">km</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Taller / Proveedor</label>
                            <input type="text" name="proveedor" class="form-control" value="<?= htmlspecialchars($mantenimiento['proveedor'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Observaciones / Factura</label>
                            <input type="text" name="observaciones" class="form-control" value="<?= htmlspecialchars($mantenimiento['observaciones'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= BASE_URL ?>/mantenimientos/imprimir.php?id=<?= $mantenimiento['id'] ?>" target="_blank" class="btn btn-outline-info d-flex align-items-center gap-1">
                            <i class="bi bi-printer"></i> Imprimir PDF
                        </a>
                        <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-warning px-4 fw-semibold text-dark shadow-sm">
                            <i class="bi bi-save me-1"></i> Actualizar Mantenimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
