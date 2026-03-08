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
$esAdmin = isset($_SESSION['usuario_rol']) && ($_SESSION['usuario_rol'] == 'admin' || $_SESSION['usuario_tipo'] == 1);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Proveedores</title>
    <link rel="stylesheet" href="../css/menu.css" />
    <link rel="stylesheet" href="../css/tablas.css" />
    <link rel="stylesheet" href="../DataTables/datatables.min.css" />
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../Font_awesome/css/all.min.css" />
    <link rel="stylesheet" href="../css/correciones.css" />

    <style>

  

/* ===== FONDO EXACTO - MÓDULO USUARIO ===== */
body {
    background: 
        /* Imagen de fondo sutil */
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
    /* background-image: 
        /* Líneas diagonales sutiles 
        linear-gradient(45deg, transparent 49%, rgba(209, 0, 27, 0.04) 50%, rgba(209, 0, 27, 0.04) 51%, transparent 52%),
        /* Puntos hexagonales 
        radial-gradient(circle at 25% 25%, rgba(197, 2, 2, 0.08) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(197, 2, 2, 0.08) 1px, transparent 1px); */
    background-size: 100px 100px, 50px 50px, 50px 50px;
    pointer-events: none;
    z-index: -1;
}

/* ===== TABLA GLASSMORPHISM - EXACTO USUARIO ===== */
.dt-table-responsive {
    background: rgba(35, 35, 35, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 15px;
    border: 1px solid rgba(209, 0, 27, 0.2);
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
    margin: 30px auto;
    max-width: 95%;
}

.table thead {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
    color: #fff;
}

.table td {
    padding: 16px 15px;
    border-color: rgba(209, 0, 27, 0.1);
    color: #e0e0e0;
}

.table-striped tbody tr:nth-of-type(odd) {
    background: rgba(209, 0, 27, 0.05);
}

div.dt-container {
    width: 100%;
}

/* DataTables CONTROLES (reemplaza tus estilos anteriores) */
.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label {
    color: #e0e0e0;
    font-weight: 500;
    margin-right: 8px;
}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    background: rgba(255,255,255,0.9);
    color: #333 !important;
    border: 1px solid rgba(209,0,27,0.3);
    border-radius: 6px;
    padding: 6px 10px;
}

.dataTables_wrapper .dataTables_info {
    color: #e0e0e0;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    color: #e0e0e0 !important;
    background: rgba(209,0,27,0.2) !important;
    border: 1px solid rgba(209,0,27,0.4);
    border-radius: 6px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
    color: #fff !important;
}

/* Offcanvas EXACTO */
.offcanvas {
    background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%);
    border-right: 1px solid rgba(209, 0, 27, 0.3);
    box-shadow: 5px 0 30px rgba(209, 0, 27, 0.2);
}

/* Botones principales */
.botones-proveedores {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
}

.btn-primary, .btn-outline-warning {
    border-radius: 10px;
    padding: 12px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .botones-proveedores {
        flex-direction: column;
        padding: 20px;
    }
    .dt-table-responsive {
        margin: 20px 10px;
        font-size: 0.9rem;
    }
}

/* ===== MODALES - EXACTOS DEL USUARIO ===== */
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

.modal-footer .btn {
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.modal-footer .btn:hover {
    filter: brightness(90%);
}

.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 1rem);
}

/* Efectos botones */
.btn {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.btn:active {
    transform: scale(0.95);
    box-shadow: 0 5px 10px rgba(0,0,0,0.15);
}

/* ===== VALIDACIONES BOOTSTRAP 5 (mantén las tuyas existentes) ===== */
.form-control.is-valid {
    border-color: #28a745 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.7-.7c.3-.3.8-.3 1.1 0L5.4 7.3c.4.4.9.4 1.2 0l2.2-2.2c.3-.3.3-.8 0-1.1l-.7-.7c-.3-.3-.8-.3-1.1 0L5 5.4c-.3.3-.8.3-1.1 0L2.3 3.7c-.3-.3-.8-.3-1.1 0l-.7.7c-.3.3-.3.8 0 1.1L2.3 6.73z'/%3e%3c/svg%3e") !important;
    box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25) !important;
}

.form-control.is-invalid {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
    box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important;
}



      /* VALIDACIONES VISUALES BOOTSTRAP 5 */
