<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Marca.php';
require_once __DIR__ . '/../models/Modelo.php';

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
        $soloActivos = !empty($_GET['solo_activos']);
        $marcaId = !empty($_GET['marca_id']) ? (int)$_GET['marca_id'] : null;

        $marcas = Marca::obtenerTodas($conn, $soloActivos);
        $modelos = Modelo::obtenerTodos($conn, $marcaId, $soloActivos);

        respuestaJson(true, [
            'marcas' => $marcas,
            'modelos' => $modelos,
            'puede_editar' => puedeEditar(),
            'es_admin' => tieneRol(['Administrador'])
        ]);
        break;

    case 'listar_marcas':
        $soloActivas = !empty($_GET['solo_activas']);
        $marcas = Marca::obtenerTodas($conn, $soloActivas);
        respuestaJson(true, ['marcas' => $marcas]);
        break;

    case 'listar_modelos':
        $marcaId = !empty($_GET['marca_id']) ? (int)$_GET['marca_id'] : null;
        $soloActivos = !empty($_GET['solo_activos']);
        $modelos = Modelo::obtenerTodos($conn, $marcaId, $soloActivos);
        respuestaJson(true, ['modelos' => $modelos]);
        break;

    case 'guardar_marca':
        requerirRolApi(['Administrador', 'Supervisor']);

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            respuestaJson(false, null, 'El nombre de la marca es obligatorio.', 400);
        }

        $existente = Marca::obtenerPorNombre($conn, $nombre);
        if ($existente) {
            respuestaJson(false, null, "La marca '{$nombre}' ya se encuentra registrada en el catálogo.", 409);
        }

        $activo = isset($_POST['activo']) ? ((int)$_POST['activo'] ? 1 : 0) : 1;

        try {
            if (Marca::crear($conn, $nombre, $activo)) {
                respuestaJson(true, ['id' => (int)$conn->insert_id], "Marca '{$nombre}' creada exitosamente.");
            } else {
                respuestaJson(false, null, 'Error al guardar la marca: ' . $conn->error, 500);
            }
        } catch (Exception $e) {
            respuestaJson(false, null, "Error al registrar la marca '{$nombre}'.", 500);
        }
        break;

    case 'actualizar_marca':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || empty($nombre)) {
            respuestaJson(false, null, 'ID y nombre de la marca son obligatorios.', 400);
        }

        $existente = Marca::obtenerPorNombre($conn, $nombre);
        if ($existente && (int)$existente['id'] !== $id) {
            respuestaJson(false, null, "Ya existe otra marca con el nombre '{$nombre}'.", 409);
        }

        try {
            if (Marca::actualizar($conn, $id, $nombre)) {
                if (isset($_POST['activo'])) {
                    $activo = (int)$_POST['activo'] ? 1 : 0;
                    Marca::cambiarEstado($conn, $id, $activo);
                }
                respuestaJson(true, null, "Marca '{$nombre}' actualizada exitosamente.");
            } else {
                respuestaJson(false, null, 'Error al actualizar la marca: ' . $conn->error, 500);
            }
        } catch (Exception $e) {
            respuestaJson(false, null, 'Error al actualizar la marca.', 500);
        }
        break;

    case 'estado_marca':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : (int)($_GET['activo'] ?? 0);
        $activo = $activo ? 1 : 0;

        if ($id <= 0) {
            respuestaJson(false, null, 'ID de marca inválido.', 400);
        }

        if (Marca::cambiarEstado($conn, $id, $activo)) {
            $estadoTxt = $activo ? 'activada' : 'inactivada';
            respuestaJson(true, null, "Marca {$estadoTxt} correctamente.");
        } else {
            respuestaJson(false, null, 'Error al cambiar el estado de la marca.', 500);
        }
        break;

    case 'guardar_modelo':
        requerirRolApi(['Administrador', 'Supervisor']);

        $marcaId = (int)($_POST['marca_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($marcaId <= 0 || empty($nombre)) {
            respuestaJson(false, null, 'Debes seleccionar una marca e ingresar el nombre del modelo.', 400);
        }

        $existente = Modelo::obtenerPorMarcaYNombre($conn, $marcaId, $nombre);
        if ($existente) {
            respuestaJson(false, null, "El modelo '{$nombre}' ya está registrado para esta marca.", 409);
        }

        $activo = isset($_POST['activo']) ? ((int)$_POST['activo'] ? 1 : 0) : 1;

        try {
            if (Modelo::crear($conn, $marcaId, $nombre, $activo)) {
                respuestaJson(true, ['id' => (int)$conn->insert_id], "Modelo '{$nombre}' creado y asociado exitosamente.");
            } else {
                respuestaJson(false, null, 'Error al guardar el modelo: ' . $conn->error, 500);
            }
        } catch (Exception $e) {
            respuestaJson(false, null, 'Error al registrar el modelo.', 500);
        }
        break;

    case 'actualizar_modelo':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? 0);
        $marcaId = (int)($_POST['marca_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || $marcaId <= 0 || empty($nombre)) {
            respuestaJson(false, null, 'ID, marca asociada y nombre del modelo son obligatorios.', 400);
        }

        $existente = Modelo::obtenerPorMarcaYNombre($conn, $marcaId, $nombre);
        if ($existente && (int)$existente['id'] !== $id) {
            respuestaJson(false, null, "Ya existe otro modelo '{$nombre}' asociado a esta marca.", 409);
        }

        try {
            if (Modelo::actualizar($conn, $id, $marcaId, $nombre)) {
                if (isset($_POST['activo'])) {
                    $activo = (int)$_POST['activo'] ? 1 : 0;
                    Modelo::cambiarEstado($conn, $id, $activo);
                }
                respuestaJson(true, null, "Modelo '{$nombre}' actualizado correctamente.");
            } else {
                respuestaJson(false, null, 'Error al actualizar el modelo: ' . $conn->error, 500);
            }
        } catch (Exception $e) {
            respuestaJson(false, null, 'Error al actualizar el modelo.', 500);
        }
        break;

    case 'estado_modelo':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : (int)($_GET['activo'] ?? 0);
        $activo = $activo ? 1 : 0;

        if ($id <= 0) {
            respuestaJson(false, null, 'ID de modelo inválido.', 400);
        }

        if (Modelo::cambiarEstado($conn, $id, $activo)) {
            $estadoTxt = $activo ? 'activado' : 'inactivado';
            respuestaJson(true, null, "Modelo {$estadoTxt} correctamente.");
        } else {
            respuestaJson(false, null, 'Error al cambiar el estado del modelo.', 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
