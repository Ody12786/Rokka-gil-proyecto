<?php
session_start();
include(__DIR__ . "/../database/connect_db.php");

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if (!isset($conex) || $conex->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error conexión BD']);
    exit;
}

$conex->autocommit(false);

try {
    $ventaId = intval($_POST['id_venta'] ?? 0);
    $montoPago = floatval($_POST['monto_pago'] ?? 0);

    if ($ventaId <= 0 || $montoPago <= 0) {
        throw new Exception('ID venta o monto inválido');
    }

    // 🔍 VERIFICAR SALDO
    $stmtSaldo = $conex->prepare("
        SELECT saldo_actual FROM saldo_venta 
        WHERE venta_id = ? AND saldo_actual > 0
    ");
    $stmtSaldo->bind_param("i", $ventaId);
    $stmtSaldo->execute();
    $result = $stmtSaldo->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        throw new Exception('Venta no encontrada o ya saldada');
    }
    $saldoActual = floatval($row['saldo_actual']);
    $stmtSaldo->close();

    if ($montoPago > $saldoActual) {
        throw new Exception("Monto ($" . number_format($montoPago,2) . ") > Saldo ($" . number_format($saldoActual,2) . ")");
    }

    // ✅ 1. GUARDAR ABONO (FALTABA ESTO)
    $stmtAbono = $conex->prepare("
        INSERT INTO abonos (venta_id, monto, fecha_pago, usuario_id) 
        VALUES (?, ?, NOW(), ?)
    ");
    $stmtAbono->bind_param("idi", $ventaId, $montoPago, $_SESSION['usuario_id']);
    if (!$stmtAbono->execute()) {
        throw new Exception('Error guardando abono: ' . $stmtAbono->error);
    }
    $stmtAbono->close();

    // ✅ 2. ACTUALIZAR SALDO
    $nuevoSaldo = max(0, $saldoActual - $montoPago);
    $stmtUpdate = $conex->prepare("
        UPDATE saldo_venta 
        SET saldo_actual = ?, ultima_actualizacion = NOW() 
        WHERE venta_id = ?
    ");
    $stmtUpdate->bind_param("di", $nuevoSaldo, $ventaId);
    if (!$stmtUpdate->execute()) {
        throw new Exception('Error actualizando saldo: ' . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // ✅ 3. SI ESTÁ PAGADA → VENTA COMPLETA
    if ($nuevoSaldo <= 0.01) {
        $stmtVenta = $conex->prepare("
            UPDATE ventas SET estado_pago = 'Pagada', porcentaje_pagado = 100 
            WHERE id = ?
        ");
        $stmtVenta->bind_param("i", $ventaId);
        $stmtVenta->execute();
        $stmtVenta->close();
    }

    $conex->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => '✅ Abono registrado',
        'data' => [
            'venta_id' => $ventaId,
            'monto_pagado' => number_format($montoPago, 2),
            'saldo_restante' => number_format($nuevoSaldo, 2),
            'total_pagada' => $nuevoSaldo <= 0.01
        ]
    ]);

} catch (Exception $e) {
    $conex->rollback();
    http_response_code(400);
    
    // 🔍 LOG DETALLADO
    $log = date('Y-m-d H:i:s') . " | Venta:$ventaId | \$" . ($montoPago ?? 0) . " | " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/debug_abonos.log', $log, FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    $conex->autocommit(true);
    $conex->close();
}
?>
