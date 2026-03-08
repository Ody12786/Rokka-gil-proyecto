document.addEventListener('DOMContentLoaded', function() {
    let modulos = document.getElementById('menu');
    let delegacion = parseInt(document.getElementById('delegation').value);
    let esAdmin = (delegacion === 1);

    // Control de visibilidad por rol
    ocultarPorRol();

    // Event listeners para navegación
    modulos.addEventListener('click', manejarClickMenu);

    // Inicializar animaciones
    inicializarAnimaciones();

    // Ocultar mensaje de bienvenida después de 3s
    ocultarBienvenida();
});


function ocultarPorRol() {
    let esAdmin = parseInt(document.getElementById('delegation').value) === 1;
    

    if (!esAdmin) {
        document.getElementById('mUsu').style.display = 'none';
        document.getElementById('mAbo').style.display = 'none';
    }
}


function manejarClickMenu(e) {
    let target = e.target.closest('.menu-item');
    if (!target) return;

    let ruta = obtenerRutaPorModulo(target.className);
    if (ruta) {
        window.location.href = ruta;
    }
}

// Mapa de rutas por clase CSS
function obtenerRutaPorModulo(className) {
    const rutas = {
        'usu': 'modulo_usuario.php',
        'pro': 'modulo_proveedor.php',
        'com': 'compras.php',
        'mat': 'compras_material.php',
        'produ': 'productos.php',
        'cli': 'clientes.php',
        'ven': 'ventas.php',
        'abo': 'pagar_abonos.php'
    };
    
    for (let clase in rutas) {
        if (className.includes(clase)) {
            return rutas[clase];
        }
    }
    return null;
}


function inicializarAnimaciones() {
    AOS.init({
        duration: 900,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });
}


function ocultarBienvenida() {
    let msg = document.getElementById('welcome-msg');
    if (msg) {
        setTimeout(function() {
            msg.style.transition = 'opacity 0.5s ease-out';
            msg.style.opacity = '0';
            setTimeout(function() {
                msg.style.display = 'none';
            }, 500);
        }, 4000); 
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.05)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
