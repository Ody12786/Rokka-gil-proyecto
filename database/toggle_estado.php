<?php
session_start();
include("connect_db.php");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'No autenticado']);
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['status' => 'error', 'mensaje' => 'ID inválido']);
    exit;
}

$id = (int)$_POST['id'];

// Obtener estado actual
$stmt = $conex->prepare("SELECT estado FROM usuario WHERE id_rec = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Usuario no encontrado']);
    exit;
}

$estadoActual = $result->fetch_assoc()['estado'];
$nuevoEstado = $estadoActual === 'A' ? 'I' : 'A';

// Actualizar
$stmt = $conex->prepare("UPDATE usuario SET estado = ? WHERE id_rec = ?");
$stmt->bind_param("si", $nuevoEstado, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success', 
        'mensaje' => 'Estado actualizado correctamente',
        'estado' => $nuevoEstado
    ]);
} else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error al actualizar']);
}

$stmt->close();
$conex->close();
?>
