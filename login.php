<?php
session_start();
// Generar/regenerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/estiloslog.css">
    <link rel="icon" href="favicon.ico">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <title>Login - OtherLevel</title>

  
</head>
<body>
<div class="container">

<div class="login" id="uno">
        <form id="formu" action="" method="POST">
            <div class="cont_img"><img src="img/rotsen_logo.png" alt="Logo"></div>

            

            <!-- Usuario -->
            <div class="form-group">
                <input type="text" name="usuario" id="nom_usu" placeholder=" "
                       autocomplete="off" maxlength="15" minlength="4"
                       class="cuadro oso" required />
                <label for="nom_usu">Usuario</label>
            </div>

            <!-- Contraseña -->
            <div class="form-group">
                <input type="password" name="contrasena" id="contrasena" placeholder=" "
                       autocomplete="off" maxlength="12" minlength="3"
                       class="cuadro" required />
                <label for="contrasena">Contraseña</label>
            </div>

            <!-- Token CSRF oculto -->
            <input type="hidden" name="csrf_token" id="csrf_token" 
                   value="<?php echo $_SESSION['csrf_token']; ?>">

            <button type="submit" name="iniciar" class="btn_inicio inicio">
                Iniciar sesión
            </button>
            <br><br>
            <a class="recup" href="recuperar_contraseña.php">
                ¿Se le olvidó su contraseña?
            </a>
        </form>
    </div>

   <div class="header-animado" style="margin-bottom: 50px;">
        <!-- ROKA SPORTS - GRANDE PRIMERO -->
        <h1 class="titulo-roka" 
            data-aos="fade-up" 
            data-aos-duration="1200" 
            data-aos-delay="100">
            Roka Sports
        </h1>
        
        <!-- OTHERLEVEL - MISMO TAMAÑO, ABAJO -->
        <h2 class="titulo-secundario" 
            data-aos="fade-up" 
            data-aos-duration="1200" 
            data-aos-delay="300">
            ¡OtherLevel!
        </h2>
        
        <!-- NO INVENTES - MISMO TAMAÑO, MÁS ABAJO -->
        <p class="titulo-secundario" 
           data-aos="fade-up" 
           data-aos-duration="1200" 
           data-aos-delay="500">
           ¡No Inventes!
        </p>
    


</div>



    

    <div class="mensajes"></div>
</div>

<script src="js/jquery-3.4.1.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
$(document).ready(function() {
    const mensajes = document.querySelector('.mensajes');

    $('.inicio').on('click', function(e) {
        e.preventDefault();
        
        const usuario = $('#nom_usu').val().trim();
        const contrasena = $('#contrasena').val().trim();

        if ( !usuario || !contrasena) {
            mostrarMensaje("Complete todos los campos", "error");
            return;
        }

        const datos = $('#formu').serialize();
        $.ajax({
            url: 'registro/session.php',
            type: 'POST',
            data: datos,
            beforeSend: function() {
                $('.inicio').prop('disabled', true).text('Ingresando...');
            },
            success: function(resp) {
                if (resp == 1) {
                    const rolTexto = $('#rol option:selected').text();
                    mostrarMensaje(`Acceso correcto como ${rolTexto}, redirigiendo...`, "exito");
                    setTimeout(() => window.location = 'menu/menu.php', 1500);
                } else if (resp == 2) {
                    mostrarMensaje("Usuario o contraseña incorrectos", "error");
                } else if (resp == 3) {
                    mostrarMensaje("Error de seguridad", "error");
                } else {
                    mostrarMensaje("Error inesperado en el servidor", "error");
                }
            },
            error: function() {
                mostrarMensaje("Error de conexión con el servidor", "error");
            },
            complete: function() {
                $('.inicio').prop('disabled', false).text('Iniciar sesión');
            }
        });
    });

    function mostrarMensaje(texto, tipo) {
        mensajes.textContent = texto;
        mensajes.classList.remove('exito', 'error', 'activa');
        mensajes.classList.add(tipo, 'activa');
        setTimeout(() => mensajes.classList.remove('activa'), 4000);
    }
});

// Limpiar mensajes al cargar/recargar
window.addEventListener('pageshow', function() {
    const mensajes = document.querySelector('.mensajes');
    if (mensajes) {
        mensajes.textContent = ''; 
        mensajes.classList.remove('error', 'exito', 'activa');
    }
});

AOS.init({ duration: 1000, easing: 'ease-in-out', once: false });
</script>
</body>
</html>
