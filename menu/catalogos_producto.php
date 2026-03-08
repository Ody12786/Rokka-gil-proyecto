<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'todos';

if ($categoria === 'todos' || $categoria === '') {
    $result = $conex->query("SELECT * FROM ordenes ORDER BY fecha_registro DESC");
} else {
    $stmt = $conex->prepare("SELECT * FROM ordenes WHERE categoria = ? ORDER BY fecha_registro DESC");
    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
$ordenes = $result->fetch_all(MYSQLI_ASSOC);
$categorias = ['Distribución', 'Personalizado'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Catálogo de Órdenes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="../css/catalogo-orden.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
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
        .product-card {
            border: 1px solid #444;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-image {
    cursor: pointer !important;
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.05);
}

.modal-img-large {
    max-width: 90vw;
    max-height: 90vh;
    width: auto;
    height: auto;
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
        <a class="navbar-brand d-flex align-items-center" href="http://localhost/Roka_Sports/menu/menu.php">
            <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1"> 
            <span class="nav-link">Roʞka System</span>
        </a>
        <div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0">Catálogo de Productos</h1>
    <a href="../menu/ordenes.php" class="btn btn-outline-light">
      Volver
    </a>
 
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
        <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Materia Prima</a>
      
        <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
        <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
        <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
            <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
            <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
        <hr class="my-2">
  <a class="nav-link text-danger" href="../database/cerrar.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
    </div>

</div>
 <div class="mt-3 text-center">
    <button id="btnPropuesta" class="btn btn-success" disabled>Ver Propuesta (0)</button>
  </div>

  <!-- MODAL IMAGEN GRANDE -->
<div class="modal fade" id="modalImagen" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="modalTitulo"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="modalImagenGrande" src="" class="modal-img-large img-fluid rounded shadow-lg" alt="Imagen ampliada">
            </div>
        </div>
    </div>
</div>

<div class="container">
  <h1 class="text-center mb-4">Propuesta de Diseños</h1>
  <form method="GET" class="mb-4 text-center">
    <select name="categoria" class="form-select form-select-sm w-auto d-inline">
      <option value="todos" <?= $categoria==='todos' || $categoria==='' ? 'selected' : '' ?>>Todas</option>
      <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat ?>" <?= ($cat === $categoria) ? 'selected' : '' ?>><?= $cat ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm ms-2">Filtrar</button>
  </form>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($ordenes as $o): 
        // Decodificar accesorios para mostrar
        $accesorios = json_decode($o['accesorios'] ?? '{}', true) ?? [];
        $accesorios_str = '';
        foreach($accesorios as $acc => $cant) {
            if ($cant > 0) {
                $nombre = match($acc) {
                    'mangas_larga' => 'mangas Largas',
                    'cuello_especial' => 'Cuello Especial', 
                    'bolsillos' => 'Bolsillos',
                    'bordado' => 'Bordado',
                    default => $acc
                };
                $accesorios_str .= '<span class="badge me-1 ' . match($acc) {
                    'mangas_larga' => 'bg-danger',
                    'cuello_especial' => 'bg-warning text-dark', 
                    'bolsillos' => 'bg-success',
                    'bordado' => 'bg-info',
                    default => 'bg-secondary'
                } . '">' . $nombre . ':' . $cant . '</span>';
            }
        }
        
        // Cantidades por tipo
        ob_start();
        if ($o['tipo_orden'] === 'Distribuido') {
            echo '<span class="badge bg-info fs-6">' . number_format($o['cantidad_total']) . ' und</span>';
        } else {
            $stmt_cant = $conex->prepare("SELECT talla, SUM(cantidad) as total FROM orden_detalle WHERE orden_id = ? GROUP BY talla HAVING total > 0 ORDER BY FIELD(talla, 'S','M','L','XL','XXL')");
            $stmt_cant->bind_param("i", $o['id']);
            $stmt_cant->execute();
            $cantidades = $stmt_cant->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_cant->close();
            
            foreach($cantidades as $c) {
                echo '<span class="badge bg-light text-dark me-1">' . $c['talla'] . ':' . $c['total'] . '</span>';
            }
            echo $accesorios_str;
        }
        $cantidades_html = ob_get_clean();
    ?>
        <div class="col">
            <div class="card product-card h-100 bg-dark text-light border border-danger shadow-lg">
                <img src="../uploads/<?= htmlspecialchars($o['imagen']) ?>" 
     class="card-img-top product-image" 
     style="height: 200px; object-fit: cover; cursor: pointer;"
     alt="<?= htmlspecialchars($o['nombre']) ?>"
     onclick="abrirModal('<?= htmlspecialchars($o['imagen']) ?>', '<?= htmlspecialchars($o['nombre']) ?>')">
                
                <div class="card-body d-flex flex-column">
                    <!-- NOMBRE PRINCIPAL -->
                    <h5 class="card-title text-danger fw-bold mb-3">
                        <?= htmlspecialchars($o['nombre']) ?>
                    </h5>
                    
                    <!-- TIPO Y CANTIDADES -->
                    <div class="mb-3">
                        <span class="badge fs-6 <?= $o['tipo_orden'] == 'Fabricado' ? 'bg-warning' : 'bg-info' ?>">
                            <?= $o['tipo_orden'] ?>
                        </span>
                        <div class="mt-2 small"><?= $cantidades_html ?></div>
                    </div>
                    
                    <!-- TELA Y METROS -->
                    <div class="mb-3 small">
                        <?php if ($o['tipo_orden'] === 'Distribuido'): ?>
                            <span class="badge bg-success">Estilizado</span>
                        <?php else: ?>
                            <strong>Tela:</strong> <?= htmlspecialchars($o['tipo_tela'] ?? '-') ?> 
                            | <strong><?= number_format($o['metros_totales'], 2) ?>m</strong>
                            <?php if ($o['metros_faltantes'] > 0): ?>
                                <span class="badge bg-danger ms-1">Faltan <?= number_format($o['metros_faltantes'], 2) ?>m</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- TÉCNICA Y CATEGORÍA -->
                    <div class="mb-3 small">
                        <strong>Técnica:</strong> <?= htmlspecialchars($o['tecnica']) ?> 
                        | <strong>Categoría:</strong> <?= htmlspecialchars($o['categoria']) ?>
                    </div>
                    
                    <!-- CHECKBOX -->
                    <div class="form-check mt-auto">
                        <input class="form-check-input orden-checkbox" 
                               type="checkbox" 
                               value="<?= $o['id'] ?>" 
                               id="orden<?= $o['id'] ?>">
                        <label class="form-check-label text-light w-100" for="orden<?= $o['id'] ?>">
                            <strong>➕ Seleccionar para propuesta al cliente</strong>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>



 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.orden-checkbox');
    const btnPropuesta = document.getElementById('btnPropuesta');

    function actualizarBoton() {
        const seleccionados = Array.from(checkboxes).filter(c => c.checked);
        const count = seleccionados.length;
        
        btnPropuesta.disabled = count === 0;
        btnPropuesta.innerHTML = `📄 Ver Propuesta (${count})`;
        
        if (count > 3) {
            Swal.fire({
                icon: 'warning',
                title: '¡Límite!',
                text: 'Máximo 3 órdenes por propuesta',
                timer: 2000
            });
            seleccionados[count-1].checked = false;
            actualizarBoton();
        }
    }

    // Eventos checkboxes
    checkboxes.forEach(c => c.addEventListener('change', actualizarBoton));
    actualizarBoton();

    // ✅ WINDOW.OPEN - DESCARGA DIRECTA
    btnPropuesta.addEventListener('click', function() {
        const seleccionados = Array.from(checkboxes)
            .filter(c => c.checked)
            .map(c => c.value);

        if (seleccionados.length === 0) {
            Swal.fire('ℹ️', 'Selecciona al menos 1 orden', 'info');
            return;
        }

        // Spinner
        const originalHTML = btnPropuesta.innerHTML;
        btnPropuesta.disabled = true;
        btnPropuesta.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Abriendo...';

        // ✅ URL con parámetros GET
        const params = new URLSearchParams({
            ordenes: JSON.stringify(seleccionados)
        });
        const url = `../vistas/generar_propuesta.php?${params}`;
        
        // ✅ Abre nueva pestaña + descarga automática
        window.open(url, '_blank');
        
        // Reset botón
        setTimeout(() => {
            btnPropuesta.innerHTML = originalHTML;
            btnPropuesta.disabled = false;
            actualizarBoton();
            Swal.fire('✅', '¡Propuesta generada!', 'success');
        }, 1500);
    });
});

//  MODAL IMAGEN - FUNCIÓN PRINCIPAL
function abrirModal(imagen, nombre) {
    document.getElementById('modalImagenGrande').src = '../uploads/' + imagen;
    document.getElementById('modalTitulo').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalImagen')).show();
}

</script>



</body>
</html>
