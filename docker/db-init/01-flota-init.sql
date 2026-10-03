-- ====================================================================
-- SISTEMA DE GESTIÓN DE FLOTA VEHICULAR
-- Base de Datos Inicial y Estructura Completa para Docker / Producción
-- Base de Datos: flota_vehicular
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `flota_vehicular` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `flota_vehicular`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------
-- 1. Tabla de Usuarios y Roles
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('Administrador', 'Supervisor', 'Consulta') NOT NULL DEFAULT 'Consulta',
    `estado` ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. Tabla de Sucursales
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sucursales` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL UNIQUE,
    `activo` TINYINT(1) DEFAULT 1,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. Tabla de Marcas (Catálogo Administrado)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `marcas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL UNIQUE,
    `activo` TINYINT(1) DEFAULT 1,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. Tabla de Modelos (Asociados a Marca)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `modelos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `marca_id` INT NOT NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_modelos_marca` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. Tabla de Posiciones de Llantas
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posiciones_llantas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 6. Tabla de Vehículos
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehiculos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `placa` VARCHAR(20) NOT NULL UNIQUE,
    `sucursal_id` INT NULL,
    `tipo` VARCHAR(50) NOT NULL DEFAULT 'Camión',
    `marca_id` INT NULL,
    `marca` VARCHAR(100) NULL,
    `modelo_id` INT NULL,
    `modelo` VARCHAR(100) NULL,
    `anio` INT NULL,
    `motor` VARCHAR(50) NULL,
    `vin` VARCHAR(50) NULL,
    `tonelaje` DECIMAL(10,2) DEFAULT 0.00,
    `estado` ENUM('Activo', 'Taller', 'Inactivo') NOT NULL DEFAULT 'Activo',
    `kilometraje_actual` INT DEFAULT 0,
    `fecha_adquisicion` DATE NULL,
    `observaciones` TEXT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vehiculos_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_vehiculos_marca` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_vehiculos_modelo` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 7. Tabla de Llantas
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `llantas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehiculo_id` INT NOT NULL,
    `posicion_id` INT NOT NULL,
    `codigo` VARCHAR(50) NOT NULL,
    `medida` VARCHAR(50) NOT NULL,
    `marca` VARCHAR(50) NOT NULL,
    `tipo_llanta` VARCHAR(50) NOT NULL,
    `fecha_instalacion` DATE NOT NULL,
    `km_instalacion` INT DEFAULT 0,
    `costo` DECIMAL(10,2) DEFAULT 0.00,
    `codigo_anterior` VARCHAR(50) NULL,
    `estado` ENUM('Activa', 'Desecho', 'Repuesto', 'Reencauche') NOT NULL DEFAULT 'Activa',
    `observaciones` TEXT NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_llantas_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_llantas_posicion` FOREIGN KEY (`posicion_id`) REFERENCES `posiciones_llantas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 8. Tabla de Mantenimientos y Órdenes de Trabajo
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mantenimientos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehiculo_id` INT NOT NULL,
    `sucursal_id` INT NULL,
    `tipo` ENUM('Preventivo', 'Correctivo') NOT NULL DEFAULT 'Preventivo',
    `titulo` VARCHAR(150) NOT NULL,
    `descripcion` TEXT NULL,
    `costo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `fecha_programada` DATE NOT NULL,
    `fecha_realizada` DATE NULL,
    `kilometraje_servicio` INT NULL,
    `proveedor` VARCHAR(100) NULL,
    `estado` ENUM('Programado', 'En proceso', 'Completado', 'Cancelado') NOT NULL DEFAULT 'Programado',
    `observaciones` TEXT NULL,
    `archivo_nombre_original` VARCHAR(255) NULL,
    `archivo_nombre` VARCHAR(255) NULL,
    `archivo_ruta` VARCHAR(255) NULL,
    `archivo_extension` VARCHAR(15) NULL,
    `archivo_mime` VARCHAR(100) NULL,
    `archivo_tamano` BIGINT UNSIGNED NULL,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mantenimientos_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mantenimientos_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 9. Tabla de Documentación Digital de Vehículos
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehiculo_documentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehiculo_id` INT NOT NULL,
    `tipo_documento` VARCHAR(100) NOT NULL,
    `nombre_original` VARCHAR(255) NOT NULL,
    `nombre_archivo` VARCHAR(255) NOT NULL,
    `ruta_archivo` VARCHAR(500) NOT NULL,
    `extension` VARCHAR(15) NULL,
    `mime_type` VARCHAR(100) NULL,
    `tamano_bytes` BIGINT UNSIGNED NULL,
    `fecha_vencimiento` DATE NULL,
    `observaciones` TEXT NULL,
    `fecha_subida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_documentos_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- SEED DATA (Datos Iniciales del Sistema)
