<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

// Validar tipo de usuario
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] != 1) {
    header("Location: menu.php");
    exit;
}

if (isset($_SESSION['asistente_id'])) {
    if ($conex) {
        $stmt = $conex->prepare("UPDATE usuario_asistente SET ultima_actividad = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['asistente_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Preparación de statement falló: " . ($conex ? $conex->error : 'no hay conexión'));
        }
    } else {
        error_log('No hay conexión a la base de datos en pagar_abonos.php al actualizar actividad.');
    }
}
// Puedes obtener usuario si lo necesitas
$usuarioId = $_SESSION['usuario_id'];
$usuarioNombre = $_SESSION['usuario_nombre'];
$usuarioTipo = $_SESSION['usuario_tipo'];
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sección de abonos</title>
    <link rel="stylesheet" href="../css/menu.css" />
    <link rel="stylesheet" href="../css/tablas.css" />
    <link rel="stylesheet" href="../DataTables/datatables.min.css" />
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
       /* FONDO DARK ROKA */
.background-wrapper {
    background: linear-gradient(135deg, 
        #1a1a1a 0%,
        #2d1b21 25%,
        #3a1f24 50%,
        #2c181a 75%,
        #1a1a1a 100%);
    background-size: 400% 400%;
    animation: gradientShift 20s ease infinite;
    min-height: 100vh;
    position: relative;
}
.background-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1;
}
.background-wrapper > * {
    position: relative;
    z-index: 2;
}
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* CARD para tabla (opcional) */
.card-abonos {
    background: linear-gradient(145deg, rgba(26,26,26,0.98), rgba(45,27,33,0.98));
    border-radius: 20px;
    border: 1px solid rgba(183,28,28,0.3);
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    color: #fff;
    padding: 1.5rem;
}
.table-abonos th {
    background: linear-gradient(135deg, #B71C1C, #8B0000, #2d1b21);
    color: #fff !important;
    font-weight: 700;
}
.table-abonos td {
    color: #e0e0e0;
}

.card-abonos {
    background: linear-gradient(145deg, rgba(26,26,26,0.98), rgba(45,27,33,0.98));
    border-radius: 20px;
    border: 1px solid rgba(183,28,28,0.3);
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    color: #fff;
    backdrop-filter: blur(15px);
}
.table-abonos th {
    background: linear-gradient(135deg, #B71C1C, #8B0000);
    color: #fff !important;
    font-weight: 700;
}
.table-abonos td {
    color: #e0e0e0;
    border-color: rgba(255,255,255,0.1);
}


    </style>
</head>
<body>

<!-- Navbar superior -->
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <!-- Botón para abrir sidebar -->
        <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1"> 
            <span class="nav-link">Roʞka System</span>
        </a>
        <a class="navbar-brand" href="http://localhost/Roka_Sports/menu/menu.php">Inicio</a>
    </div>
</nav>

<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menú</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column">
            <a class="nav-link" href="../menu/modulo_proveedor.php"><i class="bi bi-truck me-2"></i>Proveedores</a>
            <a class="nav-link" href="../menu/compras.php"><i class="bi bi-shop me-2"></i>Compras de telas</a>
            <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Compras de Materia Prima</a>
           
            <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
            <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
            <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
                       <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
            <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
            <hr class="my-2">
            <a class="nav-link text-danger" href="http://localhost/Roka_Sports/login.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
        </nav>
    </div>
</div>

 
<div> <!--  class="background-wrapper" style="background-image: url('../img/fondos/cuatro.jpg'); background-size: cover; background-position: center; min-height: 100vh;" -->


<div class="container mt-4">
    <div class="card-abonos mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 p-3">
            <h2 class="mb-0 text-white">
                <i class="bi bi-receipt text-danger me-2"></i>Historial de cuentas pendientes
            </h2>
            <button class="btn btn-danger btn-sm shadow" onclick="window.open('../vistas/exportar_abonos-fpdf.php','_blank')">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
        </div>
        <div class="table-responsive">
            <table id="tablaAbonosPendientes" class="table table-striped table-abonos mb-0" style="width:100%">
        <thead>
            <tr>
                <th>ID Venta</th>
                <th>Cliente</th>
                <th>Monto Total ($)</th>
                <th>Saldo Pendiente (USD)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
</div>

<!-- Modal para Pagar Abono -->
<div class="modal fade" id="modalPagarAbono" tabindex="-1" aria-labelledby="modalPagarAbonoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formPagarAbono" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagarAbonoLabel">Pagar Abono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalVentaId" name="id_venta" />
                    <div class="mb-3">
                        <label for="modalClienteNombre" class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="modalClienteNombre" readonly />
                    </div>
                    <div class="mb-3">
                        <label for="modalMontoTotal" class="form-label">Monto Total (Dólares)</label>
                        <input type="text" class="form-control" id="modalMontoTotal" readonly />
                    </div>
                    <div class="mb-3">
                        <label for="modalSaldoPendiente" class="form-label">Saldo Pendiente ($)</label>
                        <input type="text" class="form-control" id="modalSaldoPendiente" readonly />
                    </div>
                    <div class="mb-3">
                        <label for="modalMontoPago" class="form-label">Monto a Pagar ($)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="modalMontoPago" name="monto_pago" required />
                        <div class="invalid-feedback">
                            Por favor ingresa un monto válido dentro del rango permitido.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Confirmar Pago</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
</div>
</div> <!-- Cierre del background-wrapper -->

<script src="../js/jquery-3.4.1.min.js"></script>
<script src="../DataTables/datatables.min.js"></script>
<script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>

<script src="../js/menu_pagar_abonos.js"></script>

</body>
</html>
