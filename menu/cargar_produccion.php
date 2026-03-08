<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

$orden_id = intval($_POST['orden_id']);

$stmt = $conex->prepare("
    SELECT o.nombre, o.imagen, o.tecnica, o.tipo_tela, o.metros_totales,
           COALESCE(SUM(od.cantidad), 0) as total_piezas
    FROM ordenes o 
    LEFT JOIN orden_detalle od ON o.id = od.orden_id 
    WHERE o.id = ? 
    GROUP BY o.id
");
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orden) {
    echo json_encode(['success' => false, 'error' => 'Orden no encontrada']);
    exit;
}

// Stock tela
$stock = $conex->query("SELECT saldo FROM compras_telas WHERE tipo_tela = '{$orden['tipo_tela']}' LIMIT 1")->fetch_assoc();
$stock_tela = $stock ? $stock['saldo'] : 0;
$stock_suficiente = $stock_tela >= $orden['metros_totales'];
$faltante = max(0, $orden['metros_totales'] - $stock_tela);

echo json_encode([
    'success' => true,
    'orden' => $orden,
    'total_piezas' => $orden['total_piezas'],
    'stock_suficiente' => $stock_suficiente,
    'faltante' => number_format($faltante, 2),
    'materiales' => [
        [
            'nombre' => $orden['tipo_tela'],
            'necesario' => $orden['metros_totales'],
            'stock' => $stock_tela,
            'suficiente' => $stock_suficiente
        ]
    ]
]);
?>
