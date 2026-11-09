<?php

class Llanta
{
    /**
     * Obtiene el listado de llantas con filtros opcionales.
     */
    public static function obtenerTodas(mysqli $conn, array $filtros = []): array
    {
        $sql = "
            SELECT 
                ll.*,
                v.placa,
                v.marca AS marca_vehiculo,
                v.modelo AS modelo_vehiculo,
                v.kilometraje_actual,
                IFNULL(p.nombre, 'Sin posición') AS posicion,
                IFNULL(s.nombre, 'Sin sucursal') AS sucursal,
                GREATEST(0, IFNULL(v.kilometraje_actual, 0) - IFNULL(ll.km_instalacion, 0)) AS km_recorridos
            FROM llantas ll
            INNER JOIN vehiculos v ON ll.vehiculo_id = v.id
            LEFT JOIN posiciones_llantas p ON ll.posicion_id = p.id
            LEFT JOIN sucursales s ON v.sucursal_id = s.id
            WHERE 1=1
        ";

        $params = [];
        $types = "";

        if (!empty($filtros['vehiculo_id'])) {
            $sql .= " AND ll.vehiculo_id = ?";
            $params[] = (int)$filtros['vehiculo_id'];
            $types .= "i";
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND ll.estado = ?";
            $params[] = $filtros['estado'];
            $types .= "s";
        }

        if (!empty($filtros['buscar'])) {
            $sql .= " AND (ll.codigo LIKE ? OR ll.marca LIKE ? OR v.placa LIKE ?)";
            $term = "%" . $filtros['buscar'] . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "sss";
        }

        $sql .= " ORDER BY v.placa ASC, IFNULL(p.id, 99) ASC, ll.id ASC";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query($sql);
        }

        $llantas = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $llantas[] = $row;
            }
        }
        return $llantas;
    }

    /**
     * Obtiene las llantas asociadas a un vehículo.
     */
    public static function obtenerPorVehiculo(mysqli $conn, int $vehiculo_id): array
    {
        return self::obtenerTodas($conn, ['vehiculo_id' => $vehiculo_id]);
    }

    /**
     * Obtiene el catálogo de posiciones de llantas.
     */
    public static function obtenerPosiciones(mysqli $conn): array
    {
        $sql = "SELECT id, nombre FROM posiciones_llantas ORDER BY id ASC";
        $res = $conn->query($sql);
        $posiciones = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $posiciones[] = $row;
            }
        }
        return $posiciones;
    }

    /**
     * Obtiene una llanta por ID.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $sql = "
            SELECT 
                ll.*,
                v.placa,
                IFNULL(p.nombre, 'Sin posición') AS posicion
            FROM llantas ll
            INNER JOIN vehiculos v ON ll.vehiculo_id = v.id
            LEFT JOIN posiciones_llantas p ON ll.posicion_id = p.id
            WHERE ll.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Registra una nueva llanta y opcionalmente marca la anterior en esa posición como Reemplazada.
     */
    public static function crear(mysqli $conn, array $datos): bool
    {
        // Si ya hay una llanta activa en esa posición para ese vehículo, marcarla como Reemplazada
        $vehiculo_id = (int)$datos['vehiculo_id'];
        $posicion_id = (int)$datos['posicion_id'];

        $stmtPrev = $conn->prepare("UPDATE llantas SET estado = 'Reemplazada' WHERE vehiculo_id = ? AND posicion_id = ? AND estado = 'Activa'");
        $stmtPrev->bind_param("ii", $vehiculo_id, $posicion_id);
        $stmtPrev->execute();

        $sql = "
            INSERT INTO llantas (
                vehiculo_id, posicion_id, codigo, medida, marca,
                tipo_llanta, fecha_instalacion, km_instalacion, costo,
                codigo_anterior, estado, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);

        $km = !empty($datos['km_instalacion']) ? (int)$datos['km_instalacion'] : 0;
        $costo = !empty($datos['costo']) ? (float)$datos['costo'] : 0.00;
        $codigo_ant = !empty($datos['codigo_anterior']) ? $datos['codigo_anterior'] : null;
        $estado = !empty($datos['estado']) ? $datos['estado'] : 'Activa';

        $stmt->bind_param(
            "iisssssidsss",
            $vehiculo_id,
            $posicion_id,
            $datos['codigo'],
            $datos['medida'],
            $datos['marca'],
            $datos['tipo_llanta'],
            $datos['fecha_instalacion'],
            $km,
            $costo,
            $codigo_ant,
            $estado,
            $datos['observaciones']
        );

        return $stmt->execute();
    }

    /**
     * Elimina una llanta.
     */
    public static function eliminar(mysqli $conn, int $id): bool
    {
        $stmt = $conn->prepare("DELETE FROM llantas WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Obtiene llantas que superan un kilometraje de referencia (ej. 45,000 km) para alerta de reemplazo.
     */
    public static function obtenerProximasReemplazo(mysqli $conn, int $km_limite = 45000): array
    {
        $sql = "
            SELECT 
                ll.*,
                v.placa,
                v.marca AS marca_vehiculo,
                IFNULL(p.nombre, 'Sin posición') AS posicion,
                (v.kilometraje_actual - ll.km_instalacion) AS km_recorridos
            FROM llantas ll
            INNER JOIN vehiculos v ON ll.vehiculo_id = v.id
            LEFT JOIN posiciones_llantas p ON ll.posicion_id = p.id
            WHERE ll.estado = 'Activa' AND (v.kilometraje_actual - ll.km_instalacion) >= ?
            ORDER BY km_recorridos DESC
            LIMIT 5
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $km_limite);
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
