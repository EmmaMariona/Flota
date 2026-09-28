<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Llanta.php';

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
            'vehiculo_id' => !empty($_GET['vehiculo_id']) ? (int)$_GET['vehiculo_id'] : null,
            'estado' => trim($_GET['estado'] ?? '')
        ];
        $llantas = Llanta::obtenerTodas($conn, $filtros);
        $posiciones = Llanta::obtenerPosiciones($conn);

        respuestaJson(true, [
            'llantas' => $llantas,
            'posiciones' => $posiciones,
            'puede_editar' => puedeEditar(),
            'es_admin' => tieneRol(['Administrador'])
        ]);
        break;

    case 'posiciones':
        $posiciones = Llanta::obtenerPosiciones($conn);
        respuestaJson(true, $posiciones);
        break;

    case 'guardar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
        $posicion_id = (int)($_POST['posicion_id'] ?? 0);
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));

        if ($vehiculo_id <= 0 || $posicion_id <= 0 || empty($codigo)) {
            respuestaJson(false, null, 'Vehículo, Posición y Código de Llanta son obligatorios.', 400);
        }

        $datos = [
            'vehiculo_id' => $vehiculo_id,
            'posicion_id' => $posicion_id,
            'codigo' => $codigo,
            'medida' => trim($_POST['medida'] ?? ''),
            'marca' => trim($_POST['marca'] ?? ''),
            'tipo_llanta' => trim($_POST['tipo_llanta'] ?? 'Toda posición'),
            'fecha_instalacion' => !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : date('Y-m-d'),
            'km_instalacion' => !empty($_POST['km_instalacion']) ? (int)$_POST['km_instalacion'] : 0,
            'costo' => !empty($_POST['costo']) ? (float)$_POST['costo'] : 0,
            'codigo_anterior' => !empty($_POST['codigo_anterior']) ? trim($_POST['codigo_anterior']) : null,
            'estado' => $_POST['estado'] ?? 'Activa',
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];

        try {
            if (Llanta::crear($conn, $datos)) {
                respuestaJson(true, null, "Llanta con código {$codigo} registrada exitosamente.");
            } else {
                respuestaJson(false, null, 'Error al registrar la llanta: ' . $conn->error, 500);
            }
        } catch (Exception $e) {
            respuestaJson(false, null, "Error: posiblemente el código de llanta {$codigo} ya está registrado.", 409);
        }
        break;

    case 'cambiar_estado':
        requerirRolApi(['Administrador', 'Supervisor']);
        $id = (int)($_POST['id'] ?? 0);
        $nuevoEstado = trim($_POST['estado'] ?? '');

        if ($id <= 0 || empty($nuevoEstado)) {
            respuestaJson(false, null, 'ID y nuevo estado son obligatorios.', 400);
        }

        $stmt = $conn->prepare("UPDATE llantas SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevoEstado, $id);
        if ($stmt->execute()) {
            respuestaJson(true, null, "Estado de llanta actualizado a '{$nuevoEstado}'.");
        } else {
            respuestaJson(false, null, 'Error al actualizar estado de la llanta: ' . $conn->error, 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
