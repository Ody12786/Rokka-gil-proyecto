<?php
session_start();
include("../database/connect_db.php");

header('Content-Type: application/json; charset=utf-8');

// Validar sesión 
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['data' => [], 'error' => 'No autenticado']);
    exit;
}

try {
    $query = "SELECT v.id, v.fecha_venta, c.nombre AS cliente_nombre, v.total, v.tipo_pago, v.estado_pago, u.nombre AS usuario_nombre
              FROM ventas v
              INNER JOIN cliente c ON v.cliente_id = c.N_afiliacion
              LEFT JOIN usuario u ON v.usuario_id = u.id_rec
              ORDER BY v.fecha_venta DESC";

    $result = $conex->query($query);

    if (!$result) {
        throw new Exception("Error en consulta: " . $conex->error);
    }

    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }

    echo json_encode(['data' => $ventas]);
} catch (Exception $ex) {
    echo json_encode(['data' => [], 'error' => $ex->getMessage()]);
}
