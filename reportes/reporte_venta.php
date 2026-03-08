<?php
session_start();
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de venta inválido.");
}

$ventaId = intval($_GET['id']);

// Obtener info de la venta
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
$venta = $result->fetch_assoc();
$stmt->close();

if (!$venta) {
    die("Venta no encontrada.");
}

// Obtener detalles productos
$stmtDet = $conex->prepare("
    SELECT p.codigo, p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal
    FROM detalle_ventas dv
    INNER JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = ?
");
$stmtDet->bind_param("i", $ventaId);
$stmtDet->execute();
$resultDet = $stmtDet->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Reporte Venta #<?= htmlspecialchars($venta['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <h2>Reporte de Venta #<?= htmlspecialchars($venta['id']) ?></h2>
    <p><strong>Fecha de Venta:</strong> <?= htmlspecialchars($venta['fecha_venta']) ?></p>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($venta['cliente_nombre']) ?> (Cédula: <?= htmlspecialchars($venta['cliente_cedula']) ?>)</p>
    <p><strong>Tipo de Pago:</strong> <?= htmlspecialchars($venta['tipo_pago']) ?></p>
    <p><strong>Estado:</strong> <?= htmlspecialchars($venta['estado_pago']) ?></p>
    <p><strong>Usuario:</strong> <?= htmlspecialchars($venta['usuario_nombre']) ?></p>

    <h4>Detalle de Productos</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Cantidad</th>
                <th>Precio Unitario (Bs)</th>
                <th>Subtotal (Bs)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($producto = $resultDet->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($producto['codigo']) ?></td>
                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                <td><?= htmlspecialchars($producto['cantidad']) ?></td>
                <td><?= number_format($producto['precio_unitario'], 2, ',', '.') ?></td>
                <td><?= number_format($producto['subtotal'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h5>Total Venta: Bs <?= number_format($venta['total'], 2, ',', '.') ?></h5>

    <a href="#" onclick="window.print();return false;" class="btn btn-primary">Imprimir Reporte</a>
    <a href="../menu/ventas.php" class="btn btn-secondary">Volver</a>

</body>
</html>
