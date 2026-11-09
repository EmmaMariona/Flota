<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Vehiculo.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/Llanta.php';
require_once __DIR__ . '/../models/DocumentoVehiculo.php';
require_once __DIR__ . '/../models/Marca.php';
require_once __DIR__ . '/../models/Modelo.php';

requerirAutenticacionApi();

$accion = $_REQUEST['accion'] ?? 'listar';

// Decodificar JSON si se envió en el cuerpo
$rawInput = file_get_contents('php://input');
if (!empty($rawInput) && empty($_POST)) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $_POST = $jsonData;
    }
}

/**
 * Resuelve marca_id, marca, modelo_id y modelo a partir de los datos enviados.
 */
function resolverMarcaYModelo(mysqli $conn, array $input): array
{
    $marcaId = !empty($input['marca_id']) ? (int)$input['marca_id'] : null;
    $modeloId = !empty($input['modelo_id']) ? (int)$input['modelo_id'] : null;
    $marcaTexto = trim($input['marca'] ?? '');
    $modeloTexto = trim($input['modelo'] ?? '');

    if ($marcaId) {
        $mRow = Marca::obtenerPorId($conn, $marcaId);
        if ($mRow) {
            $marcaTexto = $mRow['nombre'];
        }
    } elseif (!empty($marcaTexto)) {
        $mRow = Marca::obtenerPorNombre($conn, $marcaTexto);
        if ($mRow) {
            $marcaId = (int)$mRow['id'];
            $marcaTexto = $mRow['nombre'];
        }
    }

    if ($modeloId) {
        $moRow = Modelo::obtenerPorId($conn, $modeloId);
        if ($moRow) {
            $modeloTexto = $moRow['nombre'];
            if (!$marcaId) {
                $marcaId = (int)$moRow['marca_id'];
                $marcaTexto = $moRow['marca_nombre'] ?? $marcaTexto;
            }
        }
    } elseif ($marcaId && !empty($modeloTexto)) {
        $moRow = Modelo::obtenerPorMarcaYNombre($conn, $marcaId, $modeloTexto);
        if ($moRow) {
            $modeloId = (int)$moRow['id'];
            $modeloTexto = $moRow['nombre'];
        }
    }

    return [
        'marca_id' => $marcaId,
        'marca' => $marcaTexto,
        'modelo_id' => $modeloId,
        'modelo' => $modeloTexto
    ];
}

