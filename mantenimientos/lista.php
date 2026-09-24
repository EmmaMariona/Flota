<?php
$titulo_pagina = "Gestión de Mantenimientos";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Sucursal.php';

$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'vehiculo_id' => $_GET['vehiculo_id'] ?? '',
    'tipo' => $_GET['tipo'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'sucursal_id' => $_GET['sucursal_id'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
];

$mantenimientos = Mantenimiento::obtenerTodos($conn, $filtros);
$vehiculos = Vehiculo::obtenerTodos($conn);
$sucursales = Sucursal::obtenerTodas($conn);

// Resumen del listado actual
$costoFiltrado = 0;
$pendientesCount = 0;
foreach ($mantenimientos as $m) {
    if ($m['estado'] === 'Completado') $costoFiltrado += (float)$m['costo'];
    if (in_array($m['estado'], ['Programado', 'En proceso'])) $pendientesCount++;
}

// Paginación eficiente para manejar miles de registros fluidamente
$porPagina = 50;
$totalRegistros = count($mantenimientos);
$totalPaginas = max(1, ceil($totalRegistros / $porPagina));
$paginaActual = max(1, min($totalPaginas, (int)($_GET['pagina'] ?? 1)));
$offset = ($paginaActual - 1) * $porPagina;
$mantenimientosPaginados = array_slice($mantenimientos, $offset, $porPagina);

// Helper para armar URLs de paginación manteniendo los filtros
function buildPageUrl($page, $filtros) {
    $params = array_filter($filtros);
    $params['pagina'] = $page;
    return '?' . http_build_query($params);
}
?>

<div class="d-flex flex-column gap-3">
    <!-- Encabezado y Botón Nuevo -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="fw-bold mb-1">Mantenimientos de Flota</h2>
            <p class="text-secondary small mb-0">Control de servicios preventivos, reparaciones correctivas, costos e historial por unidad.</p>
        </div>
        <?php if (puedeEditar()): ?>
        <a href="<?= BASE_URL ?>/mantenimientos/crear.php" class="btn btn-warning d-flex align-items-center gap-2 shadow-sm fw-semibold">
            <i class="bi bi-plus-circle"></i> Programar Mantenimiento
        </a>
        <?php endif; ?>
    </div>

    <!-- Tarjetas de Resumen Rápido -->
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card p-3 border-secondary border-opacity-25">
                <span class="text-secondary small text-uppercase fw-semibold">Servicios Encontrados</span>
                <h4 class="fw-bold text-light my-1"><?= number_format($totalRegistros) ?> registros</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-secondary border-opacity-25">
                <span class="text-secondary small text-uppercase fw-semibold">Pendientes / En Taller</span>
                <h4 class="fw-bold text-warning my-1"><?= number_format($pendientesCount) ?> servicios</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-secondary border-opacity-25">
                <span class="text-secondary small text-uppercase fw-semibold">Costo Total Ejecutado</span>
                <h4 class="fw-bold text-success my-1">L. <?= number_format($costoFiltrado, 2) ?></h4>
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card p-3">
        <form method="GET" class="row g-2 align-items-end">
            <!-- Búsqueda escrita por Placa o Descripción -->
            <div class="col-12 col-md-3">
                <label class="form-label">Buscar Placa o Detalle</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="buscar" class="form-control text-uppercase fw-semibold" placeholder="Escribe la placa ej. JAU-4282..." value="<?= htmlspecialchars($filtros['buscar']) ?>" autofocus>
                </div>
            </div>

            <!-- Selector desplegable de Vehículo -->
            <div class="col-12 col-md-3">
                <label class="form-label">O Seleccionar Vehículo</label>
                <select name="vehiculo_id" class="form-select">
                    <option value="">Todos los vehículos</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $filtros['vehiculo_id'] == $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['placa'] . ' - ' . $v['marca'] . ' (' . $v['sucursal'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tipo de Servicio -->
            <div class="col-6 col-md-2">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="Preventivo" <?= $filtros['tipo'] === 'Preventivo' ? 'selected' : '' ?>>Preventivo</option>
                    <option value="Correctivo" <?= $filtros['tipo'] === 'Correctivo' ? 'selected' : '' ?>>Correctivo</option>
                </select>
            </div>

            <!-- Estado -->
            <div class="col-6 col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="Programado" <?= $filtros['estado'] === 'Programado' ? 'selected' : '' ?>>Programado</option>
                    <option value="En proceso" <?= $filtros['estado'] === 'En proceso' ? 'selected' : '' ?>>En proceso</option>
                    <option value="Completado" <?= $filtros['estado'] === 'Completado' ? 'selected' : '' ?>>Completado</option>
                    <option value="Cancelado" <?= $filtros['estado'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <!-- Sucursal -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($filtros['buscar']) || !empty($filtros['vehiculo_id']) || !empty($filtros['tipo']) || !empty($filtros['estado']) || !empty($filtros['sucursal_id'])): ?>
                    <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-outline-secondary" title="Limpiar Filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de Mantenimientos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="text-secondary small fw-semibold">
                Mostrando <?= min($totalRegistros, $offset + 1) ?> a <?= min($totalRegistros, $offset + $porPagina) ?> de <strong class="text-light"><?= number_format($totalRegistros) ?></strong> mantenimientos
            </span>
            <span class="badge bg-secondary-subtle text-secondary">Página <?= $paginaActual ?> de <?= $totalPaginas ?></span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Servicio y Taller</th>
                        <th>Fecha</th>
                        <th>Km Odómetro</th>
                        <th>Costo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mantenimientosPaginados)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-5">
                            <i class="bi bi-tools fs-1 d-block mb-2 text-secondary"></i>
                            No se encontraron registros con los criterios de búsqueda.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($mantenimientosPaginados as $m): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $m['vehiculo_id'] ?>" class="fw-bold text-info text-decoration-none fs-6">
                                    <?= htmlspecialchars($m['placa']) ?>
                                </a>
                                <div class="text-secondary small"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($m['sucursal']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $m['tipo'] === 'Preventivo' ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?> px-2 py-1">
                                    <?= htmlspecialchars($m['tipo']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-light"><?= htmlspecialchars($m['titulo']) ?></div>
                                <?php if (!empty($m['proveedor'])): ?>
                                    <small class="text-secondary"><i class="bi bi-building me-1"></i><?= htmlspecialchars($m['proveedor']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><i class="bi bi-calendar3 me-1 text-secondary"></i><?= date('d/m/Y', strtotime($m['fecha_programada'])) ?></div>
                            </td>
                            <td><?= !empty($m['kilometraje_servicio']) ? number_format((int)$m['kilometraje_servicio']) . ' km' : '<span class="text-secondary small">N/D</span>' ?></td>
                            <td>
                                <span class="fw-bold text-success">L. <?= number_format((float)$m['costo'], 2) ?></span>
                            </td>
                            <td>
                                <?php
                                    $badge = 'bg-secondary-subtle text-secondary';
                                    if ($m['estado'] === 'Completado') $badge = 'bg-success-subtle text-success';
                                    if ($m['estado'] === 'En proceso') $badge = 'bg-warning-subtle text-warning';
                                    if ($m['estado'] === 'Programado') $badge = 'bg-primary-subtle text-primary';
                                    if ($m['estado'] === 'Cancelado') $badge = 'bg-danger-subtle text-danger';
                                ?>
                                <span class="badge <?= $badge ?> px-2 py-1">
                                    <?= htmlspecialchars($m['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/mantenimientos/imprimir.php?id=<?= $m['id'] ?>" target="_blank" class="btn btn-outline-light" title="Imprimir Orden (PDF)">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $m['vehiculo_id'] ?>" class="btn btn-outline-info" title="Ver Ficha del Vehículo">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (puedeEditar()): ?>
                                    <a href="<?= BASE_URL ?>/mantenimientos/editar.php?id=<?= $m['id'] ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (tieneRol(['Administrador'])): ?>
                                    <a href="<?= BASE_URL ?>/controllers/MantenimientoController.php?accion=eliminar&id=<?= $m['id'] ?>" 
                                       class="btn btn-outline-danger" 
                                       title="Eliminar"
                                       data-confirm="¿Estás seguro de eliminar este registro de mantenimiento?">
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

        <!-- Paginación Interactiva -->
        <?php if ($totalPaginas > 1): ?>
        <div class="card-footer bg-transparent border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="small text-secondary">
                Página <strong><?= $paginaActual ?></strong> de <strong><?= $totalPaginas ?></strong>
            </div>
            <ul class="pagination pagination-sm mb-0">
                <!-- Anterior -->
                <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link bg-dark border-secondary border-opacity-50 text-light" href="<?= buildPageUrl(max(1, $paginaActual - 1), $filtros) ?>">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </a>
                </li>

                <!-- Botones numéricos de página -->
                <?php
                $rangoInicio = max(1, $paginaActual - 2);
                $rangoFin = min($totalPaginas, $paginaActual + 2);
                for ($p = $rangoInicio; $p <= $rangoFin; $p++):
                ?>
                    <li class="page-item <?= $p === $paginaActual ? 'active' : '' ?>">
                        <a class="page-link <?= $p === $paginaActual ? 'bg-primary border-primary' : 'bg-dark border-secondary border-opacity-50 text-light' ?>" href="<?= buildPageUrl($p, $filtros) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Siguiente -->
                <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                    <a class="page-link bg-dark border-secondary border-opacity-50 text-light" href="<?= buildPageUrl(min($totalPaginas, $paginaActual + 1), $filtros) ?>">
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
