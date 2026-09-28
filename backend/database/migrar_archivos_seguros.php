<?php
require_once __DIR__ . '/../config/conexion.php';

$queries = [
    "ALTER TABLE vehiculo_documentos ADD COLUMN IF NOT EXISTS extension VARCHAR(15) NULL AFTER ruta_archivo",
    "ALTER TABLE vehiculo_documentos ADD COLUMN IF NOT EXISTS mime_type VARCHAR(100) NULL AFTER extension",
    "ALTER TABLE vehiculo_documentos ADD COLUMN IF NOT EXISTS tamano_bytes BIGINT UNSIGNED NULL AFTER mime_type",
    "ALTER TABLE mantenimientos ADD COLUMN IF NOT EXISTS archivo_nombre_original VARCHAR(255) NULL AFTER observaciones",
    "ALTER TABLE mantenimientos ADD COLUMN IF NOT EXISTS archivo_nombre VARCHAR(255) NULL AFTER archivo_nombre_original",
    "ALTER TABLE mantenimientos ADD COLUMN IF NOT EXISTS archivo_ruta VARCHAR(255) NULL AFTER archivo_nombre",
    "ALTER TABLE mantenimientos ADD COLUMN IF NOT EXISTS archivo_extension VARCHAR(15) NULL AFTER archivo_ruta",
    "ALTER TABLE mantenimientos ADD COLUMN IF NOT EXISTS archivo_mime VARCHAR(100) NULL AFTER archivo_extension",
    "ALTER TABLE mantenimientos ADD COLUMN IF NOT EXISTS archivo_tamano BIGINT UNSIGNED NULL AFTER archivo_mime"
];

foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        echo "Error ejecutando SQL: " . $conn->error . "\n";
    }
}

// Backfill metadatos de documentos existentes
$res = $conn->query("SELECT * FROM vehiculo_documentos");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rutaFisica = dirname(__DIR__, 2) . '/' . ltrim($row['ruta_archivo'], '/\\');
        $ext = strtolower(pathinfo($row['nombre_original'], PATHINFO_EXTENSION));
        $mime = is_file($rutaFisica) ? (mime_content_type($rutaFisica) ?: 'application/pdf') : ($ext === 'pdf' ? 'application/pdf' : 'image/jpeg');
        $tamano = is_file($rutaFisica) ? (int)filesize($rutaFisica) : 0;

        $stmt = $conn->prepare("UPDATE vehiculo_documentos SET extension = ?, mime_type = ?, tamano_bytes = ? WHERE id = ?");
        $stmt->bind_param("ssii", $ext, $mime, $tamano, $row['id']);
        $stmt->execute();
    }
}

echo "Migracion de metadatos de archivos completada exitosamente.\n";
