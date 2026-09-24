<?php
$current_uri = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar bg-dark text-white d-flex flex-column flex-shrink-0 p-3">
    <!-- Brand -->
    <a href="<?= BASE_URL ?>/dashboard/index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none px-2">
        <div class="brand-icon me-2 rounded d-flex align-items-center justify-content-center bg-primary text-white">
            <i class="bi bi-truck fs-4"></i>
        </div>
        <div>
            <span class="fs-5 fw-bold tracking-wide">FLOTA</span>
        </div>
    </a>
    
    <hr class="border-secondary opacity-25 my-3">

    <!-- Navegación Principal -->
    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard/index.php" class="nav-link <?= strpos($current_uri, '/dashboard') !== false ? 'active' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/vehiculos/lista.php" class="nav-link <?= strpos($current_uri, '/vehiculos') !== false ? 'active' : '' ?>">
                <i class="bi bi-truck-front me-2"></i> Vehículos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/mantenimientos/lista.php" class="nav-link <?= strpos($current_uri, '/mantenimientos') !== false ? 'active' : '' ?>">
                <i class="bi bi-tools me-2"></i> Mantenimientos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/llantas/lista.php" class="nav-link <?= strpos($current_uri, '/llantas') !== false ? 'active' : '' ?>">
                <i class="bi bi-record-circle me-2"></i> Llantas
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/reportes/index.php" class="nav-link <?= strpos($current_uri, '/reportes') !== false ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph me-2"></i> Reportes
            </a>
        </li>

        <?php if (tieneRol(['Administrador'])): ?>
        <li class="nav-header text-uppercase text-secondary fw-semibold mt-3 mb-1 px-3" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            Administración
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/usuarios/lista.php" class="nav-link <?= strpos($current_uri, '/usuarios') !== false ? 'active' : '' ?>">
                <i class="bi bi-people me-2"></i> Usuarios y Roles
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <hr class="border-secondary opacity-25 my-3">

    <!-- Usuario en sesión -->
    <div class="user-profile-badge p-2 rounded d-flex align-items-center">
        <div class="avatar rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white me-2">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold text-truncate small"><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></div>
            <div class="text-secondary small d-flex align-items-center gap-1">
                <span class="badge <?= ($usuario['rol'] ?? '') === 'Administrador' ? 'bg-danger-subtle text-danger' : (($usuario['rol'] ?? '') === 'Supervisor' ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info') ?> px-1 py-0" style="font-size: 0.7rem;">
                    <?= htmlspecialchars($usuario['rol'] ?? 'Consulta') ?>
                </span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/controllers/AuthController.php?accion=logout" class="btn btn-sm btn-outline-secondary border-0 text-danger ms-1" title="Cerrar Sesión">
            <i class="bi bi-box-arrow-right fs-5"></i>
        </a>
    </div>
</aside>