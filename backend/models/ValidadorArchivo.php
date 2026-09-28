<?php

class ValidadorArchivo
{
    /**
     * Tamaños máximos permitidos en bytes:
     * - PDF: 10 MB
     * - Imágenes (JPG, JPEG, PNG, WEBP): 5 MB
     */
    public const MAX_SIZE_PDF = 10 * 1024 * 1024;   // 10 MB (10,485,760 bytes)
    public const MAX_SIZE_IMAGE = 5 * 1024 * 1024;  // 5 MB (5,242,880 bytes)

    /**
     * Mapa estricto de extensiones permitidas y sus MIME types reales válidos.
     */
    public const EXTENSIONES_PERMITIDAS = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp']
    ];

    /**
     * Lista de extensiones explícitamente bloqueadas (ejecutables, scripts y comprimidos).
     */
    public const EXTENSIONES_BLOQUEADAS = [
        'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'cab', 'iso', 'arj', 'lz', 'z',
        'exe', 'msi', 'msp', 'bat', 'cmd', 'com', 'scr', 'pif', 'cpl', 'dll', 'sys', 'drv', 'ocx',
        'js', 'jse', 'mjs', 'cjs', 'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psm1', 'psd1', 'hta', 'reg', 'lnk', 'inf',
        'apk', 'aab', 'ipa', 'jar', 'war', 'class', 'dex',
        'sh', 'bash', 'zsh', 'csh', 'ksh', 'run', 'bin', 'elf', 'appimage', 'deb', 'rpm', 'dmg', 'pkg', 'app',
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar', 'inc',
        'asp', 'aspx', 'ascx', 'ashx', 'asmx', 'cer', 'asa', 'jsp', 'jspx', 'py', 'pyc', 'pyo', 'pl', 'pm', 'cgi', 'rb', 'lua',
        'svg', 'html', 'htm', 'xhtml', 'shtml', 'swf'
    ];

    /**
     * MIME types explícitamente bloqueados (comprimidos y ejecutables).
     */
    public const MIMES_BLOQUEADOS = [
        'application/zip',
        'application/x-zip-compressed',
        'application/vnd.rar',
        'application/x-rar-compressed',
        'application/x-rar',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-bzip2',
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-executable',
        'application/x-dosexec',
        'application/vnd.microsoft.portable-executable',
        'application/x-msi',
        'application/vnd.android.package-archive',
        'application/javascript',
        'text/javascript',
        'application/x-javascript',
        'text/vbscript',
        'application/x-bat',
        'application/x-msdos-batch',
        'application/x-sh',
        'application/x-php',
        'text/x-php'
    ];

    /**
     * Valida un archivo subido mediante $_FILES de acuerdo con las políticas de seguridad.
     *
     * @param array $file Elemento de $_FILES['campo']
     * @return array ['valido' => bool, 'mensaje' => string, 'metadatos' => array|null]
     */
    public static function validar(array $file): array
    {
        // 1. Verificar estructura y errores de subida de PHP
        if (empty($file) || !isset($file['error'])) {
            return [
                'valido' => false,
                'mensaje' => 'No se recibió ningún archivo para validar.'
            ];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valido' => false,
                'mensaje' => self::traducirErrorSubida((int)$file['error'])
            ];
        }

        $tmpName = $file['tmp_name'] ?? '';
        $nombreOriginalRaw = trim((string)($file['name'] ?? ''));

        if ($tmpName === '' || !is_file($tmpName) || !is_readable($tmpName)) {
            return [
                'valido' => false,
                'mensaje' => 'No se pudo procesar el archivo temporal en el servidor. Intenta subirlo nuevamente.'
            ];
        }

        // 2. No permitir archivos vacíos (requisito 8)
        $tamanoReal = (int)@filesize($tmpName);
        $tamanoReportado = isset($file['size']) ? (int)$file['size'] : $tamanoReal;

        if ($tamanoReal <= 0 || $tamanoReportado <= 0) {
            return [
                'valido' => false,
                'mensaje' => 'El archivo seleccionado está vacío (0 bytes). No se permite subir archivos vacíos.'
            ];
        }

        // 3. Limpiar nombre original y extraer extensión
        $nombreOriginal = self::limpiarNombreOriginal($nombreOriginalRaw);
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if ($ext === '') {
            return [
                'valido' => false,
                'mensaje' => 'El archivo no tiene una extensión válida. Solo se permiten archivos PDF, JPG, JPEG, PNG y WEBP.'
            ];
        }

        // 4. Bloquear extensiones ejecutables o comprimidas (incluyendo dobles extensiones como archivo.exe.pdf)
        $partesNombre = explode('.', strtolower($nombreOriginal));
        if (count($partesNombre) > 1) {
            // Revisar todas las partes después del nombre base
            array_shift($partesNombre);
            foreach ($partesNombre as $parteExt) {
                $parteExt = trim($parteExt);
                if (in_array($parteExt, self::EXTENSIONES_BLOQUEADAS, true)) {
                    $extMayus = strtoupper($parteExt);
                    return [
                        'valido' => false,
                        'mensaje' => "Por motivos de seguridad, los archivos comprimidos o ejecutables (.{$extMayus}) están bloqueados. Solo se permiten archivos PDF, JPG, JPEG, PNG y WEBP."
                    ];
                }
            }
        }

        if (in_array($ext, self::EXTENSIONES_BLOQUEADAS, true)) {
            $extMayus = strtoupper($ext);
            return [
                'valido' => false,
                'mensaje' => "Por motivos de seguridad, los archivos comprimidos o ejecutables (.{$extMayus}) están bloqueados. Solo se permiten archivos PDF, JPG, JPEG, PNG y WEBP."
            ];
        }

        // 5. Permitir únicamente PDF, JPG, JPEG, PNG, WEBP (requisito 1)
        if (!array_key_exists($ext, self::EXTENSIONES_PERMITIDAS)) {
            $extMayus = strtoupper($ext);
            return [
                'valido' => false,
                'mensaje' => "El formato de archivo (.{$extMayus}) no está permitido. Únicamente se permiten archivos PDF, JPG, JPEG, PNG y WEBP."
            ];
        }

        // 6. Validar tamaño máximo según tipo de archivo (requisito 4)
        $esPdf = ($ext === 'pdf');
        $limiteBytes = $esPdf ? self::MAX_SIZE_PDF : self::MAX_SIZE_IMAGE;
        $limiteTexto = $esPdf ? '10 MB' : '5 MB';
        $tipoTexto = $esPdf ? 'El documento PDF' : 'La imagen (' . strtoupper($ext) . ')';

        if ($tamanoReal > $limiteBytes) {
            $tamanoMb = number_format($tamanoReal / (1024 * 1024), 2);
            return [
                'valido' => false,
                'mensaje' => "{$tipoTexto} supera el tamaño máximo permitido de {$limiteTexto} (tamaño del archivo: {$tamanoMb} MB)."
            ];
        }

        // 7. Validar MIME Type real del archivo mediante finfo (requisito 3)
        $mimeReal = self::detectarMimeReal($tmpName);

        if ($mimeReal === '' || in_array($mimeReal, self::MIMES_BLOQUEADOS, true)) {
            return [
                'valido' => false,
                'mensaje' => 'Por motivos de seguridad, el contenido del archivo fue bloqueado (se detectó un formato ejecutable o comprimido no permitido).'
            ];
        }

        $mimesValidosParaExt = self::EXTENSIONES_PERMITIDAS[$ext];
        if (!in_array($mimeReal, $mimesValidosParaExt, true)) {
            $extMayus = strtoupper($ext);
            return [
                'valido' => false,
                'mensaje' => "El contenido interno del archivo ({$mimeReal}) no corresponde a un archivo {$extMayus} auténtico. Verifica que el archivo no haya sido renombrado ni esté dañado."
            ];
        }

        // 8. Validar firma binaria (Magic Bytes) e integridad de imagen/PDF
        $validacionBinaria = self::validarFirmaBinaria($tmpName, $ext);
        if (!$validacionBinaria['valido']) {
            return $validacionBinaria;
        }

        // Normalizar MIME estándar
        $mimeNormalizado = ($mimeReal === 'image/pjpeg') ? 'image/jpeg' : $mimeReal;

        return [
            'valido' => true,
            'mensaje' => 'Archivo válido.',
            'metadatos' => [
                'nombre_original' => $nombreOriginal,
                'extension' => $ext,
                'mime_type' => $mimeNormalizado,
                'tamano_bytes' => $tamanoReal,
                'es_pdf' => $esPdf
            ]
        ];
    }

    /**
     * Valida, renombra de forma segura y almacena el archivo en el subdirectorio indicado dentro de backend/uploads/.
     *
     * @param array  $file           Elemento de $_FILES
     * @param string $subdirectorio  Ej: 'documentos' o 'mantenimientos'
     * @param string $prefijo        Ej: 'doc_32' o 'mant_15'
     * @return array ['success' => bool, 'mensaje' => string, 'archivo' => array|null]
     */
    public static function guardarArchivoSeguro(array $file, string $subdirectorio = 'documentos', string $prefijo = 'adjunto'): array
    {
        $val = self::validar($file);
        if (!$val['valido']) {
            return [
                'success' => false,
                'mensaje' => $val['mensaje']
            ];
        }

        $meta = $val['metadatos'];
        $ext = $meta['extension'];

        // Sanitizar prefijo y subdirectorio
        $prefijoLimpio = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefijo));
        $prefijoLimpio = trim(preg_replace('/_+/', '_', $prefijoLimpio), '_');
        if ($prefijoLimpio === '') {
            $prefijoLimpio = 'archivo';
        }

        $subDirLimpio = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $subdirectorio));
        if ($subDirLimpio === '') {
            $subDirLimpio = 'documentos';
        }

        // Renombrado automático criptográficamente seguro (requisito 6)
        $tokenUnico = bin2hex(random_bytes(8));
        $nuevoNombre = "{$prefijoLimpio}_" . date('Ymd_His') . "_{$tokenUnico}.{$ext}";

        $baseUploadsDir = dirname(__DIR__) . '/uploads';
        $directorioDestino = $baseUploadsDir . '/' . $subDirLimpio . '/';

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        self::asegurarProteccionDirectorio($baseUploadsDir);

        $rutaFisica = $directorioDestino . $nuevoNombre;
        $rutaRelativa = "backend/uploads/{$subDirLimpio}/{$nuevoNombre}";

        $movido = false;
        if (is_uploaded_file($file['tmp_name'])) {
            $movido = @move_uploaded_file($file['tmp_name'], $rutaFisica);
        } else {
            // Fallback para pruebas automatizadas CLI
            $movido = @copy($file['tmp_name'], $rutaFisica);
        }

        if (!$movido || !is_file($rutaFisica)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo almacenar el archivo en el servidor. Verifica los permisos del directorio.'
            ];
        }

        @chmod($rutaFisica, 0644);

        return [
            'success' => true,
            'mensaje' => 'Archivo validado y almacenado correctamente.',
            'archivo' => [
                'nombre_original' => $meta['nombre_original'],
                'nombre_archivo'  => $nuevoNombre,
                'ruta_archivo'    => $rutaRelativa,
                'ruta_fisica'     => $rutaFisica,
                'extension'       => $meta['extension'],
                'mime_type'       => $meta['mime_type'],
                'tamano_bytes'    => $meta['tamano_bytes']
            ]
        ];
    }

    /**
     * Detecta el MIME type real leyendo los bytes del archivo con finfo.
     */
    private static function detectarMimeReal(string $filepath): string
    {
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = @finfo_file($finfo, $filepath);
                @finfo_close($finfo);
                if (is_string($detected)) {
                    $mime = strtolower(trim(explode(';', $detected)[0]));
                }
            }
        }

        if ($mime === '' && function_exists('mime_content_type')) {
            $detected = @mime_content_type($filepath);
            if (is_string($detected)) {
                $mime = strtolower(trim(explode(';', $detected)[0]));
            }
        }

        return $mime;
    }

    /**
     * Inspecciona los primeros bytes (magic bytes) del archivo para descartar ejecutables/comprimidos
     * camuflados y confirmar la estructura real de PDF, JPG, PNG o WEBP.
     */
    private static function validarFirmaBinaria(string $filepath, string $ext): array
    {
        $fh = @fopen($filepath, 'rb');
        if (!$fh) {
            return [
                'valido' => false,
                'mensaje' => 'No se pudo leer el archivo para su verificación de seguridad.'
            ];
        }
        $cabecera = (string)@fread($fh, 1024);
        @fclose($fh);

        if ($cabecera === '') {
            return [
                'valido' => false,
                'mensaje' => 'El archivo seleccionado está vacío (0 bytes).'
            ];
        }

        // Bloquear cabeceras de ejecutables Windows (MZ), ZIP/JAR/APK (PK), RAR (Rar!), 7z, ELF y scripts PHP
        if (
            strncmp($cabecera, "MZ", 2) === 0 ||
            strncmp($cabecera, "PK\x03\x04", 4) === 0 ||
            strncmp($cabecera, "Rar!", 4) === 0 ||
            strncmp($cabecera, "7z\xBC\xAF\x27\x1C", 6) === 0 ||
            strncmp($cabecera, "\x7FELF", 4) === 0 ||
            stripos($cabecera, '<?php') !== false
        ) {
            return [
                'valido' => false,
                'mensaje' => 'Por motivos de seguridad, se bloqueó el archivo porque contiene firmas de código ejecutable o archivo comprimido.'
            ];
        }

        if ($ext === 'pdf') {
            // Un PDF válido contiene la cabecera %PDF- en los primeros 1024 bytes
            if (strpos($cabecera, '%PDF-') === false) {
                return [
                    'valido' => false,
                    'mensaje' => 'El archivo PDF no tiene una estructura válida o está dañado.'
                ];
            }
            return ['valido' => true, 'mensaje' => 'OK'];
        }

        // Para imágenes (jpg, jpeg, png, webp), validar firma y dimensiones reales con getimagesize
        $infoImg = @getimagesize($filepath);
        if ($infoImg === false || empty($infoImg[0]) || empty($infoImg[1])) {
            return [
                'valido' => false,
                'mensaje' => 'El archivo de imagen no es válido o se encuentra dañado.'
            ];
        }

        $tipoImg = $infoImg[2] ?? 0;
        if (($ext === 'jpg' || $ext === 'jpeg') && $tipoImg !== IMAGETYPE_JPEG) {
            return [
                'valido' => false,
                'mensaje' => 'El contenido del archivo no corresponde a una imagen JPG/JPEG válida.'
            ];
        }
        if ($ext === 'png' && $tipoImg !== IMAGETYPE_PNG) {
            return [
                'valido' => false,
                'mensaje' => 'El contenido del archivo no corresponde a una imagen PNG válida.'
            ];
        }
        if ($ext === 'webp' && defined('IMAGETYPE_WEBP') && $tipoImg !== IMAGETYPE_WEBP) {
            return [
                'valido' => false,
                'mensaje' => 'El contenido del archivo no corresponde a una imagen WEBP válida.'
            ];
        }

        return ['valido' => true, 'mensaje' => 'OK'];
    }

    /**
     * Limpia el nombre original del archivo para guardarlo como metadato seguro en la base de datos.
     */
    private static function limpiarNombreOriginal(string $nombre): string
    {
        $nombre = str_replace('\\', '/', $nombre);
        $nombre = basename($nombre);
        // Remover caracteres de control y caracteres nulos
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre);
        $nombre = trim($nombre);
        if (mb_strlen($nombre) > 200) {
            $ext = pathinfo($nombre, PATHINFO_EXTENSION);
            $base = mb_substr(pathinfo($nombre, PATHINFO_FILENAME), 0, 180);
            $nombre = $ext !== '' ? "{$base}.{$ext}" : $base;
        }
        return $nombre;
    }

    /**
     * Traduce los códigos de error nativos de $_FILES a mensajes claros en español.
     */
    public static function traducirErrorSubida(int $codigoError): string
    {
        switch ($codigoError) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo supera el tamaño máximo permitido por el servidor (máximo 10 MB para PDF y 5 MB para imágenes).';
            case UPLOAD_ERR_PARTIAL:
                return 'La subida del archivo se interrumpió antes de completarse. Por favor, inténtalo de nuevo.';
            case UPLOAD_ERR_NO_FILE:
                return 'No se seleccionó ningún archivo para subir.';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return 'Error interno del servidor al procesar el archivo temporal.';
            default:
                return 'Ocurrió un error al recibir el archivo adjunto.';
        }
    }

    /**
     * Crea un archivo .htaccess en backend/uploads para impedir la ejecución de scripts y proteger los adjuntos.
     */
    private static function asegurarProteccionDirectorio(string $baseUploadsDir): void
    {
        $htaccessPath = rtrim($baseUploadsDir, '/\\') . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $contenido = <<<HTACCESS
# Deshabilitar ejecución de scripts en carpeta de archivos subidos
Options -ExecCGI -Indexes
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pl .py .jsp .asp .htm .html .shtml .sh .cgi
RemoveType .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pl .py .jsp .asp .htm .html .shtml .sh .cgi
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
<FilesMatch "\.(?i:php|phtml|php3|php4|php5|php7|php8|phar|exe|msi|bat|cmd|js|vbs|apk|dll|scr|zip|rar|7z|sh|pl|py|cgi|htaccess)$">
    Require all denied
</FilesMatch>
HTACCESS;
            @file_put_contents($htaccessPath, $contenido);
        }
    }
}
