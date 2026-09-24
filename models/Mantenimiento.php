<?php

class Mantenimiento
{
    /**
     * Obtiene el listado de mantenimientos con filtros opcionales.
     */
    public static function obtenerTodos(mysqli $conn, array $filtros = []): array
    {
        $sql = "
            SELECT 
                m.*,
                v.placa,
                v.marca,
                v.modelo,
                IFNULL(sm.nombre, IFNULL(sv.nombre, 'Sin sucursal')) AS sucursal
            FROM mantenimientos m
            INNER JOIN vehiculos v ON m.vehiculo_id = v.id
            LEFT JOIN sucursales sm ON m.sucursal_id = sm.id
            LEFT JOIN sucursales sv ON v.sucursal_id = sv.id
            WHERE 1=1
        ";

        $params = [];
        $types = "";

        if (!empty($filtros['buscar'])) {
            $sql .= " AND (v.placa LIKE ? OR m.titulo LIKE ? OR m.proveedor LIKE ? OR m.descripcion LIKE ?)";
            $term = "%" . trim($filtros['buscar']) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "ssss";
        }

        if (!empty($filtros['vehiculo_id'])) {
            $sql .= " AND m.vehiculo_id = ?";
            $params[] = (int)$filtros['vehiculo_id'];
            $types .= "i";
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $params[] = $filtros['tipo'];
            $types .= "s";
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND m.estado = ?";
            $params[] = $filtros['estado'];
            $types .= "s";
        }

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND m.fecha_programada >= ?";
            $params[] = $filtros['fecha_desde'];
            $types .= "s";
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND m.fecha_programada <= ?";
            $params[] = $filtros['fecha_hasta'];
            $types .= "s";
        }

        if (!empty($filtros['sucursal_id'])) {
            $sql .= " AND (m.sucursal_id = ? OR (m.sucursal_id IS NULL AND v.sucursal_id = ?))";
            $params[] = (int)$filtros['sucursal_id'];
            $params[] = (int)$filtros['sucursal_id'];
            $types .= "ii";
        }

        $sql .= " ORDER BY m.fecha_programada DESC, m.id DESC";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query($sql);
        }

        $mantenimientos = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $mantenimientos[] = $row;
            }
        }
        return $mantenimientos;
    }

    /**
     * Obtiene el historial de mantenimientos para un vehículo específico.
     */
    public static function obtenerPorVehiculo(mysqli $conn, int $vehiculo_id): array
    {
        return self::obtenerTodos($conn, ['vehiculo_id' => $vehiculo_id]);
    }

    /**
     * Obtiene un mantenimiento por su ID.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $sql = "
            SELECT 
                m.*,
                v.placa,
                v.marca,
                v.modelo,
                v.kilometraje_actual,
                IFNULL(sm.nombre, IFNULL(sv.nombre, 'Sin sucursal')) AS sucursal
            FROM mantenimientos m
            INNER JOIN vehiculos v ON m.vehiculo_id = v.id
            LEFT JOIN sucursales sm ON m.sucursal_id = sm.id
            LEFT JOIN sucursales sv ON v.sucursal_id = sv.id
            WHERE m.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Registra un nuevo mantenimiento.
     */
    public static function crear(mysqli $conn, array $datos): int|bool
    {
        $sql = "
            INSERT INTO mantenimientos (
                vehiculo_id, sucursal_id, tipo, titulo, descripcion, costo,
                fecha_programada, fecha_realizada, kilometraje_servicio,
                proveedor, estado, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);

        $vehiculo_id = (int)$datos['vehiculo_id'];
        $sucursal_id = !empty($datos['sucursal_id']) ? (int)$datos['sucursal_id'] : null;
        $costo = !empty($datos['costo']) ? (float)$datos['costo'] : 0.00;
        $fecha_realizada = !empty($datos['fecha_realizada']) ? $datos['fecha_realizada'] : null;
        $km_servicio = !empty($datos['kilometraje_servicio']) ? (int)$datos['kilometraje_servicio'] : null;

        $stmt->bind_param(
            "iisssdssisss",
            $vehiculo_id,
            $sucursal_id,
            $datos['tipo'],
            $datos['titulo'],
            $datos['descripcion'],
            $costo,
            $datos['fecha_programada'],
            $fecha_realizada,
            $km_servicio,
            $datos['proveedor'],
            $datos['estado'],
            $datos['observaciones']
        );

        $ok = $stmt->execute();
        $insertId = $conn->insert_id;

        if ($ok && $km_servicio !== null) {
            Vehiculo::actualizarKilometraje($conn, $vehiculo_id, $km_servicio);
        }

        return $ok ? $insertId : false;
    }

    /**
     * Actualiza un mantenimiento existente.
     */
    public static function actualizar(mysqli $conn, int $id, array $datos): bool
    {
        $sql = "
            UPDATE mantenimientos SET
                vehiculo_id = ?,
                sucursal_id = ?,
                tipo = ?,
                titulo = ?,
                descripcion = ?,
                costo = ?,
                fecha_programada = ?,
                fecha_realizada = ?,
                kilometraje_servicio = ?,
                proveedor = ?,
                estado = ?,
                observaciones = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);

        $vehiculo_id = (int)$datos['vehiculo_id'];
        $sucursal_id = !empty($datos['sucursal_id']) ? (int)$datos['sucursal_id'] : null;
        $costo = !empty($datos['costo']) ? (float)$datos['costo'] : 0.00;
        $fecha_realizada = !empty($datos['fecha_realizada']) ? $datos['fecha_realizada'] : null;
        $km_servicio = !empty($datos['kilometraje_servicio']) ? (int)$datos['kilometraje_servicio'] : null;

        $stmt->bind_param(
            "iisssdssisssi",
            $vehiculo_id,
            $sucursal_id,
            $datos['tipo'],
            $datos['titulo'],
            $datos['descripcion'],
            $costo,
            $datos['fecha_programada'],
            $fecha_realizada,
            $km_servicio,
            $datos['proveedor'],
            $datos['estado'],
            $datos['observaciones'],
            $id
        );

        $ok = $stmt->execute();

        if ($ok && $km_servicio !== null) {
            Vehiculo::actualizarKilometraje($conn, $vehiculo_id, $km_servicio);
        }

        return $ok;
    }

    /**
     * Elimina un registro de mantenimiento.
     */
    public static function eliminar(mysqli $conn, int $id): bool
    {
        $stmt = $conn->prepare("DELETE FROM mantenimientos WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Obtiene los mantenimientos más próximos o pendientes/en proceso.
     */
    public static function obtenerProximos(mysqli $conn, int $limite = 5): array
    {
        $sql = "
            SELECT 
                m.*,
                v.placa,
                v.marca,
                v.modelo
            FROM mantenimientos m
            INNER JOIN vehiculos v ON m.vehiculo_id = v.id
            WHERE m.estado IN ('Programado', 'En proceso')
            ORDER BY m.fecha_programada ASC
            LIMIT ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $res = $stmt->get_result();

        $lista = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $lista[] = $row;
            }
        }
        return $lista;
    }
}
