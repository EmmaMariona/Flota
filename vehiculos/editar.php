<?php
$titulo_pagina = "Editar Vehículo";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Sucursal.php';

if (!puedeEditar()) {
    setAlerta('danger', 'No tienes permisos para editar vehículos.');
    header("Location: " . BASE_URL . "/vehiculos/lista.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$vehiculo = Vehiculo::obtenerPorId($conn, $id);

if (!$vehiculo) {
    setAlerta('danger', 'Vehículo no encontrado.');
    header("Location: " . BASE_URL . "/vehiculos/lista.php");
    exit;
}

$sucursales = Sucursal::obtenerTodas($conn);
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Editar Vehículo: <span class="text-primary"><?= htmlspecialchars($vehiculo['placa']) ?></span></h2>
                <p class="text-secondary small mb-0">Actualiza la ficha técnica, kilometraje o estado operativo de la unidad.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $vehiculo['id'] ?>" class="btn btn-outline-info d-flex align-items-center gap-1">
                    <i class="bi bi-eye"></i> Ver Ficha
                </a>
                <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/controllers/VehiculoController.php?accion=actualizar" method="POST">
                    <input type="hidden" name="id" value="<?= $vehiculo['id'] ?>">

                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Identificación del Vehículo</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Placa <span class="text-danger">*</span></label>
                            <input type="text" name="placa" class="form-control text-uppercase fw-bold" required value="<?= htmlspecialchars($vehiculo['placa']) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Sucursal Asignada</label>
                            <select name="sucursal_id" class="form-select">
                                <option value="">-- Sin sucursal --</option>
                                <?php foreach ($sucursales as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $vehiculo['sucursal_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo de Unidad</label>
                            <select name="tipo" class="form-select">
                                <?php
                                $tipos = ['Camión', 'Cabezal / Rastra', 'Pickup', 'Furgón', 'Plataforma', 'Otro'];
                                foreach ($tipos as $t):
                                ?>
                                    <option value="<?= $t ?>" <?= ($vehiculo['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-gear me-2"></i>Especificaciones Mecánicas</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" class="form-control" value="<?= htmlspecialchars($vehiculo['marca'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" class="form-control" value="<?= htmlspecialchars($vehiculo['modelo'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Año</label>
                            <input type="number" name="anio" class="form-control" value="<?= htmlspecialchars($vehiculo['anio'] ?? '') ?>" min="1980" max="<?= date('Y') + 1 ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Número de Motor</label>
                            <input type="text" name="motor" class="form-control" value="<?= htmlspecialchars($vehiculo['motor'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Número de Chasis (VIN)</label>
                            <input type="text" name="vin" class="form-control text-uppercase" value="<?= htmlspecialchars($vehiculo['vin'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Capacidad de Carga (Toneladas)</label>
                            <input type="number" step="0.1" name="tonelaje" class="form-control" value="<?= htmlspecialchars($vehiculo['tonelaje'] ?? 0) ?>">
                        </div>
                    </div>

                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-speedometer2 me-2"></i>Estado Operativo y Kilometraje</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Estado Operativo</label>
                            <select name="estado" class="form-select">
                                <option value="Activo" <?= $vehiculo['estado'] === 'Activo' ? 'selected' : '' ?>>Activo (En operación)</option>
                                <option value="Taller" <?= $vehiculo['estado'] === 'Taller' ? 'selected' : '' ?>>Taller (En mantenimiento)</option>
                                <option value="Inactivo" <?= $vehiculo['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo (Fuera de servicio)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Kilometraje Actual</label>
                            <div class="input-group">
                                <input type="number" name="kilometraje_actual" class="form-control" value="<?= (int)($vehiculo['kilometraje_actual'] ?? 0) ?>" min="0">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">km</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha de Adquisición</label>
                            <input type="date" name="fecha_adquisicion" class="form-control" value="<?= htmlspecialchars($vehiculo['fecha_adquisicion'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones o Notas Adicionales</label>
                            <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($vehiculo['observaciones'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Actualizar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
