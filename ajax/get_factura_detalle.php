<?php
// get_factura_detalle.php
session_start();
header('Content-Type: application/json');
require_once 'connect_db.php';

if (!$conex) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$venta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($venta_id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID de venta no válido']);
    exit;
}

// Obtener datos de la venta
$sql_venta = "SELECT v.*, 
              c.N_afiliacion, c.Cid as cliente_cedula, c.nombre as cliente_nombre,
              u.nombre as usuario_nombre
              FROM ventas v
              LEFT JOIN cliente c ON v.cliente_id = c.N_afiliacion
              LEFT JOIN usuario u ON v.usuario_id = u.id_rec
              WHERE v.id = ?";
$stmt = $conex->prepare($sql_venta);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();

if (!$venta) {
    echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
    exit;
}

// Obtener detalle de productos
$sql_detalle = "SELECT d.*, p.codigo, p.nombre as producto_nombre
                FROM detalle_ventas d
                LEFT JOIN productos p ON d.producto_id = p.id
                WHERE d.venta_id = ?";
$stmt = $conex->prepare($sql_detalle);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$detalle = $stmt->get_result();

$productos = [];
$subtotal = 0;
while ($row = $detalle->fetch_assoc()) {
    $subtotal += $row['subtotal'];
    $productos[] = [
        'codigo' => $row['codigo'] ?? 'N/A',
        'nombre' => $row['producto_nombre'] ?? 'Producto',
        'cantidad' => $row['cantidad'],
        'precio' => $row['precio_unitario'],
        'subtotal' => $row['subtotal']
    ];
}

// Obtener abonos (si es crédito)
$abonos = [];
$total_abonado = 0;
if ($venta['tipo_pago'] === 'Crédito') {
    $sql_abonos = "SELECT a.*, u.nombre as usuario_nombre 
                   FROM abonos a
                   LEFT JOIN usuario u ON a.usuario_id = u.id_rec
                   WHERE a.venta_id = ? 
                   ORDER BY a.fecha_pago DESC";
    $stmt = $conex->prepare($sql_abonos);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $abonos_result = $stmt->get_result();
    
    while ($abono = $abonos_result->fetch_assoc()) {
        $total_abonado += $abono['monto'];
        $abonos[] = [
            'fecha' => date('d/m/Y H:i', strtotime($abono['fecha_pago'])),
            'monto' => $abono['monto'],
            'usuario' => $abono['usuario_nombre'] ?? 'Sistema'
        ];
    }
}

echo json_encode([
    'success' => true,
    'venta' => [
        'id' => str_pad($venta_id, 6, '0', STR_PAD_LEFT),
        'fecha' => date('d/m/Y', strtotime($venta['fecha_venta'])),
        'hora' => date('h:i A', strtotime($venta['fecha_registro'])),
        'cliente_nombre' => $venta['cliente_nombre'] ?? 'Consumidor Final',
        'cliente_cedula' => $venta['cliente_cedula'] ?? 'N/A',
        'vendedor' => $venta['usuario_nombre'] ?? 'Sistema',
        'tipo_pago' => $venta['tipo_pago'],
        'estado_pago' => $venta['estado_pago'],
        'subtotal' => $subtotal,
        'iva' => $venta['total_iva'],
        'total' => $venta['total'],
        'moneda_pago' => $venta['moneda_pago'],
        'tasa_dolar' => $venta['tasa_dolar'],
        'fecha_vencimiento' => $venta['fecha_vencimiento'] ? date('d/m/Y', strtotime($venta['fecha_vencimiento'])) : null,
        'total_abonado' => $total_abonado,
        'saldo_pendiente' => $venta['total'] - $total_abonado
    ],
    'productos' => $productos,
    'abonos' => $abonos
]);
?>