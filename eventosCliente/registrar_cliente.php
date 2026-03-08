<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$Cid = trim($_POST['Cid'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

if (empty($Cid) || empty($nombre)) {
    http_response_code(400);
    echo json_encode(["error" => "Debe llenar todos los campos"]);
    exit;
}

// ✅ Generar N_afiliacion único (CLI001, CLI002...)
$stmt = $conex->prepare("SELECT COUNT(*) as total FROM cliente WHERE is_deleted = 0");
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] + 1;
$N_afiliacion = 'CLI' . str_pad($total, 3, '0', STR_PAD_LEFT);
$stmt->close();

// ✅ Verificar cédula DUPLICADA (solo activos)
$stmt = $conex->prepare("SELECT N_afiliacion FROM cliente WHERE Cid = ? AND is_deleted = 0");
$stmt->bind_param("s", $Cid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(["error" => "La cédula $Cid ya está registrada"]);
    exit;
}
$stmt = $conex->prepare("SELECT N_afiliacion FROM cliente WHERE Cid = ? AND is_deleted = 1");
$stmt->bind_param("s", $Cid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $stmt = $conex->prepare("UPDATE cliente SET is_deleted = 0 WHERE Cid = ? AND is_deleted = 1");
    $stmt->bind_param("s", $Cid);
    $stmt->execute();
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Cliente restaurado correctamente"]);
        $stmt->close();
    exit;
}


$stmt->close();

// ✅ INSERT COMPLETO con tabla CORRECTA
$stmt = $conex->prepare("INSERT INTO cliente (N_afiliacion, Cid, nombre, direccion, telefono, is_deleted) VALUES (?, ?, ?, ?, ?, 0)");
$stmt->bind_param("sssss", $N_afiliacion, $Cid, $nombre, $direccion, $telefono);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true, 
        "cliente" => [
            "N_afiliacion" => $N_afiliacion,
            "Cid" => $Cid,
            "nombre" => $nombre,
            "direccion" => $direccion,
            "telefono" => $telefono
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al registrar: " . $conex->error]);
}

$stmt->close();
$conex->close();
?>
