<?php
// crud_clientes.php
session_start();
header('Content-Type: application/json');
require_once 'connect_db.php';

$accion = $_POST['accion'] ?? '';

if ($accion === 'crear') {
    $cedula = $conex->real_escape_string($_POST['cedula']);
    $nombre = $conex->real_escape_string($_POST['nombre']);
    
    // Verificar si ya existe
    $check = $conex->query("SELECT N_afiliacion FROM cliente WHERE Cid = '$cedula' AND is_deleted = 0");
    
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        echo json_encode(['success' => true, 'id' => $row['N_afiliacion'], 'message' => 'Cliente ya existente']);
    } else {
        $sql = "INSERT INTO cliente (Cid, nombre, is_deleted) VALUES ('$cedula', '$nombre', 0)";
        if ($conex->query($sql)) {
            echo json_encode(['success' => true, 'id' => $conex->insert_id, 'message' => 'Cliente creado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear cliente']);
        }
    }
}
?>