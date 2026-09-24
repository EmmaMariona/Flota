<?php

class DocumentoVehiculo
{
    /**
     * Obtiene todos los documentos asociados a un vehículo.
     */
    public static function obtenerPorVehiculo(mysqli $conn, int $vehiculo_id): array
    {
        $stmt = $conn->prepare("
            SELECT * FROM vehiculo_documentos 
            WHERE vehiculo_id = ? 
            ORDER BY fecha_subida DESC
        ");
        $stmt->bind_param("i", $vehiculo_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $docs = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $docs[] = $row;
            }
        }
        return $docs;
    }

    /**
     * Obtiene un documento por su ID.
     */
    public static function obtenerPorId(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM vehiculo_documentos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Registra un nuevo documento en la base de datos.
     */
    public static function guardar(mysqli $conn, array $datos): bool
    {
        $stmt = $conn->prepare("
            INSERT INTO vehiculo_documentos (
                vehiculo_id, tipo_documento, nombre_original, 
                nombre_archivo, ruta_archivo, fecha_vencimiento, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $fecha_venc = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null;

        $stmt->bind_param(
            "issssss",
            $datos['vehiculo_id'],
            $datos['tipo_documento'],
            $datos['nombre_original'],
            $datos['nombre_archivo'],
            $datos['ruta_archivo'],
            $fecha_venc,
            $datos['observaciones']
        );

        return $stmt->execute();
    }

    /**
     * Elimina el registro del documento y su archivo físico.
     */
    public static function eliminar(mysqli $conn, int $id): bool
    {
        $doc = self::obtenerPorId($conn, $id);
        if (!$doc) {
            return false;
        }

        // Eliminar archivo físico si existe
        $rutaFisica = __DIR__ . '/../' . ltrim($doc['ruta_archivo'], '/\\');
        if (file_exists($rutaFisica)) {
            @unlink($rutaFisica);
        }

        $stmt = $conn->prepare("DELETE FROM vehiculo_documentos WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Procesa la subida física de un archivo y su registro en base de datos.
     */
    public static function procesarSubida(
        mysqli $conn,
        int $vehiculo_id,
        string $tipo_documento,
        array $file,
        ?string $fecha_vencimiento = null,
        ?string $observaciones = null
    ): array {
        if ($vehiculo_id <= 0 || empty($tipo_documento)) {
            return ['success' => false, 'mensaje' => 'Vehículo y tipo de documento son requeridos.'];
        }

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'mensaje' => 'No se recibió un archivo válido para subir.'];
        }

        $nombreOriginal = basename($file['name']);
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        if (!in_array($ext, $permitidas)) {
            return ['success' => false, 'mensaje' => 'Formato no permitido. Formatos aceptados: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX.'];
        }

        // Crear nombre seguro
        $tipoSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $tipo_documento));
        $nuevoNombre = "doc_{$vehiculo_id}_{$tipoSlug}_" . time() . "_" . bin2hex(random_bytes(3)) . ".{$ext}";
        $directorioDestino = dirname(__DIR__) . '/uploads/documentos/';

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }

        $rutaDestinoFisica = $directorioDestino . $nuevoNombre;
        $rutaRelativa = "uploads/documentos/" . $nuevoNombre;

        if (!move_uploaded_file($file['tmp_name'], $rutaDestinoFisica)) {
            return ['success' => false, 'mensaje' => 'No se pudo guardar el archivo en el servidor.'];
        }

        $datos = [
            'vehiculo_id' => $vehiculo_id,
            'tipo_documento' => $tipo_documento,
            'nombre_original' => $nombreOriginal,
            'nombre_archivo' => $nuevoNombre,
            'ruta_archivo' => $rutaRelativa,
            'fecha_vencimiento' => !empty($fecha_vencimiento) ? $fecha_vencimiento : null,
            'observaciones' => $observaciones ?: ''
        ];

        if (self::guardar($conn, $datos)) {
            return ['success' => true, 'mensaje' => "Documento '{$tipo_documento}' cargado exitosamente.", 'ruta' => $rutaRelativa];
        } else {
            @unlink($rutaDestinoFisica);
            return ['success' => false, 'mensaje' => 'Error al registrar en la base de datos: ' . $conn->error];
        }
    }

    /**
     * Calcula el estado de vigencia de una fecha de vencimiento.
     */
    public static function obtenerEstadoVencimiento(?string $fechaVenc): array
    {
        if (empty($fechaVenc)) {
            return [
                'clase' => 'bg-secondary-subtle text-secondary',
                'texto' => 'Sin vencimiento',
                'icono' => 'bi-dash-circle',
                'badge' => 'secondary'
            ];
        }

        $hoy = new DateTime();
        $venc = new DateTime($fechaVenc);
        $diff = (int)$hoy->diff($venc)->format('%r%a');

        if ($diff < 0) {
            return [
                'clase' => 'bg-danger-subtle text-danger border border-danger border-opacity-25',
                'texto' => 'Vencido (' . abs($diff) . ' d)',
                'icono' => 'bi-exclamation-triangle-fill',
                'badge' => 'danger'
            ];
        } elseif ($diff <= 30) {
            return [
                'clase' => 'bg-warning-subtle text-warning border border-warning border-opacity-25',
                'texto' => 'Por vencer (' . $diff . ' d)',
                'icono' => 'bi-clock-history',
                'badge' => 'warning'
            ];
        } else {
            return [
                'clase' => 'bg-success-subtle text-success border border-success border-opacity-25',
                'texto' => 'Vigente',
                'icono' => 'bi-check-circle-fill',
                'badge' => 'success'
            ];
        }
    }
}

