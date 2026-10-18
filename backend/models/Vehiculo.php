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
                v.marca_id,
                v.modelo_id,
                COALESCE(ma.nombre, v.marca) AS marca,
                COALESCE(mo.nombre, v.modelo) AS modelo,
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
            LEFT JOIN marcas ma ON v.marca_id = ma.id
            LEFT JOIN modelos mo ON v.modelo_id = mo.id
            WHERE 1=1
        ";

        $params = [];
        $types = "";

        if (!empty($filtros['buscar'])) {
            $sql .= " AND (v.placa LIKE ? OR COALESCE(ma.nombre, v.marca) LIKE ? OR COALESCE(mo.nombre, v.modelo) LIKE ? OR v.vin LIKE ?)";
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
     * Obtiene un vehículo por su ID con el nombre de la sucursal, marca y modelo.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $sql = "
            SELECT 
                v.*,
                COALESCE(ma.nombre, v.marca) AS marca,
                COALESCE(mo.nombre, v.modelo) AS modelo,
                IFNULL(s.nombre, 'Sin sucursal') AS sucursal
            FROM vehiculos v
            LEFT JOIN sucursales s ON v.sucursal_id = s.id
            LEFT JOIN marcas ma ON v.marca_id = ma.id
            LEFT JOIN modelos mo ON v.modelo_id = mo.id
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
                placa, sucursal_id, tipo, marca_id, marca, modelo_id, modelo, anio, motor, vin,
                tonelaje, estado, kilometraje_actual, fecha_adquisicion, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        
        $sucursal_id = !empty($datos['sucursal_id']) ? (int)$datos['sucursal_id'] : null;
        $marca_id = !empty($datos['marca_id']) ? (int)$datos['marca_id'] : null;
        $modelo_id = !empty($datos['modelo_id']) ? (int)$datos['modelo_id'] : null;
        $anio = !empty($datos['anio']) ? (int)$datos['anio'] : null;
        $tonelaje = !empty($datos['tonelaje']) ? (float)$datos['tonelaje'] : 0.00;
        $km = isset($datos['kilometraje_actual']) && $datos['kilometraje_actual'] !== '' ? (int)$datos['kilometraje_actual'] : 0;
        $fecha_adq = !empty($datos['fecha_adquisicion']) ? $datos['fecha_adquisicion'] : null;

        $stmt->bind_param(
            "sisisisissdsiss",
            $datos['placa'],
            $sucursal_id,
            $datos['tipo'],
            $marca_id,
            $datos['marca'],
            $modelo_id,
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
                marca_id = ?,
                marca = ?,
                modelo_id = ?,
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
        $marca_id = !empty($datos['marca_id']) ? (int)$datos['marca_id'] : null;
        $modelo_id = !empty($datos['modelo_id']) ? (int)$datos['modelo_id'] : null;
        $anio = !empty($datos['anio']) ? (int)$datos['anio'] : null;
        $tonelaje = !empty($datos['tonelaje']) ? (float)$datos['tonelaje'] : 0.00;
        $km = isset($datos['kilometraje_actual']) && $datos['kilometraje_actual'] !== '' ? (int)$datos['kilometraje_actual'] : 0;
        $fecha_adq = !empty($datos['fecha_adquisicion']) ? $datos['fecha_adquisicion'] : null;

        $stmt->bind_param(
            "sisisisissdsissi",
            $datos['placa'],
            $sucursal_id,
            $datos['tipo'],
            $marca_id,
            $datos['marca'],
            $modelo_id,
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

    /**
     * Verifica si un VIN ya está registrado en otro vehículo del sistema.
     */
    public static function existeVin(mysqli $conn, string $vin, ?int $excluirId = null): bool
    {
        $vinLimpio = strtoupper(trim($vin));
        if ($vinLimpio === '' || $vinLimpio === 'S/V') {
            return false;
        }

        if ($excluirId !== null && $excluirId > 0) {
            $stmt = $conn->prepare("SELECT id FROM vehiculos WHERE UPPER(TRIM(vin)) = ? AND id != ? LIMIT 1");
            $stmt->bind_param("si", $vinLimpio, $excluirId);
        } else {
            $stmt = $conn->prepare("SELECT id FROM vehiculos WHERE UPPER(TRIM(vin)) = ? LIMIT 1");
            $stmt->bind_param("s", $vinLimpio);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    /**
     * Valida el VIN según el año del vehículo:
     * - Regla 1 (anio < 1981): 5 a 17 caracteres alfanuméricos en mayúsculas.
     * - Regla 2 (anio >= 1981): 17 caracteres exactos alfanuméricos en mayúsculas, sin I, O ni Q, y único en el sistema.
     */
    public static function validarVin(mysqli $conn, string $vin, ?int $anio, ?int $excluirId = null): array
    {
        $vinNorm = strtoupper(trim($vin));

        if ($anio !== null && $anio > 0 && $anio < 1981) {
            // Regla 1: Vehículos anteriores a 1981
            if (!preg_match('/^[A-Z0-9]{5,17}$/', $vinNorm)) {
                return [
                    'valido' => false,
                    'mensaje' => 'El VIN debe contener entre 5 y 17 caracteres alfanuméricos.'
                ];
            }
        } else {
            // Regla 2: Vehículos modelo 1981 en adelante (ISO 3779)
            if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vinNorm)) {
                return [
                    'valido' => false,
                    'mensaje' => 'El VIN debe contener exactamente 17 caracteres y no puede incluir las letras I, O o Q.'
                ];
            }
        }

        // Verificar unicidad del VIN en el sistema
        if (self::existeVin($conn, $vinNorm, $excluirId)) {
            return [
                'valido' => false,
                'mensaje' => "El VIN '{$vinNorm}' ya se encuentra registrado en otro vehículo del sistema."
            ];
        }

        return [
            'valido' => true,
            'vin' => $vinNorm,
            'mensaje' => 'VIN válido.'
        ];
    }
}