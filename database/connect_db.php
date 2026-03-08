<?php
// connect_db.php
$conex = new mysqli("localhost", "root", "", "roka_sport_gil");

if ($conex->connect_error) {
   
    error_log("Error de conexión a BD: " . $conex->connect_error);
    $conex = null; 

} else {
    $conex->set_charset("utf8");
    $conex->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}
