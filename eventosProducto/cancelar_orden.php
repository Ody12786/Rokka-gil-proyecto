<?php
session_start();
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}
// CSRF validation removed per request

if (!isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Falta id de orden"]);
    exit;
}

$id = intval($_POST['id']);

// Get image filename to delete file
$stmt = $conex->prepare("SELECT imagen FROM ordenes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($imagen);
if ($stmt->fetch()) {
    $stmt->close();
    $filePath = "../uploads/" . $imagen;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    $stmt = $conex->prepare("DELETE FROM ordenes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Error al eliminar orden"]);
    }
    $stmt->close();
} else {
    echo json_encode(["error" => "Orden no encontrada"]);
}
?>
