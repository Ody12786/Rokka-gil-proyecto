<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

// Validar sesión 
if (!isset($_SESSION['usuario']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
// CSRF validation removed per request

$venta_id = intval($_POST['venta_id'] ?? 0);
$monto_pagado = floatval($_POST['monto_pagado'] ?? 0);
$tipo_pago = $_POST['tipo_pago'] ?? '';

if ($venta_id <= 0 || $monto_pagado <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// Obtener saldo pendiente actual (total de venta menos suma de abonos)
$querySaldo = "
  SELECT total - IFNULL((SELECT SUM(monto_pagado) FROM abonos WHERE venta_id = ?), 0) AS saldo_pendiente 
  FROM ventas WHERE id = ? LIMIT 1";
$stmtSaldo = $conex->prepare($querySaldo);
$stmtSaldo->bind_param("ii", $venta_id, $venta_id);
$stmtSaldo->execute();
$resSaldo = $stmtSaldo->get_result();
if (!$resSaldo || $resSaldo->num_rows == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Venta no encontrada']);
    exit;
}
$saldo = $resSaldo->fetch_assoc()['saldo_pendiente'];

if ($monto_pagado > $saldo) {
    http_response_code(400);
    echo json_encode(['error' => 'El monto pagado supera el saldo pendiente']);
    exit;
}

// Insertar nuevo abono
$stmtInsert = $conex->prepare("INSERT INTO abonos (venta_id, monto_pagado, tipo_pago, fecha_pago) VALUES (?, ?, ?, NOW())");
$stmtInsert->bind_param("ids", $venta_id, $monto_pagado, $tipo_pago);

if (!$stmtInsert->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar el abono']);
    exit;
}

// Nuevo saldo pendiente después del abono
$nuevo_saldo = $saldo - $monto_pagado;

echo json_encode([
    'success' => true,
    'nuevo_saldo' => $nuevo_saldo
]);

$stmtInsert->close();
$stmtSaldo->close();
$conex->close();
