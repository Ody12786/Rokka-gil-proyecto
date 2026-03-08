<?php
include("connect_db.php");
include("cifrado.php");

header('Content-Type: application/json');
$id_enc = $_GET['id'] ?? '';
$id_real = decryptId($id_enc);

echo json_encode(['id_real' => $id_real]);
?>
