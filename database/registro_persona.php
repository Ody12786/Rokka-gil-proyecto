<?php
session_start();
include("connect_db.php");
  // ✅ CAMBIO: ../database/ → database/
error_log("=== DEBUG registro_persona.php ===");
error_log("POST completo: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

header('Content-Type: application/json; charset=utf-8');

if (empty($_POST)) {
    error_log("POST VACÍO");
    echo json_encode(['status' => 'error', 'message' => 'POST vacío']);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'No autenticado']);
    exit;
}


$ci = trim($_POST['ci'] ?? '');
$nombre_p = trim($_POST['nombre_p'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$sexo = trim($_POST['sexo'] ?? '');
$correo = trim($_POST['email'] ?? '');

if (empty($ci) || empty($nombre_p) || empty($sexo) || empty($correo)) {
    echo json_encode(['status'=>'error', 'message'=>'Datos requeridos inválidos']);
    exit;
}

// Verificar conexión a la base de datos
if (!$conex) {
    error_log('registro_persona: Conexión a BD inválida');
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Chequear si la cédula ya existe
if ($stmtChk = $conex->prepare("SELECT Ci FROM persona WHERE Ci = ?")) {
    $stmtChk->bind_param("s", $ci);
    $stmtChk->execute();
    $resChk = $stmtChk->get_result();
    if ($resChk && $resChk->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cédula ya registrada']);
        $stmtChk->close();
        $conex->close();
        exit;
    }
    $stmtChk->close();
} else {
    error_log('registro_persona: fallo prepare duplicado: ' . $conex->error);
}

try {
    $stmt = $conex->prepare("INSERT INTO persona (Ci, nombre_p, apellido, sexo, correo) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('registro_persona: fallo prepare insert: ' . $conex->error);
        echo json_encode(['status' => 'error', 'message' => 'Error interno en preparación de consulta']);
        $conex->close();
        exit;
    }
    $stmt->bind_param("sssss", $ci, $nombre_p, $apellido, $sexo, $correo);

    if ($stmt->execute()) {
        // Devolver CI para que el frontend pueda seleccionarla inmediatamente
        echo json_encode(['status'=>'success', 'message'=>'Persona creada', 'ci' => $ci]);
    } else {
        error_log('registro_persona: fallo execute insert: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar: ' . $stmt->error]);
    }
} catch (Exception $e) {
    error_log('registro_persona: exception: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Excepción: ' . $e->getMessage()]);
}
if (isset($stmt) && $stmt) $stmt->close();
$conex->close();
?>
