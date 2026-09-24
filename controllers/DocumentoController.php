<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/DocumentoVehiculo.php';

requerirAutenticacion();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'subir':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para subir documentos.');
            header("Location: " . BASE_URL . "/vehiculos/lista.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $tipo_documento = trim($_POST['tipo_documento'] ?? '');
            $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
            $observaciones = trim($_POST['observaciones'] ?? '');

            if ($vehiculo_id <= 0 || empty($tipo_documento)) {
                setAlerta('danger', 'Vehículo y tipo de documento son obligatorios.');
                header("Location: " . BASE_URL . "/vehiculos/detalle.php?id=" . $vehiculo_id . "&tab=documentos");
                exit;
            }

            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                setAlerta('danger', 'Debes seleccionar un archivo válido para subir.');
                header("Location: " . BASE_URL . "/vehiculos/detalle.php?id=" . $vehiculo_id . "&tab=documentos");
                exit;
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
                setAlerta('success', $res['mensaje']);
            } else {
                setAlerta('danger', $res['mensaje']);
            }

            header("Location: " . BASE_URL . "/vehiculos/detalle.php?id=" . $vehiculo_id . "&tab=documentos");
            exit;
        }
        break;

    case 'eliminar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para eliminar documentos.');
            header("Location: " . BASE_URL . "/vehiculos/lista.php");
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $vehiculo_id = (int)($_GET['vehiculo_id'] ?? 0);

        if ($id > 0) {
            if (DocumentoVehiculo::eliminar($conn, $id)) {
                setAlerta('success', 'Documento eliminado exitosamente.');
            } else {
                setAlerta('danger', 'Error al eliminar documento: ' . $conn->error);
            }
        }

        header("Location: " . BASE_URL . "/vehiculos/detalle.php?id=" . $vehiculo_id . "&tab=documentos");
        exit;

    default:
        header("Location: " . BASE_URL . "/vehiculos/lista.php");
        exit;
}
