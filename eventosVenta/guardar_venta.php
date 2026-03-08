<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    // Registro de depuración temporal para investigar por qué no llega la sesión
    $log  = "=== [".date('Y-m-d H:i:s')."] Petición NO autorizada\n";
    $log .= "Session ID: " . session_id() . "\n";
    $log .= "\$_SESSION: " . print_r($_SESSION, true) . "\n";
    $log .= "\\$_COOKIE: " . print_r($_COOKIE, true) . "\n";
    if (function_exists('getallheaders')) {
        $log .= "HEADERS: " . print_r(getallheaders(), true) . "\n";
    }
    $log .= "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n";
    @file_put_contents(__DIR__ . '/debug_sesion_log.txt', $log, FILE_APPEND);

    echo json_encode(['error' => 'No autorizado']);
    exit;
}
// CSRF validation removed per request

$usuarioId = $_SESSION['usuario_id'];

$clienteId = $_POST['cliente_id'] ?? null;
$inventario = $_POST['inventario'] ?? $_POST['tipo_producto'] ?? null;
$productoId = $_POST['producto_id'] ?? null;
$precioUnitario = floatval($_POST['precio_unitario'] ?? 0);
$unidadVenta = $_POST['unidad_venta'] ?? 'unidad';
$cantidad = intval($_POST['cantidad'] ?? 0);
// productos puede venir como JSON (string) desde el frontend
$productos_raw = $_POST['productos'] ?? null;
$productos = null;
if ($productos_raw) {
    $productos = json_decode($productos_raw, true);
    if (!is_array($productos)) $productos = null;
}
$monedaPago = $_POST['moneda_pago'] ?? 'bs';
$tasaDolar = floatval($_POST['tasa_dolar'] ?? 0);
$tipoPago = $_POST['tipo_pago'] ?? 'contado';
$modalidadPago = $_POST['modalidad_pago'] ?? null;
$fechaVencimiento = isset($_POST['fecha_vencimiento']) && $_POST['fecha_vencimiento'] !== '' ? $_POST['fecha_vencimiento'] : null;

// Validación: si viene un arreglo de productos, validar que exista cliente y productos
if ($productos && is_array($productos)) {
    if (!$clienteId || empty($productos) || !$tipoPago || ($monedaPago == 'bs' && $tasaDolar <= 0) || ($tipoPago == 'credito' && !$fechaVencimiento)) {
        echo json_encode(['error' => 'Datos incompletos o inválidos']);
        exit;
    }
} else {
    if (!$clienteId || !$inventario || !$productoId || $precioUnitario <= 0 || $cantidad <= 0 || !$tipoPago ||
        ($monedaPago == 'bs' && $tasaDolar <= 0) ||
        ($tipoPago == 'credito' && !$fechaVencimiento)) {
        echo json_encode(['error' => 'Datos incompletos o inválidos']);
        exit;
    }
}

$tablaStock = ($inventario == 'productos') ? 'productos' : 'productos_distribucion';

// Si recibimos múltiples productos, validar stock y calcular totales por cada uno
if ($productos && is_array($productos)) {
    $subtotal = 0;
    $stockChecks = [];
    foreach ($productos as $p) {
        // Intentar resolver el identificador del producto. El frontend idealmente envía el id numérico,
        // pero puede enviar el 'codigo' (ej. PR000123). Normalizamos y, si es necesario, buscamos el id real.
        $rawId = $p['id'] ?? null;
        $pid = intval($rawId);
        $pcant = intval($p['cantidad'] ?? 0);
        $pprecio = floatval($p['precio'] ?? 0);

        // Si intval devuelve 0 o no es confiable, intentar buscar por codigo en la tabla correspondiente
        if ($pid <= 0 && is_string($rawId) && $rawId !== '') {
            // Normalizar posible prefijo (ej. PR000123)
            $codigoBuscar = $rawId;
            // Si el codigo incluye letras, buscar por la columna 'codigo'
            if ($tablaStock === 'productos') {
                $stmtFind = $conex->prepare("SELECT id FROM productos WHERE codigo = ? LIMIT 1");
            } else {
                $stmtFind = $conex->prepare("SELECT id FROM productos_distribucion WHERE codigo = ? LIMIT 1");
            }
            if ($stmtFind) {
                $stmtFind->bind_param("s", $codigoBuscar);
                $stmtFind->execute();
                $stmtFind->bind_result($foundId);
                if ($stmtFind->fetch()) {
                    $pid = intval($foundId);
                }
                $stmtFind->close();
            }
        }

        if ($pid <= 0 || $pcant <= 0 || $pprecio <= 0) {
            // Registrar detalle para depuración y devolver error claro
            $dbg = "[".date('Y-m-d H:i:s')."] Producto inválido: rawId=".print_r($rawId, true)." resolvedId=".print_r($pid, true)." cantidad=".print_r($pcant, true)." precio=".print_r($pprecio, true)."\n";
            @file_put_contents(__DIR__.'/debug_guardar_productos.txt', $dbg, FILE_APPEND);
            echo json_encode(['error' => 'Datos de producto inválidos']);
            exit;
        }

        // obtener stock actual
        $stmtStock = $conex->prepare("SELECT stock FROM $tablaStock WHERE id = ?");
        $stmtStock->bind_param("i", $pid);
        $stmtStock->execute();
        $stmtStock->bind_result($stockActual);
        $fetched = $stmtStock->fetch();
        $stmtStock->close();

        // Si no se obtuvo registro del producto en la tabla de stock, registrar y retornar error.
        if (!$fetched || $stockActual === null) {
            $dbg = "[".date('Y-m-d H:i:s')."] Producto no encontrado en inventario: tabla=$tablaStock rawId=".print_r($rawId, true)." resolvedId=".print_r($pid, true)."\n";
            @file_put_contents(__DIR__.'/debug_guardar_productos.txt', $dbg, FILE_APPEND);
            echo json_encode(['error' => "Producto no encontrado: id $pid"]);
            exit;
        }

        if ($pcant > $stockActual) {
            echo json_encode(['error' => "Stock insuficiente para producto ID $pid"]);
            exit;
        }

        $subtotal += ($pprecio * $pcant);
        $stockChecks[] = ['id' => $pid, 'stock' => $stockActual, 'cantidad' => $pcant];
    }

    $iva = $subtotal * 0.16;
    $total = $subtotal + $iva;

} else {
    $factor = 1;
    if ($unidadVenta == 'paquete') $factor = 8;
    elseif ($unidadVenta == 'docena') $factor = 12;
    $cantReal = $cantidad * $factor;

    $stmtStock = $conex->prepare("SELECT stock FROM $tablaStock WHERE id = ?");
    $stmtStock->bind_param("i", $productoId);
    $stmtStock->execute();
    $stmtStock->bind_result($stockActual);
    $stmtStock->fetch();
    $stmtStock->close();

    if ($cantReal > $stockActual) {
        echo json_encode(['error' => 'Stock insuficiente']);
        exit;
    }

    $subtotal = $precioUnitario * $cantReal;
    $iva = $subtotal * 0.16;
    $total = $subtotal + $iva;
}

