<?php
// get_productos_ventas_simple.php
header('Content-Type: application/json');

// Tu conexión exacta
$conex = new mysqli("localhost", "root", "", "roka_sport_gil");

if ($conex->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $conex->connect_error]);
    exit;
}

$conex->set_charset("utf8");

// Obtener parámetros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;
$buscar = isset($_GET['buscar']) ? $conex->real_escape_string($_GET['buscar']) : '';
$categoria = isset($_GET['categoria']) ? $conex->real_escape_string($_GET['categoria']) : '';

// ===== CONTAR TOTAL =====
$sql_count = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";

if (!empty($buscar)) {
    $sql_count .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%' OR descripcion LIKE '%$buscar%')";
}
if (!empty($categoria)) {
    $sql_count .= " AND categoria = '$categoria'";
}

$result_count = $conex->query($sql_count);
$total_productos = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $por_pagina);

// ===== OBTENER PRODUCTOS =====
$sql = "SELECT 
            id, codigo, nombre, descripcion, categoria, 
            tipo_tela, talla, diseño, imagen, stock, 
            metros_de_tela, fecha_creacion
        FROM productos 
        WHERE activo = 1";

if (!empty($buscar)) {
    $sql .= " AND (nombre LIKE '%$buscar%' OR codigo LIKE '%$buscar%' OR descripcion LIKE '%$buscar%')";
}
if (!empty($categoria)) {
    $sql .= " AND categoria = '$categoria'";
}

$sql .= " ORDER BY fecha_creacion DESC LIMIT $por_pagina OFFSET $offset";

$result = $conex->query($sql);

$productos = [];
while ($row = $result->fetch_assoc()) {
    // Determinar precio (ajusta según tu lógica de negocio)
    $precio = 20; // precio por defecto
    
    // Asignar precio según el nombre o tipo
    if (strpos(strtolower($row['nombre']), 'tigre') !== false) {
        $precio = 30;
    } elseif (strpos(strtolower($row['nombre']), 'bandido') !== false) {
        $precio = 35;
    } elseif (strpos(strtolower($row['categoria']), 'columbia') !== false) {
        $precio = 40;
    }
    
    // Estado de stock
    $stock = (float)$row['stock'];
    if ($stock <= 0) {
        $badge = ['bg-danger', 'Agotado'];
    } elseif ($stock <= 5) {
        $badge = ['bg-warning text-dark', 'Pocas unidades'];
    } else {
        $badge = ['bg-success', 'En stock'];
    }
    
    // Imagen
    $imagen = '../img/producto-default.jpg';
    if (!empty($row['imagen'])) {
        $ruta_imagen = '../uploads/' . $row['imagen'];
        if (file_exists($ruta_imagen)) {
            $imagen = $ruta_imagen;
        }
    }
    
    $productos[] = [
        'id' => $row['id'],
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'categoria' => $row['categoria'],
        'tipo_tela' => $row['tipo_tela'],
        'talla' => $row['talla'],
        'diseno' => $row['diseño'],
        'imagen' => $imagen,
        'stock' => $stock,
        'stock_texto' => $stock . ' disponibles',
        'badge_color' => $badge[0],
        'badge_text' => $badge[1],
        'precio' => $precio
    ];
}

// ===== OBTENER CATEGORÍAS =====
$cat_query = "SELECT DISTINCT categoria FROM productos WHERE activo = 1 AND categoria != '' ORDER BY categoria";
$cat_result = $conex->query($cat_query);
$categorias = [];
while ($cat = $cat_result->fetch_assoc()) {
    $categorias[] = $cat['categoria'];
}

// ===== RESPUESTA =====
echo json_encode([
    'success' => true,
    'productos' => $productos,
    'paginacion' => [
        'pagina_actual' => $pagina,
        'total_productos' => $total_productos,
        'total_paginas' => $total_paginas
    ],
    'filtros' => [
        'categorias' => $categorias
    ]
]);

$conex->close();
?>