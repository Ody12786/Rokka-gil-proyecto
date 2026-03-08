<?php
session_start();
include("connect_db.php");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status'=>'error','message'=>'No autenticado']);
    exit;
}

// Recibir campos (mezcla de los tres endpoints)
$ci = trim($_POST['ci'] ?? '');
$nombre_p = trim($_POST['nombre_p'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$sexo = trim($_POST['sexo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['email'] ?? '');


$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$tipo_usuario = intval($_POST['tipo_usuario'] ?? 0);
$password = $_POST['password'] ?? '';





if (!$conex) {
    echo json_encode(['status'=>'error','message'=>'Error de conexión a la base de datos']);
    exit;
}

// Usar transacción para consistencia
$conex->begin_transaction();
try {
    if (!empty($ci)) {
      
    // 1) Insertar persona si no existe
    $stmt = $conex->prepare("SELECT Ci FROM persona WHERE Ci = ?");
    $stmt->bind_param("s", $ci);
    $stmt->execute();
    $res = $stmt->get_result();
    $persona_exists = ($res && $res->num_rows > 0);
    $stmt->close();

    if (!$persona_exists) {
        $stmt = $conex->prepare("INSERT INTO persona (Ci, nombre_p, apellido, sexo, correo) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception('Error prepare persona: ' . $conex->error);
        $stmt->bind_param("sssss", $ci, $nombre_p, $apellido, $sexo, $correo);
        if (!$stmt->execute()) throw new Exception('Error insert persona: ' . $stmt->error);
        $stmt->close();
    } else {
        // opcional: actualizar correo/nombre si se desea (no forzado aquí)
        $stmt = $conex->prepare("UPDATE persona SET nombre_p=?, apellido=?, sexo=?, correo=? WHERE Ci=?");
        if (!$stmt) throw new Exception('Error prepare persona update: ' . $conex->error);
        $stmt->bind_param("sssss", $nombre_p, $apellido, $sexo, $correo, $ci);
        if (!$stmt->execute()) throw new Exception('Error update persona: ' . $stmt->error);
        $stmt->close();
    }

    // 2) Insertar empleado si no existe
    $stmt = $conex->prepare("SELECT 1 FROM empleado WHERE cedula = ?");
    $stmt->bind_param("s", $ci);
    $stmt->execute();
    $stmt->store_result();
    $empleado_exists = ($stmt->num_rows > 0);
    $stmt->close();

    if (!$empleado_exists) {
        $stmt = $conex->prepare("INSERT INTO empleado (cedula, carnet) VALUES (?, CONCAT('E-', DATE_FORMAT(NOW(), '%y%m%d%H%i')))");
        if (!$stmt) throw new Exception('Error prepare empleado: ' . $conex->error);
        $stmt->bind_param("s", $ci);
        if (!$stmt->execute()) throw new Exception('Error insert empleado: ' . $stmt->error);
        $stmt->close();
    }

    

    // 3) Crear o actualizar usuario
    // Verificar si ya existe usuario con ese carnet (empleado)
    $stmt = $conex->prepare("SELECT carnet FROM empleado WHERE Cedula = ?");
    $stmt->bind_param("s", $ci);
    $stmt->execute();
    $result = $stmt->get_result();
    $carnet = $result->fetch_assoc()['carnet'] ?? null;
    $stmt->close();


    $stmt = $conex->prepare("SELECT id_rec FROM usuario WHERE carnet = ?");
    $stmt->bind_param("s", $carnet);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        // insertar nuevo usuario
        $pass_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conex->prepare("INSERT INTO usuario (nombre, correo, telefono, carnet, tipo, contrasena) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception('Error prepare usuario insert: ' . $conex->error);
        $stmt->bind_param("ssssis", $nombre_usuario, $correo, $telefono, $carnet, $tipo_usuario, $pass_hashed);
        if (!$stmt->execute()) throw new Exception('Error insert usuario: ' . $stmt->error);
        $stmt->close();
    } else {
        $id_rec = $usuario['id_rec'];
        if (!empty($password)) {
            $pass_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conex->prepare("UPDATE usuario SET nombre=?, correo=?, telefono=?, tipo=?, contrasena=? WHERE id_rec=?");
            if (!$stmt) throw new Exception('Error prepare usuario update: ' . $conex->error);
            $stmt->bind_param("sssisi", $nombre_usuario, $correo, $telefono, $tipo_usuario, $pass_hashed, $id_rec);
            if (!$stmt->execute()) throw new Exception('Error update usuario: ' . $stmt->error);
            $stmt->close();
        } else {
            $stmt = $conex->prepare("UPDATE usuario SET nombre=?, correo=?, telefono=?, tipo=? WHERE id_rec=?");
            if (!$stmt) throw new Exception('Error prepare usuario update2: ' . $conex->error);
            $stmt->bind_param("sssii", $nombre_usuario, $correo, $telefono, $tipo_usuario, $id_rec);
            if (!$stmt->execute()) throw new Exception('Error update usuario: ' . $stmt->error);
            $stmt->close();
        }
    }
    $conex->commit();
    } else {
        $stmt = $conex->prepare("SELECT 1 FROM usuario WHERE nombre = ?");
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // actualizar tipo de usuario
            $stmt->close();
            $stmt = $conex->prepare("UPDATE usuario SET tipo=? WHERE nombre=?");
            $stmt->bind_param("is", $tipo_usuario, $nombre_usuario);
            if (!$stmt->execute()) throw new Exception('Error update usuario tipo: ' . $stmt->error);
            $stmt->close();
            $conex->commit();
            echo json_encode(['status'=>'success','message'=>'Actualización completada']);
            $conex->close();
            exit;;
        }
    }
    

    echo json_encode(['status'=>'success','message'=>'Registro completado','ci'=>$ci,'carnet'=>$carnet]);
} catch (Exception $e) {
    $conex->rollback();
    error_log('registro_completo error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

$conex->close();
?>
