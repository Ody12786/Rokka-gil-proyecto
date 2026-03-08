<?php
session_start();
include("../database/connect_db.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$usuarioId = $_SESSION['usuario_id'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener una compra específica con sus detalles
                $id = intval($_GET['id']);
                $sql = "SELECT ct.*, p.nombres AS nombre_proveedor 
                        FROM compras_telas ct
                        LEFT JOIN proveedor p ON ct.proveedor_id = p.id
                        WHERE ct.id = ?";
                $stmt = mysqli_prepare($conex, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $compra = mysqli_fetch_assoc($result);
                
                if ($compra) {
                    // Obtener detalles
                    $sqlDetalles = "SELECT * FROM detalle_compras_telas WHERE compra_tela_id = ?";
                    $stmtDetalles = mysqli_prepare($conex, $sqlDetalles);
                    mysqli_stmt_bind_param($stmtDetalles, "i", $id);
                    mysqli_stmt_execute($stmtDetalles);
                    $resultDetalles = mysqli_stmt_get_result($stmtDetalles);
                    
                    $detalles = [];
                    while ($row = mysqli_fetch_assoc($resultDetalles)) {
                        $detalles[] = $row;
                    }
                    $compra['detalles'] = $detalles;
                    
                    echo json_encode(['status' => 'success', 'data' => $compra]);
                } else {
                    throw new Exception('Compra no encontrada');
                }
            } else {
                // Obtener todas las compras con resumen
                $sql = "SELECT ct.*, p.nombres AS nombre_proveedor,
                               (SELECT COUNT(*) FROM detalle_compras_telas WHERE compra_tela_id = ct.id) as num_telas
                        FROM compras_telas ct
                        LEFT JOIN proveedor p ON ct.proveedor_id = p.id
                        ORDER BY ct.fecha_adquisicion DESC";
                
                $result = mysqli_query($conex, $sql);
                if (!$result) throw new Exception('Error en la base de datos: ' . mysqli_error($conex));

                $data = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                echo json_encode(['status' => 'success', 'data' => $data]);
            }
            break;

        case 'POST':
            // Validar datos obligatorios
            if (empty($input['proveedor_id']) || empty($input['fecha_adquisicion']) || 
                empty($input['condicion_pago']) || empty($input['detalles']) || 
                !is_array($input['detalles']) || count($input['detalles']) === 0) {
                throw new Exception('Faltan datos obligatorios para crear la compra');
            }

            $proveedorId = intval($input['proveedor_id']);
            $fechaAdquisicion = mysqli_real_escape_string($conex, $input['fecha_adquisicion']);
            $numeroFactura = mysqli_real_escape_string($conex, trim($input['numero_factura'] ?? ''));
            $condicionPago = mysqli_real_escape_string($conex, $input['condicion_pago']);
            $observaciones = mysqli_real_escape_string($conex, trim($input['observaciones'] ?? ''));
            
            // Calcular total
            $total = 0;
            $detalles = $input['detalles'];
            
            foreach ($detalles as $detalle) {
                if (empty($detalle['tipo_tela']) || !isset($detalle['metros']) || !isset($detalle['precio_unitario'])) {
                    throw new Exception('Cada detalle debe incluir tipo de tela, metros y precio unitario');
                }
                $metros = floatval($detalle['metros']);
                $precio = floatval($detalle['precio_unitario']);
                if ($metros <= 0 || $precio <= 0) {
                    throw new Exception('Metros y precio deben ser mayores a cero');
                }
                $total += $metros * $precio;
            }
            
            $saldo = ($condicionPago === 'Crédito') ? $total : 0;
            $estadoPago = ($condicionPago === 'Contado') ? 'Pagada' : 'Pendiente';

            // Iniciar transacción
            mysqli_begin_transaction($conex);

            try {
                // Insertar compra principal
                $sqlInsert = "INSERT INTO compras_telas 
                             (fecha_adquisicion, numero_factura, tipo_tela, proveedor_id, metros, total, 
                              precio_unitario, condicion_pago, saldo, estado_pago) 
                             VALUES (?, ?, 'Multiple', ?, 0, ?, 0, ?, ?, ?)";
                
                $stmtInsert = mysqli_prepare($conex, $sqlInsert);
                mysqli_stmt_bind_param($stmtInsert, "ssisdss", 
                    $fechaAdquisicion, 
                    $numeroFactura,
                    $proveedorId,
                    $total,
                    $condicionPago,
                    $saldo,
                    $estadoPago
                );
                
                if (!mysqli_stmt_execute($stmtInsert)) {
                    throw new Exception('Error al guardar la compra: ' . mysqli_stmt_error($stmtInsert));
                }
                
                $compraId = mysqli_insert_id($conex);
                
                // Insertar detalles
                foreach ($detalles as $detalle) {
                    $tipoTela = mysqli_real_escape_string($conex, trim($detalle['tipo_tela']));
                    $metros = floatval($detalle['metros']);
                    $precioUnitario = floatval($detalle['precio_unitario']);
                    
                    $sqlDetalle = "INSERT INTO detalle_compras_telas 
                                  (compra_tela_id, tipo_tela, metros, precio_unitario) 
                                  VALUES (?, ?, ?, ?)";
                    $stmtDetalle = mysqli_prepare($conex, $sqlDetalle);
                    mysqli_stmt_bind_param($stmtDetalle, "isdd", $compraId, $tipoTela, $metros, $precioUnitario);
                    
                    if (!mysqli_stmt_execute($stmtDetalle)) {
                        throw new Exception('Error al guardar detalle: ' . mysqli_stmt_error($stmtDetalle));
                    }
                }
                
                mysqli_commit($conex);
                echo json_encode(['status' => 'success', 'message' => 'Compra registrada correctamente', 'id' => $compraId]);
                
            } catch (Exception $e) {
                mysqli_rollback($conex);
                throw $e;
            }
            break;

        case 'DELETE':
            if (empty($input['id'])) {
                throw new Exception('ID de compra no especificado');
            }
            
            $id = intval($input['id']);
            
            // Verificar existencia
            $sqlCheck = "SELECT id FROM compras_telas WHERE id = ?";
            $stmtCheck = mysqli_prepare($conex, $sqlCheck);
            mysqli_stmt_bind_param($stmtCheck, "i", $id);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            
            if (mysqli_num_rows($resultCheck) === 0) {
                throw new Exception('Compra no encontrada');
            }
            
            // Eliminar (los detalles se eliminan en cascada por FK)
            $sqlDel = "DELETE FROM compras_telas WHERE id = ?";
            $stmtDel = mysqli_prepare($conex, $sqlDel);
            mysqli_stmt_bind_param($stmtDel, "i", $id);
            
            if (!mysqli_stmt_execute($stmtDel)) {
                throw new Exception('Error al eliminar compra');
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Compra eliminada correctamente']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $ex) {
    if (isset($conex) && !mysqli_commit($conex)) {
        mysqli_rollback($conex);
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
}
?>