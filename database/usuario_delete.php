<?php
session_start();
include("connect_db.php");
include("cifrado.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'No autenticado']);
    exit;
}

$encrypted_id = $_POST['id'] ?? '';
$id = intval(decryptId($encrypted_id));

$stmt = $conex->prepare("DELETE FROM usuario WHERE id_rec=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error', 'message'=>'Error al eliminar']);
}
$stmt->close();
