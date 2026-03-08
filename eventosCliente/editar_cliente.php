<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$N_afiliacion_original = trim($_POST['N_afiliacion_original'] ?? '');
$Cid = trim($_POST['Cid'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

if (empty($N_afiliacion_original) || empty($Cid) || empty($nombre)) {
    http_response_code(400);
    echo json_encode(["error" => "Debe llenar todos los campos"]);
    exit;
}

// ✅ Verificar duplicados (tabla cliente + bind_param corregido)
$stmt = $conex->prepare("SELECT N_afiliacion FROM cliente WHERE Cid = ? AND N_afiliacion != ? AND is_deleted = 0");
$stmt->bind_param("ss", $Cid, $N_afiliacion_original);  // ✅ "ss" NO "si"
$stmt->execute();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(["error" => "La cédula $Cid ya está registrada en otro cliente activo"]);
    exit;
}
$stmt->close();

// ✅ UPDATE tabla cliente
$stmt = $conex->prepare("UPDATE cliente SET Cid = ?, nombre = ?, direccion = ?, telefono = ? WHERE N_afiliacion = ? AND is_deleted = 0");
$stmt->bind_param("sssss", $Cid, $nombre, $direccion, $telefono, $N_afiliacion_original);  // ✅ "sss" NO "ssi"

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al actualizar: " . $conex->error]);
}

$stmt->close();
$conex->close();
?>
