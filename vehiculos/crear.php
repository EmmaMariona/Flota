<?php
$titulo_pagina = "Registrar Nuevo Vehículo";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Sucursal.php';

if (!puedeEditar()) {
    setAlerta('danger', 'No tienes permisos para registrar vehículos.');
    header("Location: " . BASE_URL . "/vehiculos/lista.php");
    exit;
}

$sucursales = Sucursal::obtenerTodas($conn);
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Registrar Nuevo Vehículo</h2>
                <p class="text-secondary small mb-0">Ingresa los datos técnicos y operativos para incorporar una nueva unidad a la flota.</p>
            </div>
            <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/controllers/VehiculoController.php?accion=guardar" method="POST" enctype="multipart/form-data">
                    
                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Identificación del Vehículo</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Placa <span class="text-danger">*</span></label>
                            <input type="text" name="placa" class="form-control text-uppercase fw-bold" required placeholder="Ej. TCF-2548" autofocus>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Sucursal Asignada</label>
                            <select name="sucursal_id" class="form-select">
                                <option value="">-- Sin sucursal --</option>
                                <?php foreach ($sucursales as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo de Unidad</label>
                            <select name="tipo" class="form-select">
                                <option value="Camión">Camión</option>
                                <option value="Cabezal / Rastra">Cabezal / Rastra</option>
                                <option value="Pickup">Pickup</option>
                                <option value="Furgón">Furgón</option>
                                <option value="Plataforma">Plataforma</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-gear me-2"></i>Especificaciones Mecánicas</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" class="form-control" placeholder="Ej. Isuzu, Freightliner, Hino">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" class="form-control" placeholder="Ej. Forward, Cascadia, 500">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Año</label>
                            <input type="number" name="anio" class="form-control" placeholder="Ej. 2023" min="1980" max="<?= date('Y') + 1 ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Número de Motor</label>
                            <input type="text" name="motor" class="form-control" placeholder="Ej. 4HK1-XXXXX">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Número de Chasis (VIN)</label>
                            <input type="text" name="vin" class="form-control text-uppercase" placeholder="Ej. 1GBJC34E72XXXXX">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Capacidad de Carga (Toneladas)</label>
                            <input type="number" step="0.1" name="tonelaje" class="form-control" placeholder="Ej. 8.5">
                        </div>
                    </div>

                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-speedometer2 me-2"></i>Estado Operativo y Kilometraje</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Estado Inicial</label>
                            <select name="estado" class="form-select">
                                <option value="Activo" selected>Activo (En operación)</option>
                                <option value="Taller">Taller (En mantenimiento)</option>
                                <option value="Inactivo">Inactivo (Fuera de servicio)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Kilometraje Actual</label>
                            <div class="input-group">
                                <input type="number" name="kilometraje_actual" class="form-control" value="0" min="0">
                                <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">km</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha de Adquisición</label>
                            <input type="date" name="fecha_adquisicion" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones o Notas Adicionales</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalles sobre seguro, póliza, condición general..."></textarea>
                        </div>
                    </div>

                    <h5 class="fw-semibold text-primary mb-3"><i class="bi bi-file-earmark-text me-2"></i>Cargar Documentación del Camión (Opcional)</h5>
                    <p class="text-secondary small mb-3">Puedes adjuntar el primer documento legal o permiso de la unidad (Revisión técnica, permisos de explotación, sobredimensión, etc.). Podrás gestionar y subir más documentos en su ficha técnica.</p>
                    <div class="row g-3 mb-4 bg-dark bg-opacity-25 p-3 rounded border border-secondary border-opacity-25">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo de Documento</label>
                            <select name="documento_tipo" class="form-select">
                                <option value="">-- Seleccionar tipo (opcional) --</option>
                                <option value="Revisión Técnica">Revisión Técnica</option>
                                <option value="Permiso de Explotación">Permiso de Explotación</option>
                                <option value="Permiso de Sobredimensión">Permiso de Sobredimensión</option>
                                <option value="Póliza de Seguro">Póliza de Seguro</option>
                                <option value="Tarjeta de Circulación">Tarjeta de Circulación</option>
                                <option value="Otro">Otro Documento</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Archivo Adjunto</label>
                            <input type="file" name="documento_archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                            <div class="form-text text-secondary small">Formatos: PDF, JPG, PNG, DOCX, XLSX</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" name="documento_vencimiento" class="form-control">
                            <div class="form-text text-secondary small">Opcional (para control de vigencia)</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones del Documento</label>
                            <input type="text" name="documento_observaciones" class="form-control" placeholder="Ej. Número de póliza, emisor, cobertura o vigencia...">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar Vehículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
