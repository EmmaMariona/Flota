-- ==========================================================
-- SISTEMA DE GESTIÓN DE FLOTA VEHICULAR
-- Base de Datos: flota_vehicular
-- ==========================================================

USE `flota_vehicular`;

-- 1. Tabla de Usuarios y Roles
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('Administrador', 'Supervisor', 'Consulta') NOT NULL DEFAULT 'Consulta',
    `estado` ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Asegurar campos en Vehículos
ALTER TABLE `vehiculos`
    ADD COLUMN IF NOT EXISTS `fecha_adquisicion` DATE NULL AFTER `kilometraje_actual`,
    ADD COLUMN IF NOT EXISTS `observaciones` TEXT NULL AFTER `fecha_adquisicion`;

-- 3. Tabla de Mantenimientos
CREATE TABLE IF NOT EXISTS `mantenimientos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehiculo_id` INT NOT NULL,
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
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mantenimientos_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
