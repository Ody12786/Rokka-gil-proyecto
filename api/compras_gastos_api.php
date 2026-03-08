<?php
session_start();
include("../database/connect_db.php");

// Validar sesión por seguridad
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Obtener año actual
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// Preparar arreglo para etiquetas y datos (12 meses del año)
$labels = [];
$gastos = [];
for ($m = 1; $m <= 12; $m++) {
    $labels[] = sprintf('%04d-%02d', $anio, $m);
    $gastos[] = 0.0;
}

// Consulta para agrupar gastos por mes
$sql = "SELECT 
            DATE_FORMAT(fecha_adquisicion, '%Y-%m') AS mes,
            SUM(metros * precio_unitario) AS total_gasto
        FROM compras_telas
        WHERE YEAR(fecha_adquisicion) = ?
        GROUP BY mes
        ORDER BY mes ASC";

if ($stmt = $conex->prepare($sql)) {
    $stmt->bind_param("i", $anio);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $mes = $row['mes'];
        $total = floatval($row['total_gasto']);
        $idx = array_search($mes, $labels, true);
        if ($idx !== false) {
            $gastos[$idx] = $total;
        }
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error en la consulta SQL']);
    exit;
}

// Devolver datos JSON para Chart.js
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'labels' => $labels,
    'gastos' => $gastos
]);
?>
