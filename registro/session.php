<?php
session_start();
include("../database/connect_db.php");
// Verificar token CSRF
if (
    !isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    error_log("CSRF token inválido en login");
    echo 4;
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';


if ($usuario == '' || $contrasena == '') {
    echo 2;
    exit;
}


try {

    $sql = "SELECT id_rec, nombre, tipo, contrasena FROM usuario WHERE nombre = ? LIMIT 1";
    $stmt = $conex->prepare($sql);

    if (!$stmt) {
        error_log("Error preparando statement: " . $conex->error);
        echo 5;
        exit;
    }

    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($contrasena, $user['contrasena'])) {
            session_regenerate_id(true);


            $_SESSION['usuario_id'] = $user['id_rec'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_tipo'] = $user['tipo'];
            $_SESSION['usuario_rol'] = $user['tipo'] === 1 ? 'Administrador' : 'Estándar';
            $_SESSION['logged_in'] = time();

            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $dispositivo = strpos($user_agent, 'Mobile') !== false ? 'Móvil' : 'PC';
            $sesion_id = session_id();

            $stmt_asistente = $conex->prepare("
    INSERT INTO usuario_asistente (usuario_id, ip_address, user_agent, sesion_id, dispositivo) 
    VALUES (?, ?, ?, ?, ?)
");
            $stmt_asistente->bind_param("issss", $_SESSION['usuario_id'], $ip, $user_agent, $sesion_id, $dispositivo);
            $stmt_asistente->execute();
            $_SESSION['asistente_id'] = $conex->insert_id;
            $stmt_asistente->close();

            //Registra la fecha y hora de la última conexión
            date_default_timezone_set('America/Caracas'); 
            $fechaConexion = date("Y-m-d H:i:s");
            $sqlUpdate = "UPDATE usuario SET ultima_conexion = ? WHERE id_rec = ?";
            $stmtUpdate = $conex->prepare($sqlUpdate);
            $stmtUpdate->bind_param("si", $fechaConexion, $user['id_rec']);
            $stmtUpdate->execute();
            $stmtUpdate->close();


            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            echo 1;
        } else {
            echo 2;
        }
    } else {
        echo 2;
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Error en session.php: " . $e->getMessage());
    echo 5; // Error en servidor
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}
