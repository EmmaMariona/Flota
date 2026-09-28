<?php

class Usuario
{
    /**
     * Autentica un usuario con email y contraseña.
     */
    public static function login(mysqli $conn, string $email, string $password): ?array
    {
        $stmt = $conn->prepare("SELECT id, nombre, email, password, rol, estado FROM usuarios WHERE email = ? AND estado = 'Activo' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            if (password_verify($password, $fila['password'])) {
                unset($fila['password']);
                return $fila;
            }
        }
        return null;
    }

    /**
     * Obtiene todos los usuarios.
     */
    public static function obtenerTodos(mysqli $conn): array
    {
        $sql = "SELECT id, nombre, email, rol, estado, fecha_creacion FROM usuarios ORDER BY nombre ASC";
        $res = $conn->query($sql);
        $usuarios = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        return $usuarios;
    }

    /**
     * Obtiene un usuario por su ID.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare("SELECT id, nombre, email, rol, estado, fecha_creacion FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Registra un nuevo usuario.
     */
    public static function crear(mysqli $conn, array $datos): bool
    {
        $hash = password_hash($datos['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $datos['nombre'], $datos['email'], $hash, $datos['rol'], $datos['estado']);
        return $stmt->execute();
    }

    /**
     * Actualiza datos de un usuario (y opcionalmente contraseña).
     */
    public static function actualizar(mysqli $conn, int $id, array $datos): bool
    {
        if (!empty($datos['password'])) {
            $hash = password_hash($datos['password'], PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ?, estado = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $datos['nombre'], $datos['email'], $hash, $datos['rol'], $datos['estado'], $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, estado = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $datos['nombre'], $datos['email'], $datos['rol'], $datos['estado'], $id);
        }
        return $stmt->execute();
    }

    /**
     * Elimina un usuario por su ID (evitando eliminar al usuario 1 si es admin principal).
     */
    public static function eliminar(mysqli $conn, int $id): bool
    {
        if ($id === 1) {
            return false;
        }
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
