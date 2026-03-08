<?php
session_start();
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // ✅ SOLO CRÉDITOS PENDIENTES (saldo > 0)
    $sql = "SELECT c.id, p.nombres AS proveedor_nombre, c.fecha_adquisicion, c.total, c.saldo, c.estado_pago
            FROM compras_telas c
            INNER JOIN proveedor p ON c.proveedor_id = p.id
            WHERE c.saldo > 0
            ORDER BY c.fecha_adquisicion DESC";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conex->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $compras = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $compras]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
