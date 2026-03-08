<?php
// factura.php - Vista de factura en HTML (guardar en /Roka_Sports/menu/factura.php)
session_start();
require_once '../database/connect_db.php';


// Verificar conexión
if (!$conex) {
    die("Error de conexión a la base de datos");
}

// Obtener ID de venta
$venta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($venta_id === 0) {
    header('Location: ventas.php');
    exit;
}

// ===== OBTENER DATOS DE LA VENTA =====
$sql_venta = "SELECT v.*, 
              c.N_afiliacion, c.Cid as cliente_cedula, c.nombre as cliente_nombre,
              u.nombre as usuario_nombre
              FROM ventas v
              LEFT JOIN cliente c ON v.cliente_id = c.N_afiliacion
              LEFT JOIN usuario u ON v.usuario_id = u.id_rec
              WHERE v.id = ?";
$stmt = $conex->prepare($sql_venta);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();

if (!$venta) {
    die("Factura no encontrada");
}

// ===== OBTENER DETALLE DE PRODUCTOS =====
$sql_detalle = "SELECT d.*, p.codigo, p.nombre as producto_nombre
                FROM detalle_ventas d
                LEFT JOIN productos p ON d.producto_id = p.id
                WHERE d.venta_id = ?";
$stmt = $conex->prepare($sql_detalle);
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$detalle = $stmt->get_result();

// ===== OBTENER ABONOS (SI ES CRÉDITO) =====
$abonos = [];
$total_abonado = 0;
$saldo_pendiente = 0;

if ($venta['tipo_pago'] === 'Crédito') {
    $sql_abonos = "SELECT * FROM abonos WHERE venta_id = ? ORDER BY fecha_pago DESC";
    $stmt = $conex->prepare($sql_abonos);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $abonos_result = $stmt->get_result();
    
    while ($abono = $abonos_result->fetch_assoc()) {
        $abonos[] = $abono;
        $total_abonado += $abono['monto'];
    }
    
    $saldo_pendiente = $venta['total'] - $total_abonado;
}

