<?php
include("../database/connect_db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$rif = $_POST['rif'] ?? '';
$nombres = $_POST['nombres'] ?? '';
$empresa = $_POST['empresa'] ?? '';
$contacto = $_POST['contacto'] ?? '';

if ($rif && $nombres && $empresa && $contacto) {
$stmt = $conex->prepare("INSERT INTO Proveedor (rif, nombres, empresa, contacto, `fecha_creación`) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("ssss", $rif, $nombres, $empresa, $contacto);

if ($stmt->execute()) {
echo "1"; // Éxito
} else {
echo "Error: " . $stmt->error;
}
$stmt->close();
} else {
echo "Complete todos los campos.";
}
}
$conex->close();
?>




