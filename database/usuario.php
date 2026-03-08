<?php
session_start();
include("connect_db.php");

header('Content-Type: application/json; charset=utf-8');


if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

$carnet = trim($_POST['carnet_usuario'] ?? '');
$nombre = trim($_POST['nombre_usuario'] ?? '');
$correo = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$tipo = intval($_POST['tipo_usuario'] ?? 0);
$password = $_POST['password'] ?? '';

if (!preg_match('/^\d{6,9}$/', $carnet) || empty($nombre) || empty($correo)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

// Verificar empleado
$stmt = $conex->prepare("SELECT COUNT(*) FROM empleado WHERE carnet = ?");
$stmt->bind_param("s", $carnet);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Carnet no existe en empleados']);
    exit;
}

// Buscar usuario
$stmt = $conex->prepare("SELECT id_rec FROM usuario WHERE carnet = ?");
$stmt->bind_param("s", $carnet);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    // NUEVO
    $pass_hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conex->prepare("INSERT INTO usuario (nombre, correo, telefono, carnet, tipo, contrasena) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $nombre, $correo, $telefono, $carnet, $tipo, $pass_hashed);
} else {
    // UPDATE - ¡SIN ESPACIOS!
    $id_rec = $usuario['id_rec'];
    if (!empty($password)) {
        $pass_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conex->prepare("UPDATE usuario SET nombre=?, correo=?, telefono=?, tipo=?, contrasena=? WHERE id_rec=?");
        $stmt->bind_param("sssisi", $nombre, $correo, $telefono, $tipo, $pass_hashed, $id_rec);
        //                  ^^^^^^ 6 tipos SIN espacios
    } else {
        $stmt = $conex->prepare("UPDATE usuario SET nombre=?, correo=?, telefono=?, tipo=? WHERE id_rec=?");
        $stmt->bind_param("sssii", $nombre, $correo, $telefono, $tipo, $id_rec);
        //                  ^^^^^ 5 tipos SIN espacios
    }
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Usuario ' . (!$usuario ? 'registrado' : 'actualizado') . ' correctamente']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conex->close();
?>
