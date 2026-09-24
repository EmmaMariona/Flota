<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Vehiculo.php';

requerirAutenticacion();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'guardar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para crear vehículos.');
            header("Location: " . BASE_URL . "/vehiculos/lista.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $placa = strtoupper(trim($_POST['placa'] ?? ''));

            if (empty($placa)) {
                setAlerta('danger', 'La placa es un campo obligatorio.');
                header("Location: " . BASE_URL . "/vehiculos/crear.php");
                exit;
            }

            $datos = [
                'placa' => $placa,
                'sucursal_id' => $_POST['sucursal_id'] ?? null,
                'tipo' => trim($_POST['tipo'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'anio' => $_POST['anio'] ?? null,
                'motor' => trim($_POST['motor'] ?? ''),
                'vin' => trim($_POST['vin'] ?? ''),
                'tonelaje' => $_POST['tonelaje'] ?? 0,
                'estado' => $_POST['estado'] ?? 'Activo',
                'kilometraje_actual' => $_POST['kilometraje_actual'] ?? 0,
                'fecha_adquisicion' => !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null,
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            try {
                if (Vehiculo::crear($conn, $datos)) {
                    $nuevoVehiculoId = (int)$conn->insert_id;

                    // Procesar documento adjunto opcional
                    if (!empty($_FILES['documento_archivo']['name']) && !empty($_POST['documento_tipo'])) {
                        require_once __DIR__ . '/../models/DocumentoVehiculo.php';
                        $resDoc = DocumentoVehiculo::procesarSubida(
                            $conn,
                            $nuevoVehiculoId,
                            $_POST['documento_tipo'],
                            $_FILES['documento_archivo'],
                            $_POST['documento_vencimiento'] ?? null,
                            $_POST['documento_observaciones'] ?? null
                        );

                        if (!$resDoc['success']) {
                            setAlerta('warning', "Vehículo guardado exitosamente, pero hubo un detalle con el documento: " . $resDoc['mensaje']);
                            header("Location: " . BASE_URL . "/vehiculos/detalle.php?id=" . $nuevoVehiculoId);
                            exit;
                        }
                    }

                    setAlerta('success', "Vehículo con placa {$placa} registrado exitosamente.");
                    header("Location: " . BASE_URL . "/vehiculos/detalle.php?id=" . $nuevoVehiculoId);
                    exit;
                } else {
                    setAlerta('danger', "Error al guardar el vehículo: " . $conn->error);
                    header("Location: " . BASE_URL . "/vehiculos/crear.php");
                    exit;
                }
            } catch (Exception $e) {
                setAlerta('danger', "Error al guardar: posiblemente la placa {$placa} ya está registrada.");
                header("Location: " . BASE_URL . "/vehiculos/crear.php");
                exit;
            }
        }
        break;

    case 'actualizar':
        if (!puedeEditar()) {
            setAlerta('danger', 'No tienes permisos para editar vehículos.');
            header("Location: " . BASE_URL . "/vehiculos/lista.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $placa = strtoupper(trim($_POST['placa'] ?? ''));

            if ($id <= 0 || empty($placa)) {
                setAlerta('danger', 'Datos incompletos para actualizar el vehículo.');
                header("Location: " . BASE_URL . "/vehiculos/lista.php");
                exit;
            }

            $datos = [
                'placa' => $placa,
                'sucursal_id' => $_POST['sucursal_id'] ?? null,
                'tipo' => trim($_POST['tipo'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'anio' => $_POST['anio'] ?? null,
                'motor' => trim($_POST['motor'] ?? ''),
                'vin' => trim($_POST['vin'] ?? ''),
                'tonelaje' => $_POST['tonelaje'] ?? 0,
                'estado' => $_POST['estado'] ?? 'Activo',
                'kilometraje_actual' => $_POST['kilometraje_actual'] ?? 0,
                'fecha_adquisicion' => !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null,
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            if (Vehiculo::actualizar($conn, $id, $datos)) {
                setAlerta('success', "Vehículo {$placa} actualizado exitosamente.");
            } else {
                setAlerta('danger', "Error al actualizar vehículo: " . $conn->error);
            }
            header("Location: " . BASE_URL . "/vehiculos/lista.php");
            exit;
        }
        break;

    case 'eliminar':
        requerirRol(['Administrador']);
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            if (Vehiculo::eliminar($conn, $id)) {
                setAlerta('success', 'Vehículo eliminado correctamente.');
            } else {
                setAlerta('danger', 'No se pudo eliminar el vehículo: ' . $conn->error);
            }
        }
        header("Location: " . BASE_URL . "/vehiculos/lista.php");
        exit;

    default:
        header("Location: " . BASE_URL . "/vehiculos/lista.php");
        exit;
}