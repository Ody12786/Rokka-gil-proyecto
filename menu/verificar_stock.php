<?php
include("../database/connect_db.php");
header('Content-Type: application/json');

if (isset($_GET['tela']) && isset($_GET['metros'])) {
    $tela = trim($_GET['tela']);
    
    // 🔥 SUMAR TODOS LOS METROS de esa tela (pendientes + pagados)
    $stmt = $conex->prepare("
        SELECT 
            COALESCE(SUM(metros), 0) as stock_actual 
        FROM compras_telas 
        WHERE tipo_tela = ? 
        AND estado_pago != 'Cancelada'
    ");
    $stmt->bind_param("s", $tela);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $stock = (float)($row['stock_actual'] ?? 0);
    $metrosNecesarios = (float)$_GET['metros'];
    
    echo json_encode([
        'stock' => $stock,
        'metros' => $metrosNecesarios,
        'suficiente' => $stock >= $metrosNecesarios
    ]);
} else {
    echo json_encode(['stock' => 0, 'metros' => 0, 'suficiente' => false]);
}
?>
