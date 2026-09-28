<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Llanta.php';

requerirAutenticacion();

$accion = $_REQUEST['accion'] ?? 'exportar';

if ($accion === 'datos') {
    $tipo = $_GET['tipo'] ?? 'resumen';
    if ($tipo === 'resumen') {
        $vehiculos = Vehiculo::obtenerTodos($conn);
        $mantenimientos = Mantenimiento::obtenerTodos($conn);
        $llantas = Llanta::obtenerTodas($conn);
        respuestaJson(true, [
            'total_vehiculos' => count($vehiculos),
            'total_mantenimientos' => count($mantenimientos),
            'total_llantas' => count($llantas)
        ]);
    }
}

// Exportar o Imprimir
$tipo_reporte = $_GET['tipo_reporte'] ?? 'vehiculos';
$formato = $_GET['formato'] ?? 'print';
$timestamp = date('Ymd_His');

if ($formato === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=reporte_{$tipo_reporte}_{$timestamp}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte - <?= strtoupper($tipo_reporte) ?></title>
    <?php if ($formato === 'print'): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }
        .table th { background-color: #f1f5f9 !important; }
        @media print { .no-print { display: none !important; } }
    </style>
    <?php endif; ?>
</head>
<body class="p-4">

<?php if ($formato === 'print'): ?>
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
    <div>
        <a href="<?= BASE_URL ?>/frontend/reportes/index.html" class="btn btn-outline-secondary btn-sm">
            &larr; Volver a Reportes
        </a>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            Imprimir / Guardar como PDF
        </button>
    </div>
</div>
<?php endif; ?>

<div class="mb-4 text-center">
    <h3 style="margin-bottom: 2px;">SISTEMA DE GESTIÓN DE FLOTA VEHICULAR</h3>
    <h5 style="margin-top: 0; color: #475569;">
        <?php
            if ($tipo_reporte === 'vehiculos') echo "REPORTE DE INVENTARIO DE FLOTA VEHICULAR";
            if ($tipo_reporte === 'mantenimientos') echo "HISTORIAL Y COSTOS DE MANTENIMIENTO";
            if ($tipo_reporte === 'llantas') echo "INVENTARIO Y MONITOREO DE LLANTAS";
        ?>
    </h5>
    <small style="color: #64748b;">Generado el <?= date('d/m/Y H:i:s') ?> por <?= htmlspecialchars(usuarioActual()['nombre'] ?? 'Sistema') ?></small>
</div>

<?php
if ($tipo_reporte === 'vehiculos') {
    $filtros = [];
    if (!empty($_GET['sucursal_id'])) $filtros['sucursal_id'] = (int)$_GET['sucursal_id'];
    $items = Vehiculo::obtenerTodos($conn, $filtros);
    ?>
    <table class="table table-bordered table-sm" border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <th>Placa</th>
                <th>Sucursal</th>
                <th>Marca / Modelo</th>
                <th>Año</th>
                <th>Tipo</th>
                <th>Capacidad (Ton)</th>
                <th>Kilometraje</th>
                <th>VIN</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td><strong><?= htmlspecialchars($it['placa']) ?></strong></td>
                <td><?= htmlspecialchars($it['sucursal']) ?></td>
                <td><?= htmlspecialchars($it['marca'] . ' ' . $it['modelo']) ?></td>
                <td><?= htmlspecialchars($it['anio']) ?></td>
                <td><?= htmlspecialchars($it['tipo']) ?></td>
                <td><?= number_format((float)$it['tonelaje'], 1) ?></td>
                <td><?= number_format((int)$it['kilometraje_actual']) ?> km</td>
                <td><?= htmlspecialchars($it['vin']) ?></td>
                <td><?= htmlspecialchars($it['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
} elseif ($tipo_reporte === 'mantenimientos') {
    $filtros = [];
    if (!empty($_GET['vehiculo_id'])) $filtros['vehiculo_id'] = (int)$_GET['vehiculo_id'];
    if (!empty($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
    if (!empty($_GET['fecha_desde'])) $filtros['fecha_desde'] = $_GET['fecha_desde'];
    if (!empty($_GET['fecha_hasta'])) $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
    $items = Mantenimiento::obtenerTodos($conn, $filtros);
    $totalCosto = 0;
    ?>
    <table class="table table-bordered table-sm" border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <th>Fecha Prog.</th>
                <th>Vehículo</th>
                <th>Sucursal</th>
                <th>Tipo</th>
                <th>Título</th>
                <th>Taller / Proveedor</th>
                <th>Km Servicio</th>
                <th>Costo (Lps)</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): $totalCosto += (float)$it['costo']; ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($it['fecha_programada'])) ?></td>
                <td><strong><?= htmlspecialchars($it['placa']) ?></strong></td>
                <td><?= htmlspecialchars($it['sucursal']) ?></td>
                <td><?= htmlspecialchars($it['tipo']) ?></td>
                <td><?= htmlspecialchars($it['titulo']) ?></td>
                <td><?= htmlspecialchars($it['proveedor']) ?></td>
                <td><?= !empty($it['kilometraje_servicio']) ? number_format((int)$it['kilometraje_servicio']) . ' km' : 'N/D' ?></td>
                <td style="text-align: right;">L. <?= number_format((float)$it['costo'], 2) ?></td>
                <td><?= htmlspecialchars($it['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="7" style="text-align: right;">TOTAL GENERAL:</td>
                <td style="text-align: right;">L. <?= number_format($totalCosto, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php
} elseif ($tipo_reporte === 'llantas') {
    $items = Llanta::obtenerTodas($conn);
    ?>
    <table class="table table-bordered table-sm" border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <th>Vehículo</th>
                <th>Posición</th>
                <th>Código</th>
                <th>Marca / Medida</th>
                <th>Fecha Inst.</th>
                <th>Km Recorridos</th>
                <th>Costo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td><strong><?= htmlspecialchars($it['placa']) ?></strong></td>
                <td><?= htmlspecialchars($it['posicion']) ?></td>
                <td><code><?= htmlspecialchars($it['codigo']) ?></code></td>
                <td><?= htmlspecialchars($it['marca'] . ' ' . $it['medida']) ?></td>
                <td><?= date('d/m/Y', strtotime($it['fecha_instalacion'])) ?></td>
                <td><?= number_format((int)$it['km_recorridos']) ?> km</td>
                <td>L. <?= number_format((float)$it['costo'], 2) ?></td>
                <td><?= htmlspecialchars($it['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
?>
</body>
</html>
