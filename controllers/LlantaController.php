<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Llanta.php';

requerirAutenticacion();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'guardar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para registrar llantas.');
            header("Location: " . BASE_URL . "/llantas/lista.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $posicion_id = (int)($_POST['posicion_id'] ?? 0);
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));

            if ($vehiculo_id <= 0 || $posicion_id <= 0 || empty($codigo)) {
                setAlerta('danger', 'Vehículo, Posición y Código de Llanta son obligatorios.');
                header("Location: " . BASE_URL . "/llantas/registrar.php");
                exit;
            }

            $datos = [
                'vehiculo_id' => $vehiculo_id,
                'posicion_id' => $posicion_id,
                'codigo' => $codigo,
                'medida' => trim($_POST['medida'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'tipo_llanta' => trim($_POST['tipo_llanta'] ?? 'Toda posición'),
                'fecha_instalacion' => !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : date('Y-m-d'),
                'km_instalacion' => $_POST['km_instalacion'] ?? 0,
                'costo' => $_POST['costo'] ?? 0,
                'codigo_anterior' => !empty($_POST['codigo_anterior']) ? trim($_POST['codigo_anterior']) : null,
                'estado' => $_POST['estado'] ?? 'Activa',
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            try {
                if (Llanta::crear($conn, $datos)) {
                    setAlerta('success', "Llanta con código {$codigo} registrada exitosamente.");
                    header("Location: " . BASE_URL . "/llantas/lista.php");
                    exit;
                } else {
                    setAlerta('danger', "Error al registrar la llanta: " . $conn->error);
                    header("Location: " . BASE_URL . "/llantas/registrar.php");
                    exit;
                }
            } catch (Exception $e) {
                setAlerta('danger', "Error: Posiblemente el código de llanta {$codigo} ya está registrado.");
                header("Location: " . BASE_URL . "/llantas/registrar.php");
                exit;
            }
        }
        break;

    case 'eliminar':
        requerirRol(['Administrador']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            if (Llanta::eliminar($conn, $id)) {
                setAlerta('success', 'Registro de llanta eliminado.');
            } else {
                setAlerta('danger', 'Error al eliminar llanta: ' . $conn->error);
            }
        }
        header("Location: " . BASE_URL . "/llantas/lista.php");
        exit;

    default:
        header("Location: " . BASE_URL . "/llantas/lista.php");
        exit;
}
