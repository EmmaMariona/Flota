<?php
$titulo_pagina = "Gestión de Llantas";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Llanta.php';
require_once __DIR__ . '/../models/Vehiculo.php';

$filtros = [
    'vehiculo_id' => $_GET['vehiculo_id'] ?? '',
    'estado' => $_GET['estado'] ?? 'Activa',
    'buscar' => trim($_GET['buscar'] ?? '')
];

$llantas = Llanta::obtenerTodas($conn, $filtros);
$vehiculos = Vehiculo::obtenerTodos($conn);

$totalCostoLlantas = 0;
$alertaDesgasteCount = 0;
foreach ($llantas as $ll) {
    if ($ll['estado'] === 'Activa') {
        $totalCostoLlantas += (float)$ll['costo'];
        if ((int)$ll['km_recorridos'] >= 40000) {
            $alertaDesgasteCount++;
        }
    }
}
?>

<div class="d-flex flex-column gap-3">
    <!-- Encabezado y Botón Nuevo -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="fw-bold mb-1">Control de Llantas de Flota</h2>
            <p class="text-secondary small mb-0">Seguimiento por posición, kilometraje recorrido, rotaciones y reemplazos de neumáticos.</p>
        </div>
        <?php if (puedeEditar()): ?>
        <a href="<?= BASE_URL ?>/llantas/registrar.php" class="btn btn-info d-flex align-items-center gap-2 shadow-sm fw-semibold text-dark">
            <i class="bi bi-plus-circle"></i> Registrar / Asignar Llanta
        </a>
        <?php endif; ?>
    </div>

    <!-- Tarjetas de Resumen Rápido -->
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card p-3 border-secondary border-opacity-25">
                <span class="text-secondary small text-uppercase fw-semibold">Llantas en Monitoreo</span>
                <h4 class="fw-bold text-light my-1"><?= count($llantas) ?> neumáticos</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-secondary border-opacity-25">
                <span class="text-secondary small text-uppercase fw-semibold">Próximas a Reemplazo (>40,000 km)</span>
                <h4 class="fw-bold text-warning my-1"><?= $alertaDesgasteCount ?> alertas</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 border-secondary border-opacity-25">
                <span class="text-secondary small text-uppercase fw-semibold">Inversión en Neumáticos Activos</span>
                <h4 class="fw-bold text-success my-1">L. <?= number_format($totalCostoLlantas, 2) ?></h4>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card p-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Buscar por código, marca o placa</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" name="buscar" class="form-control" placeholder="Ej. LL-001, Michelin..." value="<?= htmlspecialchars($filtros['buscar']) ?>">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label">Vehículo</label>
                <select name="vehiculo_id" class="form-select">
                    <option value="">Todos los vehículos</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $filtros['vehiculo_id'] == $v['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['placa'] . ' - ' . $v['marca'] . ' ' . $v['modelo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="Activa" <?= $filtros['estado'] === 'Activa' ? 'selected' : '' ?>>Activas</option>
                    <option value="Reemplazada" <?= $filtros['estado'] === 'Reemplazada' ? 'selected' : '' ?>>Reemplazadas</option>
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                <?php if (!empty($filtros['buscar']) || !empty($filtros['vehiculo_id']) || $filtros['estado'] !== 'Activa'): ?>
                    <a href="<?= BASE_URL ?>/llantas/lista.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de Llantas -->
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Posición</th>
                        <th>Código</th>
                        <th>Marca y Medida</th>
                        <th>Fecha Instalación</th>
                        <th>Km Recorridos</th>
                        <th>Costo</th>
                        <th>Estado</th>
                        <?php if (tieneRol(['Administrador'])): ?><th class="text-end">Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($llantas)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <i class="bi bi-disc fs-1 d-block mb-2 text-secondary"></i>
                            No se encontraron llantas registradas con los filtros aplicados.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($llantas as $ll): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/vehiculos/detalle.php?id=<?= $ll['vehiculo_id'] ?>" class="fw-bold text-info text-decoration-none">
                                    <?= htmlspecialchars($ll['placa']) ?>
                                </a>
                                <div class="text-secondary small"><?= htmlspecialchars($ll['sucursal']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-light border border-secondary border-opacity-50">
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ll['posicion']) ?>
                                </span>
                            </td>
                            <td>
                                <code class="text-light fw-bold fs-6"><?= htmlspecialchars($ll['codigo']) ?></code>
                                <?php if (!empty($ll['codigo_anterior'])): ?>
                                    <small class="text-secondary d-block">Reemplazó a: <?= htmlspecialchars($ll['codigo_anterior']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-light"><?= htmlspecialchars($ll['marca']) ?></div>
                                <small class="text-secondary"><?= htmlspecialchars($ll['medida']) ?> | <?= htmlspecialchars($ll['tipo_llanta']) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-calendar-event me-1 text-secondary"></i><?= date('d/m/Y', strtotime($ll['fecha_instalacion'])) ?></div>
                                <small class="text-secondary">Instalado a los <?= number_format((int)$ll['km_instalacion']) ?> km</small>
                            </td>
                            <td>
                                <?php
                                    $kmRec = (int)$ll['km_recorridos'];
                                    $colorKm = 'text-light';
                                    if ($kmRec > 35000) $colorKm = 'text-warning';
                                    if ($kmRec > 45000) $colorKm = 'text-danger fw-bold';
                                ?>
                                <span class="<?= $colorKm ?>"><?= number_format($kmRec) ?> km</span>
                                <div class="progress mt-1" style="height: 4px; width: 80px;">
                                    <?php $pct = min(100, round(($kmRec / 50000) * 100)); ?>
                                    <div class="progress-bar <?= $pct > 80 ? 'bg-danger' : ($pct > 60 ? 'bg-warning' : 'bg-info') ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-success">L. <?= number_format((float)$ll['costo'], 2) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $ll['estado'] === 'Activa' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> px-2 py-1">
                                    <?= htmlspecialchars($ll['estado']) ?>
                                </span>
                            </td>
                            <?php if (tieneRol(['Administrador'])): ?>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/controllers/LlantaController.php?accion=eliminar&id=<?= $ll['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   title="Eliminar Llanta"
                                   data-confirm="¿Deseas eliminar el registro de esta llanta?">
                                    <i class="bi bi-trash"></i>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
