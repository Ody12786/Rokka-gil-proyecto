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
    $rif_original = trim($_POST['rif_original'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');

    if ($rif_original === '' || $nombres === '' || $empresa === '' || $contacto === '') {
        http_response_code(400);
        echo json_encode(["error" => "Debe llenar todos los campos."]);
        exit;
    }

    $stmt = $conex->prepare("UPDATE proveedor SET nombres = ?, empresa = ?, direccion = ?, contacto = ? WHERE rif = ?");
    $stmt->bind_param("sssss", $nombres, $empresa, $direccion, $contacto, $rif_original);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar proveedor."]);
    }

    $stmt->close();
    $conex->close();
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}
?>
