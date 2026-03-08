<?php
session_start();
header('Content-Type: application/json');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}
// CSRF validation removed per request

$method = $_SERVER['REQUEST_METHOD'];

function getJsonInput() {
    $data = json_decode(file_get_contents("php://input"), true);
    return $data ? $data : [];
}

switch ($method) {
    case 'GET':
        $sql = "SELECT c.id, c.fecha_adquisicion, c.tipo_tela, c.metros, c.condicion_pago, c.precio_unitario, c.total, c.saldo, c.estado_pago,
                       p.id AS proveedor_id, p.nombres AS proveedor_nombre
                FROM compras_telas c
                LEFT JOIN proveedor p ON c.proveedor_id = p.id
                ORDER BY c.fecha_adquisicion DESC, c.id DESC";

        $res = $conex->query($sql);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'id' => intval($row['id']),
                    'fecha_adquisicion' => $row['fecha_adquisicion'],
                    'tipo_tela' => $row['tipo_tela'],
                    'metros' => floatval($row['metros']),
                    'condicion_pago' => $row['condicion_pago'],
                    'precio_unitario' => floatval($row['precio_unitario']),
                    'total' => floatval($row['total']),
                    'saldo' => floatval($row['saldo']),
                    'estado_pago' => $row['estado_pago'],
                    'proveedor_id' => intval($row['proveedor_id']),
                    'proveedor_nombre' => $row['proveedor_nombre'] ?? 'Sin asignar'
                ];
            }
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;

    case 'POST':
        $input = getJsonInput();
        $fecha = $input['fecha_adquisicion'] ?? null;
        $tipo = $input['tipo_tela'] ?? null;
        $metros = $input['metros'] ?? null;
        $condicion = $input['condicion_pago'] ?? null;
        $proveedor_id = $input['proveedor_id'] ?? null;
        $precio_unitario = $input['precio_unitario'] ?? null;

        if (
            empty($fecha) ||
            empty($tipo) ||
            !is_numeric($metros) || floatval($metros) <= 0 ||
            empty($condicion) ||
            !is_numeric($proveedor_id) || intval($proveedor_id) <= 0 ||
            !is_numeric($precio_unitario) || floatval($precio_unitario) < 0
        ) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o inválidos (incluya proveedor y precio unitario)']);
            exit;
        }

        $metros = floatval($metros);
        $proveedor_id = intval($proveedor_id);
        $precio_unitario = floatval($precio_unitario);

        $total = $metros * $precio_unitario;

if (strtolower($condicion) === 'contado' || strtolower($condicion) === 'al contado') {
    $saldo = 0;
    $estado_pago = 'Pagada';
} else {
    $saldo = $total;
    $estado_pago = 'Pendiente';
}


        $stmt = $conex->prepare("INSERT INTO compras_telas (fecha_adquisicion, tipo_tela, metros, condicion_pago, proveedor_id, precio_unitario, total, saldo, estado_pago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            // tipos: s (string), s (string), d (double), s (string), i (int), d (double), d (double), d (double), s (string)
            $stmt->bind_param("ssdsiddss", $fecha, $tipo, $metros, $condicion, $proveedor_id, $precio_unitario, $total, $saldo, $estado_pago);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Compra registrada']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al registrar compra: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $conex->error]);
        }
        exit;

    case 'PUT':
        $input = getJsonInput();
        $id = $input['id'] ?? null;
        $fecha = $input['fecha_adquisicion'] ?? null;
        $tipo = $input['tipo_tela'] ?? null;
        $metros = $input['metros'] ?? null;
        $condicion = $input['condicion_pago'] ?? null;
        $proveedor_id = $input['proveedor_id'] ?? null;
        $precio_unitario = $input['precio_unitario'] ?? null;

        if (
            !isset($id) || !is_numeric($id) ||
            empty($fecha) ||
            empty($tipo) ||
            !isset($metros) || !is_numeric($metros) || floatval($metros) <= 0 ||
            empty($condicion) ||
            !isset($proveedor_id) || !is_numeric($proveedor_id) || intval($proveedor_id) <= 0 ||
            !isset($precio_unitario) || !is_numeric($precio_unitario) || floatval($precio_unitario) < 0
        ) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o inválidos (incluya proveedor y precio unitario)']);
            exit;
        }

        $id = intval($id);
        $metros = floatval($metros);
        $proveedor_id = intval($proveedor_id);
        $precio_unitario = floatval($precio_unitario);
        $total = $metros * $precio_unitario;

        if (strtolower($condicion) === 'contado' || strtolower($condicion) === 'al contado') {
            $saldo = 0;
            $estado_pago = 'Pagada';
        } else {
            $saldo = $total;
            $estado_pago = 'Pendiente';
        }
        

        $stmt = $conex->prepare("UPDATE compras_telas SET fecha_adquisicion=?, tipo_tela=?, metros=?, condicion_pago=?, proveedor_id=?, precio_unitario=?, total=?, saldo=?, estado_pago=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ssdsiddssi", $fecha, $tipo, $metros, $condicion, $proveedor_id, $precio_unitario, $total, $saldo, $estado_pago, $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Compra actualizada']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la compra: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $conex->error]);
        }
        exit;

    case 'DELETE':
        $input = getJsonInput();
        $id = $input['id'] ?? null;

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID no especificado']);
            exit;
        }

        $id = intval($id);

        $stmt = $conex->prepare("DELETE FROM compras_telas WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Compra eliminada']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $conex->error]);
        }
        exit;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
        exit;
}
?>