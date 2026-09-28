<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Llanta.php';

requerirAutenticacionApi();

$accion = $_REQUEST['accion'] ?? 'metricas';

switch ($accion) {
    case 'metricas':
        $metricas = Dashboard::obtenerMetricas($conn);
        $proximosMantenimientos = Mantenimiento::obtenerProximos($conn, 5);
        $llantasAlerta = Llanta::obtenerProximasReemplazo($conn, 5000);
        $costosMensuales = Dashboard::obtenerCostosMensuales($conn);
        $flotaSucursal = Dashboard::obtenerFlotaPorSucursal($conn);

        respuestaJson(true, [
            'metricas' => $metricas,
            'proximos_mantenimientos' => $proximosMantenimientos,
            'llantas_alerta' => $llantasAlerta,
            'costos_mensuales' => $costosMensuales,
            'flota_sucursal' => $flotaSucursal,
            'puede_editar' => puedeEditar(),
            'usuario' => usuarioActual()
        ]);
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
