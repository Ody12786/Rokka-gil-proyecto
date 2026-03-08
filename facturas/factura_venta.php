<?php
session_start();
include("../database/connect_db.php");

function mesEspanol($fecha) {
    $meses = [
        '01' => 'ENERO', '02' => 'FEBRERO', '03' => 'MARZO', '04' => 'ABRIL',
        '05' => 'MAYO', '06' => 'JUNIO', '07' => 'JULIO', '08' => 'AGOSTO',
        '09' => 'SEPTIEMBRE', '10' => 'OCTUBRE', '11' => 'NOVIEMBRE', '12' => 'DICIEMBRE'
    ];
    return $meses[date('m', strtotime($fecha))] ?? 'ENERO';
}

// Verifica acceso
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso no autorizado.");
}

// Valida ID venta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de venta inválido.");
}

$ventaId = intval($_GET['id']);

// Consulta datos generales de la venta y cliente
$stmt = $conex->prepare("
    SELECT v.id, v.fecha_venta, c.nombre AS cliente_nombre, c.Cid AS cliente_cedula,
           v.total, v.tipo_pago, v.estado_pago, u.nombre AS usuario_nombre
    FROM ventas v
    INNER JOIN cliente c ON v.cliente_id = c.N_afiliacion
    INNER JOIN usuario u ON v.usuario_id = u.id_rec
    WHERE v.id = ?
");
$stmt->bind_param("i", $ventaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $venta = $result->fetch_assoc();
} else {
    die("Venta no encontrada.");
}
$stmt->close();

$porcentajeIVA = 0.16; // 16% IVA
$baseImponible = $venta['total'] / (1 + $porcentajeIVA); // Monto sin IVA
$ivaCalculado = $baseImponible * $porcentajeIVA; // IVA real
$totalPagar = $baseImponible + $ivaCalculado; // Total + IVA


// Consulta detalle de productos
$stmtDetalle = $conex->prepare("
    SELECT p.codigo, p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal
    FROM detalle_ventas dv
    INNER JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = ?
");
$stmtDetalle->bind_param("i", $ventaId);
$stmtDetalle->execute();
$resultDetalle = $stmtDetalle->get_result();

if (!$resultDetalle) {
    die("Error al obtener detalle de la venta.");
}

$fechaVentaFormateada = date("d/m/Y H:i", strtotime($venta['fecha_venta']));
?>

<!-- Enlaza CSS de factura -->
<link rel="stylesheet" href="/Roka_Sports/css/factura-modal.css">

<div class="factura-modal">

    <img src="/Roka_Sports/img/factura.jpeg" alt="Logo" class="factura-logo" />

    <div class="factura-header">
        <div class="empresa">Other <strong>LEVEL</strong></div>
        <div class="rif">RIF- V.16251447-0</div>
    </div>

    <div class="factura-title">PRESUPUESTO</div>

    <div class="factura-datos">
        <div>
            Sr.<br />
            <?= htmlspecialchars($venta['cliente_nombre']) ?><br />
            EL TIGRE EDO ANZOATEGUI
        </div>
        <div class="fecha">
    El Tigre, <?= date("d", strtotime($venta['fecha_venta'])) ?> de <?= mesEspanol($venta['fecha_venta']) ?> de <?= date("Y", strtotime($venta['fecha_venta'])) ?>
</div>

    </div>

    <table class="factura-tabla">
        <thead>
            <tr>
                <th>CANT</th>
                <th>DESCRIPCION</th>
                <th>P.UNIT</th>
                <th>PRECIO TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($resultDetalle->num_rows > 0):
                while ($producto = $resultDetalle->fetch_assoc()):
            ?>
                <tr>
                    <td class="cant"><?= htmlspecialchars($producto['cantidad']) ?></td>
                    <td class="desc"><?= htmlspecialchars($producto['nombre']) ?></td>
                    <td class="punit"><?= number_format($producto['precio_unitario'], 2, ',', '.') ?></td>
                    <td class="ptotal"><?= number_format($producto['subtotal'], 2, ',', '.') ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr><td colspan="4">No hay productos para esta venta.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="display:flex;justify-content:space-between">
        <div style="flex:3">
            <div class="factura-recibido">
                RECIBIDO POR C.I. _______________________
            </div>
        </div>
        <div style="flex:2">
    <table class="factura-totales-table">
        <tr>
            <td class="titulo">BASE IMPONIBLE</td>
            <td><?= number_format($baseImponible, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="titulo">I.V.A. 16%</td>
            <td><?= number_format($ivaCalculado, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="titulo">TOTAL A PAGAR</td>
            <td><?= number_format($totalPagar, 2, ',', '.') ?></td>
        </tr>
    </table>
     <div class="factura-nota" style="width: 100%; text-align: center; margin-top: 20px; font-style: italic;">
    Sin factura omitir el IVA
</div>
</div>

  

</div>
