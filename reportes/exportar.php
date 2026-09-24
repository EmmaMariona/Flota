<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Llanta.php';

requerirAutenticacion();

$tipo_reporte = $_GET['tipo_reporte'] ?? 'vehiculos';
$formato = $_GET['formato'] ?? 'print';
$timestamp = date('Ymd_His');

// Procesar según formato Excel
if ($formato === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=reporte_{$tipo_reporte}_{$timestamp}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF"; // UTF-8 BOM para soporte de tildes y caracteres especiales en Excel
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
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; }
        .table th { background-color: #f1f5f9 !important; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
    <?php endif; ?>
</head>
<body class="p-4">

<?php if ($formato === 'print'): ?>
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
    <div>
        <a href="<?= BASE_URL ?>/reportes/index.php" class="btn btn-outline-secondary btn-sm">
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

<!-- Encabezado del Reporte Institucional -->
<div class="mb-4 text-center">
    <h3 style="margin-bottom: 2px;">SISTEMA DE GESTIÓN DE FLOTA VEHICULAR</h3>
    <h5 style="margin-top: 0; color: #475569;">
        <?php
            if ($tipo_reporte === 'vehiculos') echo "REPORTE GENERAL DE FLOTA VEHICULAR";
            elseif ($tipo_reporte === 'mantenimientos') echo "REPORTE DE MANTENIMIENTOS Y COSTOS OPERATIVOS";
            elseif ($tipo_reporte === 'llantas') echo "REPORTE DE MONITOREO Y DESGASTE DE LLANTAS";
        ?>
    </h5>
    <p style="font-size: 11px; color: #64748b;">
        Generado por: <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Sistema') ?> | 
        Fecha de emisión: <?= date('d/m/Y H:i:s') ?>
    </p>
</div>

<?php if ($tipo_reporte === 'vehiculos'): ?>
    <?php
        $filtros = ['sucursal_id' => $_GET['sucursal_id'] ?? ''];
        $datos = Vehiculo::obtenerTodos($conn, $filtros);
    ?>
    <table class="table table-bordered table-sm" border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <th>#</th>
                <th>Placa</th>
                <th>Sucursal</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Año</th>
                <th>Tipo</th>
                <th>Kilometraje</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($datos as $i => $v): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($v['placa']) ?></strong></td>
                <td><?= htmlspecialchars($v['sucursal']) ?></td>
                <td><?= htmlspecialchars($v['marca']) ?></td>
                <td><?= htmlspecialchars($v['modelo']) ?></td>
                <td><?= htmlspecialchars($v['anio'] ?: '-') ?></td>
                <td><?= htmlspecialchars($v['tipo'] ?: 'Camión') ?></td>
                <td><?= number_format((int)$v['kilometraje_actual']) ?> km</td>
                <td><?= htmlspecialchars($v['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($tipo_reporte === 'mantenimientos'): ?>
    <?php
        $filtros = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'tipo' => $_GET['tipo_servicio'] ?? ''
        ];
        $datos = Mantenimiento::obtenerTodos($conn, $filtros);
        $totalGasto = 0;
    ?>
    <table class="table table-bordered table-sm" border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <th>#</th>
                <th>Placa</th>
                <th>Sucursal</th>
                <th>Tipo</th>
                <th>Título / Descripción</th>
                <th>Fecha Prog.</th>
                <th>Fecha Realizada</th>
                <th>Proveedor / Taller</th>
                <th>Costo (Lps.)</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($datos as $i => $m): 
                if ($m['estado'] === 'Completado') $totalGasto += (float)$m['costo'];
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($m['placa']) ?></strong></td>
                <td><?= htmlspecialchars($m['sucursal']) ?></td>
                <td><?= htmlspecialchars($m['tipo']) ?></td>
                <td><?= htmlspecialchars($m['titulo']) ?></td>
                <td><?= date('d/m/Y', strtotime($m['fecha_programada'])) ?></td>
                <td><?= !empty($m['fecha_realizada']) ? date('d/m/Y', strtotime($m['fecha_realizada'])) : 'Pendiente' ?></td>
                <td><?= htmlspecialchars($m['proveedor'] ?: '-') ?></td>
                <td style="text-align: right;">L. <?= number_format((float)$m['costo'], 2) ?></td>
                <td><?= htmlspecialchars($m['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="8" style="text-align: right;">TOTAL EJECUTADO:</td>
                <td style="text-align: right;">L. <?= number_format($totalGasto, 2) ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

<?php elseif ($tipo_reporte === 'llantas'): ?>
    <?php
        $filtros = ['vehiculo_id' => $_GET['vehiculo_id'] ?? ''];
        $datos = Llanta::obtenerTodas($conn, $filtros);
    ?>
    <table class="table table-bordered table-sm" border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f1f5f9;">
                <th>#</th>
                <th>Vehículo</th>
                <th>Posición</th>
                <th>Código</th>
                <th>Marca / Medida</th>
                <th>Fecha Instalación</th>
                <th>Km Recorridos</th>
                <th>Costo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($datos as $i => $ll): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($ll['placa']) ?></strong></td>
                <td><?= htmlspecialchars($ll['posicion']) ?></td>
                <td><?= htmlspecialchars($ll['codigo']) ?></td>
                <td><?= htmlspecialchars($ll['marca'] . ' ' . $ll['medida']) ?></td>
                <td><?= date('d/m/Y', strtotime($ll['fecha_instalacion'])) ?></td>
                <td><?= number_format((int)$ll['km_recorridos']) ?> km</td>
                <td style="text-align: right;">L. <?= number_format((float)$ll['costo'], 2) ?></td>
                <td><?= htmlspecialchars($ll['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
