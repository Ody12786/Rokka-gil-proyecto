<?php
require_once '../database/connect_db.php';

header('Content-Type: application/json');

$res = $conex->query("SELECT id, nombres, empresa FROM proveedor ORDER BY nombres");

if (!$res) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $conex->error
    ]);
    exit;
}

$data = [];
while ($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $data
]);