switch ($accion) {
    case 'listar':
        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'sucursal_id' => !empty($_GET['sucursal_id']) ? (int)$_GET['sucursal_id'] : null,
            'estado' => trim($_GET['estado'] ?? '')
        ];
        $vehiculos = Vehiculo::obtenerTodos($conn, $filtros);
        respuestaJson(true, [
            'vehiculos' => $vehiculos,
            'puede_editar' => puedeEditar(),
            'es_admin' => tieneRol(['Administrador'])
        ]);
        break;

    case 'obtener':
        $id = (int)($_GET['id'] ?? 0);
        $vehiculo = Vehiculo::obtenerPorId($conn, $id);

        if (!$vehiculo) {
            respuestaJson(false, null, 'Vehículo no encontrado.', 404);
        }

        $mantenimientos = Mantenimiento::obtenerPorVehiculo($conn, $id);
        $llantas = Llanta::obtenerPorVehiculo($conn, $id);
        $documentos = DocumentoVehiculo::obtenerPorVehiculo($conn, $id);

        $gastoTotal = 0;
        foreach ($mantenimientos as $m) {
            if ($m['estado'] === 'Completado') {
                $gastoTotal += (float)$m['costo'];
            }
        }

        // Formatear estados de documentos
        foreach ($documentos as &$doc) {
            $doc['estado_vencimiento'] = DocumentoVehiculo::obtenerEstadoVencimiento($doc['fecha_vencimiento']);
        }
        unset($doc);

        respuestaJson(true, [
            'vehiculo' => $vehiculo,
            'mantenimientos' => $mantenimientos,
            'llantas' => $llantas,
            'documentos' => $documentos,
            'gasto_total' => $gastoTotal,
            'puede_editar' => puedeEditar(),
            'es_admin' => tieneRol(['Administrador'])
        ]);
        break;

    case 'validar_vin':
        $vin = strtoupper(trim($_REQUEST['vin'] ?? ''));
        $anio = !empty($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : null;
        $excluirId = !empty($_REQUEST['excluir_id']) ? (int)$_REQUEST['excluir_id'] : null;
        $resVal = Vehiculo::validarVin($conn, $vin, $anio, $excluirId);
        respuestaJson($resVal['valido'], $resVal, $resVal['mensaje']);
        break;

    case 'guardar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $placa = strtoupper(trim($_POST['placa'] ?? ''));
        if (empty($placa)) {
            respuestaJson(false, null, 'La placa es un campo obligatorio.', 400);
        }

        if (!isset($_POST['kilometraje_actual']) || trim((string)$_POST['kilometraje_actual']) === '') {
            respuestaJson(false, null, 'El kilometraje actual es un campo obligatorio.', 400);
        }

        $kmActual = filter_var($_POST['kilometraje_actual'], FILTER_VALIDATE_INT);
        if ($kmActual === false || $kmActual < 0) {
            respuestaJson(false, null, 'El kilometraje actual debe ser un número entero mayor o igual a 0.', 400);
        }

        $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
        $vinRaw = strtoupper(trim($_POST['vin'] ?? ''));

        $valVin = Vehiculo::validarVin($conn, $vinRaw, $anio, null);
        if (!$valVin['valido']) {
            respuestaJson(false, null, $valVin['mensaje'], 400);
        }

        // Si se adjuntó un archivo opcional al crear el vehículo, validarlo antes de guardar el vehículo
        $tieneArchivoAdjunto = isset($_FILES['documento_archivo']) && $_FILES['documento_archivo']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($tieneArchivoAdjunto) {
            $valAdjunto = ValidadorArchivo::validar($_FILES['documento_archivo']);
            if (!$valAdjunto['valido']) {
                respuestaJson(false, null, $valAdjunto['mensaje'], 400);
            }
        }

        $mm = resolverMarcaYModelo($conn, $_POST);

        $datos = [
            'placa' => $placa,
            'sucursal_id' => !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null,
            'tipo' => trim($_POST['tipo'] ?? 'Camión'),
            'marca_id' => $mm['marca_id'],
            'marca' => $mm['marca'],
            'modelo_id' => $mm['modelo_id'],
            'modelo' => $mm['modelo'],
            'anio' => $anio,
            'motor' => trim($_POST['motor'] ?? ''),
            'vin' => $valVin['vin'],
            'tonelaje' => !empty($_POST['tonelaje']) ? (float)$_POST['tonelaje'] : 0,
            'estado' => $_POST['estado'] ?? 'Activo',
            'kilometraje_actual' => $kmActual,
            'fecha_adquisicion' => !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null,
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];

        try {
            if (Vehiculo::crear($conn, $datos)) {
                $nuevoVehiculoId = (int)$conn->insert_id;
                $docMsg = '';

                // Procesar documento adjunto opcional si existe
                if ($tieneArchivoAdjunto) {
                    $tipoDoc = !empty($_POST['documento_tipo']) ? trim($_POST['documento_tipo']) : 'Otro';
                    $resDoc = DocumentoVehiculo::procesarSubida(
                        $conn,
                        $nuevoVehiculoId,
                        $tipoDoc,
                        $_FILES['documento_archivo'],
                        $_POST['documento_vencimiento'] ?? null,
                        $_POST['documento_observaciones'] ?? null
                    );
                    if (!$resDoc['success']) {
                        $docMsg = ' (Aviso doc: ' . $resDoc['mensaje'] . ')';
                    }
                }

                respuestaJson(true, ['id' => $nuevoVehiculoId], "Vehículo con placa {$placa} registrado exitosamente.{$docMsg}");
            } else {
                respuestaJson(false, null, 'Error al guardar el vehículo, placa existente: ' . $conn->error, 500);
            }
        } catch (Exception $e) {
            respuestaJson(false, null, "Error: posiblemente la placa {$placa} ya está registrada.", 409);
        }
        break;

    case 'actualizar':
        requerirRolApi(['Administrador', 'Supervisor']);

        $id = (int)($_POST['id'] ?? 0);
        $placa = strtoupper(trim($_POST['placa'] ?? ''));

        if ($id <= 0 || empty($placa)) {
            respuestaJson(false, null, 'ID y Placa son obligatorios.', 400);
        }

        $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
        $vinRaw = strtoupper(trim($_POST['vin'] ?? ''));

        $valVin = Vehiculo::validarVin($conn, $vinRaw, $anio, $id);
        if (!$valVin['valido']) {
            respuestaJson(false, null, $valVin['mensaje'], 400);
        }

        if (!isset($_POST['kilometraje_actual']) || trim((string)$_POST['kilometraje_actual']) === '') {
            respuestaJson(false, null, 'El kilometraje actual es un campo obligatorio.', 400);
        }

        $kmActual = filter_var($_POST['kilometraje_actual'], FILTER_VALIDATE_INT);
        if ($kmActual === false || $kmActual < 0) {
            respuestaJson(false, null, 'El kilometraje actual debe ser un número entero mayor o igual a 0.', 400);
        }

        $mm = resolverMarcaYModelo($conn, $_POST);

        $datos = [
            'placa' => $placa,
            'sucursal_id' => !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null,
            'tipo' => trim($_POST['tipo'] ?? 'Camión'),
            'marca_id' => $mm['marca_id'],
            'marca' => $mm['marca'],
            'modelo_id' => $mm['modelo_id'],
            'modelo' => $mm['modelo'],
            'anio' => $anio,
            'motor' => trim($_POST['motor'] ?? ''),
            'vin' => $valVin['vin'],
            'tonelaje' => !empty($_POST['tonelaje']) ? (float)$_POST['tonelaje'] : 0,
            'estado' => $_POST['estado'] ?? 'Activo',
            'kilometraje_actual' => $kmActual,
            'fecha_adquisicion' => !empty($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : null,
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];

        if (Vehiculo::actualizar($conn, $id, $datos)) {
            respuestaJson(true, ['id' => $id], "Vehículo {$placa} actualizado exitosamente.");
        } else {
            respuestaJson(false, null, 'Error al actualizar vehículo: ' . $conn->error, 500);
        }
        break;

    case 'eliminar':
        requerirRolApi(['Administrador', 'Supervisor']);
        $id = (int)($_REQUEST['id'] ?? 0);

        if ($id <= 0) {
            respuestaJson(false, null, 'ID inválido.', 400);
        }

        if (Vehiculo::eliminar($conn, $id)) {
            respuestaJson(true, null, 'Vehículo eliminado correctamente.');
        } else {
            respuestaJson(false, null, 'No se pudo eliminar el vehículo: ' . $conn->error, 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
