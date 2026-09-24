<nav class="navbar navbar-expand bg-dark border-bottom border-secondary border-opacity-25 px-4 py-2">
    <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
        <!-- Botón toggle para dispositivos móviles -->
        <button class="btn btn-outline-secondary d-md-none me-2" type="button" id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="d-none d-md-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1">
                <span class="spinner-grow spinner-grow-sm text-success" style="width: 0.5rem; height: 0.5rem;" role="status"></span>
                Servidor Conectado
            </span>
            <span class="text-secondary small">| Base de datos: <code class="text-light">flota_vehicular</code></span>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="dropdown">
                <button class="btn btn-sm btn-dark dropdown-toggle d-flex align-items-center gap-2 border border-secondary border-opacity-50 px-3 py-2 rounded-pill" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle text-primary"></i>
                    <span class="small fw-semibold"><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
                    <li class="dropdown-header">
                        <strong><?= htmlspecialchars($usuario['email'] ?? '') ?></strong><br>
                        <small class="text-secondary">Rol: <?= htmlspecialchars($usuario['rol'] ?? 'Consulta') ?></small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?= BASE_URL ?>/controllers/AuthController.php?accion=logout">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
