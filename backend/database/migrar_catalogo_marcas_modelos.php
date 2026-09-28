<?php
require_once __DIR__ . '/../config/conexion.php';

echo "Iniciando migración de Catálogo de Marcas y Modelos...\n";

// 1. Crear tabla marcas
$sqlMarcas = "
CREATE TABLE IF NOT EXISTS marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if (!$conn->query($sqlMarcas)) {
    die("Error creando tabla marcas: " . $conn->error . "\n");
}
echo "Tabla 'marcas' lista.\n";

// 2. Crear tabla modelos
$sqlModelos = "
CREATE TABLE IF NOT EXISTS modelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES marcas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if (!$conn->query($sqlModelos)) {
    die("Error creando tabla modelos: " . $conn->error . "\n");
}
echo "Tabla 'modelos' lista.\n";

// 3. Catálogo inicial solicitado
$catalogoInicial = [
    'Great Dane' => [
        '7311TP5-53'
    ],
    'Fruehauf' => [
        'Fruehauf'
    ],
    'Freightliner' => [
        'Columbia'
    ],
    'Hino' => [
        'XZU650L-WK'
    ],
    'Fuso' => [
        'Canter'
    ],
    'Isuzu' => [
        'FVR',
        'NKR',
        'NKR66LL5HXY2',
        'NLR55E-26',
        'NLR55E12',
        'NPR',
        'NPR71L-KJ5VA',
        'NQR',
        'QKR',
        'QKR55L-FH5AY',
        'QMR',
        'TFR7MJDLPAA',
        'TFR7MJDLPAA-QLA016',
        'TFR7MJSCLMAA-QLA004'
    ]
];

$stmtFindMarca = $conn->prepare("SELECT id FROM marcas WHERE nombre = ? LIMIT 1");
$stmtInsertMarca = $conn->prepare("INSERT INTO marcas (nombre, activo) VALUES (?, 1)");
$stmtFindModelo = $conn->prepare("SELECT id FROM modelos WHERE marca_id = ? AND nombre = ? LIMIT 1");
$stmtInsertModelo = $conn->prepare("INSERT INTO modelos (marca_id, nombre, activo) VALUES (?, ?, 1)");

foreach ($catalogoInicial as $nombreMarca => $modelos) {
    $stmtFindMarca->bind_param("s", $nombreMarca);
    $stmtFindMarca->execute();
    $resM = $stmtFindMarca->get_result();
    $rowM = $resM->fetch_assoc();

    if ($rowM) {
        $marcaId = (int)$rowM['id'];
    } else {
        $stmtInsertMarca->bind_param("s", $nombreMarca);
        $stmtInsertMarca->execute();
        $marcaId = (int)$conn->insert_id;
        echo "  + Marca insertada: {$nombreMarca} (ID: {$marcaId})\n";
    }

    foreach ($modelos as $nombreModelo) {
        $stmtFindModelo->bind_param("is", $marcaId, $nombreModelo);
        $stmtFindModelo->execute();
        $resMod = $stmtFindModelo->get_result();
        $rowMod = $resMod->fetch_assoc();

        if (!$rowMod) {
            $stmtInsertModelo->bind_param("is", $marcaId, $nombreModelo);
            $stmtInsertModelo->execute();
            echo "    + Modelo insertado: {$nombreMarca} -> {$nombreModelo}\n";
        }
    }
}

// 4. Agregar columnas opcionales marca_id y modelo_id en vehiculos para integridad relacional sin afectar campos actuales
$resCols = $conn->query("SHOW COLUMNS FROM vehiculos LIKE 'marca_id'");
if ($resCols && $resCols->num_rows === 0) {
    $conn->query("ALTER TABLE vehiculos ADD COLUMN marca_id INT NULL AFTER tipo");
    $conn->query("ALTER TABLE vehiculos ADD CONSTRAINT fk_vehiculos_marca FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL");
    echo "Columna 'marca_id' agregada a vehiculos.\n";
}

$resColsMod = $conn->query("SHOW COLUMNS FROM vehiculos LIKE 'modelo_id'");
if ($resColsMod && $resColsMod->num_rows === 0) {
    $conn->query("ALTER TABLE vehiculos ADD COLUMN modelo_id INT NULL AFTER marca");
    $conn->query("ALTER TABLE vehiculos ADD CONSTRAINT fk_vehiculos_modelo FOREIGN KEY (modelo_id) REFERENCES modelos(id) ON DELETE SET NULL");
    echo "Columna 'modelo_id' agregada a vehiculos.\n";
}

// 5. Vincular vehículos existentes cuando coincidan con marcas y modelos del catálogo (sin perder ni alterar textos originales)
$conn->query("
    UPDATE vehiculos v
    INNER JOIN marcas m ON LOWER(TRIM(v.marca)) = LOWER(TRIM(m.nombre))
    SET v.marca_id = m.id
    WHERE v.marca_id IS NULL
");

$conn->query("
    UPDATE vehiculos v
    INNER JOIN modelos mo ON v.marca_id = mo.marca_id AND LOWER(TRIM(v.modelo)) = LOWER(TRIM(mo.nombre))
    SET v.modelo_id = mo.id
    WHERE v.modelo_id IS NULL
");

echo "Migración completada exitosamente.\n";
