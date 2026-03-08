<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.php");
  exit;
}

if (isset($_SESSION['asistente_id'])) {
    include("../database/connect_db.php");
    $stmt = $conex->prepare("SELECT ua.estado FROM usuario_asistente ua WHERE ua.id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || $result->fetch_assoc()['estado'] !== 'activo') {

        $_SESSION = array();                    //  Limpiar datos
        if (ini_get("session.use_cookies")) {   //  Borrar cookie PHPSESSID
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();                      //  Destruir servidor
        header("Location: ../index.php?session_killed=1");
        exit();
    }
    $stmt->close();
}

if (isset($_SESSION['asistente_id'])) {
    $stmt = $conex->prepare("UPDATE usuario_asistente SET ultima_actividad = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $stmt->close();
}

$usuarioId = $_SESSION['usuario_id'];
$usuarioNombre = $_SESSION['usuario_nombre'];
$usuarioTipo = $_SESSION['usuario_tipo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Créditos Pendientes</title>
    <link rel="stylesheet" href="../css/menu.css" />
    <link rel="stylesheet" href="../css/tablas.css" />
    <link rel="stylesheet" href="../DataTables/datatables.min.css" />
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../Font_awesome/css/all.min.css" />

    <style>
/* ===== FONDO NEGRO EXACTO - MÓDULO USUARIO/PROVEEDOR ===== */
body {
    background: 
        /* Fondo negro puro sin imagen */
        linear-gradient(rgba(26, 26, 26, 0.95), rgba(15, 15, 15, 0.98)),
        /* Gradiente rojo sutil */
        radial-gradient(circle at 20% 80%, rgba(209, 0, 27, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(197, 2, 2, 0.12) 0%, transparent 50%),
        /* Patrón geométrico sutil */
        linear-gradient(90deg, transparent 48%, rgba(209, 0, 27, 0.03) 50%, rgba(209, 0, 27, 0.03) 52%, transparent 54%);
    background-attachment: fixed;
    background-size: cover, auto, auto, 50px 50px;
    min-height: 100vh;
    position: relative;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;

    background-size: 100px 100px, 50px 50px, 50px 50px;
    pointer-events: none;
    z-index: -1;
}

/* QUITA imagen de fondo del wrapper */
.background-wrapper {
    background: transparent !important;
    background-image: none !important;
}

/* ===== TÍTULO PRO - IGUAL USUARIO ===== */
.titulo-finanzas {
    color: #ffffff !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    font-weight: 700;
    font-size: 2.5rem;
    text-align: center;
    margin-bottom: 2rem;
    padding: 20px 0;
}

/* ===== TABLA GLASSMORPHISM - EXACTO USUARIO ===== */
.dt-table-responsive {
    background: rgba(35, 35, 35, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 15px;
    border: 1px solid rgba(209, 0, 27, 0.2);
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
    margin: 0;
    padding: 25px;
}

.table thead {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
    color: #fff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 1px;
    border: none;
    padding: 15px 12px;
}

.table td {
    padding: 16px 15px;
    border-color: rgba(209, 0, 27, 0.1);
    color: #e0e0e0;
    vertical-align: middle;
    font-weight: 500;
}

.table-striped tbody tr:nth-of-type(odd) {
    background: rgba(209, 0, 27, 0.05);
}

.table-hover tbody tr:hover {
    background: rgba(209, 0, 27, 0.15) !important;
    color: #fff !important;
}

/* DataTables CONTROLES */
.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label {
    color: #e0e0e0;
    font-weight: 500;
}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    background: rgba(255,255,255,0.9) !important;
    color: #333 !important;
    border: 1px solid rgba(209,0,27,0.3) !important;
    border-radius: 6px;
    padding: 6px 10px;
}

.dataTables_wrapper .dataTables_info {
    color: #e0e0e0;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    color: #e0e0e0 !important;
    background: rgba(209,0,27,0.2) !important;
    border: 1px solid rgba(209,0,27,0.4) !important;
    border-radius: 6px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
    color: #fff !important;
}

/* Botones ROKA */
.btn-pdf-roka, .btn-pagar-pendiente {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
    border: none !important;
    border-radius: 10px;
    padding: 12px 25px;
    font-weight: 600;
    color: white !important;
    box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4);
    transition: all 0.3s ease;
}

.btn-pdf-roka:hover, .btn-pagar-pendiente:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(209, 0, 27, 0.6);
}

/* Offcanvas */
.offcanvas {
    background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%);
    border-right: 1px solid rgba(209, 0, 27, 0.3);
    box-shadow: 5px 0 30px rgba(209, 0, 27, 0.2);
}

/* ===== MODALES - EXACTOS USUARIO ===== */
.modal-header {
    background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
    color: #fff;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.modal-footer {
    position: sticky;
    bottom: 0;
    background: #f8f9fa;
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    z-index: 1050;
}

.modal-body {
    max-height: 60vh;
    overflow-y: auto;
    padding: 1rem 1.5rem;
}

/* Inputs modales */
.form-control, .form-select {
    background: rgba(255,255,255,0.95) !important;
    border: 1px solid #dee2e6 !important;
    color: #333 !important;
}

/* Toast */
.toast {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
    border: none;
    color: white;
}

/* Modal estilos ROKA */
.bg-gradient-roka {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
    border-radius: 12px 12px 0 0 !important;
}

.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 25px 70px rgba(209, 0, 27, 0.3);
}

#fechaRangeDisplay {
    font-size: 0.85rem;
}

