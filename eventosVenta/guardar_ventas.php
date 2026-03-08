<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json; charset=utf-8');

//  Autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$clienteId = $_POST['cliente_id'] ?? null;
$productos_raw = $_POST['productos'] ?? null;
$productos = null;
if ($productos_raw) {
    $productos = json_decode($productos_raw, true);
    if (!is_array($productos)) $productos = null;
}

//  Datos venta - NORMALIZAR VALORES
$monedaPago = $_POST['moneda_pago'] ?? 'dolares';
$tasaDolar = floatval($_POST['tasa_dolar'] ?? 0);
$tipoPagoRaw = $_POST['tipo_pago'] ?? 'contado';

//  NORMALIZAR tipo_pago para coincidir con ENUM DB
if ($tipoPagoRaw === 'contado') {
    $tipoPago = 'Contado';
} elseif ($tipoPagoRaw === 'credito') {
    $tipoPago = 'Crédito';
} else {
    $tipoPago = 'Contado';
}

$fechaVencimiento = $_POST['fecha_vencimiento'] ?? null;
$cedulaCliente = $_POST['cedulaCliente'] ?? null;

//  VALIDACIONES
if (!$clienteId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cliente requerido']);
    exit;
}

if (!$productos || !is_array($productos) || empty($productos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Productos requeridos']);
    exit;
}

if ($monedaPago === 'bolivares' && $tasaDolar <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tasa dólar requerida para bolívares']);
    exit;
}

if ($tipoPago === 'Crédito' && empty($fechaVencimiento)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fecha vencimiento requerida para crédito']);
    exit;
}

$conex->autocommit(false);

try {
    //  VALIDAR CLIENTE
    $stmtChkCliente = $conex->prepare("SELECT N_afiliacion, nombre FROM cliente WHERE N_afiliacion = ? LIMIT 1");
    $stmtChkCliente->bind_param("s", $clienteId);
    $stmtChkCliente->execute();
    $stmtChkCliente->bind_result($foundClienteId, $clienteNombre);
    if (!$stmtChkCliente->fetch()) {
        $stmtChkCliente->close();
        throw new Exception('Cliente no encontrado: ' . $clienteId);
    }
    $stmtChkCliente->close();

    //  CALCULAR TOTALES + VALIDAR STOCK
    $subtotal = 0;
    $iva = 0;
    $total = 0;
    $stockChecks = [];
    $tablaStock = 'productos';

    foreach ($productos as $p) {
        $rawId = $p['id'] ?? null;
        $pid = intval($rawId);
        $pcant = intval($p['cantidad'] ?? 0);
        $pprecio = floatval($p['precio'] ?? 0);

        // 🔍 Resolver código → ID
        if ($pid <= 0 && is_string($rawId) && !empty($rawId)) {
            $stmtFind = $conex->prepare("SELECT id FROM $tablaStock WHERE codigo = ? AND activo = 1 AND stock > 0 LIMIT 1");
            $stmtFind->bind_param("s", $rawId);
            $stmtFind->execute();
            $stmtFind->bind_result($foundId);
            if ($stmtFind->fetch()) {
                $pid = intval($foundId);
            }
            $stmtFind->close();
        }

        //  VALIDAR PRODUCTO + STOCK
        if ($pid <= 0 || $pcant <= 0 || $pprecio <= 0) {
            throw new Exception('Producto inválido: ' . json_encode($p));
        }

        $stmtStock = $conex->prepare("SELECT stock, nombre, codigo FROM $tablaStock WHERE id = ? AND activo = 1");
        $stmtStock->bind_param("i", $pid);
        $stmtStock->execute();
        $stmtStock->bind_result($stockActual, $nombreProd, $codigoProd);
        if (!$stmtStock->fetch()) {
            $stmtStock->close();
            throw new Exception("Producto no disponible: ID $pid ($rawId)");
        }
        $stmtStock->close();

        if ($pcant > $stockActual) {
            throw new Exception("Stock insuficiente. $codigoProd ($nombreProd): $stockActual disponible, $pcant solicitados");
        }

        $subtotalItem = $pprecio * $pcant;
        $subtotal += $subtotalItem;
        $stockChecks[] = ['id' => $pid, 'stock' => $stockActual, 'cantidad' => $pcant];
    }

    $iva = $subtotal * 0.16;
    $total = $subtotal + $iva;

    //  LÓGICA % PAGADO CORREGIDA
    $porcentajePagado = ($tipoPago === 'Contado') ? 100 : 0;
    $estadoPago = ($porcentajePagado == 100) ? 'Pagada' : 'Pendiente';

    //  GUARDAR VENTA
    $stmtVenta = $conex->prepare("
        INSERT INTO ventas (
            fecha_venta, cliente_id, cedula_cliente, total, porcentaje_pagado, 
            total_iva, tipo_pago, moneda_pago, tasa_dolar, estado_pago, 
            usuario_id, fecha_vencimiento
        ) VALUES (
            CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    $stmtVenta->bind_param(
        "ssddssdssds",
        $clienteId,
        $cedulaCliente,
        $total,
        $porcentajePagado,
        $iva,
        $tipoPago,
        $monedaPago,
        $tasaDolar,
        $estadoPago,
        $usuarioId,
        $fechaVencimiento
    );
    
    if (!$stmtVenta->execute()) {
        throw new Exception('Error al guardar venta: ' . $stmtVenta->error);
    }
    $idVenta = $conex->insert_id;
    $stmtVenta->close();

    //  DETALLES VENTA
    $stmtDetalle = $conex->prepare("
        INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    foreach ($productos as $p) {
        $pid = intval($p['id'] ?? 0);
        $pcant = intval($p['cantidad'] ?? 0);
        $pprecio = floatval($p['precio'] ?? 0);
        $psubtotal = $pprecio * $pcant;
        
        $stmtDetalle->bind_param("iiidd", $idVenta, $pid, $pcant, $pprecio, $psubtotal);
        if (!$stmtDetalle->execute()) {
            throw new Exception('Error detalle venta: ' . $stmtDetalle->error);
        }
    }
    $stmtDetalle->close();

    //  ACTUALIZAR STOCK
    if (!empty($stockChecks)) {
        $stmtUpdStock = $conex->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        foreach ($stockChecks as $sc) {
            $nuevoStock = $sc['stock'] - $sc['cantidad'];
            $stmtUpdStock->bind_param("ii", $nuevoStock, $sc['id']);
            if (!$stmtUpdStock->execute()) {
                throw new Exception('Error actualizar stock: ' . $stmtUpdStock->error);
            }
        }
        $stmtUpdStock->close();
    }

    // CREAR SALDO_VENTA **DENTRO** DE TRANSACCIÓN
    $stmtSaldo = $conex->prepare("INSERT INTO saldo_venta (venta_id, saldo_inicial, saldo_actual) VALUES (?, ?, ?)");
    $stmtSaldo->bind_param("idd", $idVenta, $total, $total);
    if (!$stmtSaldo->execute()) {
        throw new Exception('Error al crear saldo venta: ' . $stmtSaldo->error);
    }
    $stmtSaldo->close();

    // COMMIT TODO (venta + detalles + stock + saldo)
    $conex->commit();
    
    echo json_encode([
        'success' => true, 
        'mensaje' => 'Venta registrada correctamente',
        'idVenta' => $idVenta,
        'total' => number_format($total, 2),
        'tipo_pago' => $tipoPago,
        'cliente' => $clienteNombre ?? $clienteId
    ]);

} catch (Exception $e) {
    $conex->rollback();
    http_response_code(500);
    
    $errorLog = sprintf(
        "[%s] ERROR VENTA | %s | Cliente:%s | TipoPago:%s\n", 
        date('Y-m-d H:i:s'), 
        $e->getMessage(), 
        $clienteId ?? 'N/A',
        $tipoPago ?? 'N/A'
    );
    @file_put_contents(__DIR__.'/debug_ventas.log', $errorLog, FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}
?>
