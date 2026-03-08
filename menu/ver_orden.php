<?php
session_start();
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// Obtener orden con detalles
$stmt = $conex->prepare("
    SELECT o.*, 
           (SELECT SUM(cantidad) FROM orden_detalle WHERE orden_id = o.id) as total_unidades
    FROM ordenes o 
    WHERE o.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();

// Obtener detalles por talla
$stmt_det = $conex->prepare("SELECT * FROM orden_detalle WHERE orden_id = ? ORDER BY FIELD(talla, 'S','M','L','XL','XXL')");
$stmt_det->bind_param("i", $id);
$stmt_det->execute();
$detalles = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Orden - Roka Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #1e1e2e 100%);
            color: #fff;
        }
        .card {
            background: #212529;
            border: 1px solid #444;
        }
        .card-header {
            background: #2c3e50;
            color: #fff;
        }
        .table-dark {
            background: #2d2d2d;
        }
        .badge-entregado { background: #28a745; }
        .badge-pendiente { background: #dc3545; }
        .badge-parcial { background: #ffc107; color: #000; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="mb-0">
                            <i class="bi bi-file-text"></i> Resumen de Orden #<?= $orden['id'] ?>
                            <a href="ordenes.php" class="btn btn-sm btn-light float-end">Volver</a>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="../uploads/<?= htmlspecialchars($orden['imagen']) ?>" 
                                     class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                            <div class="col-md-8">
                                <table class="table table-dark">
                                    <tr>
                                        <th width="30%">Producto:</th>
                                        <td><?= htmlspecialchars($orden['nombre']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tipo:</th>
                                        <td>
                                            <span class="badge <?= $orden['tipo_orden'] == 'Fabricado' ? 'bg-warning' : 'bg-info' ?>">
                                                <?= $orden['tipo_orden'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Técnica:</th>
                                        <td><?= $orden['tecnica'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tela:</th>
                                        <td><?= $orden['tipo_tela'] ?? 'N/A' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Metros totales:</th>
                                        <td><strong><?= number_format($orden['metros_totales'] ?? 0, 2) ?>m</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Estado entrega:</th>
                                        <td>
                                            <?php
                                            $clase = match($orden['estado_entrega'] ?? 'Por entregar') {
                                                'Entregado' => 'bg-success',
                                                'Parcial' => 'bg-warning text-dark',
                                                default => 'bg-danger'
                                            };
                                            ?>
                                            <span class="badge <?= $clase ?>"><?= $orden['estado_entrega'] ?? 'Por entregar' ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Fecha registro:</th>
                                        <td><?= date('d/m/Y H:i', strtotime($orden['fecha_registro'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($detalles)): ?>
                        <h6 class="mt-4">Detalle por Talla</h6>
                        <table class="table table-dark table-sm">
                            <thead>
                                <tr>
                                    <th>Talla</th>
                                    <th>Cantidad</th>
                                    <th>Metros/prenda</th>
                                    <th>Total metros</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $det): ?>
                                <tr>
                                    <td><strong><?= $det['talla'] ?></strong></td>
                                    <td><?= $det['cantidad'] ?></td>
                                    <td><?= number_format($det['metros_por_prenda'], 2) ?>m</td>
                                    <td><?= number_format($det['metros_consumidos'], 2) ?>m</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>