$porcentajePagado = 0;
if ($tipoPago == 'contado') {
    $porcentajePagado = 100;
} else if ($tipoPago == 'credito') {
    if ($modalidadPago == '50/50') $porcentajePagado = 50;
    elseif ($modalidadPago == '25/75') $porcentajePagado = 25;
}

$conex->autocommit(false);
try {
    $stmtVenta = $conex->prepare("INSERT INTO ventas (fecha_venta, cliente_id, total, porcentaje_pagado, total_iva, tipo_pago, estado_pago, usuario_id, fecha_vencimiento) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)");
    $estadoPago = ($porcentajePagado == 100) ? 'Pagada' : 'Pendiente';

    $stmtVenta->bind_param("sdddssis", $clienteId, $total, $porcentajePagado, $iva, $tipoPago, $estadoPago, $usuarioId, $fechaVencimiento);
    if (!$stmtVenta->execute()) throw new Exception("Error al guardar la venta: " . $stmtVenta->error);
    $idVenta = $stmtVenta->insert_id;
    $stmtVenta->close();

    $colProducto = ($inventario == 'productos') ? 'producto_id' : 'producto_distribucion_id';

    // Si tenemos varios productos, insertar cada detalle y actualizar stock por cada uno
    if ($productos && is_array($productos)) {
        $queryDetalle = "INSERT INTO detalle_ventas (venta_id, $colProducto, cantidad, precio_unitario, subtotal, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmtDetalle = $conex->prepare($queryDetalle);
        if (!$stmtDetalle) throw new Exception("Error preparando detalle: " . $conex->error);

        foreach ($productos as $p) {
            $pid = intval($p['id'] ?? 0);
            $pcant = intval($p['cantidad'] ?? 0);
            $pprecio = floatval($p['precio'] ?? 0);
            $psub = $pprecio * $pcant;
            $stmtDetalle->bind_param("iiidd", $idVenta, $pid, $pcant, $pprecio, $psub);
            if (!$stmtDetalle->execute()) throw new Exception("Error al guardar detalle: " . $stmtDetalle->error);
        }
        $stmtDetalle->close();

        // Actualizar stocks usando la información recolectada antes (stockChecks)
        if (!empty($stockChecks)) {
            $stmtUpdStock = $conex->prepare("UPDATE $tablaStock SET stock = ? WHERE id = ?");
            if (!$stmtUpdStock) throw new Exception("Error preparando update stock: " . $conex->error);
            foreach ($stockChecks as $sc) {
                $nuevoStock = $sc['stock'] - $sc['cantidad'];
                $stmtUpdStock->bind_param("ii", $nuevoStock, $sc['id']);
                if (!$stmtUpdStock->execute()) throw new Exception("Error al actualizar stock: " . $stmtUpdStock->error);
            }
            $stmtUpdStock->close();
        }

    } else {
        $queryDetalle = "INSERT INTO detalle_ventas (venta_id, $colProducto, cantidad, precio_unitario, subtotal, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmtDetalle = $conex->prepare($queryDetalle);
        $stmtDetalle->bind_param("iiidd", $idVenta, $productoId, $cantReal, $precioUnitario, $subtotal);
        if (!$stmtDetalle->execute()) throw new Exception("Error al guardar detalle: " . $stmtDetalle->error);
        $stmtDetalle->close();

        $nuevoStock = $stockActual - $cantReal;
        $stmtUpdStock = $conex->prepare("UPDATE $tablaStock SET stock = ? WHERE id = ?");
        $stmtUpdStock->bind_param("ii", $nuevoStock, $productoId);
        if (!$stmtUpdStock->execute()) throw new Exception("Error al actualizar stock: " . $stmtUpdStock->error);
        $stmtUpdStock->close();
    }

    $conex->commit();

    echo json_encode(['success' => true, 'mensaje' => 'Venta registrada correctamente', 'idVenta' => $idVenta]);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}

$conex->close();
?>
