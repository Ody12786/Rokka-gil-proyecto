<?php
include("database/connect_db.php");

if (isset($_POST['enviar'])) {

    $usuario = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['contrasena'] ?? '');
    $pregunta = trim($_POST['pregunta'] ?? '');
    $respuesta = trim($_POST['respuesta'] ?? '');
    $preguntaD = trim($_POST['preguntaDos'] ?? '');
    $respuestaD = trim($_POST['respuestaDos'] ?? '');

    if (strlen($usuario) >= 1 && strlen($pass) >= 1) {

        
        $stmt = $conex->prepare("SELECT id_rec FROM usuario WHERE nombre = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo '<script>alert("Este nombre de usuario ya existe");</script>';
            exit();
        }
        $stmt->close();

        
        $passHash = password_hash($pass, PASSWORD_DEFAULT);

        
        $stmt = $conex->prepare("INSERT INTO usuario (nombre, Clave) VALUES (?, ?)");
        $stmt->bind_param("ss", $usuario, $passHash);
        if ($stmt->execute()) {
            
            $userId = $stmt->insert_id;
            $stmt->close();

            
            $stmt2 = $conex->prepare("INSERT INTO Recuperar_contrasena (user_id, P1, R1, P2, R2) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("issss", $userId, $pregunta, $respuesta, $preguntaD, $respuestaD);

            if ($stmt2->execute()) {
                echo '<script>alert("Registro exitoso");</script>';
               
            } else {
                echo '<script>alert("Error al guardar preguntas de seguridad");</script>';
            }
            $stmt2->close();
        } else {
            echo '<script>alert("Error al registrar usuario");</script>';
        }
    } else {
        echo '<script>alert("Complete todos los campos");</script>';
    }
}
?>
