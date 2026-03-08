<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['orden_id'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

$orden_id = (int)$_POST['orden_id'];
include '../database/connect_db.php';

// TRANSACCIÓN
mysqli_autocommit($conex, FALSE);

try {
    // ELIMINAR orden_detalle
    $query_det = "DELETE FROM orden_detalle WHERE orden_id = $orden_id";
    mysqli_query($conex, $query_det);
    
    //  ELIMINAR orden
    $query_orden = "DELETE FROM ordenes WHERE id = $orden_id AND estado = 'registrada'";
    mysqli_query($conex, $query_orden);
    
    $eliminadas = mysqli_affected_rows($conex);
    
    if ($eliminadas > 0) {
        mysqli_commit($conex);
        echo json_encode([
            'success' => true, 
            'message' => "✅ Orden #$orden_id cancelada. Eliminados: $eliminadas registros"
        ]);
    } else {
        throw new Exception('Orden no encontrada o ya procesada');
    }
    
} catch (Exception $e) {
    mysqli_rollback($conex);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    mysqli_autocommit($conex, TRUE);
}
?>
