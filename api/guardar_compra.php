<?php
session_start();
include("../database/connect_db.php");
// CSRF validation removed per request

// Configuración para mostrar errores en desarrollo (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar sesión o permisos según tu sistema
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Recibir datos (ejemplo desde un formulario POST)
$proveedor_id = intval($_POST['proveedor_id'] ?? 0);
$fecha_adquisicion = $_POST['fecha_adquisicion'] ?? null;
$precio_unidad = floatval($_POST['precio_unidad'] ?? 0);
$cantidad_metros = floatval($_POST['cantidad_metros'] ?? 0);

// Validar datos básicos
if ($proveedor_id <= 0) {
    die(json_encode(['status' => 'error', 'message' => 'Proveedor no válido']));
}
if (!$fecha_adquisicion) {
    die(json_encode(['status' => 'error', 'message' => 'Fecha de adquisición no válida']));
}
if ($precio_unidad <= 0) {
    die(json_encode(['status' => 'error', 'message' => 'Precio unidad no válido']));
}
if ($cantidad_metros <= 0) {
    die(json_encode(['status' => 'error', 'message' => 'Cantidad metros no válida']));
}

// Calcular total
$total = $precio_unidad * $cantidad_metros;

// Debug - verificar los valores
error_log("Registrar compra - proveedor_id: $proveedor_id, fecha: $fecha_adquisicion, precio unidad: $precio_unidad, cantidad: $cantidad_metros, total: $total");

// Preparar la consulta de inserción
$sql = "INSERT INTO compras_telas (proveedor_id, fecha_adquisicion, precio_unidad, cantidad_metros, total, saldo, estado_pago) 
        VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";

$stmt = $conex->prepare($sql);
if (!$stmt) {
    die(json_encode(['status' => 'error', 'message' => 'Error en preparar consulta: ' . $conex->error]));
}

$stmt->bind_param('isdddd', $proveedor_id, $fecha_adquisicion, $precio_unidad, $cantidad_metros, $total, $total);

if (!$stmt->execute()) {
    die(json_encode(['status' => 'error', 'message' => 'Error ejecutando consulta: ' . $stmt->error]));
}

// Si llegamos aquí significa que guardó correctamente
echo json_encode(['status' => 'success', 'message' => "Compra registrada correctamente con total: $total"]);
?>