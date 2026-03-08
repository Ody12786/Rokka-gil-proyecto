<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

// Opcional: Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['data' => []]);
    exit;
}

$query = "SELECT id, fecha_adquisicion, tipo_tela, metros, condicion_pago FROM compras_telas ORDER BY fecha_adquisicion DESC, id DESC";
$result = $conex->query($query);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'Fecha' => $row['fecha_adquisicion'],
            'TipoTela' => $row['tipo_tela'],
            'Metros' => $row['metros'],
            'Condición' => $row['condicion_pago']
        ];
    }
}

echo json_encode(['data' => $data]);
?>