.form-control.is-valid {
    border-color: #198754 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
}

.form-control.is-invalid {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

/* ===== FIX CANVAS MODAL GRÁFICOS ===== */
.chart-container {
    position: relative;
    height: 350px !important;  /* ← ALTURA FIJA CRÍTICA */
    width: 100% !important;
    border-radius: 12px;
    background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%);
    border: 2px solid rgba(209, 0, 27, 0.3);
    padding: 15px;
    box-shadow: 0 8px 32px rgba(209, 0, 27, 0.2);
    backdrop-filter: blur(10px);
}

.chart-container canvas {
    max-height: 100% !important;
    max-width: 100% !important;
    width: 100% !important;
    height: 100% !important;
}

.chart-container {
    position: relative !important;
    height: 320px !important;
    background: linear-gradient(145deg, #1a1a1a, #2d1b21) !important;
    border: 2px solid rgba(209, 0, 27, 0.4) !important;
    border-radius: 15px !important;
    padding: 20px !important;
    margin-bottom: 1rem !important;
}

.chart-container canvas {
    filter: drop-shadow(0 4px 12px rgba(209, 0, 27, 0.3)) !important;
}

 .offcanvas {
            background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%) !important;
            border-right: 1px solid rgba(209, 0, 27, 0.3) !important;
            box-shadow: 5px 0 30px rgba(209, 0, 27, 0.2) !important;
        }

        .offcanvas .nav-link {
            color: #e0e0e0 !important;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s ease;
        }

        .offcanvas .nav-link:hover,
        .offcanvas .nav-link.active {
            background: linear-gradient(135deg, #d1001b, #a10412) !important;
            color: white !important;
            transform: translateX(5px);
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
                <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1" />
                <span class="nav-link">Roʞka Sports</span>
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
            <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Inventario</a>
           
            <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
            <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
            <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
                    <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 'estandar' && $esAdmin) { ?>
                        <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
                        <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
                    <?php } ?>
                    <hr class="my-2" />
                <a class="nav-link text-danger" href="http://localhost/Roka_Sports/login.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
            </nav>
        </div>
    </div>

   <div class="container mt-5">
    <div class="text-center mb-5">
        <h1 class="text-white display-5 fw-bold mb-3 text-shadow">
            <i class="fas fa-truck text-warning me-3"></i>
            Gestión de Proveedores
        </h1>
        <p class="text-white-50 lead">Control total de tus proveedores</p>
    </div>

    <!-- Reemplaza tu contenedor actual -->
<div class="d-flex flex-wrap gap-3 my-4 justify-content-center botones-proveedores">
    <button class="btn btn-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#modalRegistrarProveedor" id="btnNuevoProveedor">
        <i class="fas fa-users me-2"></i>Nuevo Proveedor
    </button>
</div>

<!-- Reemplaza tu tabla -->
<div class="dt-table-responsive">
    <table id="proveeTable" class="table table-striped nowrap" style="width:100%">
        <thead>
            <tr>
                <th>RIF</th>
                <th>Nombres</th>
                <th>Empresa</th>
                <th>Dirección</th>
                <th>Fecha Registro</th>
                <th>Contacto</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Los datos se cargarán dinámicamente mediante JavaScript -->
        </tbody>
    </table>
</div>

        </main>

     <!-- Modal Registrar Proveedor -->
<div class="modal fade" id="modalRegistrarProveedor" tabindex="-1" aria-labelledby="modalRegistrarProveedorLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered">
   <div class="modal-content">
     <form id="formRegistrarProveedor">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRegistrarProveedorLabel">
          <i class="fas fa-user-plus me-2 text-success"></i>Registrar Proveedor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- RIF -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-id-card me-1 text-danger"></i>RIF <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="registrar_rif" name="rif" pattern="[JGVE]-\d{8}-\d" title="Formato: J-12345678-9" required maxlength="12" autocomplete="off" />
          <div class="form-text text-muted">RIF venezolano: J-XXXXXXXX-X</div>
        </div>

        <!-- NOMBRES -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-user me-1 text-primary"></i>Nombres Completos <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="registrar_nombres" name="nombres" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü ]{3,50}" title="Solo letras y espacios" required minlength="3" maxlength="50" autocomplete="off" />
          <div class="form-text text-muted">Nombre y apellidos del representante</div>
        </div>

        <!-- EMPRESA -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-building me-1 text-info"></i>Empresa <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="registrar_empresa" name="empresa" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9 \.\,\-]{3,50}" title="Nombre empresa" required minlength="3" maxlength="50" autocomplete="off" />
          <div class="form-text text-muted">Nombre legal de la empresa</div>
        </div>

        <!-- DIRECCION -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-building me-1 text-info"></i>Dirección <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="registrar_direccion" name="direccion" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9 \.\,\-]{3,100}" title="Dirección del proveedor" required minlength="3" maxlength="100" autocomplete="off" />
          <div class="form-text text-muted">Dirección física del proveedor</div>
        </div>

        <!-- CONTACTO -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-phone me-1 text-success"></i>Contacto <span class="text-danger">*</span></label>
          <input type="tel" class="form-control shadow-sm" id="registrar_contacto" name="contacto" pattern="\d{7,11}" title="7-11 dígitos" required maxlength="11" autocomplete="tel" />
          <div class="form-text text-muted">Teléfono de contacto</div>
        </div>

        <div id="mensajeRegistrar" class="text-danger"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancelar</button>
        <button type="submit" class="btn btn-success btn-custom">
          <i class="fas fa-save me-1"></i>Registrar Proveedor
          <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
        </button>
      </div>
     </form>
   </div>
 </div>
</div>


       <!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered">
   <div class="modal-content">
     <form id="formEditarProveedor">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarProveedorLabel">
          <i class="fas fa-user-edit me-2 text-primary"></i>Editar Proveedor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_rif_original" name="rif_original" />
        
        <!-- RIF (SOLO LECTURA) -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-id-card me-1 text-danger"></i>RIF</label>
          <input type="text" class="form-control shadow-sm bg-secondary" id="edit_rif" name="rif" readonly />
        </div>

        <!-- NOMBRES -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-user me-1 text-primary"></i>Nombres Completos <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="edit_nombres" name="nombres" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü ]{3,50}" title="Solo letras y espacios" required minlength="3" maxlength="50" autocomplete="off" />
        </div>

        <!-- EMPRESA -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-building me-1 text-info"></i>Empresa <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="edit_empresa" name="empresa" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9 \.\,\-]{3,50}" title="Nombre empresa" required minlength="3" maxlength="50" autocomplete="off" />
        </div>
        <!-- DIRECCION -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-building me-1 text-info"></i>Dirección <span class="text-danger">*</span></label>
          <input type="text" class="form-control shadow-sm" id="edit_direccion" name="direccion" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9 \.\,\-]{3,100}" title="Dirección del proveedor" required minlength="3" maxlength="100" autocomplete="off" />
        </div>

        <!-- CONTACTO -->
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-phone me-1 text-success"></i>Contacto <span class="text-danger">*</span></label>
          <input type="tel" class="form-control shadow-sm" id="edit_contacto" name="contacto" pattern="\d{7,11}" title="7-11 dígitos" required maxlength="11" autocomplete="tel" />
          <div class="form-text text-muted">Teléfono de contacto</div>
        </div>

        <div id="mensajeEditar" class="text-danger"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Cancelar</button>
        <button type="submit" class="btn btn-primary btn-custom">
          <i class="fas fa-save me-1"></i>Guardar Cambios
          <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
        </button>
      </div>
     </form>
   </div>
 </div>
