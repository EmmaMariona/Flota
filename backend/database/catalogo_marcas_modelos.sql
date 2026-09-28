-- ============================================================
-- CATÁLOGO DE MARCAS Y MODELOS DE VEHÍCULOS
-- ============================================================

CREATE TABLE IF NOT EXISTS marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modelos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (marca_id) REFERENCES marcas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar Marcas Iniciales
INSERT IGNORE INTO marcas (nombre, activo) VALUES
('Great Dane', 1),
('Fruehauf', 1),
('Freightliner', 1),
('Hino', 1),
('Fuso', 1),
('Isuzu', 1);
