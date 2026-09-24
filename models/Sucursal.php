<?php

class Sucursal
{
    /**
     * Obtiene todas las sucursales ordenadas por nombre.
     */
    public static function obtenerTodas(mysqli $conn): array
    {
        $sql = "SELECT id, nombre FROM sucursales ORDER BY nombre ASC";
        $res = $conn->query($sql);
        $sucursales = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
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
        $stmt = $conn->prepare("SELECT id, nombre FROM sucursales WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }
}
