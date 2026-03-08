<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(null);
    exit;
}

if (!isset($_GET['N_afiliacion'])) {
    http_response_code(400);
    echo json_encode(null);
    exit;
}

$id = $conex->real_escape_string($_GET['N_afiliacion']);
$sql = "SELECT N_afiliacion, Cid, nombre FROM cliente WHERE N_afiliacion = '$id' LIMIT 1";
$result = $conex->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(null);
}

$conex->close();
