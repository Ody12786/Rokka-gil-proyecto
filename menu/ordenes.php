<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['asistente_id'])) {
    $stmt = $conex->prepare("UPDATE usuario_asistente SET ultima_actividad = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $stmt->close();
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


$usuarioId = $_SESSION['usuario_id'];
$usuarioNombre = $_SESSION['usuario_nombre'];
$usuarioTipo = $_SESSION['usuario_tipo'];
$esAdmin = isset($_SESSION['usuario_rol']) && ($_SESSION['usuario_rol'] == 'admin' || $_SESSION['usuario_tipo'] == 1);

// Obtener stock telas disponibles
$stmt_stock = $conex->query("
    SELECT tipo_tela, SUM(metros) as stock_total 
    FROM compras_telas 
    GROUP BY tipo_tela
");
$stock_telas = $stmt_stock->fetch_all(MYSQLI_ASSOC);

// Procesar nueva orden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $tipo_orden = $_POST['tipo_orden'];
    $tecnica = $_POST['tecnica'];
    $tipo_tela = $_POST['tipo_tela'] ?? null;
    $categoria = $_POST['categoria'];
    $imagen = '';

    // Subir imagen
    if (!empty($_FILES['imagen']['name'])) {
        $targetDir = "../uploads/";
        $imagenNombre = time() . "_" . basename($_FILES['imagen']['name']);
        $targetFile = $targetDir . $imagenNombre;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFile)) {
            $imagen = $imagenNombre;
        }
    }

    if ($nombre && $imagen) {
        // 1. Insertar orden cabecera
        $stmt = $conex->prepare("
            INSERT INTO ordenes (imagen, nombre, tipo_orden, tecnica, tipo_tela, categoria) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $imagen, $nombre, $tipo_orden, $tecnica, $tipo_tela, $categoria);
        $stmt->execute();
        $orden_id = $conex->insert_id;
        $stmt->close();

        // 1.5. Para DISTRIBUIDO: guardar cantidad
        if ($tipo_orden === 'Distribuido') {
            $cantidad_dist = (int)($_POST['cantidad_distribuido'] ?? 0);
            $stmt_cant = $conex->prepare("UPDATE ordenes SET cantidad_total = ? WHERE id = ?");
            $stmt_cant->bind_param("ii", $cantidad_dist, $orden_id);
            $stmt_cant->execute();
            $stmt_cant->close();
        }


        // 2. Para FABRICADO: insertar detalles por talla
        if ($tipo_orden === 'Fabricado' && $tipo_tela) {
            $tallas = ['S' => 1.40, 'M' => 1.45, 'L' => 1.50, 'XL' => 1.55, 'XXL' => 1.60];
            $mangas = $_POST['mangas'] ?? '';
            $cuello = $_POST['cuello'] ?? null;
            $bolsillos = isset($_POST['bolsillos_si']) ? 1 : 0;
            $bordado = isset($_POST['bordado_si']) ? 1 : 0;

            $metros_totales = 0;

            foreach ($tallas as $talla => $metros_base) {
                $cantidad = (int)($_POST["cantidad_$talla"] ?? 0);
                if ($cantidad > 0) {
                    // Regla manga larga
                    $metros_prenda = $metros_base;
                    if ($mangas === 'Larga' && $talla !== 'XXL') {
                        $metros_prenda = 1.60;
                    }

                    $metros_consumidos = $metros_prenda * $cantidad;
                    $metros_totales += $metros_consumidos;

                    // GUARDAR ACCESORIOS 
                    $accesorios = [
                        'mangas_larga' => (int)($_POST['cant_mangas'] ?? 0),
                        'cuello_especial' => (int)($_POST['cant_cuello'] ?? 0),
                        'bolsillos' => (int)($_POST['cant_bolsillos'] ?? 0),
                        'bordado' => (int)($_POST['cant_bordado'] ?? 0)
                    ];

                    // Actualizar accesorios en cabecera
                    $accesorios_json = json_encode($accesorios);
                    $stmt_acc = $conex->prepare("UPDATE ordenes SET accesorios = ? WHERE id = ?");
                    $stmt_acc->bind_param("si", $accesorios_json, $orden_id);
                    $stmt_acc->execute();
                    $stmt_acc->close();


                    // Insertar detalle
                    $stmt_det = $conex->prepare("
                        INSERT INTO orden_detalle 
                        (orden_id, talla, cantidad, metros_por_prenda, 
                         cuello, mangas, bolsillos, bordado_personalizado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_det->bind_param(
                        "isidssii",
                        $orden_id,
                        $talla,
                        $cantidad,
                        $metros_prenda,
                        $cuello,
                        $mangas,
                        $bolsillos,
                        $bordado
                    );
                    $stmt_det->execute();
                    $stmt_det->close();
                }
            }

            // 3. Validar stock tela
            $stmt_stock_tela = $conex->prepare("
                SELECT COALESCE(SUM(metros), 0) as stock 
                FROM compras_telas WHERE tipo_tela = ?
            ");
            $stmt_stock_tela->bind_param("s", $tipo_tela);
            $stmt_stock_tela->execute();
            $stock_tela = $stmt_stock_tela->get_result()->fetch_assoc()['stock'];

            $metros_faltantes = $metros_totales > $stock_tela ? $metros_totales - $stock_tela : 0;

            // 4. Actualizar totales en cabecera
            $stmt_upd = $conex->prepare("
                UPDATE ordenes 
                SET metros_totales = ?, metros_faltantes = ? 
                WHERE id = ?
            ");
            $stmt_upd->bind_param("ddi", $metros_totales, $metros_faltantes, $orden_id);
            $stmt_upd->execute();
        }

        $_SESSION['success_msg'] = '¡Orden registrada correctamente!';
        header("Location: ordenes.php");
        exit;
    }
}

// Listar órdenes
$result = $conex->query("SELECT * FROM ordenes ORDER BY fecha_registro DESC");
$ordenes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Órdenes - Roka Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/orden.css">
    <link rel="stylesheet" href="../css/pro.css">
    <style>
        /* ========================================
   TEMA OSCURO ROKA SPORTS - LETRAS FIJAS
   ======================================== */
        :root {
            --roka-bg-primary: #1a1a1a;
            --roka-bg-secondary: #2d2d2d;
            --roka-bg-card: #212529;
            --roka-text-primary: #ffffff !important;
            --roka-text-secondary: #e9ecef;
            --roka-accent: #0d6efd;
        }

        /* FORZAR LETRAS BLANCAS */
        *:not(ul>li>a) {
            color: #ffffff !important;
        }

        label,
        .form-label,
        h1,
        h5,
        h6 {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        th {
            color: #ffffff !important;
        }

        td {
            color: #e9ecef !important;
        }

        /* Body */
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #1e1e2e 100%) !important;
            color: #ffffff !important;
            min-height: 100vh;
        }

        /* Cards */
        .card {
            background: #212529 !important;
            border: 1px solid #444 !important;
            color: #ffffff !important;
        }

        .card-header {
            background: #2c3e50 !important;
            color: #ffffff !important;
            border-bottom: 1px solid #555 !important;
        }

        /* Tabla OSCURA */
        .table-dark {
            background: #2d2d2d !important;
            color: #ffffff !important;
        }

        .table-dark thead th {
            background: #1e3a8a !important;
            color: #ffffff !important;
            border: none !important;
        }

        .table-dark tbody tr:hover td {
            background: #0d6efd20 !important;
            color: #ffffff !important;
        }

        /* Formularios */
        .form-control,
        .form-select {
            background: #2c3034 !important;
            border: 1px solid #555 !important;
            color: #ffffff !important;
        }

        .form-control:focus,
        .form-select:focus {
            background: #343a40 !important;
            border-color: #0d6efd !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
        }

        .form-control::placeholder {
            color: #aaa !important;
        }

        /* Total metros VERDE BRILLANTE */
        #totalMetros {
            background: #10b981 !important;
            color: #ffffff !important;
            border: 2px solid #059669 !important;
            font-weight: bold !important;
        }

        /* Stock Info */
        #stockInfo .text-danger {
            color: #ff4444 !important;
        }

        #stockInfo .text-success {
            color: #44ff44 !important;
        }

        /* Botones */
        .btn-success {
            background: #198754 !important;
            color: white !important;
            border: none !important;
        }

        .btn:hover {
            color: white !important;
            transform: translateY(-1px);
        }

        /* Badges */
        .badge {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        /* Inputs tallas */
        .talla-input {
            color: #ffffff !important;
            background: #343a40 !important;
        }

        .talla-input:focus {
            color: #ffffff !important;
            background: #495057 !important;
        }

        /* Labels tallas */
        label small {
            color: #cccccc !important;
        }

        /* Contenedor responsive */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #2d2d2d !important;
        }

        ::-webkit-scrollbar-thumb {
            background: #555 !important;
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


        /* ALERTA AUTOMÁTICA ROKA SPORTS */
        #alertSuccess {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: none !important;
            border-left: 5px solid #34d399 !important;
            color: #ffffff !important;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4) !important;
            backdrop-filter: blur(10px);
            animation: slideDown 0.5s ease-out, fadeOut 0.5s ease-out 4s forwards;
            font-weight: 500 !important;
        }

        #alertSuccess .btn-close {
            filter: brightness(0) invert(1) !important;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                transform: translateY(-100%);
                opacity: 0;
            }
        }
    </style>


