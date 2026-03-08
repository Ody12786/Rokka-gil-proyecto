<?php
session_start();
include("../database/connect_db.php");

header('Content-Type: application/json');

$campo = $_POST['email'] ?? $_POST['carnet'] ?? '';
$tipo = $_POST['email'] ? 'correo' : 'carnet';

$stmt = $conex->prepare("SELECT COUNT(*) FROM usuario WHERE $tipo = ?");
$stmt->bind_param("s", $campo);
$stmt->execute();
$existe = $stmt->get_result()->fetch_row()[0] > 0;

echo json_encode(['existe' => (bool)$existe]);
?>
