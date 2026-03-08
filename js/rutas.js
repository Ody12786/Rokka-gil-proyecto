let modulos = document.getElementById('menu');
let icono = document.getElementById('mUsu');
let delegacion = document.getElementById('delegation').value;

if (delegacion != 1) {
    icono.style.display = "none";
}

modulos.addEventListener('click', e => {
  
    let target = e.target.closest('.menu-item');
    if (!target) return;

    if (target.classList.contains('usu')) {
        window.location = "modulo_usuario.php";
    } else if (target.classList.contains('pro')) {
        window.location = "modulo_proveedor.php";
    } else if (target.classList.contains('mat')) {
        window.location = "materia.php";
    } else if (target.classList.contains('produ')) {
        window.location = "productos.php";
    } else if (target.classList.contains('cli')) {
        window.location = "clientes.php";
    } else if (target.classList.contains('ven')) {
        window.location = "ventas.php";
    } else if (target.classList.contains('com')) {
        window.location = "compras.php";
    } else if (target.classList.contains('fin')) {
        window.location = "finanzas.php";
    }
});



