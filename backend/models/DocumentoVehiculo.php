<?php
require_once __DIR__ . '/ValidadorArchivo.php';

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
     * Registra un nuevo documento y sus metadatos en la base de datos.
     */
    public static function guardar(mysqli $conn, array $datos): bool
    {
        $stmt = $conn->prepare("
            INSERT INTO vehiculo_documentos (
                vehiculo_id, tipo_documento, nombre_original, 
                nombre_archivo, ruta_archivo, extension, mime_type, tamano_bytes,
                fecha_vencimiento, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $fecha_venc = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null;
        $extension = $datos['extension'] ?? null;
        $mime_type = $datos['mime_type'] ?? null;
        $tamano_bytes = isset($datos['tamano_bytes']) ? (int)$datos['tamano_bytes'] : 0;

        $stmt->bind_param(
            "issssssiss",
            $datos['vehiculo_id'],
            $datos['tipo_documento'],
            $datos['nombre_original'],
            $datos['nombre_archivo'],
            $datos['ruta_archivo'],
            $extension,
            $mime_type,
            $tamano_bytes,
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
        $rutaFisica = dirname(dirname(__DIR__)) . '/' . ltrim($doc['ruta_archivo'], '/\\');
        if (!file_exists($rutaFisica)) {
            $rutaFisica = dirname(__DIR__) . '/' . ltrim($doc['ruta_archivo'], '/\\');
        }
        if (file_exists($rutaFisica)) {
            @unlink($rutaFisica);
        }

        $stmt = $conn->prepare("DELETE FROM vehiculo_documentos WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Procesa la validación segura, renombrado y subida física de un archivo y su registro en base de datos.
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
            return ['success' => false, 'mensaje' => 'El vehículo y el tipo de documento son obligatorios.'];
        }

        $tipoSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $tipo_documento));
        $prefijo = "doc_{$vehiculo_id}_{$tipoSlug}";

        $resArchivo = ValidadorArchivo::guardarArchivoSeguro($file, 'documentos', $prefijo);
        if (!$resArchivo['success']) {
            return [
                'success' => false,
                'mensaje' => $resArchivo['mensaje']
            ];
        }

        $infoArchivo = $resArchivo['archivo'];

        $datos = [
            'vehiculo_id'       => $vehiculo_id,
            'tipo_documento'    => $tipo_documento,
            'nombre_original'   => $infoArchivo['nombre_original'],
            'nombre_archivo'    => $infoArchivo['nombre_archivo'],
            'ruta_archivo'      => $infoArchivo['ruta_archivo'],
            'extension'         => $infoArchivo['extension'],
            'mime_type'         => $infoArchivo['mime_type'],
            'tamano_bytes'      => $infoArchivo['tamano_bytes'],
            'fecha_vencimiento' => !empty($fecha_vencimiento) ? $fecha_vencimiento : null,
            'observaciones'     => $observaciones ?: ''
        ];

        if (self::guardar($conn, $datos)) {
            return [
                'success' => true,
                'mensaje' => "Documento '{$tipo_documento}' validado y cargado exitosamente.",
                'ruta'    => $infoArchivo['ruta_archivo'],
                'archivo' => $infoArchivo
            ];
        } else {
            if (!empty($infoArchivo['ruta_fisica']) && is_file($infoArchivo['ruta_fisica'])) {
                @unlink($infoArchivo['ruta_fisica']);
            }
            return ['success' => false, 'mensaje' => 'Error al registrar el documento en la base de datos: ' . $conn->error];
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

