<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true) ?: [];
}

$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $stmt = $conex->prepare("
                    SELECT p.id, p.nombre, p.descripcion, p.categoria, p.diseño, p.imagen, p.talla, p.stock, 
                           p.metros_de_tela, p.tipo_tela, p.tipo_tela_id, p.fecha_creacion, p.material_comprado_id, p.codigo, p.orden_id, p.activo,
                           ct.tipo_tela, cm.nombre_materia as material_comprado_nombre,
                           o.id as orden_id, o.nombre as orden_nombre,
                           CONCAT('PR', LPAD(p.id, 6, '0')) AS codigo
                    FROM productos p
                    LEFT JOIN compras_telas ct ON p.tipo_tela_id = ct.id
                    LEFT JOIN compras_material cm ON p.material_comprado_id = cm.id
                    LEFT JOIN ordenes o ON p.orden_id = o.id
                    WHERE p.id = ? AND p.activo = 1");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $producto = $res->fetch_assoc();

                if ($producto) {
                    $producto['diseno'] = $producto['diseño'] ?? '';
                    unset($producto['diseño']);
                    $producto['imagen'] = !empty($producto['imagen']) ? '../uploads/' . $producto['imagen'] : null;
                    echo json_encode(['status' => 'success', 'data' => [$producto]]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado']);
                }
                $stmt->close();
                exit;
            }

            // 🎯 LISTA COMPLETA - SOLO PRODUCTOS ACTIVOS
            $res = $conex->query("
                SELECT p.id, p.nombre, p.descripcion, p.categoria, p.diseño, p.imagen, p.talla, p.stock, 
                       p.metros_de_tela, p.tipo_tela_id, p.fecha_creacion, p.material_comprado_id, p.codigo, p.orden_id,
                       ct.tipo_tela, cm.nombre_materia as material_comprado_nombre,
                       o.id as orden_id, o.nombre as orden_nombre,
                       CONCAT('PR', LPAD(p.id, 6, '0')) AS codigo_display
                FROM productos p
                LEFT JOIN compras_telas ct ON p.tipo_tela_id = ct.id
                LEFT JOIN compras_material cm ON p.material_comprado_id = cm.id
                LEFT JOIN ordenes o ON p.orden_id = o.id
                WHERE p.activo = 1
                ORDER BY p.id DESC");
            
            $data = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $row['diseno'] = $row['diseño'] ?? '';
                    unset($row['diseño']);
                    $row['imagen'] = !empty($row['imagen']) ? '../uploads/' . $row['imagen'] : null;
                    $data[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'POST':
            $isEdit = isset($_POST['productoId']) && is_numeric($_POST['productoId']);
            $id = $isEdit ? intval($_POST['productoId']) : null;

            $nombre = trim($_POST['nombreProducto'] ?? '');
            $descripcion = trim($_POST['descripcionProducto'] ?? '');
            $categoria = trim($_POST['categoriaProducto'] ?? '');
            $diseno = trim($_POST['disenoProducto'] ?? '');
            $talla = trim($_POST['tallaProducto'] ?? null);
            $stock = isset($_POST['stockProducto']) ? intval($_POST['stockProducto']) : 0;
            $tipo_tela_id = isset($_POST['tipo_tela_id']) && is_numeric($_POST['tipo_tela_id']) ? intval($_POST['tipo_tela_id']) : null;
            $materialCompradoId = isset($_POST['materialCompradoId']) && is_numeric($_POST['materialCompradoId']) ? intval($_POST['materialCompradoId']) : null;
            $cantidadMaterial = isset($_POST['cantidadMaterialInput']) ? floatval($_POST['cantidadMaterialInput']) : 0;
            $orden_id = isset($_POST['orden_id']) && is_numeric($_POST['orden_id']) ? intval($_POST['orden_id']) : null;

            if (empty($nombre) || empty($categoria) || empty($diseno)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
                exit;
            }

            if ($stock < 1 || $stock > 99999) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Stock 1-99,999']);
                exit;
            }

            if (!$isEdit) {
                // NUEVO producto
                if ($materialCompradoId !== null && $cantidadMaterial <= 0) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Cantidad materia inválida']);
                    exit;
                }

                $nombreArchivo = null;
                if (isset($_FILES['imagenProducto']) && $_FILES['imagenProducto']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['imagenProducto']['tmp_name'];
                    $fileName = basename($_FILES['imagenProducto']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = uniqid('prod_', true) . '.' . $fileExtension;
                        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
                        if (!move_uploaded_file($fileTmpPath, $destPath)) {
                            http_response_code(400);
                            echo json_encode(['status' => 'error', 'message' => 'Error imagen']);
                            exit;
                        }
                        $nombreArchivo = $newFileName;
                    } else {
                        http_response_code(400);
                        echo json_encode(['status' => 'error', 'message' => 'Formato imagen inválido']);
                        exit;
                    }
                }

                $METROS_POR_CATEGORIA = [
                    'Franelas' => 1.00, 'Camisas Columbia' => 1.60, 'Jerseys' => 1.40,
                    'Pantalones' => 0.70, 'Uniforme deportivo' => 2.00, 
                    'Producto Distribucion' => 1.00, 'Otro' => 1.00
                ];
                $metrosUnit = $METROS_POR_CATEGORIA[$categoria] ?? 1.00;
                $metrosRequeridos = $stock * $metrosUnit;

                $conex->begin_transaction();

                if ($tipo_tela_id) {
                    $stmtTela = $conex->prepare("SELECT metros FROM compras_telas WHERE id = ?");
                    $stmtTela->bind_param("i", $tipo_tela_id);
                    $stmtTela->execute();
                    $stmtTela->bind_result($metrosDisponibles);
                    if (!$stmtTela->fetch() || $metrosRequeridos > floatval($metrosDisponibles)) {
                        $stmtTela->close();
                        throw new Exception('Tela insuficiente');
                    }
                    $stmtTela->close();
                    $stmtDesc = $conex->prepare("UPDATE compras_telas SET metros = metros - ? WHERE id = ?");
                    $stmtDesc->bind_param("di", $metrosRequeridos, $tipo_tela_id);
                    $stmtDesc->execute();
                    $stmtDesc->close();
                }

                if ($materialCompradoId !== null) {
                    $stmtStock = $conex->prepare("SELECT stock FROM compras_material WHERE id = ?");
                    $stmtStock->bind_param("i", $materialCompradoId);
                    $stmtStock->execute();
                    $stmtStock->bind_result($stockDisponible);
                    if (!$stmtStock->fetch() || $cantidadMaterial > $stockDisponible) {
                        $stmtStock->close();
                        throw new Exception('Materia insuficiente');
                    }
                    $stmtStock->close();
                    $stmtUseMat = $conex->prepare("UPDATE compras_material SET stock = stock - ? WHERE id = ?");
                    $stmtUseMat->bind_param("di", $cantidadMaterial, $materialCompradoId);
                    $stmtUseMat->execute();
                    $stmtUseMat->close();
                }

                // 🎯 FIX LÍNEA 180 - 13 parámetros = 13 tipos
                $stmt = $conex->prepare("INSERT INTO productos (nombre, descripcion, categoria, diseño, imagen, talla, stock, metros_de_tela, tipo_tela_id, tipo_tela, material_comprado_id, orden_id, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->bind_param("sssssdidissi", $nombre, $descripcion, $categoria, $diseno, $nombreArchivo, $talla, $stock, $metrosRequeridos, $tipo_tela_id, $categoria, $materialCompradoId, $orden_id);
                $stmt->execute();
                $nuevoId = $conex->insert_id;
                $stmt->close();

                $codigoGenerado = 'PR' . str_pad($nuevoId, 6, '0', STR_PAD_LEFT);
                $stmtCodigo = $conex->prepare("UPDATE productos SET codigo = ? WHERE id = ?");
                $stmtCodigo->bind_param("si", $codigoGenerado, $nuevoId);
                $stmtCodigo->execute();
                $stmtCodigo->close();

                $conex->commit();
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Producto registrado',
                    'codigo' => $codigoGenerado
                ]);
            } else {
                // EDITAR
                $stmtOld = $conex->prepare("SELECT metros_de_tela FROM productos WHERE id = ? AND activo = 1");
                $stmtOld->bind_param("i", $id);
                $stmtOld->execute();
                $stmtOld->bind_result($metrosAntiguos);
                $stmtOld->fetch();
                $stmtOld->close();

                $nombreArchivo = null;
                if (isset($_FILES['imagenProducto']) && $_FILES['imagenProducto']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['imagenProducto']['tmp_name'];
                    $fileName = basename($_FILES['imagenProducto']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = uniqid('prod_', true) . '.' . $fileExtension;
                        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $nombreArchivo = $newFileName;
                        }
                    }
                }

                if ($nombreArchivo) {
                    $stmt = $conex->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria=?, diseño=?, imagen=?, talla=?, stock=?, metros_de_tela=? WHERE id=? AND activo=1");
                    $stmt->bind_param("ssssssidi", $nombre, $descripcion, $categoria, $diseno, $nombreArchivo, $talla, $stock, $metrosAntiguos, $id);
                } else {
                    $stmt = $conex->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria=?, diseño=?, talla=?, stock=?, metros_de_tela=? WHERE id=? AND activo=1");
                    $stmt->bind_param("sssssidi", $nombre, $descripcion, $categoria, $diseno, $talla, $stock, $metrosAntiguos, $id);
                }

                if (!$stmt->execute()) {
                    throw new Exception('Error al actualizar');
                }
                $stmt->close();

                echo json_encode(['status' => 'success', 'message' => 'Producto actualizado']);
            }
            break;

        case 'DELETE':
            $input = getJsonInput();
            if (!isset($input['id']) || !is_numeric($input['id'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
                exit;
            }
            $id = intval($input['id']);

            // 🎯 SOFT DELETE - Solo ocultar (NO ELIMINAR)
            $stmt = $conex->prepare("UPDATE productos SET activo = 0 WHERE id = ? AND activo = 1");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Producto eliminado']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No encontrado o ya eliminado']);
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    }
} catch (Exception $ex) {
    if (isset($conex) && $conex->errno) $conex->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
}
?>
