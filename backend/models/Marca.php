<?php

class Marca
{
    /**
     * Obtiene todas las marcas del catálogo (opcionalmente solo las activas).
     */
    public static function obtenerTodas(mysqli $conn, bool $soloActivas = false): array
    {
        $sql = "
            SELECT 
                m.id,
                m.nombre,
                m.activo,
                m.fecha_creacion,
                (SELECT COUNT(*) FROM modelos mo WHERE mo.marca_id = m.id) AS total_modelos
            FROM marcas m
        ";

        if ($soloActivas) {
            $sql .= " WHERE m.activo = 1";
        }

        $sql .= " ORDER BY m.nombre ASC";

        $res = $conn->query($sql);
        $marcas = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $row['activo'] = (int)$row['activo'];
                $row['total_modelos'] = (int)$row['total_modelos'];
                $marcas[] = $row;
            }
        }
        return $marcas;
    }

    /**
     * Obtiene una marca por su ID.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM marcas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Obtiene una marca por su nombre exacto.
     */
    public static function obtenerPorNombre(mysqli $conn, string $nombre): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM marcas WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Crea una nueva marca en el catálogo.
     */
    public static function crear(mysqli $conn, string $nombre, int $activo = 1): bool
    {
        $stmt = $conn->prepare("INSERT INTO marcas (nombre, activo) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre, $activo);
        return $stmt->execute();
    }

    /**
     * Actualiza el nombre de una marca y sincroniza los vehículos vinculados.
     */
    public static function actualizar(mysqli $conn, int $id, string $nombre): bool
    {
        $stmt = $conn->prepare("UPDATE marcas SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
        $ok = $stmt->execute();

        if ($ok) {
            $stmtSync = $conn->prepare("UPDATE vehiculos SET marca = ? WHERE marca_id = ?");
            $stmtSync->bind_param("si", $nombre, $id);
            $stmtSync->execute();
        }

        return $ok;
    }

    /**
     * Activa o inactiva una marca.
     */
    public static function cambiarEstado(mysqli $conn, int $id, int $activo): bool
    {
        $stmt = $conn->prepare("UPDATE marcas SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $activo, $id);
        return $stmt->execute();
    }
}
