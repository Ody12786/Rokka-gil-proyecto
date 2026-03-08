<?php
session_start();
include("../database/connect_db.php"); // aquí define y conecta $conex

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit;
}

// Leer datos JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['compra_id'], $data['monto'], $data['fecha_pago'], $data['metodo_pago'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$metodos_validos = ['Efectivo', 'Transferencia', 'Divisa', 'Tarjeta', 'Pago Móvil'];
if (!in_array($data['metodo_pago'], $metodos_validos)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Método de pago inválido']);
    exit;
}

$compra_id = intval($data['compra_id']);
$monto = floatval($data['monto']);
$fecha_pago = $data['fecha_pago'];
$metodo_pago = $data['metodo_pago'];
$observaciones = isset($data['observaciones']) ? $data['observaciones'] : null;

// Obtener saldo actual de la compra
$stmt = $conex->prepare("SELECT saldo FROM compras_telas WHERE id = ?");
$stmt->bind_param("i", $compra_id);
$stmt->execute();
$stmt->bind_result($saldo_actual);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Compra no encontrada']);
    exit;
}
$stmt->close();

if ($monto <= 0 || $monto > $saldo_actual) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Monto inválido o superior al saldo pendiente']);
    exit;
}

// Insertar el pago
$stmt = $conex->prepare("INSERT INTO pagos_compras (compra_id, monto, fecha_pago, metodo_pago, observaciones) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("idsss", $compra_id, $monto, $fecha_pago, $metodo_pago, $observaciones);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al registrar el pago']);
    exit;
}
$stmt->close();

// Actualizar saldo y estado en compras_telas
$nuevo_saldo = $saldo_actual - $monto;
$estado_pago = ($nuevo_saldo <= 0.001) ? 'Pagado' : 'Pendiente';

$stmt2 = $conex->prepare("UPDATE compras_telas SET saldo = ?, estado_pago = ? WHERE id = ?");
$stmt2->bind_param("dsi", $nuevo_saldo, $estado_pago, $compra_id);
$stmt2->execute();
$stmt2->close();

echo json_encode([
    'status' => 'success',
    'saldo' => round($nuevo_saldo, 2),
    'estado_pago' => $estado_pago
]);