</div>


        <!-- Modal Confirmar Eliminación -->
        <div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-labelledby="modalConfirmDeleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalConfirmDeleteLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        ¿Está seguro que desea eliminar el proveedor con RIF <strong id="rifToDelete"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="btnCancelDelete" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="btnConfirmDelete" class="btn btn-danger">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor para alertas flotantes -->
    <div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1055;"></div>

    <?php include("inferior.html"); ?>

    <!-- Scripts -->
    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="../js/menu_modulo_proveedor.js"></script>
  <script>
// == INICIALIZAR DESPUÉS DE QUE jQuery y DataTables estén listos ==
$(document).ready(function() {
    // FUNCIONES DE VALIDACIÓN
    function validarRIF(valor) {
        return /^[JGVE]-\d{8}-\d$/.test(valor);
    }

    function validarNombres(valor) {
        const cleaned = valor.trim();
        return cleaned.length >= 3 && /^[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]+$/.test(cleaned);
    }

    function validarEmpresa(valor) {
        const cleaned = valor.trim();
        return cleaned.length >= 3 && /^[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-]+$/.test(cleaned);
    }

    function validarContacto(valor) {
        return /^\d{7,11}$/.test(valor);
    }

    function actualizarEstadoValidacion($input, esValido) {
        $input.removeClass('is-invalid is-valid');
        if (esValido && $input.val().trim().length > 0) {
            $input.addClass('is-valid');
        } else if ($input.val().trim().length > 0 && !esValido) {
            $input.addClass('is-invalid');
        }
    }

    // VALIDACIÓN RIF - Autoformato J-12345678-9
    function validarRIFInput($input) {
        // Normalizar entrada: mayúsculas, eliminar caracteres inválidos y guiones repetidos
        let raw = $input.val().toUpperCase();
        let valor = raw.replace(/[^JGVE0-9\-]/g, '').replace(/-+/g, '-');
        
        // Autoformatear en varios casos comunes:
        // - Si se escribió una letra + 9 dígitos sin guiones (ej. J123456789)
        // - Si se escribieron sólo 9 dígitos (ej. 123456789) → asumimos prefijo 'J'
        const digitos = valor.replace(/[^0-9]/g, '');
        if (digitos.length === 9 && !valor.includes('-')) {
            if (/^[JGVE][0-9]{9}$/.test(valor)) {
                // Ej: J123456789 -> J-12345678-9
                valor = valor.charAt(0) + '-' + digitos.substring(0, 8) + '-' + digitos.substring(8);
            } else if (/^[0-9]{9}$/.test(valor)) {
                // Ej: 123456789 -> J-12345678-9 (asumir J por defecto)
                valor = 'J-' + digitos.substring(0, 8) + '-' + digitos.substring(8);
            }
        }
        
        // Si ya hay guiones pero en posiciones no estándar, intentar normalizar
        const m = valor.match(/^([JGVE])?-?(\d{8})-?(\d)$/);
        if (m) {
            const letra = (m[1] ? m[1] : 'J');
            valor = letra + '-' + m[2] + '-' + m[3];
        }
        
        $input.val(valor);
        const esValido = validarRIF(valor);
        actualizarEstadoValidacion($input, esValido);
    }

    // REGISTRAR
    $('#registrar_rif').on('input', function() {
        validarRIFInput($(this));
    });

    $('#registrar_nombres').on('input', function() {
        let valor = $(this).val().replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+/, '');
        $(this).val(valor);
        actualizarEstadoValidacion($(this), validarNombres(valor));
    });

    $('#registrar_empresa').on('input', function() {
        let valor = $(this).val().replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+/, '');
        $(this).val(valor);
        actualizarEstadoValidacion($(this), validarEmpresa(valor));
    });

    $('#registrar_contacto').on('input', function() {
        let valor = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(valor);
        actualizarEstadoValidacion($(this), validarContacto(valor));
    });

    // EDITAR
    $('#edit_nombres').on('input', function() {
        let valor = $(this).val().replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+/, '');
        $(this).val(valor);
        actualizarEstadoValidacion($(this), validarNombres(valor));
    });

    $('#edit_empresa').on('input', function() {
        let valor = $(this).val().replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+/, '');
        $(this).val(valor);
        actualizarEstadoValidacion($(this), validarEmpresa(valor));
    });

    $('#edit_contacto').on('input', function() {
        let valor = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(valor);
        actualizarEstadoValidacion($(this), validarContacto(valor));
    });

    // VALIDAR FORMULARIOS
    $('#formRegistrarProveedor, #formEditarProveedor').on('submit', function(e) {
        const $form = $(this);
        let hayErrores = false;
        
        $form.find('input[required]').each(function() {
            const $campo = $(this);
            if ($campo.val().trim().length === 0 || !$campo.hasClass('is-valid')) {
                hayErrores = true;
                $campo.addClass('is-invalid');
            }
        });
        
        if (hayErrores) {
            e.preventDefault();
            Swal.fire('❌ Error', 'Corrija los campos en ROJO', 'error');
            return false;
        }
    });
});
</script>





</body>

</html>
