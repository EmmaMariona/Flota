<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Mantenimiento.php';

requerirAutenticacion();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'guardar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para registrar mantenimientos.');
            header("Location: " . BASE_URL . "/mantenimientos/lista.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $fecha_programada = $_POST['fecha_programada'] ?? '';

            if ($vehiculo_id <= 0 || empty($titulo) || empty($fecha_programada)) {
                setAlerta('danger', 'Por favor completa los campos obligatorios: Vehículo, Título y Fecha Programada.');
                header("Location: " . BASE_URL . "/mantenimientos/crear.php");
                exit;
            }

            $datos = [
                'vehiculo_id' => $vehiculo_id,
                'sucursal_id' => !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null,
                'tipo' => $_POST['tipo'] ?? 'Preventivo',
                'titulo' => $titulo,
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'costo' => $_POST['costo'] ?? 0,
                'fecha_programada' => $fecha_programada,
                'fecha_realizada' => !empty($_POST['fecha_realizada']) ? $_POST['fecha_realizada'] : null,
                'kilometraje_servicio' => !empty($_POST['kilometraje_servicio']) ? $_POST['kilometraje_servicio'] : null,
                'proveedor' => trim($_POST['proveedor'] ?? ''),
                'estado' => $_POST['estado'] ?? 'Programado',
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            $nuevoId = Mantenimiento::crear($conn, $datos);
            if ($nuevoId) {
                // Si el mantenimiento está en proceso o taller, actualizar estado del vehículo si corresponde
                if ($datos['estado'] === 'En proceso') {
                    $conn->query("UPDATE vehiculos SET estado = 'Taller' WHERE id = {$vehiculo_id}");
                }
                setAlerta('success', "Mantenimiento '{$titulo}' registrado exitosamente. Tu orden de trabajo está lista para imprimir.");
                header("Location: " . BASE_URL . "/mantenimientos/imprimir.php?id=" . $nuevoId);
                exit;
            } else {
                setAlerta('danger', "Error al guardar el mantenimiento: " . $conn->error);
                header("Location: " . BASE_URL . "/mantenimientos/crear.php");
                exit;
            }
        }
        break;

    case 'actualizar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para modificar mantenimientos.');
            header("Location: " . BASE_URL . "/mantenimientos/lista.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');

            if ($id <= 0 || $vehiculo_id <= 0 || empty($titulo)) {
                setAlerta('danger', 'Datos incompletos para actualizar el mantenimiento.');
                header("Location: " . BASE_URL . "/mantenimientos/lista.php");
                exit;
            }

            $datos = [
                'vehiculo_id' => $vehiculo_id,
                'sucursal_id' => !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null,
                'tipo' => $_POST['tipo'] ?? 'Preventivo',
                'titulo' => $titulo,
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'costo' => $_POST['costo'] ?? 0,
                'fecha_programada' => $_POST['fecha_programada'] ?? date('Y-m-d'),
                'fecha_realizada' => !empty($_POST['fecha_realizada']) ? $_POST['fecha_realizada'] : null,
                'kilometraje_servicio' => !empty($_POST['kilometraje_servicio']) ? $_POST['kilometraje_servicio'] : null,
                'proveedor' => trim($_POST['proveedor'] ?? ''),
                'estado' => $_POST['estado'] ?? 'Programado',
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            if (Mantenimiento::actualizar($conn, $id, $datos)) {
                // Actualizar estado del vehículo si se completó el servicio
                if ($datos['estado'] === 'Completado') {
                    $conn->query("UPDATE vehiculos SET estado = 'Activo' WHERE id = {$vehiculo_id} AND estado = 'Taller'");
                }
                setAlerta('success', "Mantenimiento actualizado correctamente.");
            } else {
                setAlerta('danger', "Error al actualizar mantenimiento: " . $conn->error);
            }
            header("Location: " . BASE_URL . "/mantenimientos/lista.php");
            exit;
        }
        break;

    case 'completar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para modificar mantenimientos.');
            header("Location: " . BASE_URL . "/mantenimientos/lista.php");
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $mant = Mantenimiento::obtenerPorId($conn, $id);
            if ($mant) {
                $fechaHoy = date('Y-m-d');
                $conn->query("UPDATE mantenimientos SET estado = 'Completado', fecha_realizada = IFNULL(fecha_realizada, '{$fechaHoy}') WHERE id = {$id}");
                $conn->query("UPDATE vehiculos SET estado = 'Activo' WHERE id = {$mant['vehiculo_id']} AND estado = 'Taller'");
                setAlerta('success', 'Mantenimiento marcado como Completado.');
            }
        }
        header("Location: " . BASE_URL . "/mantenimientos/lista.php");
        exit;

    case 'eliminar':
        requerirRol(['Administrador']);
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            if (Mantenimiento::eliminar($conn, $id)) {
                setAlerta('success', 'Mantenimiento eliminado correctamente.');
            } else {
                setAlerta('danger', 'Error al eliminar mantenimiento: ' . $conn->error);
            }
        }
        header("Location: " . BASE_URL . "/mantenimientos/lista.php");
        exit;

    default:
        header("Location: " . BASE_URL . "/mantenimientos/lista.php");
        exit;
}
