<?php
session_start();
include(__DIR__ . '/../database/connect_db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$nombre = $_GET['nombre'] ?? '';

if (strlen($nombre) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Nombre muy corto para buscar']);
    exit;
}

// Verifica que $conex está definido y es objeto mysqli
if (!isset($conex) || !($conex instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$sql = "SELECT Cid, nombre FROM cliente WHERE nombre LIKE ? LIMIT 10";
$stmt = $conex->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $conex->error,
    ]);
    exit;
}

$likeNombre = "%$nombre%";
$stmt->bind_param('s', $likeNombre);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Incluye campo 'text' para Select2 directamente
    $data[] = [
        'id' => $row['Cid'],
        'nombre' => $row['nombre'],
        'text' => $row['nombre'] . ' (C.I.: ' . $row['Cid'] . ')'
    ];
}

$stmt->close();

echo json_encode(['status' => 'success', 'data' => $data]);
exit;
