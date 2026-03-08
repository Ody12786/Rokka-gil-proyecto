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
    <title>Inventario</title>
    <link rel="stylesheet" href="../css/menu.css" />
    <link rel="stylesheet" href="../css/tablas.css" />
    <link rel="stylesheet" href="../DataTables/datatables.min.css" />
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.6/css/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../Font_awesome/css/all.min.css" />
    <link rel="stylesheet" href="../css/correciones.css" />

    <style>
   /* ===== FONDO GLASSMORPHISM - MÓDULO MATERIA PRIMA ===== */
.background-wrapper {
    background: 
        /* Imagen de fondo con overlay */
        linear-gradient(rgba(26, 26, 26, 0.95), rgba(15, 15, 15, 0.98)),
        /* Gradiente rojo sutil */
        radial-gradient(circle at 20% 80%, rgba(209, 0, 27, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(197, 2, 2, 0.12) 0%, transparent 50%),
        /* Patrón geométrico */
        linear-gradient(90deg, transparent 48%, rgba(209, 0, 27, 0.03) 50%, rgba(209, 0, 27, 0.03) 52%, transparent 54%),
        url('../img/fondos/doss.jpeg');
    background-attachment: fixed, fixed, fixed, fixed, fixed;
    background-size: cover, auto, auto, 50px 50px, cover;
    background-position: center, 20% 80%, 80% 20%, 0 0, center;
    min-height: 100vh;
    padding-top: 70px;
    position: relative;
}

.background-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        linear-gradient(45deg, transparent 49%, rgba(209, 0, 27, 0.04) 50%, rgba(209, 0, 27, 0.04) 51%, transparent 52%),
        radial-gradient(circle at 25% 25%, rgba(197, 2, 2, 0.08) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(197, 2, 2, 0.08) 1px, transparent 1px);
    background-size: 100px 100px, 50px 50px, 50px 50px;
    pointer-events: none;
    z-index: -1;
}

/* ===== TABLA GLASSMORPHISM ===== */
#tablaCompras_wrapper {
    background: rgba(35, 35, 35, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 15px;
    border: 1px solid rgba(209, 0, 27, 0.2);
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
    margin: 30px auto;
    max-width: 95%;
    width: 100%;
    padding: 20px;
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

/* DataTables CONTROLES */
.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label {
    color: #e0e0e0;
    font-weight: 500;
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

/* DataTables Buttons */
.dt-buttons .dt-button {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
    border: none !important;
    color: white !important;
    border-radius: 6px !important;
}

.dt-buttons .dt-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4) !important;
}

/* Offcanvas */
.offcanvas {
    background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%);
    border-right: 1px solid rgba(209, 0, 27, 0.3);
    box-shadow: 5px 0 30px rgba(209, 0, 27, 0.2);
}

/* Botones principales */
.btn-primary {
    background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
    border: none;
    border-radius: 10px;
    padding: 12px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4);
}

/* Título principal */
h2 {
    color: #fff;
    text-shadow: 0 2px 10px rgba(0,0,0,0.8);
    font-weight: 700;
}

/* Responsive */
@media (max-width: 768px) {
    #tablaCompras_wrapper {
        margin: 20px 10px;
        font-size: 0.9rem;
    }
}
/* MODALES - FONDO BLANCO COMPLETO */
.modal-content {
    background: #ffffff !important; /* Blanco puro */
    backdrop-filter: none; /* Sin blur */
    border: 1px solid #dee2e6; /* Borde Bootstrap estándar */
    border-radius: 15px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Sombra estándar */
}

.modal-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #fff;
    font-weight: 600;
    border-bottom: 1px solid #dee2e6;
}

.modal-body {
    color: #333; /* Texto negro para máximo contraste */
    max-height: 70vh;
    overflow-y: auto;
    background: #ffffff !important; /* Blanco puro */
    padding: 1.5rem;
}

