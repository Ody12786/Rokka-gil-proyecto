<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    exit(json_encode([]));
}

$stmt = $conex->prepare("SELECT id, nombre, categoria, DATE_FORMAT(fecha_registro, '%d/%m/%Y') as fecha_registro FROM ordenes ORDER BY fecha_registro DESC LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
$ordenes = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($ordenes);
?>
