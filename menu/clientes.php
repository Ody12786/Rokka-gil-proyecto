<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

function getBase64Image($imagePath) {
    if (file_exists($imagePath)) {
        $imageData = file_get_contents($imagePath);
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    return '';
}
$logoBase64 = getBase64Image('../img/IMG_4124login.png');

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Clientes - Roʞka System</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/tablas.css">
    <link rel="stylesheet" href="../DataTables/datatables.min.css">
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/correciones.css">

    <style>
      
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

            .dt-search{
                color: #fff !important;
            }
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

        /* ===== TABLA GLASSMORPHISM - EXACTO PRODUCTOS ===== */
        #clienteTable_wrapper {
            background: rgba(35, 35, 35, 0.95) !important;
            backdrop-filter: blur(25px) !important;
            border-radius: 15px !important;
            border: 1px solid rgba(209, 0, 27, 0.2) !important;
            overflow: hidden !important;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6) !important;
            margin: 30px auto !important;
            max-width: 95% !important;
        }

        .table thead {
            background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
            color: #fff !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
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
            background: rgba(255,255,255,0.9) !important;
            color: #333 !important;
            border: 1px solid rgba(209,0,27,0.3) !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .dataTables_wrapper .dataTables_info {
            color: #e0e0e0 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #e0e0e0 !important;
            background: rgba(209,0,27,0.2) !important;
            border: 1px solid rgba(209,0,27,0.4) !important;
            border-radius: 6px !important;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(209,0,27,0.4);
        }

        /* Offcanvas EXACTO PRODUCTOS */
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

        /* Botones principales PRODUCTOS */
        .btn-primary {
            background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important;
            border: none !important;
            border-radius: 10px !important;
            padding: 12px 25px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 15px rgba(209,0,27,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 25px rgba(209, 0, 27, 0.4) !important;
        }

        /* HEADER CLIENTES */
        .header-clientes {
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .header-clientes h2 {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #d1001b 0%, #ff6b6b 50%, #d1001b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .subtitle-clientes {
            color: rgba(255,255,255,0.9);
            font-size: 1.3rem;
            font-weight: 400;
            letter-spacing: 1px;
        }

        .botones-clientes {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 1rem;
            width: 100%;
        }

        #btnNuevoCliente {
            min-width: 220px;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        /* MODALES - EXACTOS PRODUCTOS */
        .modal-header {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%) !important;
            color: #fff !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
        }

        .modal-footer {
            position: sticky !important;
            bottom: 0 !important;
            background: #f8f9fa !important;
            padding: 1rem !important;
            border-top: 1px solid #dee2e6 !important;
            z-index: 1050 !important;
        }

        .modal-body {
            max-height: 60vh !important;
            overflow-y: auto !important;
            padding: 1.5rem !important;
            background: rgba(255,255,255,0.95);
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

        .form-control.is-invalid {
            border-color: #dc3545 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(0.375em + 0.1875rem) center !important;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            #clienteTable_wrapper {
                margin: 20px 10px !important;
                font-size: 0.9rem !important;
            }
            .btn-primary {
                padding: 10px 20px !important;
                font-size: 0.9rem !important;
            }
        }

        /* Botones acciones tabla */
        .btn-group-sm .btn {
            border-radius: 6px !important;
            padding: 6px 10px !important;
            font-size: 0.8rem !important;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <div class="d-flex flex-column flex-grow-1">

        <!-- Navbar superior -->
        <nav class="navbar navbar-dark bg-dark">
            <div class="container-fluid">
                <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1" />
                    <span class="nav-link">Roʞka System</span>
                </a>
                <a class="navbar-brand" href="../menu/menu.php">Inicio</a>
                
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
                    <a class="nav-link" href="../menu/compras.php"><i class="bi bi-shop me-2"></i>Compras de telas</a>
                    <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Compras de productos</a>
                    <a class="nav-link" href="../menu/modulo_proveedor.php"><i class="bi bi-truck me-2"></i>Proveedores</a>
                    <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
                    <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
                     <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
                    <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 'estandar' && $esAdmin) { ?>
                        <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
                        <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
                    <?php } ?>
                    <hr class="my-2" />
                    <a class="nav-link text-danger" href="../login.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
                </nav>
            </div>
        </div>

      
    <!-- Contenido principal -->
    <div class="container-fluid py-4 flex-grow-1">
        <div class="header-clientes mb-5 text-center">
            <h2 class="mb-2">
                <i class="fas fa-users me-3"></i>👥 CLIENTES
            </h2>
            <div class="subtitle-clientes">Gestión de clientes Roʞka Sports</div>
        </div>

        <div class="d-flex justify-content-center mb-4">
            <button id="btnNuevoCliente" class="btn btn-primary btn-lg px-5 py-3 shadow-lg" 
                    data-bs-toggle="modal" data-bs-target="#modalRegistrarCliente">
                <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
            </button>
        </div>

        <table id="clienteTable" class="table table-striped table-hover table-dark table-sm mb-0 display nowrap">
            <thead>
                <tr>
                    <th>N°Afiliación</th>
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Toasts Container -->
    <div id="alertContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <!-- Modal Registrar Cliente -->
    <div class="modal fade" id="modalRegistrarCliente" tabindex="-1">
        <div class="modal-dialog">
            <form id="formRegistrarCliente" novalidate>
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Registrar Cliente
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="registrar_Cid" class="form-label">
                                <i class="fas fa-id-card me-1 text-danger"></i>Cédula <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control shadow-sm" id="registrar_Cid" name="Cid" 
                                   pattern="\d{7,8}" max="8" min="7" required autocomplete="off">
                            <div class="invalid-feedback">Cédula válida (7-8 dígitos)</div>
                        </div>
                        <div class="mb-3">
                            <label for="registrar_nombre" class="form-label">
                                <i class="fas fa-user me-1 text-primary"></i>Nombre Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control shadow-sm" id="registrar_nombre" name="nombre" 
                                   pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" required maxlength="100" autocomplete="off">
                            <div class="invalid-feedback">Nombre completo (3-100 caracteres, solo letras)</div>
                        </div>
                        <div class="mb-3">
                            <label for="registrar_direccion" class="form-label">
                                <i class="fas fa-home me-1 text-primary"></i>Dirección
                            </label>
                            <input type="text" class="form-control shadow-sm" id="registrar_direccion" name="direccion" 
                                   pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" maxlength="100" autocomplete="off" placeholder="3-100 carateres, solo letras">
                        </div>
                        <div class="mb-3">
                            <label for="registrar_telefono" class="form-label">
                                <i class="fas fa-phone me-1 text-primary"></i>Telefono
                            </label>
                            <input type="text" class="form-control shadow-sm" id="registrar_telefono" name="telefono" autocomplete="off">
                            <div class="invalid-feedback">Ej: 04241234567</div>
                        </div>
                        <div id="mensajeRegistrarCliente" class="alert alert-danger d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Registrar Cliente
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Cliente -->
    <div class="modal fade" id="modalEditarCliente" tabindex="-1">
        <div class="modal-dialog">
            <form id="formEditarCliente" novalidate>
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-user-edit me-2"></i>Editar Cliente
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_N_afiliacion_original" name="N_afiliacion_original">
                        <div class="mb-3">
                            <label for="edit_Cid" class="form-label">
                                <i class="fas fa-id-card me-1 text-danger"></i>Cédula <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control shadow-sm" id="edit_Cid" name="Cid" 
                                   pattern="\d{7,8}" max="99999999" min="1000000" required disabled autocomplete="off">
                            <div class="invalid-feedback">Cédula válida (7-8 dígitos)</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">
                                <i class="fas fa-user me-1 text-primary"></i>Nombre Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control shadow-sm" id="edit_nombre" name="nombre" 
                                   pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" required minlength="3" maxlength="100" autocomplete="off">
                            <div class="invalid-feedback">Nombre completo (3-100 caracteres, solo letras)</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_direccion" class="form-label">
                                <i class="fas fa-home me-1 text-primary"></i>Dirección
                            </label>
                            <input type="text" class="form-control shadow-sm" id="edit_direccion" name="direccion" 
                                   pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" maxlength="100" autocomplete="off" placeholder="3-100 carateres, solo letras">
                        </div>
                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">
                                <i class="fas fa-phone me-1 text-primary"></i>Telefono
                            </label>
                            <input type="text" class="form-control shadow-sm" id="edit_telefono" name="telefono" autocomplete="off">
                            <div class="invalid-feedback">Ej: 04241234567</div>
                        </div>
                        <div id="mensajeEditarCliente" class="alert alert-danger d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>window.rokaLogoBase64 = <?php echo json_encode($logoBase64); ?>;</script>
    
    <!-- JS PRINCIPAL -->
    <script src="../js/menu_clientes.js"></script>
    
    <?php include("inferior.html"); ?>
</body>
</html>