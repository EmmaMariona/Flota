<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Mantenimiento.php';

requerirAutenticacion();

$id = (int)($_GET['id'] ?? 0);
$m = Mantenimiento::obtenerPorId($conn, $id);

if (!$m) {
    die("Orden de mantenimiento no encontrada.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Mantenimiento #<?= $m['id'] ?> - <?= htmlspecialchars($m['placa']) ?></title>
    
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
        }

        .document-wrapper {
            max-width: 850px;
            margin: 20px auto;
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .header-title {
            color: #1e293b;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .section-header {
            background-color: #f1f5f9;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            color: #334155;
            border-left: 4px solid #3b82f6;
            margin-bottom: 12px;
        }

        .data-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .data-value {
            color: #0f172a;
            font-weight: 600;
            font-size: 13px;
        }

        .signature-box {
            border-top: 1px dashed #94a3b8;
            margin-top: 60px;
            padding-top: 8px;
            text-align: center;
            font-size: 11px;
            color: #475569;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .document-wrapper {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Barra Superior con Botones de Acción (No se imprime) -->
<div class="container my-3 no-print" style="max-width: 850px;">
    <div class="d-flex justify-content-between align-items-center bg-dark text-white p-3 rounded shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">Listo para imprimir</span>
            <span class="small text-secondary">| Orden #<?= $m['id'] ?> (<?= htmlspecialchars($m['placa']) ?>)</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
            <a href="<?= BASE_URL ?>/mantenimientos/editar.php?id=<?= $m['id'] ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold px-3">
                <i class="bi bi-printer-fill me-1"></i> Imprimir / Guardar PDF
            </button>
        </div>
    </div>
</div>

<!-- Contenedor Oficial de la Orden de Trabajo -->
<div class="document-wrapper">
    <!-- Encabezado Institucional -->
    <div class="row align-items-center border-bottom pb-3 mb-4">
        <div class="col-8">
            <h4 class="header-title mb-0">SISTEMA DE GESTIÓN DE FLOTA VEHICULAR</h4>
            <div class="text-secondary small">ORDEN DE SERVICIO Y MANTENIMIENTO AUTOMOTRIZ</div>
            <div class="text-secondary small">Sucursal: <strong><?= htmlspecialchars($m['sucursal']) ?></strong></div>
        </div>
        <div class="col-4 text-end">
            <div class="border border-dark p-2 rounded text-center">
                <div class="text-secondary small fw-bold">ORDEN N°</div>
                <div class="fs-4 fw-bold text-danger"><?= str_pad($m['id'], 6, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div class="small text-muted mt-1">Emisión: <?= date('d/m/Y') ?></div>
        </div>
    </div>

    <!-- 1. Identificación del Vehículo -->
    <div class="section-header">1. Datos del Vehículo / Unidad</div>
    <div class="row g-3 mb-4">
        <div class="col-3">
            <div class="data-label">Placa</div>
            <div class="data-value fs-5 text-primary"><?= htmlspecialchars($m['placa']) ?></div>
        </div>
        <div class="col-3">
            <div class="data-label">Marca y Modelo</div>
            <div class="data-value"><?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?></div>
        </div>
        <div class="col-3">
            <div class="data-label">Sucursal Base</div>
            <div class="data-value"><?= htmlspecialchars($m['sucursal']) ?></div>
        </div>
        <div class="col-3">
            <div class="data-label">Odómetro Actual</div>
            <div class="data-value"><?= number_format((int)$m['kilometraje_actual']) ?> km</div>
        </div>
    </div>

    <!-- 2. Información del Servicio -->
    <div class="section-header">2. Especificaciones de la Orden de Trabajo</div>
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="data-label">Tipo de Servicio</div>
            <div class="data-value">
                <span class="badge <?= $m['tipo'] === 'Preventivo' ? 'bg-info text-dark' : 'bg-danger text-white' ?> px-2 py-1">
                    <?= htmlspecialchars($m['tipo']) ?>
                </span>
            </div>
        </div>
        <div class="col-4">
            <div class="data-label">Estado de la Orden</div>
            <div class="data-value">
                <strong><?= htmlspecialchars($m['estado']) ?></strong>
            </div>
        </div>
        <div class="col-4">
            <div class="data-label">Taller / Proveedor Asignado</div>
            <div class="data-value"><?= htmlspecialchars($m['proveedor'] ?: 'Taller no asignado') ?></div>
        </div>

        <div class="col-4">
            <div class="data-label">Fecha Programada</div>
            <div class="data-value"><?= date('d/m/Y', strtotime($m['fecha_programada'])) ?></div>
        </div>
        <div class="col-4">
            <div class="data-label">Fecha Realizada</div>
            <div class="data-value"><?= !empty($m['fecha_realizada']) ? date('d/m/Y', strtotime($m['fecha_realizada'])) : 'Pendiente' ?></div>
        </div>
        <div class="col-4">
            <div class="data-label">Kilometraje al Servicio</div>
            <div class="data-value"><?= !empty($m['kilometraje_servicio']) ? number_format((int)$m['kilometraje_servicio']) . ' km' : 'No especificado' ?></div>
        </div>

        <div class="col-12">
            <div class="data-label">Título del Servicio</div>
            <div class="data-value fs-6 text-dark border-bottom pb-1"><?= htmlspecialchars($m['titulo']) ?></div>
        </div>

        <div class="col-12">
            <div class="data-label">Descripción Detallada y Repuestos Requeridos</div>
            <div class="p-3 bg-light rounded border" style="min-height: 80px;">
                <?= nl2br(htmlspecialchars($m['descripcion'] ?: 'Sin descripción adicional.')) ?>
            </div>
        </div>

        <?php if (!empty($m['observaciones'])): ?>
        <div class="col-12">
            <div class="data-label">Observaciones / Número de Factura</div>
            <div class="data-value text-secondary"><?= htmlspecialchars($m['observaciones']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3. Costo y Liquidación -->
    <div class="section-header">3. Liquidación Económica</div>
    <div class="row align-items-center mb-5 p-2 bg-light rounded border mx-0">
        <div class="col-8 text-end">
            <span class="fs-6 fw-bold">COSTO TOTAL DEL SERVICIO:</span>
        </div>
        <div class="col-4 text-end">
            <span class="fs-4 fw-bold text-success">L. <?= number_format((float)$m['costo'], 2) ?></span>
        </div>
    </div>

    <!-- 4. Firmas de Conformidad -->
    <div class="row text-center mt-5 pt-4">
        <div class="col-4">
            <div class="signature-box">
                <strong>Autorizado por:</strong><br>
                Jefe de Taller / Administrador de Flota
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <strong>Ejecutado por:</strong><br>
                Mecánico Responsable / Taller
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <strong>Recibido Conforme:</strong><br>
                Conductor / Supervisor Asignado
            </div>
        </div>
    </div>

    <!-- Pie de página del documento -->
    <div class="mt-5 pt-3 border-top text-center text-muted small" style="font-size: 10px;">
        Documento oficial generado automáticamente por el Sistema de Gestión de Flota Vehicular | <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

</body>
</html>
