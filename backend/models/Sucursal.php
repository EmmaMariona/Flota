<?php

class Sucursal
{
    /**
     * Obtiene todas las sucursales ordenadas por nombre, incluyendo el conteo de vehículos asignados.
     */
    public static function obtenerTodas(mysqli $conn): array
    {
        $sql = "
            SELECT 
                s.id, 
                s.nombre,
                (SELECT COUNT(*) FROM vehiculos v WHERE v.sucursal_id = s.id) AS total_vehiculos
            FROM sucursales s
            ORDER BY s.nombre ASC
        ";
        $res = $conn->query($sql);
        $sucursales = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['total_vehiculos'] = (int)$row['total_vehiculos'];
                $sucursales[] = $row;
            }
        }
        return $sucursales;
    }

    /**
     * Obtiene una sucursal por su ID.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare("
            SELECT 
                s.id, 
                s.nombre,
                (SELECT COUNT(*) FROM vehiculos v WHERE v.sucursal_id = s.id) AS total_vehiculos
            FROM sucursales s 
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Busca una sucursal por su nombre exacto (insensible a mayúsculas/minúsculas).
     */
    public static function obtenerPorNombre(mysqli $conn, string $nombre): ?array
    {
        $stmt = $conn->prepare("SELECT id, nombre FROM sucursales WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Crea una nueva sucursal.
     */
    public static function crear(mysqli $conn, string $nombre): bool
    {
        $stmt = $conn->prepare("INSERT INTO sucursales (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        return $stmt->execute();
    }

    /**
     * Actualiza el nombre de una sucursal existente.
     */
    public static function actualizar(mysqli $conn, int $id, string $nombre): bool
    {
        $stmt = $conn->prepare("UPDATE sucursales SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
        return $stmt->execute();
    }

    /**
     * Elimina una sucursal desvinculando previamente los vehículos asociados.
     */
    public static function eliminar(mysqli $conn, int $id): bool
    {
        $stmtUnlink = $conn->prepare("UPDATE vehiculos SET sucursal_id = NULL WHERE sucursal_id = ?");
        $stmtUnlink->bind_param("i", $id);
        $stmtUnlink->execute();

        $stmt = $conn->prepare("DELETE FROM sucursales WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
