<?php
session_start();
include(__DIR__ . "/../database/connect_db.php");

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}


// Validar conexión
if (!isset($conex) || $conex === null) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo establecer conexión a la base de datos']);
    exit;
}

$idVenta = $_POST['id_venta'] ?? null;
$montoPago = $_POST['monto_pago'] ?? null;

if (!$idVenta || !$montoPago || !is_numeric($montoPago) || $montoPago <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

// Obtener saldo pendiente actual
$sqlSaldo = "
    SELECT 
        v.total,
        COALESCE(SUM(a.monto), 0) AS total_pagado,
        (v.total - COALESCE(SUM(a.monto), 0)) AS saldo_pendiente
    FROM ventas v
    LEFT JOIN abonos a ON v.id = a.venta_id
    WHERE v.id = ?
    GROUP BY v.id, v.total
";

$stmt = $conex->prepare($sqlSaldo);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error preparando consulta: ' . $conex->error]);
    exit;
}

$stmt->bind_param('i', $idVenta);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Venta no encontrada']);
    exit;
}

$venta = $result->fetch_assoc();
$saldoPendiente = floatval($venta['saldo_pendiente']);

// Evitar pagar más que el saldo pendiente (con margen pequeño)
if ($montoPago > $saldoPendiente + 0.01) {
    echo json_encode(['status' => 'error', 'message' => 'Monto de pago excede saldo pendiente']);
    exit;
}

// Insertar abono
$sqlInsert = "INSERT INTO abonos (venta_id, monto, fecha_pago, usuario_id) VALUES (?, ?, NOW(), ?)";
$stmtIns = $conex->prepare($sqlInsert);
if (!$stmtIns) {
    echo json_encode(['status' => 'error', 'message' => 'Error preparando inserción: ' . $conex->error]);
    exit;
}

$stmtIns->bind_param('idi', $idVenta, $montoPago, $_SESSION['usuario_id']);
$stmtIns->execute();

if ($stmtIns->affected_rows <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar el pago']);
    exit;
}

// Actualizar estado si la venta queda pagada
$nuevoSaldo = $saldoPendiente - $montoPago;

if ($nuevoSaldo <= 0.01) {
    $sqlUpd = "UPDATE ventas SET estado_pago = 'pagado' WHERE id = ?";
    $stmtUpd = $conex->prepare($sqlUpd);
    if ($stmtUpd) {
        $stmtUpd->bind_param('i', $idVenta);
        $stmtUpd->execute();
    }
}

echo json_encode(['status' => 'success', 'message' => 'Pago registrado correctamente']);
exit;