// Formatear fechas
$fecha_venta = date('d/m/Y', strtotime($venta['fecha_venta']));
$hora_venta = date('h:i A', strtotime($venta['fecha_registro']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #<?php echo str_pad($venta_id, 6, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b1b 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px 0;
            min-height: 100vh;
        }
        
        .factura-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(209, 0, 27, 0.3);
            overflow: hidden;
            border: 1px solid rgba(209, 0, 27, 0.2);
        }
        
        .factura-header {
            background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .factura-header h1 {
            font-weight: 800;
            margin: 0;
            font-size: 32px;
            letter-spacing: 2px;
        }
        
        .factura-header .subtitle {
            opacity: 0.9;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .factura-body {
            padding: 30px;
            background: white;
        }
        
        .info-cliente {
            background: #f8f9fa;
            border-left: 4px solid #d1001b;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .table-productos {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-productos th {
            background: #1a1a1a;
            color: white;
            padding: 12px;
            font-size: 14px;
        }
        
        .table-productos td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-productos tr:hover {
            background: #fff3f3;
        }
        
        .totales {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .total-final {
            font-size: 24px;
            color: #d1001b;
            font-weight: 800;
        }
        
        .estado-pagada {
            background: #198754;
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
        }
        
        .estado-pendiente {
            background: #ffc107;
            color: #000;
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
        }
        
        .btn-imprimir {
            background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-imprimir:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4);
            color: white;
        }
        
        .btn-volver {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-volver:hover {
            background: #5a6268;
            color: white;
        }
        
        .footer-factura {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .factura-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .factura-header {
                background: #d1001b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="factura-container">
            <!-- HEADER DE FACTURA -->
            <div class="factura-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1>ROʞKA SPORTS</h1>
                        <div class="subtitle">RIF: J-12345678-9 · Teléfono: 0412-1907798</div>
                        <div class="subtitle">facturas@rokasports.com · @rokasports</div>
                    </div>
                    <div class="text-end">
                        <div style="font-size: 40px; font-weight: 800;">
                            #<?php echo str_pad($venta_id, 6, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="subtitle">Fecha: <?php echo $fecha_venta; ?> - <?php echo $hora_venta; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- CUERPO DE FACTURA -->
            <div class="factura-body">
                <!-- ESTADO DE PAGO -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="badge <?php echo $venta['estado_pago'] === 'Pagada' ? 'bg-success' : 'bg-warning text-dark'; ?> p-3 fs-6">
                            <i class="fas <?php echo $venta['estado_pago'] === 'Pagada' ? 'fa-check-circle' : 'fa-clock'; ?> me-2"></i>
                            <?php echo $venta['estado_pago']; ?>
                        </span>
                        <span class="badge bg-secondary p-3 fs-6 ms-2">
                            <i class="fas fa-credit-card me-2"></i>
                            <?php echo $venta['tipo_pago']; ?>
                        </span>
                    </div>
                    <div class="no-print">
                        <button onclick="window.print()" class="btn btn-imprimir">
                            <i class="fas fa-print me-2"></i> Imprimir
                        </button>
                        <button onclick="window.close()" class="btn btn-volver">
                            <i class="fas fa-arrow-left me-2"></i> Volver
                        </button>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DEL CLIENTE -->
                <div class="info-cliente">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-3" style="color: #d1001b;">
                                <i class="fas fa-user me-2"></i> CLIENTE
                            </h5>
                            <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></p>
                            <p class="mb-0"><strong>Cédula:</strong> <?php echo $venta['cliente_cedula'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-3" style="color: #d1001b;">
                                <i class="fas fa-user-tie me-2"></i> VENDEDOR
                            </h5>
                            <p class="mb-1"><strong>Vendedor:</strong> <?php echo htmlspecialchars($venta['usuario_nombre'] ?? 'Sistema'); ?></p>
                            <p class="mb-0"><strong>Facturación:</strong> <?php echo $fecha_venta; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- TABLA DE PRODUCTOS -->
                <h5 class="mb-3" style="color: #d1001b;">
                    <i class="fas fa-box me-2"></i> DETALLE DE PRODUCTOS
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-productos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="text-black">
                            <?php 
                            $subtotal = 0;
                            while ($item = $detalle->fetch_assoc()): 
                                $subtotal += $item['subtotal'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['codigo'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['producto_nombre'] ?? 'Producto'); ?></td>
                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                <td class="text-end">$ <?php echo number_format($item['precio_unitario'], 2); ?></td>
                                <td class="text-end">$ <?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- TOTALES -->
                <div class="totales">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>SUBTOTAL:</strong></td>
                                    <td class="text-end">$ <?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>IVA (16%):</strong></td>
                                    <td class="text-end">$ <?php echo number_format($venta['total_iva'], 2); ?></td>
                                </tr>
                                <tr style="border-top: 2px solid #d1001b;">
                                    <td><strong>TOTAL:</strong></td>
                                    <td class="text-end total-final">$ <?php echo number_format($venta['total'], 2); ?></td>
                                </tr>
                                
                                <?php if ($venta['moneda_pago'] === 'bolivares' && $venta['tasa_dolar'] > 0): ?>
                                <tr>
                                    <td><strong>TASA BCV:</strong></td>
                                    <td class="text-end">Bs <?php echo number_format($venta['tasa_dolar'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>TOTAL EN BOLÍVARES:</strong></td>
                                    <td class="text-end">Bs <?php echo number_format($venta['total'] * $venta['tasa_dolar'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DE CRÉDITO (SI APLICA) -->
                <?php if ($venta['tipo_pago'] === 'Crédito'): ?>
                <div class="mt-4 p-3" style="background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1" style="color: #856404;">
                                <i class="fas fa-clock me-2"></i> VENTA A CRÉDITO
                            </h6>
                            <p class="mb-0 small">
                                Fecha de vencimiento: <strong><?php echo date('d/m/Y', strtotime($venta['fecha_vencimiento'])); ?></strong>
                            </p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0"><strong>Total abonado:</strong> $ <?php echo number_format($total_abonado, 2); ?></p>
                            <p class="mb-0"><strong>Saldo pendiente:</strong> $ <?php echo number_format($saldo_pendiente, 2); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($abonos)): ?>
                    <div class="mt-3">
                        <p class="mb-2 small fw-bold">HISTORIAL DE ABONOS:</p>
                        <table class="table table-sm table-borderless small">
                            <thead>
                                <tr style="background: #ffe69c;">
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abonos as $abono): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($abono['fecha_pago'])); ?></td>
                                    <td>Bs <?php echo number_format($abono['monto'], 2); ?></td>
                                    <td><?php echo $abono['usuario_id']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- OBSERVACIONES -->
                <div class="mt-4 text-center text-muted small">
                    <p class="mb-1">Esta factura es un comprobante oficial de compra en Roʞka Sports</p>
                    <p class="mb-0">Gracias por su preferencia. Vuelva pronto!</p>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="footer-factura no-print">
                <i class="fas fa-qrcode me-2"></i> Generado por Roʞka System - Módulo de Ventas
            </div>
        </div>
    </div>
    
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>
</html>