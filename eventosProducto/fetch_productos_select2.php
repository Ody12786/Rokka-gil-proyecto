<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include("../database/connect_db.php");

// 🔒 Autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'data' => []]);
    exit;
}

$term = $_GET['term'] ?? '';
$tipo = $_GET['tipo'] ?? 'productos';

// 🔥 SOLO PRODUCTOS DISPONIBLES
$param = "%$term%";
$query = "
    SELECT 
        id, 
        codigo, 
        nombre, 
        stock, 
        talla, 
        categoria 
    FROM productos 
    WHERE activo = 1 
    AND stock > 0 
    AND (codigo LIKE ? OR nombre LIKE ? OR talla LIKE ? OR categoria LIKE ?)
    ORDER BY codigo ASC, nombre ASC 
    LIMIT 50
";

$stmt = $conex->prepare($query);
$stmt->bind_param("ssss", $param, $param, $param, $param);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    
    $texto = $row['codigo'];
    if ($row['nombre']) $texto .= ' - ' . $row['nombre'];
    if ($row['talla']) $texto .= ' (' . $row['talla'] . ')';
    if ($row['categoria']) $texto .= ' | ' . $row['categoria'];
    $texto .= ' <span class="text-success fw-bold">[Stock: ' . $row['stock'] . ']</span>';

    $data[] = [
        'id' => $row['id'],
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'stock' => $row['stock'],
        'talla' => $row['talla'],
        'categoria' => $row['categoria'],
        'text' => $texto  // Para dropdown Select2
    ];
}

$stmt->close();
$conex->close();

echo json_encode([
    'status' => 'success',
    'data' => $data
]);
?>
