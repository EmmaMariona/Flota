<?php
require_once __DIR__ . '/backend/config/conexion.php';
require_once __DIR__ . '/backend/config/auth.php';

if (estaAutenticado()) {
    header("Location: " . BASE_URL . "/frontend/index.html");
} else {
    header("Location: " . BASE_URL . "/frontend/login.html");
}
exit;