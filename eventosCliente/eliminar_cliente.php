<?php
session_start();
include("../database/connect_db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}

$N_afiliacion = $_POST['id'] ?? '';

if (empty($N_afiliacion)) {
    echo json_encode(['success' => false, 'error' => 'ID requerido']);
    exit;
}

try {
    $stmt = $conex->prepare("UPDATE cliente SET is_deleted = 1 WHERE N_afiliacion = ? AND is_deleted = 0");
    $stmt->bind_param("s", $N_afiliacion);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Cliente no encontrado o ya eliminado']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error en actualización']);
    }
     $stmt->close();
    $conex->close(); 
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