.modal-footer {
    background: #f8f9fa; /* Gris claro estándar */
    border-top: 1px solid #dee2e6;
}

.form-control, .form-select {
    background: #ffffff !important; /* Blanco puro */
    border: 1px solid #ced4da; /* Borde estándar */
    color: #333;
}

.form-control:focus, .form-select:focus {
    background: #ffffff !important;
    border-color: #4b6cb7;
    box-shadow: 0 0 0 0.2rem rgba(75, 108, 183, 0.25);
}

/* VALIDACIONES BOOTSTRAP 5 */
.form-control.is-valid {
    border-color: #198754 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
}

.form-control.is-invalid, .form-select.is-invalid {
    border-color: #fd7e14 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.invalid-feedback {
    color: #dc3545 !important;
    display: block !important;
}
.botones-proveedores {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
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

<body class="d-flex flex-column min-vh-100">
    <!-- Navbar superior -->
    <nav class="navbar navbar-dark bg-dark position-sticky" style="top:0; z-index: 1030;">


        <div class="container-fluid">
            <!-- Botón para abrir sidebar -->
            <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70"
                    class="me-6 rounded-circle bg-primary p-1" />
                <span class="nav-link">Roʞka System</span>
            </a>
            <a class="navbar-brand" href="http://localhost/Roka_Sports/menu/menu.php">Inicio</a>
        </div>
    </nav>

    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Cerrar"></button>
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
                <a class="nav-link text-danger" href="http://localhost/Roka_Sports/login.php"><i
                        class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
            </nav>
        </div>
    </div>

    <div> <!--class="background-wrapper" style="background-image: url('../img/fondos/doss.jpeg'); background-size: cover; background-position: center; min-height: 100vh; padding-top: 70px;" -->
        <div class="container mt-4">
            <div class="text-center mb-5">
    <h1 class="text-white display-5 fw-bold mb-3 text-shadow">
        <i class="fas fa-cubes text-warning me-3"></i>
        Gestión de Inventario 
    </h1>
    <p class="text-white-50 lead">Control total de tus materiales</p>
</div>

            <div class="d-flex flex-wrap gap-3 my-4 justify-content-center botones-proveedores">
    <button class="btn btn-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#modalNuevaCompra">
        <i class="fas fa-box-open me-2"></i>Agregar Material
    </button>
</div>


            <table id="tablaCompras" class="table table-striped dt-responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Materia Prima</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Precio Unitario</th>
                        <th>Fecha Compra</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal para registrar/editar compra -->
<div class="modal fade" id="modalNuevaCompra" tabindex="-1" aria-labelledby="modalNuevaCompraLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formNuevaCompra" class="modal-content needs-validation" novalidate>
      <input type="hidden" id="compraId" value="" />
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevaCompraLabel">
          <i class="fas fa-box-open me-2 text-primary"></i>Registrar Materia Prima
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- PROVEEDOR -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-truck me-1 text-primary"></i>Proveedor <span class="text-primary">*</span>
          </label>
          <select id="proveedorSelect" name="proveedor_id" class="form-select shadow-sm" required>
            <option value="" selected disabled>-- Seleccione proveedor --</option>
          </select>
          <div class="invalid-feedback">Seleccione un proveedor válido.</div>
        </div>

        <!-- MATERIA PRIMA -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-cube me-1 text-primary"></i>Materia Prima <span class="text-primary">*</span>
          </label>
          <input type="text" 
                 id="nombreMateriaInput" 
                 name="nombre_materia" 
                 class="form-control shadow-sm" 
                 placeholder="Hilo algodón 40/2"
                 pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-_]{3,50}" 
                 title="Nombre materia prima (3-50 caracteres)" 
                 required 
                 minlength="3" 
                 maxlength="50"
                 autocomplete="off"
                 oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-_]/g, '').replace(/\s{2,}/g, ' ').trim()"
                 onpaste="setTimeout(() => { this.value = this.value.replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-_]/g, '').replace(/\s{2,}/g, ' ').trim(); }, 0)" />
          <div class="invalid-feedback">Nombre válido (3-50 caracteres, letras/números).</div>
          <div class="form-text text-muted">Ej: Hilo algodón 40/2, Botón plástico 15mm</div>
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-info-circle me-1 text-info"></i>Descripción
          </label>
          <input type="text" 
                 id="descripcionCompra" 
                 name="descripcion" 
                 class="form-control shadow-sm" 
                 maxlength="255"
                 placeholder="Color: blanco, calidad premium, origen: nacional"
                 autocomplete="off"
                 oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-_]/g, '').replace(/\s{2,}/g, ' ').trim()" />
          <div class="form-text text-muted">Detalles opcionales (máx. 255 caracteres)</div>
        </div>

        <!-- CANTIDAD -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-hashtag me-1 text-warning"></i>Cantidad <span class="text-primary">*</span>
          </label>
          <input type="number" 
                 step="0.01" 
                 min="0.01" 
                 max="999999.99"
                 id="cantidadCompra" 
                 name="cantidad"
                 class="form-control shadow-sm" 
                 placeholder="150.5"
                 required />
          <div class="invalid-feedback">Cantidad mínima 0.01, máximo 999,999.99</div>
          <div class="form-text text-muted">Cantidad comprada</div>
        </div>

        <!-- UNIDAD -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-ruler-combined me-1 text-primary"></i>Unidad <span class="text-primary">*</span>
          </label>
          <select id="unidadCompra" name="unidad" class="form-select shadow-sm" required>
            <option value="" selected disabled>-- Seleccione unidad --</option>
            <option value="unidad">Unidad</option>
            <option value="docena">Docena (x12)</option>
            <option value="paquete_100">Paquete x100</option>
            <option value="metro">Metro (m)</option>
            <option value="rollo">Rollo</option>
          </select>
          <div class="invalid-feedback">Seleccione unidad de medida.</div>
        </div>

        <!-- STOCK AUTOMÁTICO -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-warehouse me-1 text-secondary"></i>Stock Inicial
          </label>
          <input type="number" 
                 step="0.01" 
                 min="0" 
                 id="stockCompra" 
                 name="stock" 
                 class="form-control shadow-sm bg-light" 
                 readonly />
          <div class="form-text text-success fw-bold">
            <i class="fas fa-magic me-1"></i>Calculado automáticamente
          </div>
        </div>

        <!-- PRECIO UNITARIO -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fas fa-dollar-sign me-1 text-success"></i>Precio Unitario <span class="text-danger">*</span>
          </label>
          <input type="number" 
                 step="0.01" 
                 min="0.01" 
                 max="99999.99"
                 id="precioUnitarioCompra" 
                 name="precio_unitario"
                 class="form-control shadow-sm" 
                 placeholder="25.50"
                 required />
          <div class="invalid-feedback">Precio mínimo $0.01, máximo $99,999.99</div>
          <div class="form-text text-muted">Precio por unidad de medida seleccionada</div>
        </div>

        <div id="mensajeValidacion" class="alert alert-warning d-none" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i><span id="textoValidacion"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary">
          <i class="fas fa-times me-1"></i>Cerrar
        </button>
        <button type="submit" class="btn btn-primary btn-custom">
          <i class="fas fa-save me-1"></i>Guardar Material
          <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
        </button>
      </div>
    </form>
  </div>
</div>


      <!-- Modal Confirmación -->
      <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalConfirmacionLabel">
                            Confirmar eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body" id="mensajeConfirmacion">
                        ¿Estás seguro de que deseas eliminar esta materia prima? Esta acción no se puede deshacer.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Contenedor alertas accesible -->
        <div id="alertContainer" aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3"
            style="z-index: 1080;"></div>
    </div>

    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.6/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.6/js/responsive.bootstrap5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    
 

    <script src="../js/compras_material.js"></script>


</body>

</html>