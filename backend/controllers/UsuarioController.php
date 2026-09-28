<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Usuario.php';

requerirRolApi(['Administrador']);

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
        $usuarios = Usuario::obtenerTodos($conn);
        respuestaJson(true, $usuarios);
        break;

    case 'obtener':
        $id = (int)($_GET['id'] ?? 0);
        $usuario = Usuario::obtenerPorId($conn, $id);
        if (!$usuario) {
            respuestaJson(false, null, 'Usuario no encontrado.', 404);
        }
        unset($usuario['password']);
        respuestaJson(true, $usuario);
        break;

    case 'guardar':
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $rol = trim($_POST['rol'] ?? 'Consulta');
        $estado = trim($_POST['estado'] ?? 'Activo');

        if (empty($nombre) || empty($email) || empty($password)) {
            respuestaJson(false, null, 'Nombre, correo y contraseña son obligatorios.', 400);
        }

        if (Usuario::existeEmail($conn, $email)) {
            respuestaJson(false, null, "El correo '{$email}' ya está registrado por otro usuario.", 409);
        }

        $datos = [
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'rol' => $rol,
            'estado' => $estado
        ];

        if (Usuario::crear($conn, $datos)) {
            respuestaJson(true, null, "Usuario '{$nombre}' creado exitosamente.");
        } else {
            respuestaJson(false, null, 'Error al crear usuario: ' . $conn->error, 500);
        }
        break;

    case 'actualizar':
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = trim($_POST['rol'] ?? 'Consulta');
        $estado = trim($_POST['estado'] ?? 'Activo');
        $password = trim($_POST['password'] ?? '');

        if ($id <= 0 || empty($nombre) || empty($email)) {
            respuestaJson(false, null, 'ID, nombre y correo son obligatorios.', 400);
        }

        if (Usuario::existeEmail($conn, $email, $id)) {
            respuestaJson(false, null, "El correo '{$email}' ya pertenece a otro usuario.", 409);
        }

        $datos = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'estado' => $estado
        ];
        if (!empty($password)) {
            $datos['password'] = $password;
        }

        if (Usuario::actualizar($conn, $id, $datos)) {
            respuestaJson(true, null, "Usuario '{$nombre}' actualizado exitosamente.");
        } else {
            respuestaJson(false, null, 'Error al actualizar usuario: ' . $conn->error, 500);
        }
        break;

    case 'eliminar':
        $id = (int)($_REQUEST['id'] ?? 0);
        $actual = usuarioActual();

        if ($id <= 0) {
            respuestaJson(false, null, 'ID inválido.', 400);
        }

        if ($actual && (int)$actual['id'] === $id) {
            respuestaJson(false, null, 'No puedes eliminar tu propia cuenta de usuario en sesión.', 400);
        }

        if (Usuario::eliminar($conn, $id)) {
            respuestaJson(true, null, 'Usuario eliminado correctamente.');
        } else {
            respuestaJson(false, null, 'Error al eliminar usuario: ' . $conn->error, 500);
        }
        break;

    default:
        respuestaJson(false, null, 'Acción no válida.', 400);
}
