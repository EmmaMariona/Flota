<?php
$alerta = obtenerAlerta();
if ($alerta):
    $icono = 'bi-info-circle-fill';
    if ($alerta['tipo'] === 'success') $icono = 'bi-check-circle-fill';
    if ($alerta['tipo'] === 'danger') $icono = 'bi-exclamation-triangle-fill';
    if ($alerta['tipo'] === 'warning') $icono = 'bi-exclamation-circle-fill';
?>
<div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert">
    <i class="bi <?= $icono ?> fs-5 me-2"></i>
    <div><?= htmlspecialchars($alerta['mensaje']) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