</head>


<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="http://localhost/Roka_Sports/menu/menu.php">
                <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1">
                <span class="nav-link">Roʞka System</span>
            </a>
            <a href="../menu/catalogos_producto.php" class="btn btn-outline-light">
                Ver Catálogo de Productos
            </a>
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
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="bi bi-list-task"></i> Gestión de Órdenes</h1>

                <!-- Msj de registro exitoso-->
                <?php
                if (isset($_SESSION['success_msg'])) {
                    $mensaje = $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']);
                ?>
                    <div class="alert alert-success alert-dismissible fade show" id="alertSuccess" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong><?= $mensaje ?></strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>



                <!-- FORMULARIO PRINCIPAL -->
                <form method="POST" enctype="multipart/form-data" id="formOrdenes" class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Orden</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- TIPO ORDEN -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tipo Orden <span class="text-danger">*</span></label>
                                <select name="tipo_orden" id="tipo_orden" class="form-select" required onchange="toggleSecciones()">
                                    <option value="Fabricado"> Fabricado</option>
                                    <option value="Distribuido"> Distribuido</option>
                                </select>
                            </div>

                            <!-- TÉCNICA -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Técnica <span class="text-danger">*</span></label>
                                <select name="tecnica" class="form-select" required>
                                    <option value="Sublimación"> Sublimación</option>
                                    <option value="Bordado"> Bordado</option>
                                    <option value="DTF"> DTF</option>
                                    <option value="Vinil"> Vinil</option>
                                    <option value="Otro"> Otro</option>
                                </select>
                            </div>

                            <!-- CATEGORÍA -->
                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="Personalizado"> Personalizado</option>
                                    <option value="Distribucion"> Distribuido</option>
                                </select>
                            </div>

                            <!-- IMAGEN -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Imagen <span class="text-danger">*</span></label>
                                <input type="file" name="imagen" accept="image/*" class="form-control" required>
                            </div>

                            <!-- NOMBRE -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Producto <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" required maxlength="100">
                            </div>

                            <!-- CANTIDAD DISTRIBUIDO (solo Distribuido) -->
                            <div class="col-md-6 cantidad-section" id="seccionCantidad" style="display:none;">
                                <label class="form-label fw-bold">Cantidad Total <span class="text-danger">*</span></label>
                                <input type="number" name="cantidad_distribuido" min="1" class="form-control" placeholder="Ej: 50 unidades">
                                <div class="form-text">Total de prendas ya confeccionadas por proveedor</div>
                            </div>


                            <!-- TIPO TELA (solo Fabricado) -->
                            <div class="col-md-6 tela-section" id="seccionTela" style="display:none;">
                                <label class="form-label fw-bold">Tipo Tela <span class="text-danger">*</span></label>
                                <select name="tipo_tela" id="tipo_tela" class="form-select">
                                    <?php foreach ($stock_telas as $tela): ?>
                                        <option value="<?= htmlspecialchars($tela['tipo_tela']) ?>"
                                            data-stock="<?= $tela['stock_total'] ?>">
                                            <?= htmlspecialchars($tela['tipo_tela']) ?> (<?= number_format($tela['stock_total'], 1) ?>m)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="stockInfo" class="form-text"></div>
                            </div>

                            <!-- TALLAS (solo Fabricado) -->
                            <div class="col-12 seccion-tallas" id="seccionTallas" style="display:none;">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <i class="bi bi-rulers"></i> Cantidades por Talla
                                </h6>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-2">
                                        <label class="form-label small">S (1.40m)</label>
                                        <input type="number" name="cantidad_S" min="0" value="0" class="form-control text-center talla-input" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">M (1.45m)</label>
                                        <input type="number" name="cantidad_M" min="0" value="0" class="form-control text-center talla-input" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">L (1.50m)</label>
                                        <input type="number" name="cantidad_L" min="0" value="0" class="form-control text-center talla-input" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">XL (1.55m)</label>
                                        <input type="number" name="cantidad_XL" min="0" value="0" class="form-control text-center talla-input" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">XXL (1.60m)</label>
                                        <input type="number" name="cantidad_XXL" min="0" value="0" class="form-control text-center talla-input" onchange="calcularTotal()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Total</label>
                                        <div id="totalMetros" class="form-control fw-bold bg-light text-primary text-center fs-5">0.0m</div>
                                    </div>
                                </div>
                            </div>

                            <!-- ESPECIFICACIONES (solo Fabricado) -->
                            <div class="col-12 seccion-especs" id="seccionEspecs" style="display:none;">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <i class="bi bi-gear"></i> Cantidades por Accesorio
                                </h6>
                                <div class="row g-3">
                                    <!-- MANGAS -->
                                    <div class="col-md-3">
                                        <label class="form-label">Mangas</label>
                                        <select name="mangas" id="mangas" class="form-select mb-2" onchange="toggleAccesorios()">
                                            <option value="">Corta (0)</option>
                                            <option value="Larga">Larga</option>
                                        </select>
                                        <input type="number" name="cant_mangas" min="0" placeholder="Cant. mangas largas"
                                            class="form-control talla-input" id="inputMangas" style="display:none;">
                                    </div>

                                    <!-- CUELLO -->
                                    <div class="col-md-3">
                                        <label class="form-label">Cuello</label>
                                        <select name="cuello" id="cuello" class="form-select mb-2" onchange="toggleAccesorios()">
                                            <option value="">Estándar (0)</option>
                                            <option value="Redondo">Redondo</option>
                                            <option value="V">V</option>
                                            <option value="Polo">Polo</option>
                                        </select>
                                        <input type="number" name="cant_cuello" min="0" placeholder="Cant. cuellos"
                                            class="form-control talla-input" id="inputCuello" style="display:none;">
                                    </div>

                                    <!-- BOLSILLOS -->
                                    <div class="col-md-3">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="bolsillos_si" id="bolsillos" class="form-check-input" onchange="toggleAccesorios()">
                                            <label class="form-check-label">Bolsillos</label>
                                        </div>
                                        <input type="number" name="cant_bolsillos" min="0" placeholder="Cant. bolsillos"
                                            class="form-control talla-input" id="inputBolsillos" style="display:none;">
                                    </div>

                                    <!-- BORDADO -->
                                    <div class="col-md-3">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="bordado_si" id="bordado" class="form-check-input" onchange="toggleAccesorios()">
                                            <label class="form-check-label">Bordado</label>
                                        </div>
                                        <input type="number" name="cant_bordado" min="0" placeholder="Cant. bordados"
                                            class="form-control talla-input" id="inputBordado" style="display:none;">
                                    </div>
                                </div>
                            </div>


                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg px-5">
                                    <i class="bi bi-check-lg"></i> Registrar Orden
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- TABLA ÓRDENES -->
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-table"></i> Órdenes Registradas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tablaOrdenes" class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Técnica</th>
                                        <th>Tela</th>
                                        <th>Metros</th>
                                        <th>Cantidades</th>
                                        <th>Faltan</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <tr>
                                            <td><?= $orden['id'] ?></td>
                                            <td>
                                                <img src="../uploads/<?= htmlspecialchars($orden['imagen']) ?>"
                                                    style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                                            </td>
                                            <td><?= htmlspecialchars($orden['nombre']) ?></td>
                                            <td>
                                                <span class="badge <?= $orden['tipo_orden'] == 'Fabricado' ? 'bg-warning' : 'bg-info' ?>">
                                                    <?= $orden['tipo_orden'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($orden['tecnica']) ?></td>
                                            <td>
                                                <?php if ($orden['tipo_orden'] === 'Distribuido'): ?>
                                                    <span class="badge bg-success">Estilizado</span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($orden['tipo_tela'] ?? '-') ?>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if ($orden['metros_totales'] > 0): ?>
                                                    <strong><?= number_format($orden['metros_totales'], 2) ?>m</strong>
                                                <?php else: ?>
                                                    <span class="badge bg-info">U/D</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php
                                                if ($orden['tipo_orden'] === 'Distribuido' && $orden['cantidad_total'] > 0) {
                                                    echo '<span class="badge bg-info fw-bold">' . number_format($orden['cantidad_total']) . ' und</span>';
                                                } elseif ($orden['tipo_orden'] === 'Distribuido') {
                                                    echo '<span class="badge bg-secondary">0 und</span>';
                                                } elseif ($orden['tipo_orden'] === 'Fabricado') {
                                                    $accesorios = json_decode($orden['accesorios'] ?? '{}', true);
                                                    $detalles = [];

                                                    // Tallas
                                                    $stmt_cant = $conex->prepare("SELECT talla, SUM(cantidad) as total FROM orden_detalle WHERE orden_id = ? GROUP BY talla HAVING total > 0 ORDER BY FIELD(talla, 'S','M','L','XL','XXL')");
                                                    $stmt_cant->bind_param("i", $orden['id']);
                                                    $stmt_cant->execute();
                                                    $cantidades = $stmt_cant->get_result()->fetch_all(MYSQLI_ASSOC);
                                                    $stmt_cant->close();

                                                    foreach ($cantidades as $c) {
                                                        $detalles[] = $c['talla'] . ':' . $c['total'];
                                                    }

                                                    // Accesorios
                                                    foreach ($accesorios as $acc => $cant) {
                                                        if ($cant > 0) {
                                                            $nombre = match ($acc) {
                                                                'mangas_larga' => '<span class="badge bg-danger">ML</span>',
                                                                'cuello_especial' => '<span class="badge bg-warning text-dark">C</span>',
                                                                'bolsillos' => '<span class="badge bg-success">B</span>',
                                                                'bordado' => '<span class="badge bg-info">Br</span>',
                                                                default => '<span class="badge bg-secondary">' . $acc . '</span>'
                                                            };
                                                            $detalles[] = $nombre . ':' . $cant;
                                                        }
                                                    }

                                                    echo !empty($detalles) ? implode(' ', $detalles) : '<span class="text-muted">—</span>';
                                                } else {
                                                    echo '<span class="text-muted">—</span>';
                                                }
                                                ?>
                                            </td>



                                            <td class="<?= $orden['metros_faltantes'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                                <?= $orden['metros_faltantes'] > 0 ? number_format($orden['metros_faltantes'], 2) . 'm' : '✅' ?>
                                            </td>
                                            <td>
                                                <?php
                                                $estado_entrega = $orden['estado_entrega'] ?? 'Por entregar';
                                                $badge_class = match ($estado_entrega) {
                                                    'Entregado' => 'bg-success',
                                                    'Parcial' => 'bg-warning text-dark',
                                                    default => 'bg-danger'
                                                };
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $estado_entrega ?></span>
                                            </td>
                                            <td><?= date('d/m H:i', strtotime($orden['fecha_registro'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="ver_orden.php?id=<?= $orden['id'] ?>" class="btn btn-info" title="Ver detalle">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="editar_orden.php?id=<?= $orden['id'] ?>" class="btn btn-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($orden['metros_faltantes'] == 0): ?>
                                                        <button type="button" class="btn btn-danger" onclick="producirOrden(<?= $orden['id'] ?>)" title="Producir">
                                                            <i class="bi bi-play-circle"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-danger" onclick="producirOrden(<?= $orden['id'] ?>)" title="Faltan <?= number_format($orden['metros_faltantes'], 2) ?>m" disabled>
                                                            <i class="bi bi-play-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-success" title="Marcar como entregado"
                                                        onclick="marcarEntregado(<?= $orden['id'] ?>)">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button class="btn btn-danger" onclick="cancelarOrden(<?= $orden['id'] ?>)" title="Cancelar Orden">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PRODUCIR -->
<div class="modal fade" id="modalProducir" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header bg-danger border-0">
                <h5 class="modal-title fw-bold" id="ordenTitle">Cargando...</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Loading -->
                <div id="loadingSpinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-white-50">Cargando datos...</p>
                </div>

                <!-- Contenido -->
                <div id="produccionContent" style="display: none;">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <img id="ordenImagen" src="" class="img-fluid rounded shadow w-100" style="height: 250px; object-fit: cover;">
                        </div>
                        <div class="col-md-8">
                            <h3 id="ordenNombre" class="fw-bold text-white mb-3">Producto</h3>
                            <div class="mb-2">
                                <span class="badge bg-info fs-6 px-3 py-2 me-2">#<span id="ordenId">123</span></span>
                                <span class="badge bg-success fs-6 px-3 py-2"><span id="totalPiezas">0</span> prendas</span>
                            </div>
                            <p class="mb-2"><strong>Técnica:</strong> <span id="tecnicaInfo">-</span></p>
                            <p class="mb-2"><strong>Tela:</strong> <span id="telaInfo">-</span></p>
                            <p class="mb-0"><strong>Metros:</strong> <span id="metrosRequeridos" class="fw-bold text-warning">-</span></p>
                        </div>
                    </div>

                    <!-- Materiales requeridos -->
                    <h6 class="fw-bold text-white border-bottom pb-2 mb-4">
                        <i class="bi bi-cube me-2 text-warning"></i>MATERIA PRIMA
                    </h6>
                    <div id="listaMateriales">Cargando...</div>

                    <!-- Alerta stock -->
                    <div id="alertaStock" class="alert alert-danger d-none mt-4"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConsumir" class="btn btn-success btn-lg px-5 py-3 fw-bold" disabled>
                    <i class="bi bi-play-circle me-2"></i>PRODUCIR ORDEN
                </button>
            </div>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>

    </script>

    <script>
        $(document).ready(function() {
            $('#tablaOrdenes').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [0, 'desc']
                ]
            });
        });

        function cancelarOrden(ordenId) {
            Swal.fire({
                title: '¿Cancelar Orden #' + ordenId + '?',
                text: 'Esta acción eliminará la orden permanentemente',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash me-1"></i> Cancelar',
                cancelButtonText: '<i class="bi bi-x-circle me-1"></i> Mantener',
                buttonsStyling: false,
                customClass: {
                    popup: 'swal-custom-popup', // ← clase custom
                    confirmButton: 'btn btn-danger px-4 py-2 mx-2 rounded-pill fw-bold',
                    cancelButton: 'btn btn-secondary px-4 py-2 mx-2 rounded-pill fw-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'cancelar_orden.php',
                        method: 'POST',
                        data: {
                            orden_id: ordenId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Cancelada!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.error
                                });
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Error de conexión', 'error');
                        }
                    });
                }
            });
        }

        function toggleSecciones() {
            const tipo = document.getElementById('tipo_orden').value;
            const fabricado = tipo === 'Fabricado';

            document.getElementById('seccionTela').style.display = fabricado ? 'block' : 'none';
            document.getElementById('seccionTallas').style.display = fabricado ? 'block' : 'none';
            document.getElementById('seccionEspecs').style.display = fabricado ? 'block' : 'none';
            document.getElementById('seccionCantidad').style.display = !fabricado ? 'block' : 'none';

            if (fabricado) calcularTotal();
        }

        // AUTO-DESAPARECER ALERTA (5 segundos)
        setTimeout(function() {
            const alert = document.getElementById('alertSuccess');
            if (alert) {
                alert.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);


        function calcularTotal() {
            const tallas = {
                S: 1.40,
                M: 1.45,
                L: 1.50,
                XL: 1.55,
                XXL: 1.60
            };
            const mangas = document.getElementById('mangas').value;
            let totalMetros = 0;

            Object.keys(tallas).forEach(talla => {
                const qty = parseInt(document.querySelector(`[name="cantidad_${talla}"]`).value) || 0;
                if (qty > 0) {
                    let metros = tallas[talla];
                    if (mangas === 'Larga' && talla !== 'XXL') metros = 1.60;
                    totalMetros += metros * qty;
                }
            });

            document.getElementById('totalMetros').textContent = totalMetros.toFixed(2) + 'm';

            // Validar stock
            const telaSelect = document.getElementById('tipo_tela');
            if (telaSelect.value && totalMetros > 0) {
                const stock = parseFloat(telaSelect.selectedOptions[0].dataset.stock);
                const faltan = Math.max(0, totalMetros - stock);
                document.getElementById('stockInfo').innerHTML =
                    faltan > 0 ?
                    `<span class="text-danger fw-bold">⚠️ Faltan ${faltan.toFixed(2)}m</span>` :
                    `<span class="text-success">✅ Stock OK (${stock.toFixed(1)}m disp.)</span>`;
            }
        }

        // Stock tela onchange
        document.getElementById('tipo_tela').addEventListener('change', calcularTotal);

        function toggleAccesorios() {
            // Mangas
            document.getElementById('inputMangas').style.display =
                document.getElementById('mangas').value ? 'block' : 'none';

            // Cuello  
            document.getElementById('inputCuello').style.display =
                document.getElementById('cuello').value ? 'block' : 'none';

            // Bolsillos
            document.getElementById('inputBolsillos').style.display =
                document.getElementById('bolsillos').checked ? 'block' : 'none';

            // Bordado
            document.getElementById('inputBordado').style.display =
                document.getElementById('bordado').checked ? 'block' : 'none';
        }

        function marcarEntregado(ordenId) {
            if (confirm('¿Marcar esta orden como entregada?')) {
                fetch('actualizar_estado.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + ordenId + '&estado=Entregado'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        }

       function producirOrden(ordenId) {
    $('#modalProducir').modal('show');
    $('#loadingSpinner').removeClass('d-none');
    $('#produccionContent').hide();
    
    $.ajax({
        url: 'cargar_produccion.php',
        method: 'POST',
        data: { orden_id: ordenId },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                // Llenar datos
                $('#ordenTitle').text('Producir: ' + data.orden.nombre);
                $('#ordenId').text(data.orden.id);
                $('#ordenNombre').text(data.orden.nombre);
                $('#ordenImagen').attr('src', '../uploads/' + data.orden.imagen);
                $('#tecnicaInfo').text(data.orden.tecnica);
                $('#telaInfo').text(data.orden.tipo_tela);
                $('#metrosRequeridos').text(data.orden.metros_totales + 'm');
                $('#totalPiezas').text(data.total_piezas);

                $('#loadingSpinner').addClass('d-none');
                $('#produccionContent').show();
                
                // Materiales
                let html = '';
                data.materiales.forEach(m => {
                    html += `
                        <div class="d-flex justify-content-between p-3 border-bottom">
                            <div>
                                <strong>${m.nombre}</strong><br>
                                <small>Stock: ${m.stock}m | Necesario: ${m.necesario}m</small>
                            </div>
                            <span class="badge ${m.suficiente ? 'bg-success' : 'bg-danger'}">
                                ${m.suficiente ? '✅' : '❌'}
                            </span>
                        </div>`;
                });
                $('#listaMateriales').html(html);

                // Stock suficiente?
                if (data.stock_suficiente) {
                    $('#btnConsumir').prop('disabled', false);
                    $('#alertaStock').addClass('d-none');
                } else {
                    $('#btnConsumir').prop('disabled', true);
                    $('#alertaStock').html('❌ Faltan ' + data.faltante + 'm de ' + data.orden.tipo_tela).removeClass('d-none');
                }
            }
        }
    });
}

// Evento botón PRODUCIR
$(document).on('click', '#btnConsumir', function() {
    const ordenId = $('#ordenId').text();
    Swal.fire({
        title: '¿PRODUCIR orden #' + ordenId + '?',
        text: 'Se consumirá inventario',
        icon: 'question',
        showCancelButton: true
    }).then(result => {
        if (result.isConfirmed) {
            // Llamar tu procesar_produccion.php
            $.post('procesar_produccion.php', {
                orden_id: ordenId,
                materia_prima: JSON.stringify([]) // Array vacío por ahora
            }, function(response) {
                if (response.success) {
                    Swal.fire('¡Producción OK!', response.message, 'success');
                    $('#modalProducir').modal('hide');
                    location.reload();
                }
            }, 'json');
        }
    });
});

    </script>

</body>

</html>