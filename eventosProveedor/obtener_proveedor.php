<?php
include('../database/connect_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8'); // Cabecera JSON

    $rif = $_POST['rif'] ?? '';

    if ($rif) {
        $stmt = $conex->prepare("SELECT rif, nombres, empresa, contacto FROM Proveedor WHERE rif = ?");
        $stmt->bind_param("s", $rif);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $data = $result->fetch_assoc();
            echo json_encode($data);
        } else {
            echo json_encode(null);
        }

        $stmt->close();
    } else {
        echo json_encode(null);
    }
}
$conex->close();
?>

