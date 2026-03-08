<?php
session_start();
include(__DIR__ . "/../database/connect_db.php");

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if (!isset($conex) || $conex === null) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No conexión BD']);
    exit;
}


$sql = "
    SELECT 
        v.id AS id_venta,
        c.nombre AS cliente_nombre,
        v.total AS monto_total,
        (v.total - sv.saldo_actual) AS total_pagado,
        sv.saldo_actual AS saldo_pendiente
    FROM ventas v
    INNER JOIN cliente c ON v.cliente_id = c.N_afiliacion
    INNER JOIN saldo_venta sv ON v.id = sv.venta_id
    WHERE sv.saldo_actual > 0
    ORDER BY sv.ultima_actualizacion DESC
";

$result = $conex->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $conex->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['total_pagado'] = round($row['total_pagado'], 2);
    $row['saldo_pendiente'] = round($row['saldo_pendiente'], 2);
    $data[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $data]);
$conex->close();
exit;
?>
