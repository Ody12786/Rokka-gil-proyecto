<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

try {
    $stmt = $conex->prepare("SELECT N_afiliacion, Cid, nombre FROM cliente WHERE is_deleted = 1 ORDER BY nombre ASC");
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
    echo json_encode(["error" => $e->getMessage()]);
}
?>
