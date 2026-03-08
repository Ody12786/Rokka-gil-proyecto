<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

$Cid = trim($_GET['Cid'] ?? '');

if ($Cid === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Falta la cédula"]);
    exit;
}

// Sanitizar y validar solo números
if (!ctype_digit($Cid)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "La cédula debe contener solo números"]);
    exit;
}

$stmt = $conex->prepare("SELECT N_afiliacion, Cid, nombre FROM cliente WHERE Cid = ? LIMIT 1");
$stmt->bind_param("s", $Cid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["status" => "success", "data" => [$row]]); // poner data como array para frontend
} else {
    // Devuelve array vacío para indicar que no encontró clientes
    echo json_encode(["status" => "success", "data" => []]);
}

$stmt->close();
$conex->close();
