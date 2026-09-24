<?php
$titulo_pagina = "Crear Nuevo Usuario";
require_once __DIR__ . '/../includes/header.php';

requerirRol(['Administrador']);
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Crear Usuario</h2>
                <p class="text-secondary small mb-0">Agrega un nuevo operador al sistema con su rol correspondiente.</p>
            </div>
            <a href="<?= BASE_URL ?>/usuarios/lista.php" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=guardar" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej. Juan Pérez">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="correo@empresa.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña de Acceso <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label">Rol de Usuario</label>
                            <select name="rol" class="form-select">
                                <option value="Consulta">Consulta (Solo lectura)</option>
                                <option value="Supervisor">Supervisor (Crear y editar)</option>
                                <option value="Administrador">Administrador (Total)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="Activo" selected>Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                        <a href="<?= BASE_URL ?>/usuarios/lista.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
