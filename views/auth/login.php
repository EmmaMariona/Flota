<?php
$es_pagina_publica = true;
$titulo_pagina = "Iniciar Sesión";
require_once __DIR__ . '/../../includes/header.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    header("Location: " . BASE_URL . "/dashboard/index.php");
    exit;
}
?>

<div class="d-flex align-items-center justify-content-center min-vh-100 p-3" style="background: radial-gradient(circle at top, #1e293b 0%, #0f172a 100%);">
    <div class="card border-secondary border-opacity-25 shadow-lg w-100" style="max-width: 440px;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto rounded-3 d-flex align-items-center justify-content-center bg-primary text-white mb-3 shadow" style="width: 54px; height: 54px;">
                    <i class="bi bi-truck fs-2"></i>
                </div>
                <h3 class="fw-bold text-white mb-1">Gestión de Flota</h3>
                <p class="text-secondary small">Sistema Centralizado de Administración Vehicular</p>
            </div>

            <?php include __DIR__ . '/../../includes/alerts.php'; ?>

            <form action="<?= BASE_URL ?>/controllers/AuthController.php?accion=login" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" value="admin@flota.com" required placeholder="correo@ejemplo.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" value="admin123" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow">
                    <i class="bi bi-box-arrow-in-right"></i> Ingresar al Sistema
                </button>
            </form>

            <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                <div class="small text-secondary fw-semibold mb-2 text-center">Credenciales de Prueba (Proyecto Universitario):</div>
                <div class="d-flex flex-column gap-1 small bg-dark p-3 rounded border border-secondary border-opacity-25">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-danger-subtle text-danger">Administrador</span>
                        <code class="text-light">admin@flota.com / admin123</code>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-warning-subtle text-warning">Supervisor</span>
                        <code class="text-light">supervisor@flota.com / admin123</code>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-info-subtle text-info">Consulta</span>
                        <code class="text-light">consulta@flota.com / admin123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
