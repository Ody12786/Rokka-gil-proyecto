<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || !isset($_SESSION['usuario_nombre'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: ../index.php?error=sesion");
    exit();
}

if (isset($_SESSION['asistente_id'])) {
    include("../database/connect_db.php");
    $stmt = $conex->prepare("SELECT ua.estado FROM usuario_asistente ua WHERE ua.id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0 || $result->fetch_assoc()['estado'] !== 'activo') {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: ../index.php?session_killed=1");
        exit();
    }
    $stmt->close();
}

// Query SIMPLIFICADA
$stmt = $conex->query("
    SELECT pt.*, o.nombre, o.tipo_tela, o.tecnica
    FROM productos_terminados pt 
    JOIN ordenes o ON pt.orden_id = o.id 
    ORDER BY pt.fecha_produccion DESC
");
$productos = $stmt->fetch_all(MYSQLI_ASSOC);

// Stats
$disponibles = $conex->query("SELECT SUM(cantidad) as total FROM productos_terminados WHERE estado='disponible'")->fetch_assoc()['total'] ?? 0;
$total_prod = $conex->query("SELECT SUM(cantidad) as total FROM productos_terminados")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Productos Terminados - Roka Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="bg-dark text-white">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="menu.php">
                <img src="../img/IMG_4124login.png" alt="Logo" width="50" class="rounded-circle me-2">
                Roʞka System - Productos
            </a>
            <a href="productos_distribuidos.php" class="btn btn-outline-info">
                <i class="bi bi-truck me-1"></i>Distribución
            </a>
            <a href="ordenes.php" class="btn btn-outline-warning">
                <i class="bi bi-arrow-left"></i> Órdenes Pendientes
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="bi bi-box-seam"></i> Productos Terminados</h1>

        <!-- Stats SIMPLIFICADOS -->
        <div class="row mb-4 text-center">
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-75 text-white">
                    <div class="card-body">
                        <h3><?= number_format($disponibles) ?></h3>
                        <small><i class="bi bi-check-circle"></i> Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning bg-opacity-75 text-dark">
                    <div class="card-body">
                        <h3><?= number_format($total_prod - $disponibles) ?></h3>
                        <small><i class="bi bi-cart-check"></i> Vendidos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3><?= number_format($total_prod) ?></h3>
                        <small><i class="bi bi-boxes"></i> Total Producido</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla ESENCIAL -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <h5><i class="bi bi-grid-3x3"></i> Inventario</h5>
                <span class="badge bg-success"><?= count($productos) ?> lotes</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaProductos" class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Talla</th>
                                <th>Cant.</th>
                                <th>Tela</th>
                                <th>Color</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td>
                                        <img src="../uploads/<?= htmlspecialchars($prod['imagen']) ?>"
                                            style="width:45px;height:45px;object-fit:cover;border-radius:4px;">
                                    </td>
                                    <td><?= htmlspecialchars($prod['nombre']) ?></td>
                                    <td><span class="badge bg-info"><?= $prod['talla'] ?></span></td>
                                    <td><strong class="text-success"><?= number_format($prod['cantidad']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= $prod['tipo_tela'] ?></span></td>
                                    <td>
                                        <?php if ($prod['color']): ?>
                                            <span class="badge" style="background:<?= getColorBadge($prod['color']) ?>"><?= $prod['color'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $prod['estado'] == 'disponible' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($prod['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m H:i', strtotime($prod['fecha_produccion'])) ?></td>
                                    <td>
                                        <button class="btn btn-outline-warning btn-sm" onclick="guardarStandby(<?= $prod['id'] ?>)"
                                            title="Guardar como modelo para catálogo">
                                            <i class="bi bi-star-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
   <!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaProductos').DataTable({
                language: {
                    decimal: ",",
                    emptyTable: "No hay productos terminados",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ lotes",
                    infoEmpty: "No hay datos",
                    infoFiltered: "(filtrado de _MAX_ total)",
                    thousands: ".",
                    lengthMenu: "Mostrar _MENU_ lotes",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron productos",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                order: [
                    [7, 'desc']
                ], // Fecha
                pageLength: 25,
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    } // Imagen no ordenable
                ]
            });
          
        });
          function guardarStandby(id) {
    if (confirm('¿Guardar este producto TERMINADO como modelo STANDBY para catálogo de ventas?')) {
        const fila = $(`button[onclick="guardarStandby(${id})"]`).closest('tr');
        const nombre = fila.find('td:nth-child(2)').text().trim();
        const talla = fila.find('td:nth-child(3)').text().replace(/[^\w]/g, '');
        const tipoTela = fila.find('td:nth-child(5)').text().trim();
        const color = fila.find('td:nth-child(6)').text().trim();
        const imgSrc = fila.find('img').attr('src');
        
        $.post('guardar_modelo_terminado.php', {
            producto_id: id,
            nombre: nombre,
            talla: talla,
            tipo_tela: tipoTela,
            color: color,
            imagen: imgSrc
        }, function(response) {
            if (response.success) {
                Swal.fire('¡Guardado!', 'Producto agregado a Standby', 'success');
            } else {
                Swal.fire('Error', response.error, 'error');
            }
        }, 'json').fail(function() {
            alert('Error de conexión');
        });
    }
}

    </script>
</body>

</html>