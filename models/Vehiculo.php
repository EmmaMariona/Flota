<?php

class Vehiculo
{
    /**
     * Obtiene la lista de vehículos con filtros opcionales (búsqueda, sucursal, estado).
     */
    public static function obtenerTodos(mysqli $conn, array $filtros = []): array
    {
        $sql = "
            SELECT 
                v.id,
                v.placa,
                v.tipo,
                v.marca,
                v.modelo,
                v.anio,
                v.motor,
                v.vin,
                v.tonelaje,
                v.estado,
                v.kilometraje_actual,
                v.fecha_adquisicion,
                v.observaciones,
                v.fecha_creacion,
                v.sucursal_id,
                IFNULL(s.nombre, 'Sin sucursal') AS sucursal
            FROM vehiculos v
            LEFT JOIN sucursales s ON v.sucursal_id = s.id
            WHERE 1=1
        ";

        $params = [];
        $types = "";

        if (!empty($filtros['buscar'])) {
            $sql .= " AND (v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR v.vin LIKE ?)";
            $term = "%" . $filtros['buscar'] . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "ssss";
        }

        if (!empty($filtros['sucursal_id'])) {
            $sql .= " AND v.sucursal_id = ?";
            $params[] = (int)$filtros['sucursal_id'];
            $types .= "i";
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND v.estado = ?";
            $params[] = $filtros['estado'];
            $types .= "s";
        }

        $sql .= " ORDER BY v.placa ASC";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query($sql);
        }

        $vehiculos = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $vehiculos[] = $row;
            }
        }
        return $vehiculos;
    }

    /**
     * Obtiene un vehículo por su ID con el nombre de la sucursal.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $sql = "
            SELECT 
                v.*,
                IFNULL(s.nombre, 'Sin sucursal') AS sucursal
            FROM vehiculos v
            LEFT JOIN sucursales s ON v.sucursal_id = s.id
            WHERE v.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Registra un nuevo vehículo en la base de datos.
     */
    public static function crear(mysqli $conn, array $datos): bool
    {
        $sql = "
            INSERT INTO vehiculos (
                placa, sucursal_id, tipo, marca, modelo, anio, motor, vin,
                tonelaje, estado, kilometraje_actual, fecha_adquisicion, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        
        $sucursal_id = !empty($datos['sucursal_id']) ? (int)$datos['sucursal_id'] : null;
        $anio = !empty($datos['anio']) ? (int)$datos['anio'] : null;
        $tonelaje = !empty($datos['tonelaje']) ? (float)$datos['tonelaje'] : 0.00;
        $km = !empty($datos['kilometraje_actual']) ? (int)$datos['kilometraje_actual'] : 0;
        $fecha_adq = !empty($datos['fecha_adquisicion']) ? $datos['fecha_adquisicion'] : null;

        $stmt->bind_param(
            "sisssissdsiss",
            $datos['placa'],
            $sucursal_id,
            $datos['tipo'],
            $datos['marca'],
            $datos['modelo'],
            $anio,
            $datos['motor'],
            $datos['vin'],
            $tonelaje,
            $datos['estado'],
            $km,
            $fecha_adq,
            $datos['observaciones']
        );

        return $stmt->execute();
    }

    /**
     * Actualiza la información de un vehículo existente.
     */
    public static function actualizar(mysqli $conn, int $id, array $datos): bool
    {
        $sql = "
            UPDATE vehiculos SET
                placa = ?,
                sucursal_id = ?,
                tipo = ?,
                marca = ?,
                modelo = ?,
                anio = ?,
                motor = ?,
                vin = ?,
                tonelaje = ?,
                estado = ?,
                kilometraje_actual = ?,
                fecha_adquisicion = ?,
                observaciones = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($sql);

        $sucursal_id = !empty($datos['sucursal_id']) ? (int)$datos['sucursal_id'] : null;
        $anio = !empty($datos['anio']) ? (int)$datos['anio'] : null;
        $tonelaje = !empty($datos['tonelaje']) ? (float)$datos['tonelaje'] : 0.00;
        $km = !empty($datos['kilometraje_actual']) ? (int)$datos['kilometraje_actual'] : 0;
        $fecha_adq = !empty($datos['fecha_adquisicion']) ? $datos['fecha_adquisicion'] : null;

        $stmt->bind_param(
            "sisssissdsissi",
            $datos['placa'],
            $sucursal_id,
            $datos['tipo'],
            $datos['marca'],
            $datos['modelo'],
            $anio,
            $datos['motor'],
            $datos['vin'],
            $tonelaje,
            $datos['estado'],
            $km,
            $fecha_adq,
            $datos['observaciones'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Elimina un vehículo por su ID.
     */
    public static function eliminar(mysqli $conn, int $id): bool
    {
        $stmt = $conn->prepare("DELETE FROM vehiculos WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Actualiza el kilometraje actual de un vehículo (e.g. al registrar un mantenimiento).
     */
    public static function actualizarKilometraje(mysqli $conn, int $id, int $nuevoKm): bool
    {
        $stmt = $conn->prepare("UPDATE vehiculos SET kilometraje_actual = GREATEST(kilometraje_actual, ?) WHERE id = ?");
        $stmt->bind_param("ii", $nuevoKm, $id);
        return $stmt->execute();
    }
}