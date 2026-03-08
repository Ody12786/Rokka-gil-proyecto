<?php
session_start();
include("connect_db.php");

header('Content-Type: application/json; charset=utf-8');

// Solo admin puede cerrar sesiones
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

// POST: cerrar sesiones específicas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids_sesiones = json_decode($_POST['ids_sesiones'] ?? '[]', true);
    
    if (empty($ids_sesiones)) {
        echo json_encode(['status' => 'error', 'message' => 'No se seleccionaron sesiones']);
        exit;
    }
    
    $stmt = $conex->prepare("UPDATE usuario_asistente SET estado = 'inactivo' WHERE id = ?");
    $exitos = 0;
    
    foreach ($ids_sesiones as $id_sesion) {
        $stmt->bind_param("i", $id_sesion);
        if ($stmt->execute()) $exitos++;
    }
    
    $stmt->close();
    $conex->close();
    
    echo json_encode([
        'status' => 'success', 
        'message' => "Cerradas $exitos sesiones"
    ]);
    exit;
}

// GET: listar sesiones activas
$stmt = $conex->prepare("
    SELECT ua.*, u.nombre 
    FROM usuario_asistente ua 
    JOIN usuario u ON ua.usuario_id = u.id_rec 
    WHERE ua.estado = 'activo'
    ORDER BY ua.ultima_actividad DESC
");

$stmt->execute();
$result = $stmt->get_result();
$sesiones = [];
while ($row = $result->fetch_assoc()) {
    $sesiones[] = $row;
}

$stmt->close();
$conex->close();

echo json_encode(['sesiones' => $sesiones]);
?>
