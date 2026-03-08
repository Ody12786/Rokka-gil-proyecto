<?php
session_start();
include("connect_db.php");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status'=>'error', 'message'=>'No autenticado']);
    exit;
}


$cedula_empleado = trim($_POST['cedula_empleado'] ?? '');
$carnet = trim($_POST['carnet'] ?? '');

// Validaciones básicas con mensajes claros
if (empty($cedula_empleado)) {
    echo json_encode(['status'=>'error', 'field' => 'cedula_empleado', 'message'=>'Seleccione una persona (cédula)']);
    exit;
}
if (!preg_match('/^\d{6,9}$/', $carnet)) {
    echo json_encode(['status'=>'error', 'field' => 'carnet', 'message'=>'Carnet inválido. Debe tener 6-9 dígitos numéricos']);
    exit;
}

// Verificar que la cédula exista en persona
if ($chkPerson = $conex->prepare("SELECT nombre_p, apellido, correo FROM persona WHERE Ci = ?")) {
    $chkPerson->bind_param("s", $cedula_empleado);
    $chkPerson->execute();
    $res = $chkPerson->get_result();
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['status'=>'error', 'field' => 'cedula_empleado', 'message'=>'Cédula no encontrada en registros de personas']);
        $chkPerson->close();
        $conex->close();
        exit;
    }
    $personaRow = $res->fetch_assoc();
    $chkPerson->close();
} else {
    error_log('registro_empleado: fallo al preparar select persona: ' . $conex->error);
}

// Verificar carnet único
$stmt = $conex->prepare("SELECT 1 FROM empleado WHERE carnet = ?");
$stmt->bind_param("s", $carnet);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status'=>'error', 'field' => 'carnet', 'message'=>'Carnet ya registrado']);
    $stmt->close();
    $conex->close();
    exit;
}
$stmt->close();

$stmt = $conex->prepare("INSERT INTO empleado (cedula, carnet) VALUES (?, ?)");
if (!$stmt) {
    echo json_encode(['status'=>'error', 'message'=>'Error interno: ' . $conex->error]);
    $conex->close();
    exit;
}
$stmt->bind_param("ss", $cedula_empleado, $carnet);  // ✅ ss no "is"

if ($stmt->execute()) {
    // devolver datos para que el frontend pueda pasar a pestaña Usuario
    $nombreCompleto = trim(($personaRow['nombre_p'] ?? '') . ' ' . ($personaRow['apellido'] ?? ''));
    $correoPersona = $personaRow['correo'] ?? '';
    echo json_encode(['status'=>'success', 'message'=>'Empleado creado', 'cedula' => $cedula_empleado, 'carnet' => $carnet, 'nombre' => $nombreCompleto, 'correo' => $correoPersona]);
} else {
    echo json_encode(['status'=>'error', 'message'=>'Error al guardar: ' . $stmt->error]);
}
if (isset($stmt) && $stmt) $stmt->close();
$conex->close();
?>
