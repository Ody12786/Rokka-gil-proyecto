<?php
session_start();
include("../database/connect_db.php");

// 👇 CERRAR ASISTENTE (NUEVO)
if (isset($_SESSION['asistente_id'])) {
    $stmt = $conex->prepare("UPDATE usuario_asistente SET estado = 'inactivo' WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $stmt->close();
}

// Limpiar sesiones
$_SESSION = array();
session_unset();
session_destroy();
session_write_close();

// Redirigir al login
header("Location: ../index.php?logout=1");
exit();
?>