.form-control-lg:focus {
    border-color: #d1001b;
    box-shadow: 0 0 0 0.2rem rgba(209, 0, 27, 0.25);
}

/* ✅ Botones NUNCA ocultos en responsive */
table.dataTable.dtr-column td.control:before,
table.dataTable.dtr-column td.dtr-control:before {
    background-color: #d1001b;
}

/* Columna acciones SIEMPRE visible */
.never {
    position: relative !important;
    display: table-cell !important;
    visibility: visible !important;
}

/* Botón pagar 100% ancho en móvil */
@media (max-width: 768px) {
    .btnPagar {
        font-size: 0.75rem !important;
        padding: 0.25rem 0.5rem !important;
        min-height: 32px !important;
    }
}


/* Responsive */
@media (max-width: 768px) {
    .titulo-finanzas {
        font-size: 2rem;
    }
    .dt-table-responsive {
        margin: 10px;
        padding: 15px;
    }
}
</style>


    




</head>

<body>
<div> <!-- class="background-wrapper" -->
    <!-- Navbar superior -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1"> 
                <span class="nav-link">Roʞka Sports</span>
            </a>
            <a class="navbar-brand" href="http://localhost/Roka_Sports/menu/menu.php">Inicio</a>
        </div>
    </nav>

    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start" id="offcanvasSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link" href="../menu/modulo_proveedor.php"><i class="bi bi-truck me-2"></i>Proveedores</a>
                <a class="nav-link" href="../menu/compras.php"><i class="bi bi-shop me-2"></i>Compras de telas</a>
                <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Inventario</a>
                <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
                <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
                <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
                <a class="nav-link active" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
                <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
                <hr class="my-2">
                <a class="nav-link text-danger" href="http://localhost/Roka_Sports/login.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
            </nav>
        </div>
    </div>

   <div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <!-- HEADER: Título + Botón alineados -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <!-- Título -->
                <div class="titulo-finanzas flex-grow-1">
                    <i class="fas fa-wallet me-3"></i>
                    <span>Créditos Pendientes</span>
                </div>
                
                <!-- Botón PDF -->
                <button class="btn btn-pdf-roka shadow-lg px-4 py-2" onclick="window.open('../vistas/exportar_finanzas_pdf.php', '_blank')">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </button>
            </div>
            
            <!-- Tabla Glassmorphism -->
            <div class="dt-table-responsive">
                <table id="tablaComprasPendientes" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th width="50"><i class="fas fa-circle-info"></i></th>
                            <th>ID Compra</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Saldo Pendiente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1200" id="toastContainer"></div>

  <!-- Modal Registrar Pago - VALIDACIÓN FECHA ✅ -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formRegistrarPago" novalidate>
            <input type="hidden" id="compraIdPago" name="compraIdPago" />
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-gradient-roka">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>Registrar Pago
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- SALDO PENDIENTE -->
                    <div class="alert alert-warning mb-4" role="alert">
                        <strong id="labelSaldoPendiente" class="text-danger fs-5"></strong>
                    </div>

                    <!-- MONTO -->
                    <div class="mb-3">
                        <label for="montoPago" class="form-label fw-bold">
                            <i class="fas fa-coins text-warning me-1"></i>Monto a pagar
                        </label>
                        <input type="number" 
                               class="form-control form-control-lg" 
                               id="montoPago" 
                               name="montoPago" 
                               min="0.01" 
                               step="0.01" 
                               required />
                        <div class="invalid-feedback">Monto debe ser > 0 y ≤ saldo pendiente</div>
                        <div class="form-text text-muted">Máximo: <span id="maxMontoDisplay">-</span></div>
                    </div>

                    <!-- FECHA VALIDADA -->
                   <div class="mb-3">
    <label for="fechaPago" class="form-label fw-bold">
        <i class="fas fa-calendar-check text-success me-1"></i>Fecha de pago
    </label>
    <input type="date" 
           class="form-control form-control-lg" 
           id="fechaPago" 
           name="fechaPago" 
           required 
           readonly /> <!-- ✅ Bloqueado visualmente -->
    <div class="form-text text-success fw-semibold">
        📅 <strong>Fecha Actual</strong> (Automático)
    </div>
</div>


                    <!-- MÉTODO -->
                    <div class="mb-3">
                        <label for="metodoPago" class="form-label fw-bold">
                            <i class="fas fa-credit-card text-primary me-1"></i>Método de pago
                        </label>
                        <select class="form-select form-select-lg" id="metodoPago" name="metodoPago" required>
                            <option value="" disabled selected>Seleccione método</option>
                            <option value="Efectivo">💵 Efectivo</option>
                            <option value="Transferencia">🏦 Transferencia</option>
                            <option value="Pago Móvil">📱 Pago Móvil</option>
                            <option value="Tarjeta">💳 Tarjeta</option>
                            <option value="Divisa">💵 Divisa</option>
                        </select>
                        <div class="invalid-feedback">Seleccione método de pago</div>
                    </div>

                    <!-- OBSERVACIONES -->
                    <div class="mb-3">
                        <label for="obsPago" class="form-label">Observaciones (opcional)</label>
                        <textarea class="form-control" 
                                  id="obsPago" 
                                  name="obsPago" 
                                  rows="3" 
                                  placeholder="Ej: Pago parcial, referencia #1234..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-pagar-pendiente shadow-lg px-4">
                        <i class="fas fa-save me-2"></i>Guardar Pago
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="../js/jquery-3.4.1.min.js"></script>
<script src="../DataTables/datatables.min.js"></script>
<script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
<script src="../js/finanzas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
