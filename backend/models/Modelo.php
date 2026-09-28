<?php

class Modelo
{
    /**
     * Obtiene la lista de modelos, opcionalmente filtrados por marca_id y/o estado activo.
     */
    public static function obtenerTodos(mysqli $conn, ?int $marca_id = null, bool $soloActivos = false): array
    {
        $sql = "
            SELECT 
                mo.id,
                mo.marca_id,
                mo.nombre,
                mo.activo,
                mo.fecha_creacion,
                m.nombre AS marca_nombre,
                m.activo AS marca_activa
            FROM modelos mo
            INNER JOIN marcas m ON mo.marca_id = m.id
            WHERE 1=1
        ";

        $params = [];
        $types = "";

        if ($marca_id !== null && $marca_id > 0) {
            $sql .= " AND mo.marca_id = ?";
            $params[] = $marca_id;
            $types .= "i";
        }

        if ($soloActivos) {
            $sql .= " AND mo.activo = 1";
        }

        $sql .= " ORDER BY m.nombre ASC, mo.nombre ASC";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query($sql);
        }

        $modelos = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $row['marca_id'] = (int)$row['marca_id'];
                $row['activo'] = (int)$row['activo'];
                $row['marca_activa'] = (int)$row['marca_activa'];
                $modelos[] = $row;
            }
        }
        return $modelos;
    }

    /**
     * Obtiene un modelo por su ID junto con el nombre de su marca.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare("
            SELECT mo.*, m.nombre AS marca_nombre
            FROM modelos mo
            INNER JOIN marcas m ON mo.marca_id = m.id
            WHERE mo.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Busca un modelo por marca_id y nombre.
     */
    public static function obtenerPorMarcaYNombre(mysqli $conn, int $marca_id, string $nombre): ?array
    {
        $stmt = $conn->prepare("
            SELECT mo.*, m.nombre AS marca_nombre
            FROM modelos mo
            INNER JOIN marcas m ON mo.marca_id = m.id
            WHERE mo.marca_id = ? AND LOWER(TRIM(mo.nombre)) = LOWER(TRIM(?))
            LIMIT 1
        ");
        $stmt->bind_param("is", $marca_id, $nombre);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Crea un nuevo modelo asociado a una marca.
     */
    public static function crear(mysqli $conn, int $marca_id, string $nombre, int $activo = 1): bool
    {
        $stmt = $conn->prepare("INSERT INTO modelos (marca_id, nombre, activo) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $marca_id, $nombre, $activo);
        return $stmt->execute();
    }

    /**
     * Actualiza un modelo (nombre y marca asociada) y sincroniza los vehículos vinculados.
     */
    public static function actualizar(mysqli $conn, int $id, int $marca_id, string $nombre): bool
    {
        $stmt = $conn->prepare("UPDATE modelos SET marca_id = ?, nombre = ? WHERE id = ?");
        $stmt->bind_param("isi", $marca_id, $nombre, $id);
        $ok = $stmt->execute();

        if ($ok) {
            $stmtMarca = $conn->prepare("SELECT nombre FROM marcas WHERE id = ?");
            $stmtMarca->bind_param("i", $marca_id);
            $stmtMarca->execute();
            $resM = $stmtMarca->get_result();
            $rowM = $resM ? $resM->fetch_assoc() : null;
            $nombreMarca = $rowM ? $rowM['nombre'] : '';

            if (!empty($nombreMarca)) {
                $stmtSync = $conn->prepare("UPDATE vehiculos SET marca_id = ?, marca = ?, modelo = ? WHERE modelo_id = ?");
                $stmtSync->bind_param("issi", $marca_id, $nombreMarca, $nombre, $id);
                $stmtSync->execute();
            } else {
                $stmtSync = $conn->prepare("UPDATE vehiculos SET modelo = ? WHERE modelo_id = ?");
                $stmtSync->bind_param("si", $nombre, $id);
                $stmtSync->execute();
            }
        }

        return $ok;
    }

    /**
     * Activa o inactiva un modelo.
     */
    public static function cambiarEstado(mysqli $conn, int $id, int $activo): bool
    {
        $stmt = $conn->prepare("UPDATE modelos SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $activo, $id);
        return $stmt->execute();
    }
}
