<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Usuario.php';

$accion = $_REQUEST['accion'] ?? '';


$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $_POST = array_merge($_POST, $jsonData);
    }
}

switch ($accion) {
    case 'check':
        if (estaAutenticado()) {
            respuestaJson(true, ['usuario' => usuarioActual(), 'authenticated' => true]);
        } else {
            respuestaJson(false, ['authenticated' => false], 'No autenticado', 200);
        }
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                respuestaJson(false, null, 'Por favor ingresa tu correo y contraseña.', 400);
            }

            $usuario = Usuario::login($conn, $email, $password);

            if ($usuario) {
                $_SESSION['usuario'] = $usuario;
                setAlerta('success', '¡Bienvenido de nuevo, ' . htmlspecialchars($usuario['nombre']) . '!');
                
                if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || !empty($rawInput)) {
                    respuestaJson(true, [
                        'usuario' => $usuario,
                        'redirect' => BASE_URL . '/frontend/index.html'
                    ], 'Inicio de sesión exitoso.');
                }

                header("Location: " . BASE_URL . "/frontend/index.html");
                exit;
            } else {
                if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || !empty($rawInput)) {
                    respuestaJson(false, null, 'Credenciales incorrectas o usuario inactivo.', 401);
                }

                setAlerta('danger', 'Credenciales incorrectas o usuario inactivo.');
                header("Location: " . BASE_URL . "/frontend/login.html");
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

        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            respuestaJson(true, ['redirect' => BASE_URL . '/frontend/login.html'], 'Sesión cerrada correctamente.');
        }

        header("Location: " . BASE_URL . "/frontend/login.html");
        exit;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
