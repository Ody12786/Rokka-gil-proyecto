<?php
session_start();
include("../database/connect_db.php");

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');
$conex = $conex ?? null;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conex);
        break;
    case 'POST':
        handlePost($conex);
        break;
    case 'PUT':
        handlePut($conex);
        break;
    case 'DELETE':
        handleDelete($conex);
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
        break;
}

// Obtener productos distribución sin proveedor (sin join)
function handleGet($conex) {
    try {
        $sqlProdDistrib = "
            SELECT 
                id, nombre, descripcion,
                cantidad, stock, tipo_tela,
                precio_unitario, fecha_adquisicion,
                condicion_pago,
                'Producto Distribución' AS categoria
            FROM productos_distribucion
        ";

        $resultProdDistrib = $conex->query($sqlProdDistrib);
        if (!$resultProdDistrib) throw new Exception($conex->error);

        $productosDistribucion = [];
        while ($row = $resultProdDistrib->fetch_assoc()) {
            $productosDistribucion[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $productosDistribucion]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al obtener datos: ' . $e->getMessage()]);
    }
}

// Crear nuevo producto distribución
function handlePost($conex) {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['nombre', 'cantidad', 'precio_unitario', 'fecha_adquisicion', 'condicion_pago', 'proveedor_id', 'tipo_tela', 'stock'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "El campo {$field} es obligatorio"]);
            exit;
        }
    }

    $nombre = $data['nombre'];
    $descripcion = $data['descripcion'] ?? '';
    $cantidad = floatval($data['cantidad']);
    $stock = intval($data['stock']);
    $precio_unitario = floatval($data['precio_unitario']);
    $fecha_adquisicion = $data['fecha_adquisicion'];
    $condicion_pago = $data['condicion_pago'];
    $proveedor_id = intval($data['proveedor_id']); // guardarlo si está en la tabla, aunque no se use aquí para mostrar
    $tipo_tela = $data['tipo_tela'];

    $stmt = $conex->prepare("INSERT INTO productos_distribucion (nombre, descripcion, cantidad, stock, precio_unitario, fecha_adquisicion, condicion_pago, proveedor_id, tipo_tela) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiidssis", $nombre, $descripcion, $cantidad, $stock, $precio_unitario, $fecha_adquisicion, $condicion_pago, $proveedor_id, $tipo_tela);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Producto para distribución registrado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar: ' . $stmt->error]);
    }
    $stmt->close();
}

// Actualizar producto distribución
function handlePut($conex) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID es obligatorio para actualizar']);
        exit;
    }

    $id = intval($data['id']);
    $nombre = $data['nombre'] ?? '';
    $descripcion = $data['descripcion'] ?? '';
    $cantidad = floatval($data['cantidad'] ?? 0);
    $stock = intval($data['stock'] ?? 0);
    $precio_unitario = floatval($data['precio_unitario'] ?? 0);
    $fecha_adquisicion = $data['fecha_adquisicion'] ?? '';
    $condicion_pago = $data['condicion_pago'] ?? '';
    $proveedor_id = intval($data['proveedor_id'] ?? 0);
    $tipo_tela = $data['tipo_tela'] ?? '';

    $stmt = $conex->prepare("UPDATE productos_distribucion SET nombre=?, descripcion=?, cantidad=?, stock=?, precio_unitario=?, fecha_adquisicion=?, condicion_pago=?, proveedor_id=?, tipo_tela=? WHERE id=?");
    $stmt->bind_param("ssiidssisi", $nombre, $descripcion, $cantidad, $stock, $precio_unitario, $fecha_adquisicion, $condicion_pago, $proveedor_id, $tipo_tela, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Producto para distribución actualizado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $stmt->error]);
    }
    $stmt->close();
}

// Eliminar producto distribución
function handleDelete($conex) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID es obligatorio para eliminar']);
        exit;
    }

    $id = intval($data['id']);

    $stmt = $conex->prepare("DELETE FROM productos_distribucion WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Producto eliminado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