-- ====================================================================

-- 1. Usuarios del Sistema (Contraseña por defecto: admin123)
-- Hash bcrypt para 'admin123': $2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `estado`) VALUES
(1, 'Administrador Principal', 'admin@flota.com', '$2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO', 'Administrador', 'Activo'),
(2, 'Carlos Mendoza (Supervisor)', 'supervisor@flota.com', '$2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO', 'Supervisor', 'Activo'),
(3, 'Ana López (Consulta)', 'consulta@flota.com', '$2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO', 'Consulta', 'Activo')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 2. Sucursales Iniciales
INSERT INTO `sucursales` (`id`, `nombre`, `activo`) VALUES
(1, 'San Pedro Sula', 1),
(2, 'Tegucigalpa', 1),
(3, 'Choloma', 1),
(4, 'La Ceiba', 1),
(5, 'San Fernando', 1),
(6, 'Comayagüela', 1),
(7, 'CDN', 1),
(8, 'Administración', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 3. Catálogo de Marcas
INSERT INTO `marcas` (`id`, `nombre`, `activo`) VALUES
(1, 'Great Dane', 1),
(2, 'Fruehauf', 1),
(3, 'Freightliner', 1),
(4, 'Hino', 1),
(5, 'Fuso', 1),
(6, 'Isuzu', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 4. Catálogo de Modelos
INSERT INTO `modelos` (`marca_id`, `nombre`, `activo`) VALUES
-- Great Dane
(1, '7311TP5-53', 1),
-- Fruehauf
(2, 'Fruehauf', 1),
-- Freightliner
(3, 'Columbia', 1),
-- Hino
(4, 'XZU650L-WK', 1),
-- Fuso
(5, 'Canter', 1),
-- Isuzu
(6, 'FVR', 1),
(6, 'NKR', 1),
(6, 'NKR66LL5HXY2', 1),
(6, 'NLR55E-26', 1),
(6, 'NLR55E12', 1),
(6, 'NPR', 1),
(6, 'NPR71L-KJ5VA', 1),
(6, 'NQR', 1),
(6, 'QKR', 1),
(6, 'QKR55L-FH5AY', 1),
(6, 'QMR', 1),
(6, 'TFR7MJDLPAA', 1),
(6, 'TFR7MJDLPAA-QLA016', 1),
(6, 'TFR7MJSCLMAA-QLA004', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 5. Catálogo de Posiciones de Llantas
INSERT INTO `posiciones_llantas` (`id`, `nombre`) VALUES
(1, 'Delantera Izquierda'),
(2, 'Delantera Derecha'),
(3, 'Trasera Izquierda Interna'),
(4, 'Trasera Izquierda Externa'),
(5, 'Trasera Derecha Interna'),
(6, 'Trasera Derecha Externa'),
(7, 'Repuesto 1'),
(8, 'Repuesto 2')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 6. Vehículos Iniciales de Prueba
INSERT INTO `vehiculos` (`id`, `placa`, `sucursal_id`, `tipo`, `marca_id`, `marca`, `modelo_id`, `modelo`, `anio`, `motor`, `vin`, `tonelaje`, `estado`, `kilometraje_actual`, `fecha_adquisicion`, `observaciones`) VALUES
(1, 'HAA-1234', 1, 'Camión', 6, 'Isuzu', 11, 'NPR', 2021, '4HK1-TC', '1GBKC14B1JF109283', 5.50, 'Activo', 45200, '2021-03-15', 'Unidad en óptimas condiciones asignada a rutas de distribución norte.'),
(2, 'HAB-5678', 2, 'Cabezal', 3, 'Freightliner', 3, 'Columbia', 2019, 'Detroit DD15', '3AKJHHDR5KSLL1294', 18.00, 'Activo', 125800, '2019-07-20', 'Mantenimiento al día. Ruta pesada interurbana.'),
(3, 'HAC-9012', 4, 'Camión', 6, 'Isuzu', 6, 'FVR', 2018, '6HK1-TCS', 'JALB4B1B3K7001928', 12.00, 'Taller', 89300, '2018-11-10', 'En taller por mantenimiento correctivo de frenos.'),
(4, 'HAD-3456', 3, 'Remolque', 1, 'Great Dane', 1, '7311TP5-53', 2022, 'N/A', '1GRAA0621NB123456', 25.00, 'Activo', 31000, '2022-01-25', 'Furgón seco de 53 pies.'),
(5, 'HAE-7890', 1, 'Camión Liviano', 4, 'Hino', 4, 'XZU650L-WK', 2020, 'N04C', 'JHMXZU65000102938', 4.50, 'Activo', 62400, '2020-05-18', 'Reparto local en San Pedro Sula.')
ON DUPLICATE KEY UPDATE `placa` = VALUES(`placa`);

-- 7. Llantas Iniciales de Muestra
INSERT INTO `llantas` (`vehiculo_id`, `posicion_id`, `codigo`, `medida`, `marca`, `tipo_llanta`, `fecha_instalacion`, `km_instalacion`, `costo`, `codigo_anterior`, `estado`, `observaciones`) VALUES
(1, 1, 'LL-001', '215/75R17.5', 'Michelin', 'Direccional', CURDATE() - INTERVAL 90 DAY, 35000, 3200.00, NULL, 'Activa', 'Llanta delantera izquierda en excelente estado'),
(1, 2, 'LL-002', '215/75R17.5', 'Michelin', 'Direccional', CURDATE() - INTERVAL 90 DAY, 35000, 3200.00, NULL, 'Activa', 'Llanta delantera derecha en excelente estado'),
(1, 3, 'LL-003', '215/75R17.5', 'Bridgestone', 'Tracción', CURDATE() - INTERVAL 120 DAY, 30000, 2800.00, NULL, 'Activa', 'Trasera izquierda interna'),
(1, 4, 'LL-004', '215/75R17.5', 'Bridgestone', 'Tracción', CURDATE() - INTERVAL 120 DAY, 30000, 2800.00, NULL, 'Activa', 'Trasera izquierda externa')
ON DUPLICATE KEY UPDATE `codigo` = VALUES(`codigo`);

-- 8. Mantenimientos de Muestra
INSERT INTO `mantenimientos` (`vehiculo_id`, `sucursal_id`, `tipo`, `titulo`, `descripcion`, `costo`, `fecha_programada`, `fecha_realizada`, `kilometraje_servicio`, `proveedor`, `estado`, `observaciones`) VALUES
(1, 1, 'Preventivo', 'Cambio de Aceite y Filtros 40,000 km', 'Reemplazo de aceite sintético 15W-40, filtro de aceite y filtro de aire', 2500.00, CURDATE() - INTERVAL 15 DAY, CURDATE() - INTERVAL 14 DAY, 40250, 'Taller Isuzu Central', 'Completado', 'Servicio realizado sin novedades'),
(1, 1, 'Correctivo', 'Cambio de Pastillas de Freno', 'Reemplazo de pastillas de freno delanteras por desgaste', 1850.50, CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 5 DAY, 44800, 'Frenos y Servicios del Norte', 'Completado', 'Discos en buen estado'),
(1, 1, 'Preventivo', 'Alineación y Balanceo Programado', 'Revisión y balanceo computarizado de neumáticos', 850.00, CURDATE() + INTERVAL 5 DAY, NULL, 46000, 'Llantilandia', 'Programado', 'Pendiente para la próxima semana'),
(2, 2, 'Preventivo', 'Mantenimiento General 120,000 km', 'Revisión de frenos, suspensión, fluidos y engrase general', 4200.00, CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 29 DAY, 120500, 'Centro Diesel Mayorga', 'Completado', 'Buen estado en general'),
(3, 4, 'Correctivo', 'Reparación de Sistema de Frenos Neumáticos', 'Revisión de válvulas y cambio de pulmones de freno', 3800.00, CURDATE() - INTERVAL 2 DAY, NULL, 89300, 'Taller El Atlántico', 'En proceso', 'Esperando repuesto')
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);
