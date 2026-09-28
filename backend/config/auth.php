<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (empty($_COOKIE['flota_cache_v']) || $_COOKIE['flota_cache_v'] !== '20260927_v7') {
        header('Clear-Site-Data: "cache"');
        setcookie('flota_cache_v', '20260927_v7', time() + 86400 * 30, '/');
    }
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
        header("Location: " . BASE_URL . "/frontend/login.html");
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
        header("Location: " . BASE_URL . "/frontend/index.html");
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

/**
 * Exige autenticación para peticiones API/Fetch. Devuelve JSON 401 si no está autenticado.
 */
function requerirAutenticacionApi(): void
{
    if (!estaAutenticado()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'mensaje' => 'Sesión expirada o no autenticado.',
            'redirect' => BASE_URL . '/frontend/login.html'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Exige un rol específico para peticiones API/Fetch. Devuelve JSON 403 si no tiene permiso.
 */
function requerirRolApi(array $rolesPermitidos): void
{
    requerirAutenticacionApi();
    if (!tieneRol($rolesPermitidos)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'mensaje' => 'No tienes permisos suficientes para realizar esta acción.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Emite una respuesta estándar en formato JSON y termina la ejecución.
 */
function respuestaJson(bool $success, mixed $data = null, string $mensaje = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    $payload = ['success' => $success];
    if (!empty($mensaje)) $payload['mensaje'] = $mensaje;
    if ($data !== null) $payload['data'] = $data;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
