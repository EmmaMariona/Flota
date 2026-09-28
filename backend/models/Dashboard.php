<?php

class Dashboard
{
    /**
     * Obtiene métricas clave (KPIs) para las tarjetas principales del dashboard.
     */
    public static function obtenerMetricas(mysqli $conn): array
    {
        // Vehículos
        $qVehiculos = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'Activo' THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN estado = 'Taller' THEN 1 ELSE 0 END) AS taller,
                SUM(CASE WHEN estado = 'Inactivo' THEN 1 ELSE 0 END) AS inactivos
            FROM vehiculos
        ";
        $resV = $conn->query($qVehiculos)->fetch_assoc();

        // Mantenimientos
        $qMantenimientos = "
            SELECT 
                SUM(CASE WHEN estado IN ('Programado', 'En proceso') THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'Completado' THEN 1 ELSE 0 END) AS completados,
                SUM(CASE WHEN estado = 'Completado' AND MONTH(fecha_realizada) = MONTH(CURRENT_DATE()) AND YEAR(fecha_realizada) = YEAR(CURRENT_DATE()) THEN costo ELSE 0 END) AS costo_mes,
                SUM(CASE WHEN estado = 'Completado' THEN costo ELSE 0 END) AS costo_total
            FROM mantenimientos
        ";
        $resM = $conn->query($qMantenimientos)->fetch_assoc();

        // Llantas
        $qLlantas = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'Activa' THEN 1 ELSE 0 END) AS activas
            FROM llantas
        ";
        $resL = $conn->query($qLlantas)->fetch_assoc();

        return [
            'vehiculos_total' => (int)($resV['total'] ?? 0),
            'vehiculos_activos' => (int)($resV['activos'] ?? 0),
            'vehiculos_taller' => (int)($resV['taller'] ?? 0),
            'vehiculos_inactivos' => (int)($resV['inactivos'] ?? 0),
            'mantenimientos_pendientes' => (int)($resM['pendientes'] ?? 0),
            'mantenimientos_completados' => (int)($resM['completados'] ?? 0),
            'costo_mes' => (float)($resM['costo_mes'] ?? 0),
            'costo_total' => (float)($resM['costo_total'] ?? 0),
            'llantas_activas' => (int)($resL['activas'] ?? 0)
        ];
    }

    /**
     * Datos para el gráfico de costos mensuales (últimos 6 meses).
     */
    public static function obtenerCostosMensuales(mysqli $conn): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(fecha_realizada, '%Y-%m') AS mes_anio,
                DATE_FORMAT(fecha_realizada, '%b %Y') AS etiqueta,
                SUM(costo) AS total
            FROM mantenimientos
            WHERE estado = 'Completado' AND fecha_realizada >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes_anio, etiqueta
            ORDER BY mes_anio ASC
        ";
        $res = $conn->query($sql);
        $labels = [];
        $data = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['etiqueta'];
                $data[] = (float)$row['total'];
            }
        } else {
            // Valores de ejemplo para visualización en presentación si no hay historial amplio
            $labels = ['May', 'Jun', 'Jul', 'Ago', 'Sep'];
            $data = [3200, 4800, 2900, 5600, 4350.50];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Datos para el gráfico de distribución de vehículos por sucursal.
     */
    public static function obtenerFlotaPorSucursal(mysqli $conn): array
    {
        $sql = "
            SELECT 
                IFNULL(s.nombre, 'Sin Sucursal') AS sucursal,
                COUNT(v.id) AS total
            FROM vehiculos v
            LEFT JOIN sucursales s ON v.sucursal_id = s.id
            GROUP BY sucursal
            ORDER BY total DESC
            LIMIT 7
        ";
        $res = $conn->query($sql);
        $labels = [];
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $labels[] = $row['sucursal'];
                $data[] = (int)$row['total'];
            }
        }
        return ['labels' => $labels, 'data' => $data];
    }
}
