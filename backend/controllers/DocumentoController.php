<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/DocumentoVehiculo.php';

requerirAutenticacionApi();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'listar':
        $vehiculo_id = (int)($_GET['vehiculo_id'] ?? 0);
        if ($vehiculo_id <= 0) {
            respuestaJson(false, null, 'Vehículo ID requerido.', 400);
        }
        $docs = DocumentoVehiculo::obtenerPorVehiculo($conn, $vehiculo_id);
        foreach ($docs as &$d) {
            $d['estado_vencimiento'] = DocumentoVehiculo::obtenerEstadoVencimiento($d['fecha_vencimiento']);
        }
        unset($d);
        respuestaJson(true, $docs);
        break;

    case 'subir':
        requerirRolApi(['Administrador', 'Supervisor']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $tipo_documento = trim($_POST['tipo_documento'] ?? '');
            $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
            $observaciones = trim($_POST['observaciones'] ?? '');

            if ($vehiculo_id <= 0 || empty($tipo_documento)) {
                respuestaJson(false, null, 'Vehículo y tipo de documento son obligatorios.', 400);
            }

            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
                respuestaJson(false, null, 'Por favor, selecciona un archivo (PDF, JPG, JPEG, PNG o WEBP) para subir.', 400);
            }

            $res = DocumentoVehiculo::procesarSubida(
                $conn,
                $vehiculo_id,
                $tipo_documento,
                $_FILES['archivo'],
                $fecha_vencimiento,
                $observaciones
            );

            if ($res['success']) {
                respuestaJson(true, $res, $res['mensaje']);
            } else {
                respuestaJson(false, null, $res['mensaje'], 400);
            }
        }
        break;

    case 'eliminar':
        requerirRolApi(['Administrador', 'Supervisor']);
        $id = (int)($_REQUEST['id'] ?? 0);

        if ($id <= 0) {
            respuestaJson(false, null, 'ID inválido.', 400);
        }

        if (DocumentoVehiculo::eliminar($conn, $id)) {
            respuestaJson(true, null, 'Documento eliminado exitosamente.');
        } else {
            respuestaJson(false, null, 'Error al eliminar documento: ' . $conn->error, 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
