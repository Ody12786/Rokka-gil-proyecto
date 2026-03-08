<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

try {
     $stmt = $conex->prepare("SELECT * FROM cliente WHERE is_deleted = 0 ORDER BY nombre ASC");
    
    if (!$stmt) {
        throw new Exception("Error preparing: " . $conex->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(["data" => $data]);
    
    $stmt->close();
    $conex->close();  
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error del servidor: " . $e->getMessage()]);
}
?>
