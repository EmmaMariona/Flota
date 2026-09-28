<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Vehiculo.php';

requerirAutenticacionApi();

$accion = $_REQUEST['accion'] ?? 'listar';

$rawInput = file_get_contents('php://input');
if (!empty($rawInput) && empty($_POST)) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $_POST = $jsonData;
    }
}

switch ($accion) {
    case 'listar':
        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'vehiculo_id' => !empty($_GET['vehiculo_id']) ? (int)$_GET['vehiculo_id'] : null,
            'sucursal_id' => !empty($_GET['sucursal_id']) ? (int)$_GET['sucursal_id'] : null,
            'tipo' => trim($_GET['tipo'] ?? ''),
            'estado' => trim($_GET['estado'] ?? ''),
            'fecha_desde' => trim($_GET['fecha_desde'] ?? ''),
            'fecha_hasta' => trim($_GET['fecha_hasta'] ?? '')
        ];
        $mantenimientos = Mantenimiento::obtenerTodos($conn, $filtros);
        respuestaJson(true, [
            'mantenimientos' => $mantenimientos,
            'puede_editar' => puedeEditar(),
            'es_admin' => tieneRol(['Administrador'])
        ]);
        break;

    case 'obtener':
        $id = (int)($_GET['id'] ?? 0);
        $mantenimiento = Mantenimiento::obtenerPorId($conn, $id);

        if (!$mantenimiento) {
            respuestaJson(false, null, 'Mantenimiento no encontrado.', 404);
        }

        $vehiculo = Vehiculo::obtenerPorId($conn, (int)$mantenimiento['vehiculo_id']);

        respuestaJson(true, [
            'mantenimiento' => $mantenimiento,
            'vehiculo' => $vehiculo,
            'puede_editar' => puedeEditar(),
            'es_admin' => tieneRol(['Administrador'])
        ]);
        break;

    case 'guardar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $fecha_programada = $_POST['fecha_programada'] ?? '';

        if ($vehiculo_id <= 0 || empty($titulo) || empty($fecha_programada)) {
            respuestaJson(false, null, 'Vehículo, Título y Fecha Programada son obligatorios.', 400);
        }

        $tieneArchivo = isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($tieneArchivo) {
            $valAdjunto = ValidadorArchivo::validar($_FILES['archivo_adjunto']);
            if (!$valAdjunto['valido']) {
                respuestaJson(false, null, $valAdjunto['mensaje'], 400);
            }
        }

        $datos = [
            'vehiculo_id' => $vehiculo_id,
            'sucursal_id' => !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null,
            'tipo' => $_POST['tipo'] ?? 'Preventivo',
            'titulo' => $titulo,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'costo' => !empty($_POST['costo']) ? (float)$_POST['costo'] : 0,
            'fecha_programada' => $fecha_programada,
            'fecha_realizada' => !empty($_POST['fecha_realizada']) ? $_POST['fecha_realizada'] : null,
            'kilometraje_servicio' => !empty($_POST['kilometraje_servicio']) ? (int)$_POST['kilometraje_servicio'] : null,
            'proveedor' => trim($_POST['proveedor'] ?? ''),
            'estado' => $_POST['estado'] ?? 'Programado',
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];

        $nuevoId = Mantenimiento::crear($conn, $datos);
        if ($nuevoId) {
            if ($datos['estado'] === 'En proceso') {
                $conn->query("UPDATE vehiculos SET estado = 'Taller' WHERE id = {$vehiculo_id}");
            }
            if ($tieneArchivo) {
                Mantenimiento::guardarArchivoAdjunto($conn, (int)$nuevoId, $_FILES['archivo_adjunto']);
            }
            respuestaJson(true, ['id' => $nuevoId], "Mantenimiento '{$titulo}' registrado exitosamente.");
        } else {
            respuestaJson(false, null, 'Error al guardar el mantenimiento: ' . $conn->error, 500);
        }
        break;

    case 'actualizar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? 0);
        $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');

        if ($id <= 0 || $vehiculo_id <= 0 || empty($titulo)) {
            respuestaJson(false, null, 'Datos incompletos para actualizar el mantenimiento.', 400);
        }

        $tieneArchivo = isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($tieneArchivo) {
            $valAdjunto = ValidadorArchivo::validar($_FILES['archivo_adjunto']);
            if (!$valAdjunto['valido']) {
                respuestaJson(false, null, $valAdjunto['mensaje'], 400);
            }
        }

        $datos = [
            'vehiculo_id' => $vehiculo_id,
            'sucursal_id' => !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null,
            'tipo' => $_POST['tipo'] ?? 'Preventivo',
            'titulo' => $titulo,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'costo' => !empty($_POST['costo']) ? (float)$_POST['costo'] : 0,
            'fecha_programada' => $_POST['fecha_programada'] ?? date('Y-m-d'),
            'fecha_realizada' => !empty($_POST['fecha_realizada']) ? $_POST['fecha_realizada'] : null,
            'kilometraje_servicio' => !empty($_POST['kilometraje_servicio']) ? (int)$_POST['kilometraje_servicio'] : null,
            'proveedor' => trim($_POST['proveedor'] ?? ''),
            'estado' => $_POST['estado'] ?? 'Programado',
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];

        if (Mantenimiento::actualizar($conn, $id, $datos)) {
            if ($datos['estado'] === 'Completado') {
                $conn->query("UPDATE vehiculos SET estado = 'Activo' WHERE id = {$vehiculo_id} AND estado = 'Taller'");
            }
            if ($tieneArchivo) {
                Mantenimiento::guardarArchivoAdjunto($conn, $id, $_FILES['archivo_adjunto']);
            }
            respuestaJson(true, ['id' => $id], 'Mantenimiento actualizado exitosamente.');
        } else {
            respuestaJson(false, null, 'Error al actualizar mantenimiento: ' . $conn->error, 500);
        }
        break;

    case 'completar':
        requerirRolApi(['Administrador', 'Supervisor']);
        $id = (int)($_REQUEST['id'] ?? 0);

        if ($id <= 0) {
            respuestaJson(false, null, 'ID inválido.', 400);
        }

        $m = Mantenimiento::obtenerPorId($conn, $id);
        if ($m) {
            $stmt = $conn->prepare("UPDATE mantenimientos SET estado = 'Completado', fecha_realizada = IFNULL(fecha_realizada, CURDATE()) WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $conn->query("UPDATE vehiculos SET estado = 'Activo' WHERE id = {$m['vehiculo_id']} AND estado = 'Taller'");
                respuestaJson(true, null, 'Mantenimiento marcado como Completado.');
            }
        }
        respuestaJson(false, null, 'Error al completar mantenimiento.', 500);
        break;

    case 'eliminar':
        requerirRolApi(['Administrador']);
        $id = (int)($_REQUEST['id'] ?? 0);

        if ($id <= 0) {
            respuestaJson(false, null, 'ID inválido.', 400);
        }

        if (Mantenimiento::eliminar($conn, $id)) {
            respuestaJson(true, null, 'Mantenimiento eliminado correctamente.');
        } else {
            respuestaJson(false, null, 'No se pudo eliminar el mantenimiento: ' . $conn->error, 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
