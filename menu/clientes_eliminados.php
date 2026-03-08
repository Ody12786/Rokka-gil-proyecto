<?php
session_start();
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

$usuarioId = $_SESSION['usuario_id'];
$usuarioNombre = $_SESSION['usuario_nombre'];
$usuarioTipo = $_SESSION['usuario_tipo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes Eliminados - Roʞka System</title>

    <!-- CSS Base -->
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/tablas.css">
    <link rel="stylesheet" href="../DataTables/datatables.min.css">
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: 
                linear-gradient(rgba(26, 26, 26, 0.95), rgba(15, 15, 15, 0.98)),
                radial-gradient(circle at 20% 80%, rgba(209, 0, 27, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(197, 2, 2, 0.12) 0%, transparent 50%),
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
            background-image: 
                linear-gradient(45deg, transparent 49%, rgba(209, 0, 27, 0.04) 50%, rgba(209, 0, 27, 0.04) 51%, transparent 52%),
                radial-gradient(circle at 25% 25%, rgba(197, 2, 2, 0.08) 1px, transparent 1px),
                radial-gradient(circle at 75% 75%, rgba(197, 2, 2, 0.08) 1px, transparent 1px);
            background-size: 100px 100px, 50px 50px, 50px 50px;
            pointer-events: none;
            z-index: -1;
        }

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
        }

        .header-eliminados h2 {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #d1001b 0%, #ff6b6b 50%, #d1001b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle-eliminados {
            color: rgba(255,255,255,0.9);
            font-size: 1.3rem;
            font-weight: 400;
            letter-spacing: 1px;
        }

        .btn-success {
            background: linear-gradient(135deg, #198754, #146c43) !important;
            border: none !important;
            border-radius: 10px !important;
        }

        .btn-success:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 25px rgba(25, 135, 84, 0.4) !important;
        }

        .offcanvas { 
            background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%) !important; 
        }
        .btn-primary { 
            background: linear-gradient(135deg, #d1001b 0%, #a10412 100%) !important; 
        }
        .modal-header { 
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%) !important; 
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <div class="d-flex flex-column flex-grow-1">

        <!-- Navbar superior -->
        <nav class="navbar navbar-dark bg-dark">
            <div class="container-fluid">
                <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1" />
                    <span class="nav-link">Roʞka System</span>
                </a>
                <a class="navbar-brand" href="clientes.php">← Volver Clientes Activos</a>
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
                    <a class="nav-link active" href="clientes_eliminados.php"><i class="bi bi-people-slash me-2"></i>Clientes Eliminados</a>
                    <a class="nav-link" href="clientes.php"><i class="bi bi-people-fill me-2"></i>Clientes Activos</a>
                    <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
                    <hr class="my-2" />
                    <a class="nav-link text-danger" href="../login.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
                </nav>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="container-fluid py-4">
            <div class="header-eliminados mb-5 text-center">
                <h2 class="mb-2">
                    <i class="fas fa-users-slash me-3"></i>👥 CLIENTES ELIMINADOS
                </h2>
                <div class="subtitle-eliminados">
                    Clientes ocultos temporalmente - Usa "Restaurar" para recuperar
                </div>
            </div>

            <div class="table-responsive shadow-lg rounded-3 overflow-hidden mb-4">
                <table id="clienteTable" class="table table-striped table-hover table-dark table-sm mb-0 display nowrap" style="width:96%">
                    <thead class="table-dark">
                        <tr>
                            <th>N°Afiliación</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Toasts Container -->
        <div id="alertContainer" aria-live="polite" aria-atomic="true" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>
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

  
    <script src="../js/menu_clientes_eliminados.js"></script>
    <?php include("inferior.html"); ?>
</body>
</html>
