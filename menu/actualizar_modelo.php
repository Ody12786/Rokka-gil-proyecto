<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $tela = isset($_POST['tela']) ? trim($_POST['tela']) : null;
    $talla = isset($_POST['talla']) ? trim($_POST['talla']) : null;
    $color = $_POST['color'] ?? '#dc3545';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    
    $stmt = $conex->prepare("
        UPDATE modelos_standby 
        SET nombre = ?, 
            tela = ?, 
            talla = ?, 
            color = ?, 
            descripcion = ?, 
            fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id = ? AND activo = 1
    ");
    
    $stmt->bind_param("sssssi", $nombre, $tela, $talla, $color, $descripcion, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Modelo actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Modelo no encontrado o sin cambios']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error en base de datos: ' . $conex->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
}
?>
