<?php
session_start();
include("../database/connect_db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $distribucion_id = (int)$_POST['distribucion_id'];
    $nombre = trim($_POST['nombre']);
    $categoria = $_POST['categoria'];
    $tecnica = $_POST['tecnica'];
    $imagen = basename($_POST['imagen']); // Solo nombre archivo
    
    $stmt = $conex->prepare("
        INSERT INTO modelos_standby (imagen, nombre, categoria, tecnica, cantidad) 
        VALUES (?, ?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE 
        nombre = VALUES(nombre), 
        categoria = VALUES(categoria),
        tecnica = VALUES(tecnica)
    ");
    $stmt->bind_param("ssss", $imagen, $nombre, $categoria, $tecnica);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conex->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar']);
    }
    $stmt->close();
}
?>
