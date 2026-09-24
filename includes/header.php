<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/flota');
}
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

// Si la página no es pública (como login), exigir autenticación
if (!isset($es_pagina_publica) || !$es_pagina_publica) {
    requerirAutenticacion();
}

$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) . " - " : "" ?>Gestión de Flota Vehicular</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js para estadísticas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Estilos personalizados (Tema Oscuro Profesional) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/estilo.css">
</head>
<body class="bg-body-tertiary">
<?php if (!isset($es_pagina_publica) || !$es_pagina_publica): ?>
<div class="d-flex min-vh-100 layout-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="d-flex flex-column flex-grow-1 main-content-wrapper">
        <?php include __DIR__ . '/navbar.php'; ?>
        <main class="container-fluid p-4 content-area">
            <?php include __DIR__ . '/alerts.php'; ?>
<?php endif; ?>