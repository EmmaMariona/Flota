<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/flota');
}

/**
 * Verifica si el usuario ha iniciado sesión.
 */
function estaAutenticado(): bool
{
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

/**
 * Requiere que el usuario esté autenticado. Si no, lo redirige al login.
 */
function requerirAutenticacion(): void
{
    if (!estaAutenticado()) {
        header("Location: " . BASE_URL . "/views/auth/login.php");
        exit;
    }
}

/**
 * Obtiene los datos del usuario en sesión.
 */
function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/**
 * Comprueba si el usuario tiene uno de los roles dados.
 */
function tieneRol(array $rolesPermitidos): bool
{
    $usuario = usuarioActual();
    if (!$usuario) {
        return false;
    }
    return in_array($usuario['rol'], $rolesPermitidos, true);
}

/**
 * Exige que el usuario tenga un rol específico, si no bloquea o redirige.
 */
function requerirRol(array $rolesPermitidos): void
{
    requerirAutenticacion();
    if (!tieneRol($rolesPermitidos)) {
        setAlerta('danger', 'No tienes permisos suficientes para realizar esta acción.');
        header("Location: " . BASE_URL . "/dashboard/index.php");
        exit;
    }
}

/**
 * Helper para saber si el usuario puede editar (Admin o Supervisor).
 */
function puedeEditar(): bool
{
    return tieneRol(['Administrador', 'Supervisor']);
}

/**
 * Establece un mensaje flash en la sesión.
 */
function setAlerta(string $tipo, string $mensaje): void
{
    $_SESSION['alerta'] = [
        'tipo' => $tipo, // success, danger, warning, info
        'mensaje' => $mensaje
    ];
}

/**
 * Obtiene y limpia el mensaje flash de la sesión.
 */
function obtenerAlerta(): ?array
{
    if (isset($_SESSION['alerta'])) {
        $alerta = $_SESSION['alerta'];
        unset($_SESSION['alerta']);
        return $alerta;
    }
    return null;
}
