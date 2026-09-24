<?php
$titulo_pagina = "Gestión de Usuarios";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Usuario.php';

requerirRol(['Administrador']);

$usuarios = Usuario::obtenerTodos($conn);
?>

<div class="d-flex flex-column gap-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="fw-bold mb-1">Usuarios y Control de Acceso</h2>
            <p class="text-secondary small mb-0">Administración de cuentas y asignación de roles (Administrador, Supervisor, Consulta).</p>
        </div>
        <a href="<?= BASE_URL ?>/usuarios/crear.php" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-person-plus"></i> Nuevo Usuario
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Rol de Acceso</th>
                        <th>Estado</th>
                        <th>Fecha de Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-light d-flex align-items-center gap-2">
                                <div class="avatar rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php
                                $rolClass = 'bg-info-subtle text-info';
                                if ($u['rol'] === 'Administrador') $rolClass = 'bg-danger-subtle text-danger';
                                if ($u['rol'] === 'Supervisor') $rolClass = 'bg-warning-subtle text-warning';
                            ?>
                            <span class="badge <?= $rolClass ?> px-2 py-1">
                                <?= htmlspecialchars($u['rol']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $u['estado'] === 'Activo' ? 'badge-activo' : 'badge-inactivo' ?> px-2 py-1">
                                <?= htmlspecialchars($u['estado']) ?>
                            </span>
                        </td>
                        <td><small class="text-secondary"><?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></small></td>
                        <td class="text-end">
                            <?php if ((int)$u['id'] !== 1): ?>
                            <a href="<?= BASE_URL ?>/controllers/UsuarioController.php?accion=eliminar&id=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               title="Eliminar Usuario"
                               data-confirm="¿Deseas eliminar al usuario <?= htmlspecialchars($u['nombre']) ?>?">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary small">Principal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
