<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php"); // Incluye $conex

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}
// CSRF validation removed per request

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rif = trim($_POST['rif'] ?? '');

    if ($rif === '') {
        http_response_code(400);
        echo json_encode(["error" => "Falta el RIF"]);
        exit;
    }

    $stmt = $conex->prepare("DELETE FROM proveedor WHERE rif = ?");
    $stmt->bind_param("s", $rif);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar"]);
    }

    $stmt->close();
    $conex->close();
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}
?>
