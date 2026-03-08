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
    $nombres = trim($_POST['nombres'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');

    if ($rif === '' || $nombres === '' || $empresa === '' || $direccion === '' || $contacto === '') {
        http_response_code(400);
        echo json_encode(["error" => "Debe llenar todos los campos."]);
        exit;
    }

    // Verificar si ya existe RIF
    $stmt = $conex->prepare("SELECT rif FROM proveedor WHERE rif = ?");
    $stmt->bind_param("s", $rif);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        http_response_code(409);
        echo json_encode(["error" => "El RIF ya está registrado."]);
        exit;
    }

    $stmt->close();

    $fecha_creacion = date('Y-m-d H:i:s');
    $stmt = $conex->prepare("INSERT INTO proveedor (rif, nombres, empresa, direccion, fecha_creación, contacto) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $rif, $nombres, $empresa, $direccion, $fecha_creacion, $contacto);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar proveedor."]);
    }

    $stmt->close();
    $conex->close();
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}
?>
