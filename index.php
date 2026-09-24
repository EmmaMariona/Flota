<?php
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/auth.php';

if (estaAutenticado()) {
    header("Location: " . BASE_URL . "/dashboard/index.php");
} else {
    header("Location: " . BASE_URL . "/views/auth/login.php");
}
exit;