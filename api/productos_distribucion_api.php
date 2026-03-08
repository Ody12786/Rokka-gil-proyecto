<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");
// CSRF validation removed per request

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}
// CSRF validation removed per request

$method = $_SERVER['REQUEST_METHOD'];

function getJsonInput() {
    $data = json_decode(file_get_contents("php://input"), true);
    return $data ?: [];
}

$uploadDir = __DIR__ . '/../uploads/distribucion';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $stmt = $conex->prepare("SELECT id, codigo, nombre, descripcion, proveedor_id, fecha_adquisicion, cantidad, precio_unitario, condicion_pago, usuario_id, creado_en, stock, tipo_tela, categoria, diseno, imagen, caracteristicas FROM productos_distribucion WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $producto = $res->fetch_assoc();
                if ($producto) {
                    $producto['imagen'] = !empty($producto['imagen']) ? '../uploads/distribucion/' . $producto['imagen'] : null;
                    echo json_encode(['status' => 'success', 'data' => [$producto]]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Producto de distribución no encontrado']);
                }
                $stmt->close();
            } else {
                $res = $conex->query("SELECT id, codigo, nombre, descripcion, proveedor_id, fecha_adquisicion, cantidad, precio_unitario, condicion_pago, usuario_id, creado_en, stock, tipo_tela, categoria, diseno, imagen, caracteristicas FROM productos_distribucion ORDER BY creado_en DESC");
                $data = [];
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $row['imagen'] = !empty($row['imagen']) ? '../uploads/distribucion/' . $row['imagen'] : null;
                        $data[] = $row;
                    }
                }
                echo json_encode(['status' => 'success', 'data' => $data]);
            }
            break;

        case 'POST':
            $isEdit = isset($_POST['id']) && is_numeric($_POST['id']);
            $id = $isEdit ? intval($_POST['id']) : null;

            // Sanitización básica
            $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES);
            $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''), ENT_QUOTES);
            $proveedor_id = isset($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
            $fecha_adquisicion = trim($_POST['fecha_adquisicion'] ?? '');
            $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
            $precio_unitario = isset($_POST['precio_unitario']) ? floatval($_POST['precio_unitario']) : 0.0;
            $condicion_pago = htmlspecialchars(trim($_POST['condicion_pago'] ?? ''), ENT_QUOTES);
            $tipo_tela = htmlspecialchars(trim($_POST['tipo_tela'] ?? ''), ENT_QUOTES);
            $categoria = htmlspecialchars(trim($_POST['categoria'] ?? ''), ENT_QUOTES);
            $diseno = htmlspecialchars(trim($_POST['diseno'] ?? ''), ENT_QUOTES);
            $usuario_id = $_SESSION['usuario_id'];
            $stock = $cantidad;

            if (empty($nombre) || !$proveedor_id || empty($fecha_adquisicion) || $cantidad <= 0 || $precio_unitario <= 0 || empty($categoria) || empty($diseno)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o inválidos']);
                exit;
            }

            $conex->begin_transaction();
            $nombreArchivo = null;

            // Validación y carga de imagen
            if (isset($_FILES['imagenProducto']) && $_FILES['imagenProducto']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['imagenProducto']['tmp_name'];
                $mime = mime_content_type($tmpName);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                    throw new Exception('Tipo de imagen no permitido');
                }
                $ext = pathinfo($_FILES['imagenProducto']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = uniqid() . '.' . $ext;
                $destino = $uploadDir . '/' . $nombreArchivo;
                if (!move_uploaded_file($tmpName, $destino)) {
                    throw new Exception('Error al subir la imagen');
                }
            }

            if ($isEdit) {
                if ($nombreArchivo) {
                    $stmt = $conex->prepare("UPDATE productos_distribucion SET nombre = ?, descripcion = ?, proveedor_id = ?, fecha_adquisicion = ?, cantidad = ?, precio_unitario = ?, condicion_pago = ?, tipo_tela = ?, categoria = ?, diseno = ?, stock = ?, imagen = ? WHERE id = ?");
                    $stmt->bind_param("ssissdssssisi", $nombre, $descripcion, $proveedor_id, $fecha_adquisicion, $cantidad, $precio_unitario, $condicion_pago, $tipo_tela, $categoria, $diseno, $stock, $nombreArchivo, $id);
                } else {
                    $stmt = $conex->prepare("UPDATE productos_distribucion SET nombre = ?, descripcion = ?, proveedor_id = ?, fecha_adquisicion = ?, cantidad = ?, precio_unitario = ?, condicion_pago = ?, tipo_tela = ?, categoria = ?, diseno = ?, stock = ? WHERE id = ?");
                    $stmt->bind_param("ssissdssssi", $nombre, $descripcion, $proveedor_id, $fecha_adquisicion, $cantidad, $precio_unitario, $condicion_pago, $tipo_tela, $categoria, $diseno, $stock, $id);
                }
                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar producto: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                $stmt = $conex->prepare("INSERT INTO productos_distribucion (nombre, descripcion, proveedor_id, fecha_adquisicion, cantidad, precio_unitario, condicion_pago, tipo_tela, categoria, diseno, usuario_id, stock, imagen, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssissdsssssis", $nombre, $descripcion, $proveedor_id, $fecha_adquisicion, $cantidad, $precio_unitario, $condicion_pago, $tipo_tela, $categoria, $diseno, $usuario_id, $stock, $nombreArchivo);
                if (!$stmt->execute()) {
                    throw new Exception('Error al registrar producto: ' . $stmt->error);
                }
                $nuevoId = $conex->insert_id;
                $stmt->close();

                // Generar código único y guardarlo
                $codigoGenerado = 'PD-' . str_pad($nuevoId, 6, '0', STR_PAD_LEFT);
                error_log("Generando código: $codigoGenerado para ID: $nuevoId");  // Logging para debug
                
                $stmtUpdate = $conex->prepare("UPDATE productos_distribucion SET codigo = ? WHERE id = ?");
                $stmtUpdate->bind_param("si", $codigoGenerado, $nuevoId);
                if (!$stmtUpdate->execute()) {
                    throw new Exception('Error al guardar código: ' . $stmtUpdate->error);
                }
                if ($stmtUpdate->affected_rows === 0) {
                    throw new Exception('No se actualizó el código (posible fila no encontrada)');
                }
                $stmtUpdate->close();
            }

            $conex->commit();
            echo json_encode(['status' => 'success', 'message' => $isEdit ? 'Producto actualizado' : 'Producto registrado', 'codigo' => $codigoGenerado ?? null]);
            break;

        case 'DELETE':
            $input = getJsonInput();
            if (!isset($input['id']) || !is_numeric($input['id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
                exit;
            }
            $id = intval($input['id']);
            $stmt = $conex->prepare("DELETE FROM productos_distribucion WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Producto eliminado']);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado o no eliminado']);
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $ex) {
    if ($conex && $conex->errno) $conex->rollback();
    error_log("Error en API productos_distribucion: " . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor']);
}
?>
