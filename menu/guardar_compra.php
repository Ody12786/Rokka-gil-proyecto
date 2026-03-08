<?php
session_start();
include("../database/connect_db.php");
header('Content-Type: application/json');

if (
    isset($_POST['fechaCompra'], $_POST['tipoTela'], $_POST['metros'], $_POST['condicionPago']) &&
    !empty($_POST['fechaCompra']) && !empty($_POST['tipoTela']) && !empty($_POST['metros']) && !empty($_POST['condicionPago'])
) {
    $fecha = $_POST['fechaCompra'];
    $tipo = $_POST['tipoTela'];
    $metros = floatval($_POST['metros']);
    $condicion = $_POST['condicionPago'];

    $sql = "INSERT INTO compras_telas (fecha_adquisicion, tipo_tela, metros, condicion_pago) VALUES (?, ?, ?, ?)";
    if ($stmt = $conex->prepare($sql)) {
        $stmt->bind_param("ssds", $fecha, $tipo, $metros, $condicion);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al guardar la compra.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Error en la consulta.']);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos.']);
}
?>

