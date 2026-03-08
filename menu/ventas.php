<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

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
  <!-- CSRF meta removed -->
  <title class="text-white">Gestión de Ventas</title>
  <link rel="stylesheet" href="../css/menu.css" />
  <link rel="stylesheet" href="../css/tablas.css" />
  <link rel="stylesheet" href="../css/factura-modal.css" />
  <link rel="stylesheet" href="../DataTables/datatables.min.css" />
  <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* ===== FONDO EXACTO - MÓDULO VENTAS ===== */
    body {
      background:
        /* Fondo oscuro glassmorphism */
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
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      /* background-image:
        linear-gradient(45deg, transparent 49%, rgba(209, 0, 27, 0.04) 50%, rgba(209, 0, 27, 0.04) 51%, transparent 52%),
        radial-gradient(circle at 25% 25%, rgba(197, 2, 2, 0.08) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(197, 2, 2, 0.08) 1px, transparent 1px); */
      background-size: 100px 100px, 50px 50px, 50px 50px;
      pointer-events: none;
      z-index: -1;
    }

    /* ===== HEADER VENTAS ===== */
    .header-ventas {
      text-align: center;
      margin-bottom: 2rem;
      padding: 2rem 0;
      background: rgba(35, 35, 35, 0.8);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      border: 1px solid rgba(209, 0, 27, 0.3);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .header-ventas h2 {
      color: #fff;
      font-weight: 700;
      text-shadow: 0 2px 10px rgba(209, 0, 27, 0.5);
      margin-bottom: 0.5rem;
    }

    .header-ventas .subtitle-ventas {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1.1rem;
      font-weight: 400;
    }

    /* ===== TABLA GLASSMORPHISM - VENTAS ===== */
    #tablaVentas_wrapper {
      background: rgba(35, 35, 35, 0.95) !important;
      backdrop-filter: blur(25px) !important;
      border-radius: 15px !important;
      border: 1px solid rgba(209, 0, 27, 0.2) !important;
      overflow: hidden !important;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6) !important;
      width: 90% !important;
      color: white !important;
    }

    .table thead {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .table thead th {
      border: none !important;
      padding: 16px 15px !important;
      font-weight: 600 !important;
    }

    .table td {
      padding: 16px 15px !important;
      border-color: rgba(209, 0, 27, 0.1) !important;
      color: #e0e0e0 !important;
      vertical-align: middle;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(209, 0, 27, 0.05) !important;
    }

    .table-striped tbody tr:hover {
      background: rgba(209, 0, 27, 0.15) !important;
      transform: scale(1.01);
      transition: all 0.2s ease;
    }

    /* DataTables CONTROLES */
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label {
      color: #e0e0e0 !important;
      font-weight: 500 !important;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      background: rgba(255, 255, 255, 0.9) !important;
      color: #333 !important;
      border: 1px solid rgba(209, 0, 27, 0.3) !important;
      border-radius: 6px !important;
      padding: 6px 10px !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .dataTables_wrapper .dataTables_info {
      color: #e0e0e0 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      color: #e0e0e0 !important;
      background: rgba(209, 0, 27, 0.2) !important;
      border: 1px solid rgba(209, 0, 27, 0.4) !important;
      border-radius: 6px !important;
      margin: 0 2px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      box-shadow: 0 4px 12px rgba(209, 0, 27, 0.4);
    }

    /* Offcanvas EXACTO */
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

    /* Botones principales VENTAS */
    .btn-primary {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      border: none !important;
      padding: 12px 25px !important;
      font-weight: 600 !important;
      transition: all 0.3s ease !important;
      box-shadow: 0 4px 15px rgba(209, 0, 27, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4) !important;
    }

    .btn-success {
      background: linear-gradient(135deg, #198754 0%, #146c43 100%) !important;
      border: none !important;
    }

    .btn-info {
      background: linear-gradient(135deg, #0dcaf0 0%, #0aa3c1 100%) !important;
      border: none !important;
      font-weight: 600 !important;

    }

    /* MODALES - VENTAS */
    .modal-header {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
      color: #fff !important;
      font-weight: 600 !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
    }

    /* Navbar */
    .navbar-dark {
      background: linear-gradient(135deg, #1a1a1a 0%, #2d1b1b 100%) !important;
      box-shadow: 0 4px 20px rgba(209, 0, 27, 0.3) !important;
    }

    /* Botones acciones tabla */
    .btn-group-sm .btn {
      border-radius: 6px !important;
      padding: 6px 10px !important;
      font-size: 0.8rem !important;
    }

    /* Botones centrados */
    .botones-ventas {
      display: flex !important;
      justify-content: center !important;
      gap: 1rem;
      width: 100%;
    }

    #btnNuevaVenta,
    #btnVerGraficoEstadistico {
      min-width: 220px;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
      font-style: normal;
    }

    /* Responsive */
    @media (max-width: 768px) {
      #tablaVentas_wrapper {
        margin: 20px 10px !important;
        font-size: 0.9rem !important;
      }

      .btn-primary {
        padding: 10px 20px !important;
        font-size: 0.9rem !important;
      }
    }

    /* ELIMINAR FONDO PERSONALIZADO */
    .background-wrapper {
      background: transparent !important;
    }

    #divPrecioDolar {
      transition: all 0.3s ease !important;
    }

    #precioDolar {
      transition: all 0.3s ease;
    }

    /* ===== MODAL VENTAS - FONDO OSCURO ROJO CORREGIDO ===== */
    #modalRegistrarVenta .modal-content {
      background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%) !important;
      border: 1px solid rgba(209, 0, 27, 0.4) !important;
      box-shadow: 0 25px 70px rgba(209, 0, 27, 0.3) !important;
      backdrop-filter: blur(20px) !important;
      color: #e0e0e0 !important;
    }

    #modalRegistrarVenta .modal-header {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    #modalRegistrarVenta .modal-body {
      background: rgba(35, 35, 35, 0.95) !important;
      color: #e0e0e0 !important;
      padding: 2rem !important;
    }

    #modalRegistrarVenta .modal-footer {
      background: rgba(26, 26, 26, 0.9) !important;
      border-top: 1px solid rgba(209, 0, 27, 0.2) !important;
    }

    /* Formularios en modal - Contraste mejorado */
    #modalRegistrarVenta .form-control,
    #modalRegistrarVenta .form-select {
      background: rgba(255, 255, 255, 0.92) !important;
      color: #333 !important;
      border: 1px solid rgba(209, 0, 27, 0.3) !important;
      border-radius: 8px !important;
    }

    #modalRegistrarVenta .form-control:focus,
    #modalRegistrarVenta .form-select:focus {
      background: #fff !important;
      color: #333 !important;
      border-color: #d1001b !important;
      box-shadow: 0 0 0 0.2rem rgba(209, 0, 27, 0.25) !important;
    }

    /* Tabla productos - Mejor contraste */
    #productosVentaTable {
      background: rgba(15, 15, 15, 0.95) !important;
      border-radius: 12px !important;
      overflow: hidden !important;
    }

    #productosVentaTable thead th {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      color: #fff !important;
      border: none !important;
    }

    #productosVentaTable tbody td {
      color: #e0e0e0 !important;
      border-color: rgba(209, 0, 27, 0.15) !important;
    }

    #productosVentaTable tbody tr:hover {
      background: rgba(209, 0, 27, 0.12) !important;
    }

    /* Select2 en modal */
    /* ===== SELECT2 CLIENTES - LEGIBLE BLANCO ===== */
    #modalRegistrarVenta .select2-container--default .select2-selection--single {
      background: rgba(255, 255, 255, 0.95) !important;
      border: 2px solid rgba(209, 0, 27, 0.3) !important;
      height: 48px !important;
      border-radius: 10px !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    #modalRegistrarVenta .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #333 !important;
      line-height: 44px !important;
      padding-left: 16px !important;
    }

    #modalRegistrarVenta .select2-dropdown {
      background: #ffffff !important;
      /* ← BLANCO PURO */
      border: 2px solid rgba(209, 0, 27, 0.4) !important;
      border-radius: 12px !important;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
      margin-top: 4px;
    }

    #modalRegistrarVenta .select2-container--default .select2-results__option {
      color: #333 !important;
      padding: 12px 16px !important;
    }

    #modalRegistrarVenta .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background: linear-gradient(135deg, #d1001b, #a10412) !important;
      color: #fff !important;
    }

    /* Labels mejorados */
    #modalRegistrarVenta .form-label {
      color: #fff !important;
      font-weight: 600 !important;
      margin-bottom: 0.5rem !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Subtotal destacado */
    #modalRegistrarVenta #subtotalProducto,
    #modalRegistrarVenta #totalVentaBs {
      color: #d1001b !important;
      font-weight: 700 !important;
      font-size: 1.2rem !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Botones en modal */
    #modalRegistrarVenta .btn {
      border-radius: 8px !important;
      font-weight: 600 !important;
      padding: 10px 20px !important;
      transition: all 0.3s ease !important;
    }

    #modalRegistrarVenta .btn-primary {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
      border: none !important;
    }

    #modalRegistrarVenta .btn:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
    }

    /* ===== FIX CANVAS MODAL GRÁFICOS ===== */
    .chart-container {
      position: relative;
      height: 350px !important;
      /* ← ALTURA FIJA CRÍTICA */
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


    /* Modal responsive */
    @media (max-width: 768px) {
      .chart-container {
        height: 300px !important;
      }

      .modal-xl .modal-body {
        padding: 1.5rem !important;
      }
    }
  </style>


</head>

<body>

  <!-- Navbar superior -->
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
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

      <a class="nav-link text-danger" href="http://localhost/Roka_Sports/menu/menu.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
    </div>
  </div>


  <!-- Header + Botones centrados -->
  <div class="container-fluid py-4">
    <!-- HEADER VENTAS -->
    <div class="header-ventas mb-5 text-center">
      <h2 class="mb-2">
        <i class="fas fa-cash-register me-3"></i>VENTAS
      </h2>
      <div class="subtitle-ventas">
        Gestión completa de ventas Roʞka Sports
      </div>
    </div>

    <!-- BOTONES CENTRADOS -->
    <div class="d-flex justify-content-center mb-4 botones-ventas">
      <a id="btnNuevaVenta" href="generar_ventas.php" class="btn btn-primary btn-lg px-5 py-3 shadow-lg">
        <i class="fas fa-plus me-2"></i>Nueva Venta
      </a>
      <button id="btnVerGraficoEstadistico" class="btn btn-info btn-lg px-5 py-3 shadow-lg">
        <i class="fas fa-chart-bar me-2"></i>Estadísticas
      </button>
    </div>


    <!-- MODAL GRÁFICOS - VERSION FUNCIONAL -->
    <div class="modal fade" id="modalGraficoEstadistico" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">
              <i class="fas fa-chart-bar me-2"></i>📊 Estadísticas Roʞa Sports
            </h5>
            <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-3">
            <div class="row">
              <!-- MES -->
              <div class="col-lg-6 mb-4">
                <h6 class="text-white mb-3">📅 Ventas por Mes</h6>
                <div class="chart-container" style="position: relative; height: 320px; background: #1a1a1a; border-radius: 10px; padding: 20px;">
                  <canvas id="chartMes"></canvas>
                </div>
              </div>
              <!-- DÍAS -->
              <div class="col-lg-6 mb-4">
                <h6 class="text-white mb-3">📊 Ventas por Día</h6>
                <div class="chart-container" style="position: relative; height: 320px; background: #1a1a1a; border-radius: 10px; padding: 20px;">
                  <canvas id="chartDias"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>



    <!-- TABLA VENTAS -->
    <table id="tablaVentas" class="display nowrap" style="width:96%">
      <thead>
        <tr>
          <th>N° Factura</th>
          <th>Fecha Venta</th>
          <th>Cliente</th>
          <th>Total ($)</th>
          <th>Tipo Pago</th>
          <th>Estado</th>
          <th>Usuario</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Registrar Venta -->
  <div class="modal fade" id="modalRegistrarVenta" tabindex="-1" aria-labelledby="modalRegistrarVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <form id="formRegistrarVenta" novalidate>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalRegistrarVentaLabel">Registrar Venta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">

            <!-- Selección Cliente -->
            <div class="mb-3">
              <label for="selectClienteNombre" class="form-label">Selecciona Cliente por Nombre</label>
              <select id="selectClienteNombre" class="form-control" style="width: 100%;" aria-describedby="clienteHelp" placeholder="Selecciona cliente"></select>
            </div>

            <!-- Datos generales -->
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label for="cedulaCliente" class="form-label">Cédula del Cliente</label>
                <input type="text" class="form-control" id="cedulaCliente" name="cedulaCliente" required />
                <div class="invalid-feedback">Debe ingresar cédula correcta de cliente registrado.</div>

              </div>
              <div class="col-md-4">
                <label for="tipoPago" class="form-label">Tipo de Pago</label>
                <select id="tipoPago" class="form-select" name="tipoPago" required>
                  <option value="" disabled selected>Seleccione tipo de pago</option>
                  <option value="contado">Contado</option>
                  <option value="credito">Crédito</option>
                </select>
                <div class="invalid-feedback">Seleccione tipo de pago.</div>
              </div>
              <div class="col-md-4" id="divFechaVencimiento" style="display:none;">
                <label for="fechaVencimiento" class="form-label">Fecha de Vencimiento (para crédito)</label>
                <input type="date" id="fechaVencimiento" name="fechaVencimiento" class="form-control" min="<?= date('Y-m-d') ?>" />
                <div class="invalid-feedback">Ingrese fecha de vencimiento válida.</div>
              </div>
            </div>




            <!-- Agregar Producto -->
            <div class="row g-3 align-items-end mb-4">
              <div class="col-md-4">
                <label for="codigoProducto" class="form-label">Selecciona código de producto</label>
                <select id="codigoProducto" name="codigoProducto" class="form-control" style="width: 100%;" placeholder="Ingrese código o nombre"></select>
              </div>
              <div class="col-md-2">
                <label for="cantidadProducto" class="form-label">Cantidad</label>
                <input type="number" min="1" value="1" id="cantidadProducto" class="form-control" />
              </div>
              <div class="col-md-3">
                <label for="precioUnitario" class="form-label">Precio Unitario ($)</label>
                <input type="number" min="0" step="0.01" id="precioUnitario" class="form-control" />
              </div>
              <div class="col-md-3">
                <label for="monedaPago" class="form-label">Moneda de pago</label>
                <select id="monedaPago" class="form-select">
                  <option value="dolares" selected>Dólares</option>
                  <option value="bolivares">Bolívares</option>
                </select>
              </div>
              <div class="col-md-3 mt-3" id="divPrecioDolar" style="display:none;">
                <label for="precioDolar" class="form-label">Precio Dólar BCV</label>
                <input type="number" min="0" step="0.01" id="precioDolar" class="form-control" placeholder="Precio del dólar" />
              </div>
              <div class="col-md-3 mt-3">
                <strong>Subtotal (con IVA): </strong>
                <span id="subtotalProducto">Bs 0.00</span>
              </div>
              <div class="col-md-3 mt-3">
                <button type="button" id="btnAgregarProducto" class="btn btn-primary w-100">Agregar Producto</button>
              </div>
            </div>

            <!-- Tabla productos agregados -->
            <div class="table-responsive">
              <table id="productosVentaTable" class="table table-striped table-bordered" style="width: 100%;">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario ($)</th>
                    <th>Subtotal (+IVA)</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                  <tr>
                    <th colspan="4" class="text-end">Total:</th>
                    <th id="totalVentaBs">0.00</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Pago en abonos -->
            <div id="divAbonos" style="display:none;" class="mt-4">
              <h6>Pago en abonos</h6>
              <div class="mb-3">
                <button type="button" class="btn btn-outline-primary me-2" id="abono_50_50">Abono 50% y 50%</button>
                <button type="button" class="btn btn-outline-primary" id="abono_25_75">Abono 25% y 75%</button>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="primerAbono" class="form-label">Primer pago ($)</label>
                  <input type="text" id="primerAbono" readonly class="form-control" />
                </div>
                <div class="col-md-6">
                  <label for="segundoAbono" class="form-label">Segundo pago ($)</label>
                  <input type="text" id="segundoAbono" readonly class="form-control" />
                </div>
              </div>
            </div>

            <!-- Contenedor alertas -->
            <div id="alertContainerModal" class="mt-3"></div>

          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Guardar Venta</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  </div>
  <style>
    div.factura-body table tbody tr td {
      color: black !important;
    }
    .no-print {
      display: none !important;
    }
  </style>
  <div class="modal fade" id="modalFactura" tabindex="-1" aria-labelledby="modalFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header gap-3">
          <h5 class="modal-title" id="modalFacturaLabel">Factura</h5>
          <button type="button" class="btn btn-danger " id="ver">Abrir en otra pestaña</button>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="contenidoFactura">
          <!-- Factura dinámica -->
        </div>
      </div>
    </div>
  </div>




  <!-- JS: jQuery, Bootstrap, DataTables -->
  <script src="../js/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
  <script src="../DataTables/datatables.min.js"></script>
  <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>

  <!-- CDN para botones -->

  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

  <script src="../js/menu_ventas.js"></script>





</body>

</html>