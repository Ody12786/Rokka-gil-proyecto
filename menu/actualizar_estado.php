<?php
session_start();
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $estado = $_POST['estado'];
    
    $stmt = $conex->prepare("UPDATE ordenes SET estado_entrega = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success]);
}
?>