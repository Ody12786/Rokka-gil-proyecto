<?php
session_start();
include("connect_db.php");
include("cifrado.php");
header('Content-Type: application/json; charset=utf-8');


if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'No autenticado']);
    exit;
}

$encrypted_id = $_POST['id_usuario'] ?? '';
$id = intval(decryptId($encrypted_id));
error_log("Encrypted ID recibido: $encrypted_id");
$nombre = trim($_POST['nombre_usuario'] ?? '');
$carnet = trim($_POST['carnet'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['email'] ?? '');

if (!preg_match('/^\d{6,9}$/', $carnet)) {
    echo json_encode(['status'=>'error', 'message'=>'Carnet debe tener entre 6 y 9 dígitos']);
    exit;
}

//error_log("Datos recibidos - ID: $id, Nombre: '$nombre', Carnet: '$carnet', Correo: '$correo', Telefono: '$telefono'");

if ($id <= 0 || empty($nombre) || empty($carnet) || empty($correo)) {
    echo json_encode(['status'=>'error', 'message'=>'Faltan datos obligatorios']);
    exit;
}

$sql = "UPDATE usuario SET nombre=?, carnet=?, telefono=?, correo=? WHERE id_rec=?";
if ($stmt = $conex->prepare($sql)) {
    $stmt->bind_param("ssssi", $nombre, $carnet, $telefono, $correo, $id);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Error al actualizar']);
    }
    $stmt->close();
} else {
    echo json_encode(['status'=>'error', 'message'=>'Error en la consulta SQL']);
}
$conex->close();
?>
