$(document).ready(function() {
    const mensajes = document.querySelector('.mensajes');

    $('.inicio').on('click', function(e) {
        e.preventDefault();

        const usuario = $('#nom_usu').val().trim();
        const contrasena = $('#contrasena').val().trim();

        if (!usuario || !contrasena) {
            mensajes.textContent = "Ingrese datos para continuar";
            mensajes.classList.remove('exito', 'error', 'activa');
            mensajes.classList.add('error', 'activa');
            setTimeout(() => mensajes.classList.remove('activa'), 3000);
            return;
        }

        const datos = $('#formu').serialize();
        $.ajax({
            url: 'registro/session.php',
            type: 'POST',
            data: datos,
            success: function(resp) {
                mensajes.classList.remove('exito', 'error', 'activa');
                if (resp == 1) {
                    mensajes.textContent = "Acceso correcto, redirigiendo...";
                    mensajes.classList.add('exito', 'activa');
                    setTimeout(() => window.location = 'menu/menu.php', 1000);
                } else if (resp == 2) {
                    mensajes.textContent = "Usuario o contraseña incorrectos";
                    mensajes.classList.add('error', 'activa');
                    setTimeout(() => mensajes.classList.remove('activa'), 3000);
                } else {
                    mensajes.textContent = 'Ocurrió un error inesperado';
                    mensajes.classList.add('error', 'activa');
                    setTimeout(() => mensajes.classList.remove('activa'), 3000);
                }
            },
            error: function() {
                mensajes.classList.remove('exito', 'error', 'activa');
                mensajes.textContent = 'Error en la comunicación con el servidor';
                mensajes.classList.add('error', 'activa');
                setTimeout(() => mensajes.classList.remove('activa'), 3000);
            }
        });
    });
});
