<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// DEBUG
file_put_contents('debug_produccion.log', date('H:i:s') . " POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

if (!isset($_POST['orden_id']) || !isset($_POST['materia_prima'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

$orden_id = intval($_POST['orden_id']);
$materia_prima = json_decode($_POST['materia_prima'], true);

if (!$materia_prima || !is_array($materia_prima)) {
    echo json_encode(['success' => false, 'error' => 'Error en materiales']);
    exit;
}

include '../database/connect_db.php';

// 1. OBTENER DATOS ORDEN (SOLO CAMPOS EXISTENTES)
$query = "SELECT tipo_tela, metros_totales, 
          (SELECT SUM(cantidad) FROM orden_detalle WHERE orden_id = o.id) as total_piezas 
          FROM ordenes o WHERE id = $orden_id";
$result = mysqli_query($conex, $query);
$orden = mysqli_fetch_assoc($result);

if (!$orden || $orden['total_piezas'] == 0) {
    echo json_encode(['success' => false, 'error' => 'Orden inválida']);
    exit;
}

$total_piezas = intval($orden['total_piezas']);
$metros_tela = floatval($orden['metros_totales']);
$tipo_tela = $orden['tipo_tela'];

file_put_contents('debug_produccion.log', "ORDEN: $total_piezas piezas, $metros_tela m $tipo_tela\n", FILE_APPEND);

//  TRANSACCIÓN
mysqli_autocommit($conex, FALSE);

// TELA
if ($metros_tela > 0) {
    $query_tela = "UPDATE compras_telas SET saldo = GREATEST(0, saldo - $metros_tela) 
                   WHERE tipo_tela = '$tipo_tela' AND saldo > 0";
    mysqli_query($conex, $query_tela);
}

//  MATERIA PRIMA (consumo simplificado)
foreach ($materia_prima as $mp_id) {
    $mp_id = intval($mp_id);
    $consumo = $total_piezas * 1; // 1 unidad por prenda
    $query_mp = "UPDATE materia_prima SET stock = GREATEST(0, stock - $consumo) 
                 WHERE id = $mp_id AND stock > 0";
    mysqli_query($conex, $query_mp);
}

// CAMBIAR ESTADO ORDEN
$query_orden = "UPDATE ordenes SET estado = 'produccion' WHERE id = $orden_id";
mysqli_query($conex, $query_orden);

// DETALLES
$query_detalle = "UPDATE orden_detalle SET estado = 'produccion' WHERE orden_id = $orden_id";
mysqli_query($conex, $query_detalle);

// PRODUCTOS TERMINADOS (SOLO CAMPOS BÁSICOS)
$query_productos = "INSERT INTO productos_terminados (orden_id, nombre, imagen, color, talla, cantidad, tipo_tela, tecnica, estado)
                    SELECT o.id, o.nombre, o.imagen, o.color, od.talla, od.cantidad, o.tipo_tela, o.tecnica, 'disponible'
                    FROM ordenes o JOIN orden_detalle od ON o.id = od.orden_id 
                    WHERE o.id = $orden_id";
mysqli_query($conex, $query_productos);

$productos_creados = mysqli_affected_rows($conex);

// COMMIT
mysqli_commit($conex);
mysqli_autocommit($conex, TRUE);

file_put_contents('debug_produccion.log', "✅ OK: $total_piezas prendas, $productos_creados productos\n\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => "✅ Producción OK: $total_piezas prendas $tipo_tela",
    'detalle' => ['orden_id' => $orden_id, 'prendas' => $total_piezas]
]);
?>
