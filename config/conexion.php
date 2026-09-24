<?php
require_once __DIR__ . '/env.php';

// Cargar variables de entorno desde el archivo .env en la raíz del proyecto
cargarEnv(__DIR__ . '/../.env');

$host = env('DB_HOST', 'localhost');
$port = (int)env('DB_PORT', 3306);
$user = env('DB_USER', 'root');
$password = env('DB_PASS', '');
$database = env('DB_NAME', 'flota_vehicular');
$charset = env('DB_CHARSET', 'utf8mb4');

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

$conn->set_charset($charset);

if (!defined('BASE_URL')) {
    define('BASE_URL', env('BASE_URL', '/flota'));
}