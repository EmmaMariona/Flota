<?php
require_once __DIR__ . '/../config/conexion.php';

function parseXlsx($filePath) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        die("No se pudo abrir el archivo XLSX.\n");
    }

    $sharedStrings = [];
    $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($stringsXml) {
        $xml = simplexml_load_string($stringsXml);
        foreach ($xml->si as $val) {
            if (isset($val->t)) {
                $sharedStrings[] = (string)$val->t;
            } elseif (isset($val->r)) {
                $text = '';
                foreach ($val->r as $run) {
                    $text .= (string)$run->t;
                }
                $sharedStrings[] = $text;
            } else {
                $sharedStrings[] = '';
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) {
        die("No se encontró sheet1.xml en el archivo.\n");
    }

    $xml = simplexml_load_string($sheetXml);
    $rows = [];

    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $cellRef = (string)$cell['r'];
            $colIndex = preg_replace('/[0-9]/', '', $cellRef);
            $type = (string)$cell['t'];
            $val = (string)$cell->v;

            if ($type === 's') {
                $val = $sharedStrings[(int)$val] ?? '';
            }

            $rowData[$colIndex] = trim($val);
        }
        $rows[] = $rowData;
    }

    $zip->close();
    return $rows;
}

function excelDateToYmd($serial) {
    if (is_numeric($serial) && $serial > 1000) {
        $unix = ($serial - 25569) * 86400;
        return gmdate('Y-m-d', (int)$unix);
    }
    if (!empty($serial) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $serial)) {
        return $serial;
    }
    return date('Y-m-d');
}

$file = 'C:\\Users\\emariona\\Downloads\\Historial de mantenimientos.xlsx';
$rows = parseXlsx($file);

// Mapear sucursales existentes (case-insensitive)
$resS = $conn->query("SELECT id, LOWER(TRIM(nombre)) AS nombre FROM sucursales");
$sucursalesMap = [];
while ($s = $resS->fetch_assoc()) {
    $sucursalesMap[$s['nombre']] = (int)$s['id'];
}

// Función helper para obtener o crear sucursal
function obtenerIdSucursal($conn, &$sucursalesMap, $nombreSuc) {
    $nombreLimpio = trim($nombreSuc);
    if (empty($nombreLimpio)) return null;
    $nombreKey = strtolower($nombreLimpio);

    if (isset($sucursalesMap[$nombreKey])) {
        return $sucursalesMap[$nombreKey];
    }

    // Insertar nueva sucursal si no existe
    $stmt = $conn->prepare("INSERT INTO sucursales (nombre) VALUES (?)");
    $stmt->bind_param("s", $nombreLimpio);
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $sucursalesMap[$nombreKey] = $id;
        return $id;
    }
    return null;
}

// Mapear vehículos existentes
$resV = $conn->query("SELECT id, UPPER(TRIM(placa)) AS placa FROM vehiculos");
$vehiculosMap = [];
while ($v = $resV->fetch_assoc()) {
    $vehiculosMap[$v['placa']] = (int)$v['id'];
}

// Función helper para obtener o crear vehículo
function obtenerIdVehiculo($conn, &$vehiculosMap, $placa, $sucursalId) {
    $placaLimpia = strtoupper(trim($placa));
    if (empty($placaLimpia)) return null;

    if (isset($vehiculosMap[$placaLimpia])) {
        return $vehiculosMap[$placaLimpia];
    }

    // Insertar vehículo nuevo con datos básicos
    $stmt = $conn->prepare("INSERT INTO vehiculos (placa, sucursal_id, tipo, marca, modelo, estado, kilometraje_actual) VALUES (?, ?, 'Camión', 'S/M', 'S/M', 'Activo', 0)");
    $stmt->bind_param("si", $placaLimpia, $sucursalId);
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $vehiculosMap[$placaLimpia] = $id;
        return $id;
    }
    return null;
}

// Comenzar importación
echo "Iniciando importación masiva...\n";

