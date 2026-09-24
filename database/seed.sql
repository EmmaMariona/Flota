-- ==========================================================
-- SISTEMA DE GESTIÓN DE FLOTA VEHICULAR
-- Datos de prueba e iniciales (Seed Data)
-- ==========================================================

USE `flota_vehicular`;

-- 1. Insertar Usuarios Iniciales (Password: admin123 para todos)
-- Hash bcrypt para 'admin123': $2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `estado`) VALUES
('Administrador Principal', 'admin@flota.com', '$2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO', 'Administrador', 'Activo'),
('Carlos Mendoza (Supervisor)', 'supervisor@flota.com', '$2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO', 'Supervisor', 'Activo'),
('Ana López (Consulta)', 'consulta@flota.com', '$2y$10$5jrWiHRn3dySkqHyLlZCmOpqs4/i8H2X4GPDwrH1e6YODHxptyBlO', 'Consulta', 'Activo')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 2. Insertar Mantenimientos de Muestra para Vehículos 1 y 2
INSERT INTO `mantenimientos` (`vehiculo_id`, `tipo`, `titulo`, `descripcion`, `costo`, `fecha_programada`, `fecha_realizada`, `kilometraje_servicio`, `proveedor`, `estado`, `observaciones`) VALUES
(1, 'Preventivo', 'Cambio de Aceite y Filtros 10,000 km', 'Reemplazo de aceite sintético 15W-40, filtro de aceite y filtro de aire', 2500.00, CURDATE() - INTERVAL 15 DAY, CURDATE() - INTERVAL 14 DAY, 10250, 'Taller Isuzu Central', 'Completado', 'Servicio realizado sin novedades'),
(1, 'Correctivo', 'Cambio de Pastillas de Freno', 'Reemplazo de pastillas de freno delanteras por desgaste', 1850.50, CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 5 DAY, 11400, 'Frenos y Servicios del Norte', 'Completado', 'Discos en buen estado'),
(1, 'Preventivo', 'Alineación y Balanceo Programado', 'Revisión y balanceo computarizado de 4 neumáticos', 850.00, CURDATE() + INTERVAL 5 DAY, NULL, 12500, 'Llantilandia', 'Programado', 'Pendiente para la próxima semana'),
(2, 'Preventivo', 'Mantenimiento General 20,000 km', 'Revisión de frenos, suspensión, fluidos y cambio de bujías', 3400.00, CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 29 DAY, 20100, 'Centro Automotriz Mayorga', 'Completado', 'Buen estado en general'),
(2, 'Correctivo', 'Reparación de Fuga en Radiador', 'Soldadura de panel y cambio de refrigerante de alta duración', 1400.00, CURDATE() - INTERVAL 2 DAY, NULL, 21200, 'Radiadores Express', 'En proceso', 'Esperando repuesto de manguera')
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

-- 3. Insertar Llantas de Muestra para Vehículo 1
INSERT INTO `llantas` (`vehiculo_id`, `posicion_id`, `codigo`, `medida`, `marca`, `tipo_llanta`, `fecha_instalacion`, `km_instalacion`, `costo`, `codigo_anterior`, `estado`, `observaciones`) VALUES
(1, 1, 'LL-001', '215/75R17.5', 'Michelin', 'Direccional', CURDATE() - INTERVAL 90 DAY, 5000, 3200.00, NULL, 'Activa', 'Llanta delantera izquierda en excelente estado'),
(1, 2, 'LL-002', '215/75R17.5', 'Michelin', 'Direccional', CURDATE() - INTERVAL 90 DAY, 5000, 3200.00, NULL, 'Activa', 'Llanta delantera derecha en excelente estado'),
(1, 3, 'LL-003', '215/75R17.5', 'Bridgestone', 'Tracción', CURDATE() - INTERVAL 120 DAY, 2000, 2800.00, NULL, 'Activa', 'Trasera izquierda interna'),
(1, 4, 'LL-004', '215/75R17.5', 'Bridgestone', 'Tracción', CURDATE() - INTERVAL 120 DAY, 2000, 2800.00, NULL, 'Activa', 'Trasera izquierda externa')
ON DUPLICATE KEY UPDATE `marca` = VALUES(`marca`);
