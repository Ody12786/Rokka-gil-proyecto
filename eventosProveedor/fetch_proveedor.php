<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php"); // Este define $conex

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$sql = "SELECT rif, nombres, empresa, direccion, fecha_creación, contacto FROM proveedor";
$result = $conex->query($sql);  

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["data" => $data]);
$conex->close();
?>
