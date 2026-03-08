<?php
session_start();
include("connect_db.php");
include("cifrado.php");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

$sql = "SELECT u.id_rec AS id, u.nombre, u.correo AS email, u.telefono, u.carnet, 
               u.tipo, u.estado, '' AS foto_carnet 
        FROM usuario u 
        ORDER BY u.nombre ASC";  

$result = $conex->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error en consulta: ' . $conex->error]);
    exit;
}

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $row['id_encriptado'] = encryptId($row['id']);
    $usuarios[] = $row;
}

echo json_encode(['data' => $usuarios]);
$conex->close();
?>
