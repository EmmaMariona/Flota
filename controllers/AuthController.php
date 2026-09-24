<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Usuario.php';

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                setAlerta('danger', 'Por favor ingresa tu correo y contraseña.');
                header("Location: " . BASE_URL . "/views/auth/login.php");
                exit;
            }

            $usuario = Usuario::login($conn, $email, $password);

            if ($usuario) {
                $_SESSION['usuario'] = $usuario;
                setAlerta('success', '¡Bienvenido de nuevo, ' . htmlspecialchars($usuario['nombre']) . '!');
                header("Location: " . BASE_URL . "/dashboard/index.php");
                exit;
            } else {
                setAlerta('danger', 'Credenciales incorrectas o usuario inactivo.');
                header("Location: " . BASE_URL . "/views/auth/login.php");
                exit;
            }
        }
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        session_start();
        setAlerta('info', 'Has cerrado sesión exitosamente.');
        header("Location: " . BASE_URL . "/views/auth/login.php");
        exit;

    default:
        header("Location: " . BASE_URL . "/views/auth/login.php");
        exit;
}
