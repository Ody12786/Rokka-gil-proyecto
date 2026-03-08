<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../database/connect_db.php";

// 🔍 DEBUG XAMPP (MUY IMPORTANTE)
if (!$conex || $conex->connect_error) {
    echo json_encode([
        'reply' => '❌ <strong>XAMPP OFF</strong><br>' .
                   '▶ MySQL debe estar VERDE<br>' .
                   '▶ Puerto 3306 activo<br>' .
                   '▶ Verifica localhost/phpmyadmin'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => '❌ Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = isset($_POST['message']) ? trim($_POST['message']) : '';
if ($message === '') {
    echo json_encode(['reply' => '❓ Escribe un mensaje.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$messageLower = mb_strtolower($message, 'UTF-8');

// Formateador de cantidades: si es entero muestra sin decimales, sino con 1 decimal
function format_quantity($n) {
    if (!is_numeric($n)) return $n;
    $n = round((float)$n, 1);
    if (floor($n) == $n) return (string)(int)$n;
    return number_format($n, 1, '.', '');
}



function getClientes($conex, $limit = 5): array {
    $stmt = $conex->prepare("SELECT N_afiliacion, Cid, nombre FROM cliente WHERE is_deleted = 0 ORDER BY N_afiliacion DESC LIMIT ?");
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $clientes = []; 
    while($row = $result->fetch_assoc()) $clientes[] = $row; 
    $stmt->close(); 
    return $clientes;
}

function getVentas($conex, $limit = 3): array {
    $stmt = $conex->prepare("SELECT id, fecha_venta, total FROM ventas ORDER BY fecha_venta DESC LIMIT ?");
    if (!$stmt) return []; 
    $stmt->bind_param("i", $limit); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $ventas = []; 
    while($row = $result->fetch_assoc()) $ventas[] = $row; 
    $stmt->close(); 
    return $ventas;
}

function getProductos($conex, $limit = 5): array {
    $stmt = $conex->prepare("SELECT nombre, stock FROM productos WHERE stock > 0 AND activo = 1 ORDER BY nombre ASC LIMIT ?");
    if (!$stmt) return []; 
    $stmt->bind_param("i", $limit); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $productos = []; 
    while($row = $result->fetch_assoc()) $productos[] = $row; 
    $stmt->close(); 
    return $productos;
}

function getMateriales($conex, $limit = 5): array {
    $stmt = $conex->prepare("SELECT nombre_materia, stock FROM compras_material WHERE stock > 0 ORDER BY nombre_materia ASC LIMIT ?");
    if (!$stmt) return []; 
    $stmt->bind_param("i", $limit); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $materiales = []; 
    while($row = $result->fetch_assoc()) $materiales[] = $row; 
    $stmt->close(); 
    return $materiales;
}

function getTelas($conex, $limit = 5): array {
    $stmt = $conex->prepare("SELECT tipo_tela, metros, total FROM compras_telas ORDER BY id DESC LIMIT ?");
    if (!$stmt) return []; 
    $stmt->bind_param("i", $limit); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $telas = []; 
    while($row = $result->fetch_assoc()) $telas[] = $row; 
    $stmt->close(); 
    return $telas;
}

function getMermasTelas($conex, $limit = 5): array {
    $stmt = $conex->prepare("
        SELECT 
            ct.id,
            ct.tipo_tela,
            ct.metros as metros_comprados,
            COALESCE(SUM(p.metros_de_tela), 0) as metros_usados,
            ROUND(GREATEST(ct.metros - COALESCE(SUM(p.metros_de_tela), 0), 0), 1) as merma,
                CASE 
                    WHEN ct.metros > 0 THEN 
                        ROUND((GREATEST(ct.metros - COALESCE(SUM(p.metros_de_tela), 0), 0) / ct.metros) * 100, 1)
                    ELSE 0 
                END as merma_porcentaje
        FROM compras_telas ct 
        LEFT JOIN productos p ON p.tipo_tela_id = ct.id AND p.activo = 1 AND p.metros_de_tela IS NOT NULL
        GROUP BY ct.id, ct.tipo_tela, ct.metros
        HAVING merma > 0 
        ORDER BY merma DESC 
        LIMIT ?
    ");
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $mermas = [];
    while($row = $result->fetch_assoc()) $mermas[] = $row;
    $stmt->close();
    return $mermas;
}


function getCostoMermas($conex, $limit = 5): array {
    $stmt = $conex->prepare("
        SELECT 
            ct.id, ct.tipo_tela, 
            ct.metros as metros_comprados,
            ct.total as costo_total,
            COALESCE(SUM(p.metros_de_tela), 0) as metros_usados,
            ROUND(GREATEST(ct.metros - COALESCE(SUM(p.metros_de_tela), 0), 0), 1) as merma_metros,
            ROUND((GREATEST(ct.metros - COALESCE(SUM(p.metros_de_tela), 0), 0) / NULLIF(ct.metros, 0)) * ct.total, 2) as costo_merma
        FROM compras_telas ct 
        LEFT JOIN productos p ON p.tipo_tela_id = ct.id AND p.activo = 1 AND p.metros_de_tela IS NOT NULL
        GROUP BY ct.id, ct.tipo_tela, ct.metros, ct.total
        HAVING merma_metros > 0 
        ORDER BY costo_merma DESC 
        LIMIT ?
    ");
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $costos = [];
    while($row = $result->fetch_assoc()) $costos[] = $row;
    $stmt->close();
    return $costos;
}


function getPrecioJusto($conex, $limit = 5): array {
    $stmt = $conex->prepare("SELECT p.nombre, p.metros_de_tela, ct.tipo_tela, ct.metros as metros_comprados, ct.total as costo_tela FROM productos p LEFT JOIN compras_telas ct ON p.tipo_tela_id = ct.id WHERE p.metros_de_tela IS NOT NULL AND p.activo = 1 GROUP BY p.id ORDER BY p.nombre LIMIT ?");
    if (!$stmt) return []; 
    $stmt->bind_param("i", $limit); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $precios = []; 
    while($row = $result->fetch_assoc()) $precios[] = $row; 
    $stmt->close(); 
    return $precios;
}

function getStockCritico($conex, $limit = 10): array {
    $stmt = $conex->prepare("SELECT nombre, stock FROM productos WHERE stock > 0 AND stock <= 5 AND activo = 1 ORDER BY stock ASC LIMIT ?");
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit); 
    $stmt->execute();
    $result = $stmt->get_result();
    $criticos = []; 
    while($row = $result->fetch_assoc()) $criticos[] = $row; 
    $stmt->close(); 
    return $criticos;
}

function getAlertas($conex): array {
    $criticos = count(getStockCritico($conex));
    $mermas = getMermasTelas($conex);
    $total_mermas = empty($mermas) ? 0 : array_sum(array_column($mermas, 'merma'));
    return ['criticos' => $criticos, 'mermas_m' => $total_mermas];
}

function getVentasHoy($conex): array {
    $stmt = $conex->prepare("SELECT COUNT(*) as ventas_hoy, COALESCE(SUM(total), 0) as total_hoy FROM ventas WHERE DATE(fecha_venta) = CURDATE()");
    if (!$stmt) return [['ventas_hoy'=>0, 'total_hoy'=>0]];
    $stmt->execute(); 
    $result = $stmt->get_result();
    $hoy = $result->fetch_assoc() ?: ['ventas_hoy'=>0, 'total_hoy'=>0];
    $stmt->close(); 
    return [$hoy];
}

function getGanancias($conex, $dias = 30): array {
    $stmt = $conex->prepare("
        SELECT 
            COALESCE(SUM(total), 0) as ventas_total,
            COALESCE(COUNT(*), 0) as ventas_cantidad,
            CASE WHEN COUNT(*) > 0 THEN ROUND(AVG(total), 2) ELSE 0 END as ticket_promedio,
            DATE_FORMAT(MIN(fecha_venta), '%d/%m') as primer_venta,
            DATE_FORMAT(MAX(fecha_venta), '%d/%m') as ultima_venta
        FROM ventas 
        WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    ");
    if (!$stmt) return [['ventas_total'=>0, 'ventas_cantidad'=>0, 'ticket_promedio'=>0]];
    $stmt->bind_param("i", $dias); 
    $stmt->execute(); 
    $result = $stmt->get_result();
    $ganancias = $result->fetch_assoc() ?: ['ventas_total'=>0, 'ventas_cantidad'=>0, 'ticket_promedio'=>0];
    $stmt->close(); 
    return [$ganancias];
}
function getCreditosPendientes($conex, $limit = 5): array {
    $sql = "
        SELECT 
            v.id,
            v.fecha_venta,
            v.total,
            c.nombre AS cliente,
            COALESCE(SUM(a.monto), 0) AS pagado,
            (v.total - COALESCE(SUM(a.monto), 0)) AS saldo_pendiente
        FROM ventas v
        JOIN cliente c      ON c.N_afiliacion = v.cliente_id
        LEFT JOIN abonos a  ON a.venta_id = v.id
        WHERE v.tipo_pago = 'Crédito'
          AND v.estado_pago = 'Pendiente'
        GROUP BY v.id
        HAVING saldo_pendiente > 0
        ORDER BY saldo_pendiente DESC
        LIMIT ?
    ";
    $stmt = $conex->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
    return $rows;
}
function getCuentasPorCobrar($conex, $limit = 10): array {
    $sql = "
        SELECT 
            ct.id,
            ct.fecha_adquisicion,
            ct.tipo_tela,
            p.nombres AS proveedor,
            ct.total,
            COALESCE(SUM(pg.monto), 0) AS pagado,
            (ct.total - COALESCE(SUM(pg.monto), 0)) AS saldo_pendiente
        FROM compras_telas ct
        JOIN proveedor p        ON p.id = ct.proveedor_id
        LEFT JOIN pagos_compras pg ON pg.compra_id = ct.id
        WHERE ct.condicion_pago = 'Crédito'
        GROUP BY ct.id
        HAVING saldo_pendiente > 0
        ORDER BY saldo_pendiente DESC
        LIMIT ?
    ";
    $stmt = $conex->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
    return $rows;
}






// 🔥 ARRAYS DE RESPUESTAS
$saludos_humanos = [
    "¡Hola Charlero! 😊 ¿Qué necesitas de Roʞka?",
    "¡Buen día! 👋 ¿En qué te ayudo?",
    "¡Hola jefe! 🚀 ¿Qué buscamos?",
    "¿Qué tal? 😄 ¿Stock o ventas?",
    "¡Ey! 💪 ¿Datos rápidos?"
];

$respuestas_error = [
    "Uy, no entendí 😅 Prueba /stockcritico",
    "Mmm, ese comando no 😬 Mira las alertas",
    "Jeje, no capté 😜 Usa /ganancias", 
    "No sé ese comando 🤔 /creditos?",
    "Eso no lo tengo 😅 /cxp?"
];

try {
    if (strpos($messageLower, '/ayuda') === 0 || strpos($messageLower, '/help') === 0) {
        $reply = "🤖 <strong>Roʞka AI - TODOS los COMANDOS:</strong><hr>
                  <span class='badge bg-success'>/clientes /ventas /productos</span><br>
                  <span class='badge bg-warning'>/materiales /telas /ganancias</span><br>
                  <span class='badge bg-danger'>/stockcritico /mermas /costomermas</span><br>
                  <span class='badge bg-primary'>/creditos /cxp /resumen /ayuda</span><br>
                  <span class='badge bg-info'>/precioprod</span>";
                  
    } elseif (strpos($messageLower, '/resumen') === 0) {
        $criticos = count(getStockCritico($conex));
        $ganancias_data = getGanancias($conex, 7);
        $ganancias = !empty($ganancias_data) ? $ganancias_data[0] : ['ventas_total'=>0, 'ticket_promedio'=>0];
        $creditos = getCreditosPendientes($conex);
        $creditos_count = count($creditos);
        $creditos_total = array_sum(array_column($creditos, 'saldo_pendiente'));
        $cxp = getCuentasPorCobrar($conex);
        $cxp_total = empty($cxp) ? 0 : array_sum(array_column($cxp, 'saldo_pendiente'));
        
        $reply = "📊 <strong>RESUMEN Roʞka (7 días):</strong><hr>
                  🚨 <strong>Stock crítico:</strong> {$criticos} productos<br>
                  💰 <strong>Ventas:</strong> $" . number_format($ganancias['ventas_total'], 2) . "<br>
                  💳 <strong>Créditos pendientes:</strong> {$creditos_count} ($$" . number_format($creditos_total, 2) . ")<br>
                  🏭 <strong>CxP proveedores:</strong> $" . number_format($cxp_total, 2) . "<br>
                  📈 <strong>Ticket promedio:</strong> $" . number_format($ganancias['ticket_promedio'], 2);

    } elseif (strpos($messageLower, '/alertas') === 0) {
        $alertas = getAlertas($conex);
        $creditos_count = count(getCreditosPendientes($conex));
        $cxp = getCuentasPorCobrar($conex);
        $cxp_count = count($cxp);
        
        $reply = "🚨 <strong>ALERTAS Roʞka:</strong><hr>
                  ⚠️ Stock crítico: {$alertas['criticos']}<br>
                  🧵 Mermas total: " . format_quantity($alertas['mermas_m']) . "m<br>
                  💳 Créditos pendientes: {$creditos_count}<br>
                  🏭 CxP proveedores: {$cxp_count}";
                  
    } elseif (strpos($messageLower, '/recordatorios') === 0 || strpos($messageLower, '/recordatorio') === 0) {
        // 🚨 STOCK CRÍTICO
        $criticos = count(getStockCritico($conex));
        
        // 💳 CRÉDITOS PENDIENTES (clientes)
        $creditos = getCreditosPendientes($conex);
        $deuda_clientes = array_sum(array_column($creditos, 'saldo_pendiente'));
        
        // 🏭 CUENTAS POR PAGAR (proveedores)
        $cxp = getCuentasPorCobrar($conex);
        $deuda_proveedores = array_sum(array_column($cxp, 'saldo_pendiente'));
        
        // 📦 PRODUCTOS SIN TELA ASIGNADA
        $stmt_tela = $conex->prepare("\n            SELECT COUNT(*) as sin_tela \n            FROM productos p \n            LEFT JOIN compras_telas ct ON p.tipo_tela_id = ct.id \n            WHERE p.activo = 1 AND (p.metros_de_tela IS NULL OR p.metros_de_tela = 0)\n              AND ct.id IS NULL\n        ");
        if ($stmt_tela) {
            $stmt_tela->execute();
            $sin_tela = $stmt_tela->get_result()->fetch_assoc()['sin_tela'] ?? 0;
            $stmt_tela->close();
        } else {
            $sin_tela = 0;
        }
        
        // 🔔 ARRAY DE TAREAS
        $pendientes = [];
        
        if ($criticos > 0) {
            $pendientes[] = "🛒 <strong>REPONER {$criticos} productos críticos</strong>";
        }
        
        if ($deuda_clientes > 0) {
            $pendientes[] = "💳 <strong>COBRAR</strong> $" . number_format($deuda_clientes, 0) . " a clientes";
        }
        
        if ($deuda_proveedores > 0) {
            $pendientes[] = "🏭 <strong>PAGAR</strong> $" . number_format($deuda_proveedores, 0) . " a proveedores";
        }
        
        if ($sin_tela > 0) {
            $pendientes[] = "🧵 <strong>Asignar tela</strong> a {$sin_tela} productos";
        }
        
        // 📊 METAS DEL DÍA (ejemplo)
        $hoy = getVentasHoy($conex)[0];
        $meta_diaria = 5000;
        $faltante = max(0, $meta_diaria - $hoy['total_hoy']);
        if ($faltante > 0) {
            $pendientes[] = "🎯 <strong>Vender</strong> $" . number_format($faltante, 0) . " más hoy";
        }
        
        // RESULTADO
        if (empty($pendientes)) {
            $reply = "🎉 <strong>¡TODO AL DÍA! ✅</strong><hr>\n                  <em>Estás completamente actualizado 🚀</em>";
        } else {
            $reply = "🔔 <strong>RECORDATORIOS URGENTES:</strong><hr>";
            $reply .= implode('<br><br>', $pendientes);
            $reply .= "<hr><small>💡 Ejecuta /stockcritico /creditos /cxp para detalles</small>";
        }
    } elseif (strpos($messageLower, '/ventashoy') === 0) {
        $hoy = getVentasHoy($conex)[0];
        $reply = "📅 <strong>VENTAS HOY:</strong><hr>
                  💰 Total: $" . number_format($hoy['total_hoy'], 2) . "<br>
                  📊 {$hoy['ventas_hoy']} transacciones";
                  
    } elseif (strpos($messageLower, '/creditos') === 0) {
        $creditos = getCreditosPendientes($conex);
        if (empty($creditos)) {
            $reply = "✅ <strong>¡Perfecto!</strong> No hay créditos pendientes 💳";
        } else {
            $total_saldo = array_sum(array_column($creditos, 'saldo_pendiente'));
            $reply = "💳 <strong>CRÉDITOS PENDIENTES (CLIENTES):</strong><br><br>";
            foreach ($creditos as $c) {
                $reply .= "👤 <strong>" . htmlspecialchars($c['cliente']) . "</strong><br>
                           🧾 Venta #{$c['id']} ({$c['fecha_venta']})<br>
                           💵 Total: $" . number_format($c['total'], 2) . "<br>
                           ✅ Pagado: $" . number_format($c['pagado'], 2) . "<br>
                           ⚠️ <strong>Saldo: $" . number_format($c['saldo_pendiente'], 2) . "</strong><br><br>";
            }
            $reply .= "<hr><strong>🚨 TOTAL PENDIENTE: $" . number_format($total_saldo, 2) . "</strong>";
        }
        
    } elseif (strpos($messageLower, '/stockcritico') === 0 || strpos($messageLower, '/stock') === 0) {
        $criticos = getStockCritico($conex);
        if (empty($criticos)) {
            $reply = "✅ <strong>¡PERFECTO!</strong> Todo el stock está OK 😊<br>
                      <small>No hay productos en peligro</small>";
        } else {
            $reply = "🚨 <strong>STOCK CRÍTICO (" . count($criticos) . "):</strong><br>";
            foreach ($criticos as $p) {
                $reply .= "⚠️ <strong>" . htmlspecialchars($p['nombre']) . "</strong> → <span class='text-danger'>{$p['stock']}ud</span><br>";
            }
            $reply .= "<hr><strong>🛒 ¡REPONER YA!</strong>";
        }
        
    } elseif (strpos($messageLower, '/cxp') === 0 || strpos($messageLower, '/cuentas') === 0) {
        $cxp = getCuentasPorCobrar($conex);
        if (empty($cxp)) {
            $reply = "✅ <strong>¡Genial!</strong> No hay cuentas por pagar a proveedores 📦";
        } else {
            $total_saldo = array_sum(array_column($cxp, 'saldo_pendiente'));
            $reply = "🏭 <strong>CUENTAS POR PAGAR (PROVEEDORES):</strong><br><br>";
            foreach ($cxp as $c) {
                $reply .= "🏭 <strong>" . htmlspecialchars($c['proveedor']) . "</strong><br>
                           🧵 Compra #{$c['id']} - " . htmlspecialchars($c['tipo_tela']) . "<br>
                           📅 {$c['fecha_adquisicion']}<br>
                           💵 Total: $" . number_format($c['total'], 2) . "<br>
                           ✅ Pagado: $" . number_format($c['pagado'], 2) . "<br>
                           ⚠️ <strong>Saldo: $" . number_format($c['saldo_pendiente'], 2) . "</strong><br><br>";
            }
            $reply .= "<hr><strong>🚨 TOTAL PENDIENTE: $" . number_format($total_saldo, 2) . "</strong>";
        }
        
    } elseif (strpos($messageLower, '/ganancias') === 0 || strpos($messageLower, '/ingresos') === 0) {
        $ganancias = getGanancias($conex, 30);
        $data = $ganancias[0];
        $reply = "💰 <strong>GANANCIAS Roʞka (30 días):</strong><br><br>";
        $reply .= "💵 <strong>Ventas:</strong> $" . number_format($data['ventas_total'], 2) . "<br>";
        $reply .= "📊 <strong>Transacciones:</strong> {$data['ventas_cantidad']}<br>";
        $reply .= "🎫 <strong>Ticket promedio:</strong> $" . number_format($data['ticket_promedio'], 2) . "<br>";
        $reply .= "📅 <strong>Período:</strong> {$data['primer_venta']} - {$data['ultima_venta']}<br><br>";
        
        $ticket = $data['ticket_promedio'] ?? 0;
        $reply .= "<strong>📈 TENDENCIA:</strong><br>";
        
        if ($ticket > 50) {
            $reply .= "⭐ <strong>¡EXCELENTE!</strong> ¡Clientes Other Level!";
        } elseif ($ticket > 30) {
            $reply .= "✅ <strong>BUENO</strong> Mantén el ritmo";
        } else {
            $reply .= "⚠️ <strong>ATENCIÓN</strong> Tickets bajos";
        }
        
        $reply .= "<hr><strong>💎 " . number_format($data['ventas_total'], 0) . " FACTURADOS</strong>";
        
    } elseif (strpos($messageLower, '/mermas') === 0 || strpos($messageLower, '/desperdicio') === 0) {
        $mermas = getMermasTelas($conex);
        if (empty($mermas)) {
            $reply = "✅ ¡Perfecto! No hay mermas de telas 😊";
        } else {
            $total_merma = array_sum(array_column($mermas, 'merma'));
            $reply = "📊 <strong>MERMAS DE TELAS:</strong><br>";
            foreach ($mermas as $m) {
                $porcentaje = (isset($m['metros_comprados']) && $m['metros_comprados'] > 0)
                    ? sprintf("%.1f", ($m['merma'] / $m['metros_comprados']) * 100)
                    : '0.0';
                $display_merma = format_quantity($m['merma']);
                $reply .= "🧵 <strong>" . htmlspecialchars($m['tipo_tela']) . "</strong>: {$display_merma}m ({$porcentaje}%)<br>";
            }
            $reply .= "<hr><strong>Total: " . format_quantity($total_merma) . "m</strong>";
        }
        
    } elseif (strpos($messageLower, '/costomermas') === 0 || strpos($messageLower, '/costomerma') === 0) {
        $costos = getCostoMermas($conex);
        if (empty($costos)) {
            $reply = "✅ <strong>¡Excelente!</strong> No hay costo por mermas 💰";
        } else {
            $total_costo = array_sum(array_column($costos, 'costo_merma'));
            $reply = "💸 <strong>COSTO DE MERMAS:</strong><br><br>";
            foreach ($costos as $c) {
                $metros_comprados = $c['metros_comprados'] ?? 0;
                $merma_metros = $c['merma_metros'] ?? 0;
                $porcentaje = ($metros_comprados > 0) ? sprintf("%.1f", ($merma_metros / $metros_comprados) * 100) : '0.0';
                $display_merma = format_quantity($merma_metros);
                $display_metros = format_quantity($metros_comprados);
                $reply .= "🧵 <strong>" . htmlspecialchars($c['tipo_tela']) . "</strong><br>
                           &nbsp;&nbsp;💵 <span class='text-danger'>Costo merma: $" . number_format($c['costo_merma'], 2) . "</span><br>
                           &nbsp;&nbsp;📏 {$display_merma}m de {$display_metros}m ({$porcentaje}%)<br><br>";
            }
            $reply .= "<hr><strong>🚨 TOTAL PERDIDO: $" . number_format($total_costo, 2) . "</strong>";
        }
        
    } elseif (strpos($messageLower, '/precioprod') === 0 || strpos($messageLower, '/precio') === 0) {
        $precios = getPrecioJusto($conex);
        if (empty($precios)) {
            $reply = "📊 <strong>No hay productos con precio calculado.</strong>";
        } else {
            $reply = "💰 <strong>PRECIOS JUSTOS Roʞka:</strong><br><br>";
            foreach ($precios as $prod) {
                $costo_metro = $prod['metros_comprados'] > 0 ? $prod['costo_tela'] / $prod['metros_comprados'] : 0;
                $costo_producto = $costo_metro * $prod['metros_de_tela'];
                $precio_minimo = $costo_producto * 2.5;
                $precio_sugerido = $costo_producto * 3.2;
                $precio_premium = $costo_producto * 4.0;
                
                $reply .= "👕 <strong>" . htmlspecialchars($prod['nombre']) . "</strong><br>
                           &nbsp;&nbsp;📏 " . $prod['metros_de_tela'] . "m " . htmlspecialchars($prod['tipo_tela']) . "<br>
                           &nbsp;&nbsp;💵 <strong>Costo: $" . number_format($costo_producto, 2) . "</strong><br>
                           &nbsp;&nbsp;💰 <strong>Precio mínimo: $" . number_format($precio_minimo, 2) . "</strong><br>
                           &nbsp;&nbsp;⭐ <strong>Sugerido: $" . number_format($precio_sugerido, 2) . "</strong><br>
                           &nbsp;&nbsp;💎 <strong>Premium: $" . number_format($precio_premium, 2) . "</strong><hr>";
            }
        }
        
    } elseif (strpos($messageLower, '/clientes') === 0) {
        $clientes = getClientes($conex);
        $reply = empty($clientes) ? '📋 No hay clientes.' : 
            '👥 <strong>Últimos clientes:</strong><br>' . 
            implode('<br>', array_map(fn($c) => 
                "👤 {$c['N_afiliacion']} - {$c['Cid']} - " . htmlspecialchars($c['nombre'])
            , $clientes));
            
    } elseif (strpos($messageLower, '/ventas') === 0) {
        $ventas = getVentas($conex);
        $reply = empty($ventas) ? '💰 No hay ventas.' :
            '💰 <strong>Últimas ventas:</strong><br>' .
            implode('<br>', array_map(fn($v) => 
                "💵 #{$v['id']} - {$v['fecha_venta']} - $" . number_format($v['total'], 2)
            , $ventas));
            
    } elseif (strpos($messageLower, '/productos') === 0) {
        $productos = getProductos($conex);
        $reply = empty($productos) ? '📦 Sin stock.' :
            '📦 <strong>Productos:</strong><br>' .
            implode('<br>', array_map(fn($p) => "• " . htmlspecialchars($p['nombre']) . " ({$p['stock']}ud)", $productos));
            
    } elseif (strpos($messageLower, '/material') === 0 || strpos($messageLower, '/materiales') === 0) {
        $materiales = getMateriales($conex);
        $reply = empty($materiales) ? '⚙️ Sin materiales.' :
            '⚙️ <strong>Materiales:</strong><br>' .
            implode('<br>', array_map(fn($m) => "• " . htmlspecialchars($m['nombre_materia']) . " ({$m['stock']}ud)", $materiales));
            
    } elseif (strpos($messageLower, '/telas') === 0) {
        $telas = getTelas($conex);
        $reply = empty($telas) ? '🧵 Sin telas.' :
            '🧵 <strong>Telas:</strong><br>' .
            implode('<br>', array_map(fn($t) => "• " . htmlspecialchars($t['tipo_tela']) . " ({$t['metros']}m)", $telas));
            
    } elseif (preg_match('/\b(hola|buen|buenas|hey|ey|saludos)\b/i', $message)) {
        $reply = $saludos_humanos[array_rand($saludos_humanos)] . "<br><br>" .
                 "<strong>🚀 COMANDOS PRO:</strong><br>" .
                 "<span class='badge bg-success'>/clientes /ventas</span> <span class='badge bg-primary'>/creditos /cxp</span><br>" .
                 "<span class='badge bg-warning'>/ganancias /stockcritico</span> <span class='badge bg-danger'>/mermas</span><br>" .
                 "<span class='badge bg-info'>/resumen /alertas /ayuda</span>";
                 
    } else {
        $reply = $respuestas_error[array_rand($respuestas_error)] . "<br><br>" .
                 "<strong>🚀 COMANDOS:</strong><br>/resumen /creditos /cxp /stockcritico /ganancias /ayuda";
    }
  
} catch (Exception $e) {
    error_log("RoʞkaBot ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $reply = "❌ Error del sistema: " . $e->getMessage() . "\nVerifica XAMPP, base de datos y revisa el log de PHP/Apache.";
}

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
?>
