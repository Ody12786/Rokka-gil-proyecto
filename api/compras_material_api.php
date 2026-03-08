<?php
session_start();
include("../database/connect_db.php");
// CSRF validation removed per request

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

// Valores permitidos para unidad
$valoresUnidadPermitidos = ['unidad', 'docena', 'paquete_100','metro', 'rollo'];

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT cm.id, cm.nombre_materia, cm.descripcion, cm.cantidad, cm.unidad, cm.stock, 
                           cm.precio_unitario, cm.fecha_compra, cm.proveedor_id, p.nombres AS nombre_proveedor
                    FROM compras_material cm
                    LEFT JOIN proveedor p ON cm.proveedor_id = p.id
                    ORDER BY cm.fecha_compra DESC";

            $result = mysqli_query($conex, $sql);
            if (!$result) throw new Exception('Error en la base de datos: ' . mysqli_error($conex));

            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Limpieza extra para unidad
                $row['unidad'] = strtolower(trim($row['unidad']));
                if (!in_array($row['unidad'], $valoresUnidadPermitidos)) {
                    $row['unidad'] = 'unidad'; // valor por defecto
                }
                // Formatear fecha si es necesario
                if (empty($row['fecha_compra']) || $row['fecha_compra'] == '0000-00-00') {
                    $row['fecha_compra'] = date('Y-m-d'); // fecha actual como fallback
                }
                $data[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'POST':
            if (
                empty($input['nombre_materia']) || !isset($input['cantidad']) || empty($input['unidad'])
                || !isset($input['precio_unitario']) || !isset($input['stock'])
            ) {
                throw new Exception('Faltan datos obligatorios para crear la compra');
            }

            $nombreMateria = mysqli_real_escape_string($conex, trim($input['nombre_materia']));
            $descripcion = mysqli_real_escape_string($conex, trim($input['descripcion'] ?? ''));
            $cantidad = floatval($input['cantidad']);
            $unidad = strtolower(trim($input['unidad']));
            $stock = floatval($input['stock']);
            $precioUnitario = floatval($input['precio_unitario']);
            $fechaCompra = date('Y-m-d');

            if (!in_array($unidad, $valoresUnidadPermitidos)) {
                throw new Exception('Unidad inválida');
            }

            if ($cantidad <= 0 || $stock < 0 || $precioUnitario < 0) {
                throw new Exception('Valores numéricos inválidos');
            }

            if (!$fechaCompra || $fechaCompra === '0000-00-00') {
                throw new Exception('Fecha de compra inválida');
            }

            $proveedorIdRaw = $input['proveedor_id'] ?? null;
            $proveedorId = ($proveedorIdRaw !== null && $proveedorIdRaw !== '' && is_numeric($proveedorIdRaw) && intval($proveedorIdRaw) > 0)
                ? intval($proveedorIdRaw) : "NULL";

            error_log("Insertando compra: nombre_materia=$nombreMateria, unidad=$unidad, fecha_compra=$fechaCompra");

            if ($proveedorId === "NULL") {
                $sqlInsert = "INSERT INTO compras_material (nombre_materia, descripcion, cantidad, unidad, stock, precio_unitario, fecha_compra, usuario_id, proveedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)";
                $stmtInsert = mysqli_prepare($conex, $sqlInsert);
                mysqli_stmt_bind_param($stmtInsert, "sssddsis", $nombreMateria, $descripcion, $cantidad, $unidad, $stock, $precioUnitario, $fechaCompra, $usuarioId);
            } else {
                $sqlInsert = "INSERT INTO compras_material (nombre_materia, descripcion, cantidad, unidad, stock, precio_unitario, fecha_compra, usuario_id, proveedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = mysqli_prepare($conex, $sqlInsert);
                mysqli_stmt_bind_param($stmtInsert, "sssddsisi", $nombreMateria, $descripcion, $cantidad, $unidad, $stock, $precioUnitario, $fechaCompra, $usuarioId, $proveedorId);
            }

            if (!mysqli_stmt_execute($stmtInsert)) {
                error_log('Error MySQL insert: ' . mysqli_stmt_error($stmtInsert));
                throw new Exception('Error al guardar la compra: ' . mysqli_stmt_error($stmtInsert));
            }
            mysqli_stmt_close($stmtInsert);

            echo json_encode(['status' => 'success', 'message' => 'Compra registrada correctamente']);
            break;

        case 'PUT':
            if (empty($input['id']))
                throw new Exception('ID de compra es requerido para actualizar');

            $id = intval($input['id']);

            if (
                empty($input['nombre_materia']) || !isset($input['cantidad']) || empty($input['unidad'])
                || !isset($input['precio_unitario']) || !isset($input['stock'])
            ) {
                throw new Exception('Faltan datos obligatorios para actualizar la compra');
            }

            $nombreMateria = mysqli_real_escape_string($conex, trim($input['nombre_materia']));
            $descripcion = mysqli_real_escape_string($conex, trim($input['descripcion'] ?? ''));
            $cantidad = floatval($input['cantidad']);
            $unidad = strtolower(trim($input['unidad']));
            $stock = floatval($input['stock']);
            $precioUnitario = floatval($input['precio_unitario']);

            if (!in_array($unidad, $valoresUnidadPermitidos)) {
                throw new Exception('Unidad inválida');
            }

            if ($cantidad <= 0 || $stock < 0 || $precioUnitario < 0) {
                throw new Exception('Valores numéricos inválidos');
            }

            $proveedorIdRaw = $input['proveedor_id'] ?? null;
            $proveedorId = ($proveedorIdRaw !== null && $proveedorIdRaw !== '' && is_numeric($proveedorIdRaw) && intval($proveedorIdRaw) > 0)
                ? intval($proveedorIdRaw) : "NULL";

            error_log("Actualizando compra ID $id: nombre_materia=$nombreMateria, unidad=$unidad");

            if ($proveedorId === "NULL") {
                $sqlUpdate = "UPDATE compras_material SET nombre_materia = ?, descripcion = ?, cantidad = ?, unidad = ?, stock = ?, precio_unitario = ?, proveedor_id = NULL WHERE id = ?";
                $stmtUpdate = mysqli_prepare($conex, $sqlUpdate);
                mysqli_stmt_bind_param($stmtUpdate, "sssddsi", $nombreMateria, $descripcion, $cantidad, $unidad, $stock, $precioUnitario, $id);
            } else {
                $sqlUpdate = "UPDATE compras_material SET nombre_materia = ?, descripcion = ?, cantidad = ?, unidad = ?, stock = ?, precio_unitario = ?, proveedor_id = ? WHERE id = ?";
                $stmtUpdate = mysqli_prepare($conex, $sqlUpdate);
                mysqli_stmt_bind_param($stmtUpdate, "sssddsis", $nombreMateria, $descripcion, $cantidad, $unidad, $stock, $precioUnitario, $proveedorId, $id);
            }

            if (!mysqli_stmt_execute($stmtUpdate)) {
                error_log('Error MySQL update: ' . mysqli_stmt_error($stmtUpdate));
                throw new Exception('Error al actualizar la compra: ' . mysqli_stmt_error($stmtUpdate));
            }
            mysqli_stmt_close($stmtUpdate);

            echo json_encode(['status' => 'success', 'message' => 'Compra actualizada correctamente']);
            break;

        case 'DELETE':
    if (empty($input['id']))
        throw new Exception('ID de compra no especificado');

    $id = intval($input['id']);

    // ✅ PREPARED STATEMENT para verificar existencia
    $sqlCheck = "SELECT id FROM compras_material WHERE id = ?";
    $stmtCheck = mysqli_prepare($conex, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "i", $id);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    
    if (mysqli_num_rows($resultCheck) === 0) {
        mysqli_stmt_close($stmtCheck);
        throw new Exception('Compra no encontrada');
    }
    mysqli_stmt_close($stmtCheck);

    // ✅ PREPARED STATEMENT para DELETE
    $sqlDel = "DELETE FROM compras_material WHERE id = ?";
    $stmtDel = mysqli_prepare($conex, $sqlDel);
    mysqli_stmt_bind_param($stmtDel, "i", $id);
    
    if (!mysqli_stmt_execute($stmtDel)) {
        error_log('Error DELETE: ' . mysqli_stmt_error($stmtDel));
        mysqli_stmt_close($stmtDel);
        throw new Exception('Error al eliminar compra');
    }
    
    mysqli_stmt_close($stmtDel);
    echo json_encode(['status' => 'success', 'message' => 'Compra eliminada correctamente']);
    break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $ex) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
}
