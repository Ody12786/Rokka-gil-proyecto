<?php
// procesar_venta.php
session_start();
header('Content-Type: application/json');
require_once 'connect_db.php';

if (!$conex) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$accion = $data['accion'] ?? '';

switch ($accion) {
    
    case 'agregar_carrito':
        // Agregar producto a la sesión (carrito temporal)
        if (!isset($_SESSION['carrito_venta'])) {
            $_SESSION['carrito_venta'] = [];
        }
        
        $producto_id = $data['producto_id'];
        $cantidad = (int)($data['cantidad'] ?? 1);
        
        // Verificar stock disponible
        $stmt = $conex->prepare("SELECT id, nombre, stock, codigo FROM productos WHERE id = ? AND activo = 1");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit;
        }
        
        // Calcular stock actual en carrito
        $stock_en_carrito = 0;
        foreach ($_SESSION['carrito_venta'] as $item) {
            if ($item['id'] == $producto_id) {
                $stock_en_carrito += $item['cantidad'];
            }
        }
        
        $stock_disponible = $producto['stock'] - $stock_en_carrito;
        
        if ($cantidad > $stock_disponible) {
            echo json_encode([
                'success' => false, 
                'message' => "Stock insuficiente. Disponible: $stock_disponible unidades"
            ]);
            exit;
        }
        
        // Agregar o actualizar carrito
        $encontrado = false;
        foreach ($_SESSION['carrito_venta'] as &$item) {
            if ($item['id'] == $producto_id) {
                $item['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $_SESSION['carrito_venta'][] = [
                'id' => $producto_id,
                'codigo' => $producto['codigo'],
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'precio' => $data['precio'],
                'subtotal' => $cantidad * $data['precio']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'carrito' => $_SESSION['carrito_venta'],
            'total_items' => count($_SESSION['carrito_venta']),
            'total_venta' => calcularTotalCarrito()
        ]);
        break;
        
    case 'eliminar_item':
        $producto_id = $data['producto_id'];
        
        if (isset($_SESSION['carrito_venta'])) {
            foreach ($_SESSION['carrito_venta'] as $key => $item) {
                if ($item['id'] == $producto_id) {
                    unset($_SESSION['carrito_venta'][$key]);
                    $_SESSION['carrito_venta'] = array_values($_SESSION['carrito_venta']);
                    break;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado',
            'carrito' => $_SESSION['carrito_venta'] ?? [],
            'total_items' => count($_SESSION['carrito_venta'] ?? []),
            'total_venta' => calcularTotalCarrito()
        ]);
        break;
        
    case 'actualizar_cantidad':
        $producto_id = $data['producto_id'];
        $cantidad = (int)$data['cantidad'];
        
        if (isset($_SESSION['carrito_venta'])) {
            foreach ($_SESSION['carrito_venta'] as &$item) {
                if ($item['id'] == $producto_id) {
                    // Verificar stock
                    $stmt = $conex->prepare("SELECT stock FROM productos WHERE id = ?");
                    $stmt->bind_param("i", $producto_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $producto = $result->fetch_assoc();
                    
                    if ($cantidad > $producto['stock']) {
                        echo json_encode([
                            'success' => false,
                            'message' => "Stock máximo disponible: {$producto['stock']} unidades"
                        ]);
                        exit;
                    }
                    
                    $item['cantidad'] = $cantidad;
                    $item['subtotal'] = $cantidad * $item['precio'];
                    break;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'carrito' => $_SESSION['carrito_venta'] ?? [],
            'total_venta' => calcularTotalCarrito()
        ]);
        break;
        
    case 'obtener_carrito':
        echo json_encode([
            'success' => true,
            'carrito' => $_SESSION['carrito_venta'] ?? [],
            'total_items' => count($_SESSION['carrito_venta'] ?? []),
            'total_venta' => calcularTotalCarrito()
        ]);
        break;
        
    case 'limpiar_carrito':
        unset($_SESSION['carrito_venta']);
        echo json_encode([
            'success' => true,
            'message' => 'Carrito limpiado',
            'total_venta' => 0
        ]);
        break;
        
    case 'registrar_venta':
        // Validar datos obligatorios
        if (empty($_SESSION['carrito_venta'])) {
            echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
            exit;
        }
        
        $cliente_id = $data['cliente_id'] ?? null;
        $tipo_pago = $conex->real_escape_string($data['tipo_pago'] ?? 'Contado');
        $moneda_pago = $conex->real_escape_string($data['moneda_pago'] ?? 'dolares');
        $tasa_dolar = (float)($data['tasa_dolar'] ?? 0);
        $usuario_id = $_SESSION['usuario_id'] ?? 1; // Ajusta según tu sistema de login
        
        if (!$cliente_id) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar un cliente']);
            exit;
        }
        
        $conex->begin_transaction();
        
        try {
            // Calcular totales
            $subtotal = 0;
            foreach ($_SESSION['carrito_venta'] as $item) {
                $subtotal += $item['subtotal'];
            }
            
            $iva = $subtotal * 0.16; // 16% IVA
            $total = $subtotal + $iva;
            
            // Fecha de vencimiento para crédito
            $fecha_vencimiento = null;
            if ($tipo_pago === 'Crédito') {
                $fecha_vencimiento = date('Y-m-d', strtotime('+7 days'));
            }
            
            // Insertar venta
            $stmt = $conex->prepare("
                INSERT INTO ventas (
                    fecha_venta, cliente_id, cedula_cliente, total, 
                    total_iva, tipo_pago, moneda_pago, tasa_dolar, 
                    estado_pago, usuario_id, fecha_vencimiento
                ) VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $cedula = null;
            $estado_pago = $tipo_pago === 'Contado' ? 'Pagada' : 'Pendiente';
            $porcentaje_pagado = $tipo_pago === 'Contado' ? 100 : 0;
            
            $stmt->bind_param(
                "isddssdsis",
                $cliente_id,
                $cedula,
                $total,
                $iva,
                $tipo_pago,
                $moneda_pago,
                $tasa_dolar,
                $estado_pago,
                $usuario_id,
                $fecha_vencimiento
            );
            $stmt->execute();
            $venta_id = $conex->insert_id;
            
            // Insertar detalles y actualizar stock
            $stmt_detalle = $conex->prepare("
                INSERT INTO detalle_ventas (
                    venta_id, producto_id, cantidad, precio_unitario, subtotal
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt_update_stock = $conex->prepare("
                UPDATE productos SET stock = stock - ? WHERE id = ?
            ");
            
            foreach ($_SESSION['carrito_venta'] as $item) {
                // Detalle
                $stmt_detalle->bind_param(
                    "iiidd",
                    $venta_id,
                    $item['id'],
                    $item['cantidad'],
                    $item['precio'],
                    $item['subtotal']
                );
                $stmt_detalle->execute();
                
                // Actualizar stock
                $stmt_update_stock->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt_update_stock->execute();
            }
            
            // Si es crédito, crear saldo inicial
            if ($tipo_pago === 'Crédito') {
                $stmt_saldo = $conex->prepare("
                    INSERT INTO saldo_venta (venta_id, saldo_inicial, saldo_actual)
                    VALUES (?, ?, ?)
                ");
                $stmt_saldo->bind_param("idd", $venta_id, $total, $total);
                $stmt_saldo->execute();
            }
            
            $conex->commit();
            
            // Limpiar carrito
            unset($_SESSION['carrito_venta']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'venta_id' => $venta_id,
                'total' => $total
            ]);
            
        } catch (Exception $e) {
            $conex->rollback();
            echo json_encode(['success' => false, 'message' => 'Error al registrar venta: ' . $e->getMessage()]);
        }
        break;
        
    case 'buscar_clientes':
        $termino = $conex->real_escape_string($data['termino'] ?? '');
        
        $sql = "SELECT N_afiliacion, Cid, nombre FROM cliente 
                WHERE is_deleted = 0 AND (nombre LIKE '%$termino%' OR Cid LIKE '%$termino%')
                LIMIT 10";
        
        $result = $conex->query($sql);
        $clientes = [];
        
        while ($row = $result->fetch_assoc()) {
            $clientes[] = [
                'id' => $row['N_afiliacion'],
                'cedula' => $row['Cid'],
                'nombre' => $row['nombre']
            ];
        }
        
        echo json_encode(['success' => true, 'clientes' => $clientes]);
        break;
}

function calcularTotalCarrito() {
    $total = 0;
    if (isset($_SESSION['carrito_venta'])) {
        foreach ($_SESSION['carrito_venta'] as $item) {
            $total += $item['subtotal'];
        }
    }
    return $total;
}
?>