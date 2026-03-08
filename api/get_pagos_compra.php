<?php
session_start();
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['compra_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parámetro compra_id requerido']);
    exit;
}

$compra_id = intval($_GET['compra_id']);

$sql = "SELECT id, monto, fecha_pago, metodo_pago, observaciones FROM pagos_compras WHERE compra_id = ? ORDER BY fecha_pago DESC";
$stmt = $conex->prepare($sql);
$stmt->bind_param("i", $compra_id);
$stmt->execute();
$result = $stmt->get_result();

$pagos = [];
while ($row = $result->fetch_assoc()) {
    $pagos[] = $row;
}

echo json_encode($pagos);
