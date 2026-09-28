<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Sucursal.php';

requerirAutenticacionApi();

$accion = $_REQUEST['accion'] ?? 'listar';

$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $_POST = array_merge($_POST, $jsonData);
    }
}

switch ($accion) {
    case 'listar':
        $sucursales = Sucursal::obtenerTodas($conn);
        respuestaJson(true, $sucursales);
        break;

    case 'obtener':
        $id = (int)($_GET['id'] ?? 0);
        $sucursal = Sucursal::obtenerPorId($conn, $id);
        if (!$sucursal) {
            respuestaJson(false, null, 'Sucursal no encontrada.', 404);
        }
        respuestaJson(true, $sucursal);
        break;

    case 'guardar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            respuestaJson(false, null, 'El nombre de la sucursal es obligatorio.', 400);
        }

        $existente = Sucursal::obtenerPorNombre($conn, $nombre);
        if ($existente) {
            respuestaJson(false, null, "La sucursal '{$nombre}' ya se encuentra registrada.", 409);
        }

        if (Sucursal::crear($conn, $nombre)) {
            respuestaJson(true, ['id' => (int)$conn->insert_id], "Sucursal '{$nombre}' creada exitosamente.");
        } else {
            respuestaJson(false, null, 'Error al crear la sucursal: ' . $conn->error, 500);
        }
        break;

    case 'actualizar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || empty($nombre)) {
            respuestaJson(false, null, 'ID y nombre de la sucursal son obligatorios.', 400);
        }

        $existente = Sucursal::obtenerPorNombre($conn, $nombre);
        if ($existente && (int)$existente['id'] !== $id) {
            respuestaJson(false, null, "Ya existe otra sucursal con el nombre '{$nombre}'.", 409);
        }

        if (Sucursal::actualizar($conn, $id, $nombre)) {
            respuestaJson(true, null, "Sucursal actualizada a '{$nombre}' exitosamente.");
        } else {
            respuestaJson(false, null, 'Error al actualizar la sucursal: ' . $conn->error, 500);
        }
        break;

    case 'eliminar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            respuestaJson(false, null, 'ID de sucursal inválido.', 400);
        }

        if (Sucursal::eliminar($conn, $id)) {
            respuestaJson(true, null, 'Sucursal eliminada correctamente.');
        } else {
            respuestaJson(false, null, 'Error al eliminar la sucursal: ' . $conn->error, 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