$insertados = 0;
$omitidos = 0;
$vehiculosNuevos = 0;
$stmtInsert = $conn->prepare("
    INSERT INTO mantenimientos (
        vehiculo_id, tipo, titulo, descripcion, costo,
        fecha_programada, fecha_realizada, kilometraje_servicio,
        proveedor, estado, observaciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completado', ?)
");

// Desactivar autocommit para mayor velocidad en batch
$conn->autocommit(FALSE);

for ($i = 3; $i < count($rows); $i++) {
    $r = $rows[$i];
    $placa = strtoupper(trim($r['D'] ?? ''));
    if (empty($placa)) {
        $omitidos++;
        continue;
    }

    $nombreSucursal = trim($r['C'] ?? '');
    $sucursalId = obtenerIdSucursal($conn, $sucursalesMap, $nombreSucursal);

    $esVehiculoNuevo = !isset($vehiculosMap[$placa]);
    $vehiculoId = obtenerIdVehiculo($conn, $vehiculosMap, $placa, $sucursalId);
    if ($esVehiculoNuevo && $vehiculoId) {
        $vehiculosNuevos++;
    }

    if (!$vehiculoId) {
        $omitidos++;
        continue;
    }

    $fecha = excelDateToYmd($r['B'] ?? '');
    $taller = trim($r['E'] ?? '');
    $costoPrev = (float)str_replace([',', 'L', ' '], '', $r['F'] ?? '0');
    $costoCorrec = (float)str_replace([',', 'L', ' '], '', $r['G'] ?? '0');
    $costoTotal = (float)str_replace([',', 'L', ' '], '', $r['H'] ?? '0');

    // Determinar tipo y costo
    $tipo = 'Preventivo';
    $costo = $costoTotal;

    if ($costoCorrec > 0 && $costoPrev == 0) {
        $tipo = 'Correctivo';
        if ($costo <= 0) $costo = $costoCorrec;
    } elseif ($costoPrev > 0 && $costoCorrec == 0) {
        $tipo = 'Preventivo';
        if ($costo <= 0) $costo = $costoPrev;
    } elseif ($costoCorrec > 0 && $costoPrev > 0) {
        $tipo = 'Correctivo'; // Si tuvo ambos, suele ser correctivo con rutina preventiva
    }

    // Kilometraje
    $km = (int)str_replace([',', '.', ' '], '', $r['I'] ?? '0');
    $kmServicio = $km > 0 ? $km : null;

    // Título y descripción
    $revision = trim($r['J'] ?? '');
    $trabajo = trim($r['K'] ?? '');

    if (!empty($trabajo)) {
        $titulo = mb_substr($trabajo, 0, 140);
        $descripcion = $trabajo . (!empty($revision) ? " (Revisión de: $revision)" : "");
    } elseif (!empty($revision)) {
        $titulo = "Mantenimiento " . mb_substr($revision, 0, 120);
        $descripcion = "Revisión programada de " . $revision;
    } else {
        $titulo = "Servicio " . $tipo;
        $descripcion = "Mantenimiento registrado en " . ($taller ?: "taller");
    }

    $observaciones = !empty($revision) ? "Revisión de: $revision" : "";

    $stmtInsert->bind_param(
        "isssdsssss",
        $vehiculoId,
        $tipo,
        $titulo,
        $descripcion,
        $costo,
        $fecha,
        $fecha,
        $kmServicio,
        $taller,
        $observaciones
    );

    if ($stmtInsert->execute()) {
        $insertados++;

        // Actualizar kilometraje del vehículo si este es mayor
        if ($kmServicio !== null && $kmServicio > 0) {
            $conn->query("UPDATE vehiculos SET kilometraje_actual = GREATEST(kilometraje_actual, $kmServicio) WHERE id = $vehiculoId");
        }
    } else {
        $omitidos++;
    }
}

// Confirmar transacción
$conn->commit();
$conn->autocommit(TRUE);

echo "========================================\n";
echo "IMPORTACIÓN COMPLETADA CON ÉXITO\n";
echo "========================================\n";
echo "Total registros importados a Mantenimientos: $insertados\n";
echo "Vehículos nuevos incorporados a la flota: $vehiculosNuevos\n";
echo "Total vehículos actuales en la flota: " . count($vehiculosMap) . "\n";
echo "Filas omitidas (vacías o sin placa): $omitidos\n";
