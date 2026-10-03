<?php
/**
 * Cargador de Variables de Entorno (.env)
 * Permite separar la configuración sensible (credenciales, hosts) del código fuente.
 */

if (!function_exists('cargarEnv')) {
    function cargarEnv(string $rutaArchivo): void
    {
        if (!file_exists($rutaArchivo)) {
            return;
        }

        $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Ignorar comentarios
            if (empty($linea) || str_starts_with($linea, '#')) {
                continue;
            }

            // Separar clave=valor
            if (strpos($linea, '=') !== false) {
                list($clave, $valor) = explode('=', $linea, 2);
                $clave = trim($clave);
                $valor = trim($valor);

                // Quitar comillas si las tiene ("valor" o 'valor')
                if (
                    (str_starts_with($valor, '"') && str_ends_with($valor, '"')) ||
                    (str_starts_with($valor, "'") && str_ends_with($valor, "'"))
                ) {
                    $valor = substr($valor, 1, -1);
                }

                // Cargar en $_ENV, $_SERVER y putenv solo si no está definida en el entorno del sistema/contenedor
                if (getenv($clave) === false && !isset($_SERVER[$clave])) {
                    $_ENV[$clave] = $valor;
                    $_SERVER[$clave] = $valor;
                    putenv("{$clave}={$valor}");
                }
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Helper para obtener una variable de entorno con valor por defecto.
     * Prioriza variables de entorno del sistema/contenedor (Docker), luego $_SERVER y $_ENV.
     */
    function env(string $clave, mixed $default = null): mixed
    {
        $val = getenv($clave);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_SERVER[$clave]) && $_SERVER[$clave] !== '') {
            return $_SERVER[$clave];
        }
        if (isset($_ENV[$clave]) && $_ENV[$clave] !== '') {
            return $_ENV[$clave];
        }
        return $default;
    }
}
