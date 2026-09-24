<?php
$titulo_pagina = "Registrar Llanta";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Llanta.php';
require_once __DIR__ . '/../models/Vehiculo.php';

if (!puedeEditar()) {
    setAlerta('danger', 'No tienes permisos para registrar llantas.');
    header("Location: " . BASE_URL . "/llantas/lista.php");
    exit;
}

$vehiculos = Vehiculo::obtenerTodos($conn);
$posiciones = Llanta::obtenerPosiciones($conn);
$preseleccionId = (int)($_GET['vehiculo_id'] ?? 0);
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Registrar / Asignar Llanta</h2>
                <p class="text-secondary small mb-0">Asigna un neumático a una posición específica de un vehículo para su monitoreo de kilometraje.</p>
            </div>
            <a href="<?= BASE_URL ?>/llantas/lista.php" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Volver al Catálogo
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/controllers/LlantaController.php?accion=guardar" method="POST">
                    
                    <h5 class="fw-semibold text-info mb-3"><i class="bi bi-truck me-2"></i>Vehículo y Posición de Montaje</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Vehículo <span class="text-danger">*</span></label>
                            <select name="vehiculo_id" class="form-select" required>
                                <option value="">-- Seleccionar Vehículo --</option>
                                <?php foreach ($vehiculos as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $preseleccionId === (int)$v['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['placa'] . ' | ' . $v['marca'] . ' ' . $v['modelo'] . ' (' . $v['sucursal'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Posición en el Vehículo <span class="text-danger">*</span></label>
                            <select name="posicion_id" class="form-select" required>
                                <option value="">-- Seleccionar Posición --</option>
                                <?php foreach ($posiciones as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-secondary">Si ya existe una llanta activa en esta posición, pasará automáticamente a estado "Reemplazada".</small>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-info mb-3"><i class="bi bi-record-circle me-2"></i>Datos del Neumático</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Código / Número de Serie <span class="text-danger">*</span></label>
                            <input type="text" name="codigo" class="form-control text-uppercase fw-bold" required placeholder="Ej. LL-5012">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" class="form-control" placeholder="Ej. Michelin, Bridgestone, Goodyear">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Medida</label>
                            <input type="text" name="medida" class="form-control" placeholder="Ej. 295/80R22.5 o 215/75R17.5">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo de Banda / Aplicación</label>
                            <select name="tipo_llanta" class="form-select">
                                <option value="Direccional">Direccional (Eje delantero)</option>
                                <option value="Tracción">Tracción (Eje motriz)</option>
                                <option value="Toda posición" selected>Toda posición</option>
                                <option value="Remolque">Remolque / Rastra</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Costo (Lempiras)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">L.</span>
                                <input type="number" step="0.01" name="costo" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Código Llanta Reemplazada (Opcional)</label>
                            <input type="text" name="codigo_anterior" class="form-control text-uppercase" placeholder="Ej. LL-4089">
                        </div>
                    </div>

                    <h5 class="fw-semibold text-info mb-3"><i class="bi bi-speedometer2 me-2"></i>Instalación y Kilometraje</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha de Instalación <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_instalacion" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Kilometraje del Vehículo al Instalar</label>
                            <div class="input-group">
                                <input type="number" name="km_instalacion" class="form-control" placeholder="0" min="0">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">km</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones / Motivo de Instalación</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales, estado del rin, balanceo..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= BASE_URL ?>/llantas/lista.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-info px-4 fw-semibold text-dark">
                            <i class="bi bi-save me-1"></i> Guardar y Asignar Llanta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
