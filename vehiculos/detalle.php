<?php
$titulo_pagina = "Ficha Técnica del Vehículo";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Llanta.php';
require_once __DIR__ . '/../models/DocumentoVehiculo.php';

$id = (int)($_GET['id'] ?? 0);
$vehiculo = Vehiculo::obtenerPorId($conn, $id);

if (!$vehiculo) {
    setAlerta('danger', 'Vehículo no encontrado.');
    header("Location: " . BASE_URL . "/vehiculos/lista.php");
    exit;
}

$mantenimientos = Mantenimiento::obtenerPorVehiculo($conn, $id);
$llantas = Llanta::obtenerPorVehiculo($conn, $id);
$documentos = DocumentoVehiculo::obtenerPorVehiculo($conn, $id);
$tabActiva = $_GET['tab'] ?? 'mantenimientos';

// Calcular gasto total en mantenimiento para este vehículo
$gastoTotal = 0;
foreach ($mantenimientos as $m) {
    if ($m['estado'] === 'Completado') {
        $gastoTotal += (float)$m['costo'];
    }
}
?>

<div class="d-flex flex-column gap-4">
    <!-- Barra Superior de Navegación del Detalle -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left"></i> Vehículos
                </a>
                <span class="text-secondary small">/</span>
                <span class="text-light small fw-bold"><?= htmlspecialchars($vehiculo['placa']) ?></span>
            </div>
            <h2 class="fw-bold mb-0 d-flex align-items-center gap-3">
                <span><?= htmlspecialchars($vehiculo['placa']) ?></span>
                <?php
                    $claseBadge = 'badge-activo';
                    if ($vehiculo['estado'] === 'Taller') $claseBadge = 'badge-taller';
                    if ($vehiculo['estado'] === 'Inactivo') $claseBadge = 'badge-inactivo';
                ?>
                <span class="badge <?= $claseBadge ?> fs-6 px-3 py-1"><?= htmlspecialchars($vehiculo['estado']) ?></span>
            </h2>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (puedeEditar()): ?>
            <a href="<?= BASE_URL ?>/mantenimientos/crear.php?vehiculo_id=<?= $vehiculo['id'] ?>" class="btn btn-warning btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-wrench"></i> Registrar Mantenimiento
            </a>
            <a href="<?= BASE_URL ?>/llantas/registrar.php?vehiculo_id=<?= $vehiculo['id'] ?>" class="btn btn-info btn-sm d-flex align-items-center gap-1 text-dark shadow-sm">
                <i class="bi bi-record-circle"></i> Asignar Llanta
            </a>
            <a href="<?= BASE_URL ?>/vehiculos/editar.php?id=<?= $vehiculo['id'] ?>" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ficha Técnica Resumida -->
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card h-100 p-4">
                <h5 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Especificaciones Generales</h5>
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Sucursal</span>
                        <strong class="text-light"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($vehiculo['sucursal']) ?></strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Marca y Modelo</span>
                        <strong class="text-light"><?= htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?></strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Año / Tipo</span>
                        <strong class="text-light"><?= htmlspecialchars($vehiculo['anio'] ?: 'N/D') ?> (<?= htmlspecialchars($vehiculo['tipo'] ?: 'Camión') ?>)</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Capacidad</span>
                        <strong class="text-light"><?= number_format((float)($vehiculo['tonelaje'] ?? 0), 1) ?> Toneladas</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Kilometraje Actual</span>
                        <strong class="text-info fs-5"><?= number_format((int)$vehiculo['kilometraje_actual']) ?> km</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <span class="text-secondary small d-block">Fecha de Adquisición</span>
                        <strong class="text-light"><?= !empty($vehiculo['fecha_adquisicion']) ? date('d/m/Y', strtotime($vehiculo['fecha_adquisicion'])) : 'No registrada' ?></strong>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-secondary small d-block">Número de Motor</span>
                        <code class="text-light"><?= htmlspecialchars($vehiculo['motor'] ?: 'No especificado') ?></code>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-secondary small d-block">Chasis / VIN</span>
                        <code class="text-light"><?= htmlspecialchars($vehiculo['vin'] ?: 'No especificado') ?></code>
                    </div>
                    <?php if (!empty($vehiculo['observaciones'])): ?>
                    <div class="col-12">
                        <span class="text-secondary small d-block">Observaciones</span>
                        <p class="text-light small bg-dark p-2 rounded border border-secondary border-opacity-25 mb-0">
                            <?= nl2br(htmlspecialchars($vehiculo['observaciones'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Documentación y Permisos Cargados en Especificaciones Generales -->
                    <div class="col-12 border-top border-secondary border-opacity-25 pt-3 mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small fw-bold text-uppercase">
                                <i class="bi bi-file-earmark-check text-primary me-1"></i> Documentos Cargados a este Camión (<?= count($documentos) ?>)
                            </span>
                            <button type="button" class="btn btn-link btn-sm text-primary p-0 text-decoration-none small" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('#documentos-tab')).show();">
                                Administrar / Cargar Nuevo <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                        <?php if (empty($documentos)): ?>
                            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
                                <span class="text-secondary small"><i class="bi bi-info-circle me-1"></i> No hay documentos cargados para este camión (Revisión técnica, permisos, etc.).</span>
                                <?php if (puedeEditar()): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-3 text-nowrap" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('#documentos-tab')).show();">
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Cargar Documentación
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($documentos as $doc): 
                                    $estadoVenc = DocumentoVehiculo::obtenerEstadoVencimiento($doc['fecha_vencimiento']);
                                    $ext = strtolower(pathinfo($doc['nombre_original'], PATHINFO_EXTENSION));
                                    $iconoDoc = 'bi-file-earmark-text text-secondary';
                                    if ($ext === 'pdf') $iconoDoc = 'bi-file-earmark-pdf text-danger';
                                    elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) $iconoDoc = 'bi-file-earmark-image text-info';
                                    elseif (in_array($ext, ['doc', 'docx'])) $iconoDoc = 'bi-file-earmark-word text-primary';
                                    elseif (in_array($ext, ['xls', 'xlsx'])) $iconoDoc = 'bi-file-earmark-excel text-success';
                                ?>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden me-1">
                                            <i class="bi <?= $iconoDoc ?> fs-4 flex-shrink-0"></i>
                                            <div class="overflow-hidden">
                                                <div class="text-light small fw-bold text-truncate" title="<?= htmlspecialchars($doc['tipo_documento']) ?>">
                                                    <?= htmlspecialchars($doc['tipo_documento']) ?>
                                                </div>
                                                <span class="badge <?= $estadoVenc['clase'] ?>" style="font-size: 0.7rem;">
                                                    <i class="bi <?= $estadoVenc['icono'] ?> me-1"></i><?= $estadoVenc['texto'] ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <a href="<?= BASE_URL . '/' . htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="Ver / Abrir documento en nueva pestaña">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL . '/' . htmlspecialchars($doc['ruta_archivo']) ?>" download="<?= htmlspecialchars($doc['nombre_original']) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Descargar">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-success mb-3"><i class="bi bi-cash-coin me-2"></i>Inversión Acumulada</h5>
                    <div class="display-6 fw-bold text-success mb-2">L. <?= number_format($gastoTotal, 2) ?></div>
                    <p class="text-secondary small">Total gastado en mantenimientos preventivos y correctivos completados en esta unidad.</p>
                </div>
                <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Total Servicios Realizados:</span>
                        <strong class="text-light"><?= count($mantenimientos) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Llantas Activas:</span>
                        <strong class="text-info"><?= count($llantas) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary small">Documentos Cargados:</span>
                        <strong class="text-warning"><?= count($documentos) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Historial de Mantenimientos, Llantas y Documentación -->
    <div class="card">
        <div class="card-header border-bottom border-secondary border-opacity-25 p-0">
            <ul class="nav nav-tabs border-0 px-3" id="detalleTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $tabActiva === 'mantenimientos' ? 'active' : 'text-secondary' ?> py-3 px-4 fw-semibold" id="mantenimientos-tab" data-bs-toggle="tab" data-bs-target="#mantenimientos" type="button">
                        <i class="bi bi-tools me-2 text-warning"></i> Historial de Mantenimientos (<?= count($mantenimientos) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $tabActiva === 'llantas' ? 'active' : 'text-secondary' ?> py-3 px-4 fw-semibold" id="llantas-tab" data-bs-toggle="tab" data-bs-target="#llantas" type="button">
                        <i class="bi bi-record-circle me-2 text-info"></i> Llantas Instaladas (<?= count($llantas) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $tabActiva === 'documentos' ? 'active' : 'text-secondary' ?> py-3 px-4 fw-semibold" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#tab-documentos" type="button">
                        <i class="bi bi-file-earmark-check me-2 text-primary"></i> Documentación y Permisos (<?= count($documentos) ?>)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="detalleTabContent">
                <!-- Pestaña Mantenimientos -->
                <div class="tab-pane fade <?= $tabActiva === 'mantenimientos' ? 'show active' : '' ?> table-responsive" id="mantenimientos">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Fecha Prog.</th>
                                <th>Tipo</th>
                                <th>Título y Taller</th>
                                <th>Km Servicio</th>
                                <th>Costo</th>
                                <th>Estado</th>
                                <th>Fecha Realizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mantenimientos)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">No hay mantenimientos registrados para este vehículo.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($mantenimientos as $m): ?>
                                <tr>
                                    <td><i class="bi bi-calendar-event me-1 text-secondary"></i><?= date('d/m/Y', strtotime($m['fecha_programada'])) ?></td>
                                    <td>
                                        <span class="badge <?= $m['tipo'] === 'Preventivo' ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?>">
                                            <?= htmlspecialchars($m['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-light"><?= htmlspecialchars($m['titulo']) ?></div>
                                        <small class="text-secondary"><?= htmlspecialchars($m['proveedor'] ?: 'Taller no especificado') ?></small>
                                    </td>
                                    <td><?= !empty($m['kilometraje_servicio']) ? number_format((int)$m['kilometraje_servicio']) . ' km' : 'N/D' ?></td>
                                    <td class="fw-bold text-success">L. <?= number_format((float)$m['costo'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $m['estado'] === 'Completado' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                                            <?= htmlspecialchars($m['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($m['fecha_realizada']) ? date('d/m/Y', strtotime($m['fecha_realizada'])) : '<span class="text-secondary">Pendiente</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pestaña Llantas -->
                <div class="tab-pane fade <?= $tabActiva === 'llantas' ? 'show active' : '' ?> table-responsive" id="llantas">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Posición</th>
                                <th>Código</th>
                                <th>Marca / Medida</th>
                                <th>Instalación</th>
                                <th>Km Recorrido</th>
                                <th>Costo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($llantas)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">No hay llantas registradas para este vehículo.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($llantas as $ll): ?>
                                <tr>
                                    <td class="fw-bold text-info"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ll['posicion']) ?></td>
                                    <td><code class="text-light fw-bold"><?= htmlspecialchars($ll['codigo']) ?></code></td>
                                    <td>
                                        <div class="fw-semibold text-light"><?= htmlspecialchars($ll['marca']) ?></div>
                                        <small class="text-secondary"><?= htmlspecialchars($ll['medida']) ?> (<?= htmlspecialchars($ll['tipo_llanta']) ?>)</small>
                                    </td>
                                    <td>
                                        <div><?= date('d/m/Y', strtotime($ll['fecha_instalacion'])) ?></div>
                                        <small class="text-secondary"><?= number_format((int)$ll['km_instalacion']) ?> km</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-warning"><?= number_format((int)$ll['km_recorridos']) ?> km</span>
                                    </td>
                                    <td class="text-light">L. <?= number_format((float)$ll['costo'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $ll['estado'] === 'Activa' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                            <?= htmlspecialchars($ll['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pestaña Documentación y Permisos -->
                <div class="tab-pane fade <?= $tabActiva === 'documentos' ? 'show active' : '' ?>" id="tab-documentos">
                    <div class="p-4 border-bottom border-secondary border-opacity-25 bg-dark bg-opacity-25">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-light mb-1"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Cargar Nuevo Documento o Permiso</h6>
                                <p class="text-secondary small mb-0">Sube revisiones técnicas, permisos de explotación, permisos de sobredimensión, pólizas de seguro u otros documentos legales.</p>
                            </div>
                        </div>

                        <?php if (puedeEditar()): ?>
                        <form action="<?= BASE_URL ?>/controllers/DocumentoController.php?accion=subir" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="vehiculo_id" value="<?= $vehiculo['id'] ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-3">
                                    <label class="form-label small text-secondary">Tipo de Documento <span class="text-danger">*</span></label>
                                    <select name="tipo_documento" class="form-select form-select-sm" required>
                                        <option value="">-- Seleccione tipo --</option>
                                        <option value="Revisión Técnica">Revisión Técnica</option>
                                        <option value="Permiso de Explotación">Permiso de Explotación</option>
                                        <option value="Permiso de Sobredimensión">Permiso de Sobredimensión</option>
                                        <option value="Póliza de Seguro">Póliza de Seguro</option>
                                        <option value="Tarjeta de Circulación">Tarjeta de Circulación</option>
                                        <option value="Otro">Otro Documento</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small text-secondary">Archivo <span class="text-danger">*</span></label>
                                    <input type="file" name="archivo" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-secondary">Fecha Vencimiento</label>
                                    <input type="date" name="fecha_vencimiento" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-secondary">Observaciones</label>
                                    <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Opcional...">
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                                        <i class="bi bi-upload me-1"></i> Subir Documento
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-secondary py-2 small mb-0">Solo los usuarios con permisos de edición pueden cargar nuevos documentos.</div>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo de Documento</th>
                                    <th>Archivo</th>
                                    <th>Vencimiento / Estado</th>
                                    <th>Fecha de Carga</th>
                                    <th>Observaciones</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documentos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No hay documentos registrados para este vehículo.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($documentos as $d): 
                                        $estado = DocumentoVehiculo::obtenerEstadoVencimiento($d['fecha_vencimiento']);
                                        $ext = strtolower(pathinfo($d['nombre_original'], PATHINFO_EXTENSION));
                                        $icono = 'bi-file-earmark-text text-secondary';
                                        if ($ext === 'pdf') $icono = 'bi-file-earmark-pdf text-danger';
                                        elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) $icono = 'bi-file-earmark-image text-info';
                                        elseif (in_array($ext, ['doc', 'docx'])) $icono = 'bi-file-earmark-word text-primary';
                                        elseif (in_array($ext, ['xls', 'xlsx'])) $icono = 'bi-file-earmark-excel text-success';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi <?= $icono ?> fs-5"></i>
                                                <strong class="text-light"><?= htmlspecialchars($d['tipo_documento']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-light small"><?= htmlspecialchars($d['nombre_original']) ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="badge <?= $estado['clase'] ?> px-2 py-1">
                                                    <i class="bi <?= $estado['icono'] ?> me-1"></i><?= $estado['texto'] ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($d['fecha_vencimiento'])): ?>
                                                <small class="text-secondary d-block mt-1">Vence: <?= date('d/m/Y', strtotime($d['fecha_vencimiento'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-secondary small"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i', strtotime($d['fecha_subida'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-secondary small"><?= !empty($d['observaciones']) ? htmlspecialchars($d['observaciones']) : '—' ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL . '/' . htmlspecialchars($d['ruta_archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Ver / Abrir documento en nueva pestaña">
                                                    <i class="bi bi-eye me-1"></i> Ver
                                                </a>
                                                <a href="<?= BASE_URL . '/' . htmlspecialchars($d['ruta_archivo']) ?>" download="<?= htmlspecialchars($d['nombre_original']) ?>" class="btn btn-sm btn-outline-secondary" title="Descargar archivo">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <?php if (puedeEditar()): ?>
                                                <a href="<?= BASE_URL ?>/controllers/DocumentoController.php?accion=eliminar&id=<?= $d['id'] ?>&vehiculo_id=<?= $vehiculo['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este documento? Esta acción eliminará el archivo del servidor.');" title="Eliminar documento">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
