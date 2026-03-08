<?php
session_start();
include("connect_db.php");
header('Content-Type: application/json');

$stmt = $conex->prepare("SELECT ci, nombre_p FROM persona ORDER BY nombre_p ASC");
$stmt->execute();
$result = $stmt->get_result();
$personas = [];
while ($row = $result->fetch_assoc()) {
    $personas[] = $row;
}
echo json_encode($personas);
?>
