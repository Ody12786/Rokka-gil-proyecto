<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = (int)$_POST['producto_id'];
    $nombre = trim($_POST['nombre']);
    $talla = $_POST['talla'] ?? '';
    $tipo_tela = $_POST['tipo_tela'] ?? '';
    $color = $_POST['color'] ?? '#6b7280';
    $imagen = basename($_POST['imagen']);
    
    // Verificar conexión DB
    if ($conex->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Error conexión DB: ' . $conex->connect_error]);
        exit;
    }
    
    $stmt = $conex->prepare("
        INSERT INTO modelos_standby (imagen, nombre, categoria, tela, talla, color, tecnica) 
        VALUES (?, ?, 'Producto Terminado', ?, ?, ?, 'Manual')
    ");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error prepare: ' . $conex->error]);
        exit;
    }
    
    $stmt->bind_param("sssss", $imagen, $nombre, $tipo_tela, $talla, $color);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conex->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error execute: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'POST requerido']);
}
?>
