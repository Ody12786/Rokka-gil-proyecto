<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Verificar que existe y está activo
    $check = $conex->prepare("SELECT id FROM modelos_standby WHERE id = ? AND activo = 1");
    $check->bind_param("i", $id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Modelo no encontrado']);
        exit;
    }
    
    // Cambiar activo = 0 (soft delete)
    $stmt = $conex->prepare("UPDATE modelos_standby SET activo = 0, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Modelo eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar modelo']);
    }
    
    $stmt->close();
    $check->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
}
?>
