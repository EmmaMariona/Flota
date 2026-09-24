<?php
$titulo_pagina = "Listado de Vehículos";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../models/Vehiculo.php';
require_once __DIR__ . '/../../models/Sucursal.php';

$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'sucursal_id' => $_GET['sucursal_id'] ?? '',
    'estado' => $_GET['estado'] ?? ''
];

$vehiculos = Vehiculo::obtenerTodos($conn, $filtros);
$sucursales = Sucursal::obtenerTodas($conn);
?>

<div class="d-flex flex-column gap-3">
    <!-- Encabezado y Botón Nuevo -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="fw-bold mb-1">Gestión de Vehículos</h2>
            <p class="text-secondary small mb-0">Control del parque vehicular, fichas técnicas y estado operativo.</p>
        </div>
        <?php if (puedeEditar()): ?>
        <a href="<?= BASE_URL ?>/vehiculos/crear.php" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-circle"></i> Nuevo Vehículo
        </a>
        <?php endif; ?>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Buscar vehículo</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" name="buscar" class="form-control" placeholder="Placa, marca, modelo, VIN..." value="<?= htmlspecialchars($filtros['buscar']) ?>">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label">Sucursal</label>
                <select name="sucursal_id" class="form-select">
                    <option value="">Todas las sucursales</option>
                    <?php foreach ($sucursales as $suc): ?>
                        <option value="<?= $suc['id'] ?>" <?= $filtros['sucursal_id'] == $suc['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($suc['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="Activo" <?= $filtros['estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="Taller" <?= $filtros['estado'] === 'Taller' ? 'selected' : '' ?>>Taller</option>
                    <option value="Inactivo" <?= $filtros['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                <?php if (!empty($filtros['buscar']) || !empty($filtros['sucursal_id']) || !empty($filtros['estado'])): ?>
                    <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="btn btn-outline-secondary" title="Limpiar Filtros"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Listado de Vehículos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="text-secondary small fw-semibold">
                Total encontrados: <strong class="text-light"><?= count($vehiculos) ?></strong> vehículos
            </span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Sucursal</th>
                        <th>Marca y Modelo</th>
                        <th>Año / Tipo</th>
                        <th>Kilometraje</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehiculos)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No se encontraron vehículos con los criterios seleccionados.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($vehiculos as $v): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $v['id'] ?>" class="fw-bold text-decoration-none text-info fs-6">
                                    <?= htmlspecialchars($v['placa']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-light border border-secondary border-opacity-50">
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($v['sucursal']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-light"><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></div>
                                <?php if (!empty($v['vin'])): ?>
                                    <small class="text-secondary">VIN: <?= htmlspecialchars($v['vin']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($v['anio'] ?: 'N/A') ?></div>
                                <small class="text-secondary"><?= htmlspecialchars($v['tipo'] ?: 'Camión') ?></small>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= number_format((int)$v['kilometraje_actual']) ?> km</span>
                            </td>
                            <td>
                                <?php
                                    $claseBadge = 'badge-activo';
                                    if ($v['estado'] === 'Taller') $claseBadge = 'badge-taller';
                                    if ($v['estado'] === 'Inactivo') $claseBadge = 'badge-inactivo';
                                ?>
                                <span class="badge <?= $claseBadge ?> px-2 py-1">
                                    <?= htmlspecialchars($v['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $v['id'] ?>" class="btn btn-outline-info" title="Ficha Técnica Completa">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $v['id'] ?>&tab=documentos" class="btn btn-outline-primary" title="Ver Documentación y Permisos">
                                        <i class="bi bi-file-earmark-check"></i>
                                    </a>
                                    <?php if (puedeEditar()): ?>
                                    <a href="<?= BASE_URL ?>/vehiculos/editar.php?id=<?= $v['id'] ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (tieneRol(['Administrador'])): ?>
                                    <a href="<?= BASE_URL ?>/controllers/VehiculoController.php?accion=eliminar&id=<?= $v['id'] ?>" 
                                       class="btn btn-outline-danger" 
                                       title="Eliminar"
                                       data-confirm="¿Deseas eliminar el vehículo con placa <?= htmlspecialchars($v['placa']) ?>? También se eliminarán sus registros vinculados.">
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>