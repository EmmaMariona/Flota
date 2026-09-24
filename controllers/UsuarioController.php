<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Usuario.php';

requerirRol(['Administrador']);

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $rol = $_POST['rol'] ?? 'Consulta';
            $estado = $_POST['estado'] ?? 'Activo';

            if (empty($nombre) || empty($email) || empty($password)) {
                setAlerta('danger', 'Todos los campos son obligatorios.');
                header("Location: " . BASE_URL . "/usuarios/crear.php");
                exit;
            }

            $datos = [
                'nombre' => $nombre,
                'email' => $email,
                'password' => $password,
                'rol' => $rol,
                'estado' => $estado
            ];

            try {
                if (Usuario::crear($conn, $datos)) {
                    setAlerta('success', "Usuario {$nombre} creado exitosamente con rol {$rol}.");
                    header("Location: " . BASE_URL . "/usuarios/lista.php");
                    exit;
                } else {
                    setAlerta('danger', "Error al crear usuario: " . $conn->error);
                    header("Location: " . BASE_URL . "/usuarios/crear.php");
                    exit;
                }
            } catch (Exception $e) {
                setAlerta('danger', "El correo {$email} ya se encuentra registrado.");
                header("Location: " . BASE_URL . "/usuarios/crear.php");
                exit;
            }
        }
        break;

    case 'actualizar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'Consulta';
            $estado = $_POST['estado'] ?? 'Activo';
            $password = trim($_POST['password'] ?? '');

            if ($id <= 0 || empty($nombre) || empty($email)) {
                setAlerta('danger', 'Datos incompletos.');
                header("Location: " . BASE_URL . "/usuarios/lista.php");
                exit;
            }

            $datos = [
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $rol,
                'estado' => $estado,
                'password' => $password
            ];

            if (Usuario::actualizar($conn, $id, $datos)) {
                setAlerta('success', "Usuario actualizado correctamente.");
            } else {
                setAlerta('danger', "Error al actualizar usuario: " . $conn->error);
            }
            header("Location: " . BASE_URL . "/usuarios/lista.php");
            exit;
        }
        break;

    case 'eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            if ($id === 1) {
                setAlerta('danger', 'No es posible eliminar al Administrador Principal del sistema.');
            } elseif (Usuario::eliminar($conn, $id)) {
                setAlerta('success', 'Usuario eliminado exitosamente.');
            } else {
                setAlerta('danger', 'Error al eliminar usuario: ' . $conn->error);
            }
        }
        header("Location: " . BASE_URL . "/usuarios/lista.php");
        exit;

    default:
        header("Location: " . BASE_URL . "/usuarios/lista.php");
        exit;
}
