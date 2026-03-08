<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

$venta_id = intval($_GET['venta_id'] ?? 0);

if ($venta_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de venta inválido']);
    exit;
}

// Obtener saldo pendiente y lista de abonos
$queryVenta = "SELECT total FROM ventas WHERE id = ?";
$stmt = $conex->prepare($queryVenta);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Venta no encontrada']);
    exit;
}
$total = floatval($res->fetch_assoc()['total']);

$queryAbonos = "SELECT fecha_pago, monto_pagado, tipo_pago, IFNULL(observaciones, '') AS observaciones FROM abonos WHERE venta_id = ? ORDER BY fecha_pago DESC";
$stmtAbonos = $conex->prepare($queryAbonos);
$stmtAbonos->bind_param("i", $venta_id);
$stmtAbonos->execute();
$resAbonos = $stmtAbonos->get_result();

$sumAbonos = 0.0;
$abonos = [];
while ($row = $resAbonos->fetch_assoc()) {
    $sumAbonos += floatval($row['monto_pagado']);
    $abonos[] = $row;
}

$saldo_pendiente = $total - $sumAbonos;

echo json_encode([
    'saldo_pendiente' => $saldo_pendiente,
    'abonos' => $abonos
]);

$stmt->close();
$stmtAbonos->close();
$conex->close();
