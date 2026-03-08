<?php
session_start();
date_default_timezone_set('America/Caracas');
// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || !isset($_SESSION['usuario_nombre'])) {
    session_destroy();
    header("Location: ../index.php?error=sesion");
    exit();
}

if (isset($_SESSION['asistente_id'])) {
    include("../database/connect_db.php");
    $stmt = $conex->prepare("SELECT ua.estado FROM usuario_asistente ua WHERE ua.id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || $result->fetch_assoc()['estado'] !== 'activo') {

        $_SESSION = array();                    //  Limpiar datos
        if (ini_get("session.use_cookies")) {   //  Borrar cookie PHPSESSID
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();                      //  Destruir servidor
        header("Location: ../index.php?session_killed=1");
        exit();
    }
    $stmt->close();
}


// Timeout de sesión (60 minutos de inactividad)
$timeout = 3600;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// ===== CONEXIÓN DB + ASISTENTE + CONTADOR =====
include("../database/connect_db.php");

$usuarios_online_count = 0;
$esAdmin = false;
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuarioTipo = 0;
if (isset($_SESSION['usuario_tipo'])) {
    $usuarioTipo = $_SESSION['usuario_tipo'];
}

// Actualizar asistente actual
if (isset($_SESSION['asistente_id']) && isset($conex) && $conex && !$conex->connect_error) {
    $stmt = $conex->prepare("UPDATE usuario_asistente SET ultima_actividad = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['asistente_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Contador usuarios online (solo estado='activo')
if (isset($conex) && $conex && !$conex->connect_error) {
    $stmt_count = $conex->prepare("
        SELECT COUNT(*) as total 
        FROM usuario_asistente 
        WHERE estado = 'activo'
    ");
    if ($stmt_count) {
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $row_count = $result_count->fetch_assoc();
        $usuarios_online_count = $row_count['total'];
        $stmt_count->close();
    }

    // Verificar admin
    $stmt_admin = $conex->prepare("SELECT tipo FROM usuario WHERE id_rec = ?");
    if ($stmt_admin) {
        $stmt_admin->bind_param("i", $_SESSION['usuario_id']);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();
        if ($row_admin = $result_admin->fetch_assoc()) {
            $esAdmin = ($row_admin['tipo'] == 1 || $_SESSION['usuario_rol'] == 'admin');
        }
        $stmt_admin->close();
    }
}

$usuarioId = $_SESSION['usuario_id'];

// ===== ÚLTIMA CONEXIÓN CON FIX HORA =====
$ultimaConexion = "No registrado";
if (isset($conex) && $conex && !$conex->connect_error) {
    $stmt_user = $conex->prepare("SELECT ultima_conexion FROM usuario WHERE id_rec = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $usuarioId);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($row_user = $result_user->fetch_assoc()) {
            $ultimaConexion_raw = $row_user['ultima_conexion'];
            try {
                // FIX HORA: UTC → Caracas (-4 horas)
                $datetime = new DateTime($ultimaConexion_raw, new DateTimeZone('UTC'));
                $datetime->setTimezone(new DateTimeZone('America/Caracas'));
                $ultimaConexion = $datetime->format('d/m/Y H:i');
            } catch (Exception $e) {
                $ultimaConexion = "Formato inválido";
            }
        }
        $stmt_user->close();
    }
}

$_SESSION['es_admin'] = $esAdmin;

// Datos usuario para navbar
$usuarioId = $_SESSION['usuario_id'];
$ultimaConexion = "No registrado";
if (isset($conex) && $conex && !$conex->connect_error) {
    $stmt_user = $conex->prepare("SELECT ultima_conexion FROM usuario WHERE id_rec = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $usuarioId);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($row_user = $result_user->fetch_assoc()) {
            $ultimaConexion = $row_user['ultima_conexion'];
            try {
                $datetime = new DateTime($ultimaConexion);
                $ultimaConexion = $datetime->format('d/m/Y H:i');
            } catch (Exception $e) {
                $ultimaConexion = "Formato inválido";
            }
        }
        $stmt_user->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Menú - Roʞka System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css" />
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../Font_awesome/css/all.min.css" />


</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg bannerM">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#li_menu"
                    aria-controls="li_menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="../img/IMG_4124login.png" alt="Logo Roʞka" width="70" height="70"
                        class="me-2 rounded-circle bg-primary p-1 shadow-sm">
                    <span class="nav-link mb-0 fw-bold">Roʞka System</span>
                </a>

                <div class="collapse navbar-collapse" id="li_menu">
                    <!-- Saludo con rol -->
                    <span class="navbar-text ms-lg-3 me-auto">
                        Hola, <strong><?php echo htmlspecialchars($usuarioNombre); ?></strong>
                        <span class="badge bg-<?php echo $esAdmin ? 'danger' : 'secondary'; ?> ms-2">
                            <?php echo strtoupper($_SESSION['usuario_rol'] ?? ($usuarioTipo == 1 ? 'ADMIN' : 'ESTÁNDAR')); ?>
                        </span>
                    </span>

                    <!-- Navbar items alineados a la derecha -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a href="ordenes.php" class="btn btn-outline-light btn-sm me-2">
                                📋 Ir a órdenes
                            </a>
                        </li>
                        <li class="nav-item d-flex align-items-center">
                            <span class="navbar-text me-3 small">
                                <i class="fas fa-clock me-1"></i>
                                Última conexión: <strong><?php echo htmlentities($ultimaConexion); ?></strong>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger fw-bold" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="background-wrapper" style="background-image: url('../img/interfazmenu.jpg'); 
     background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh;">

        <div class="main-content">
            <input type="hidden" id="delegation" value="<?php echo $usuarioTipo; ?>">
            <input type="hidden" id="es_admin" value="<?php echo $esAdmin ? '1' : '0'; ?>">

            <?php if (!empty($usuarioNombre)): ?>
                <div class="bienvenida" id="welcome-msg" data-aos="fade-down"
                    style="margin:20px auto; text-align:center; font-size:1.3em; font-weight: 600; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                    👋 ¡Bienvenido, <strong><?php echo $usuarioNombre ?></strong>!
                    <?php if ($esAdmin): ?>
                        <span class="badge bg-danger ms-2">ADMIN</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="menu-modern" id="menu">
                <div class="menu-items">
                    <!-- Solo Admin -->
                    <div class="menu-item usu <?php echo !$esAdmin ? 'd-none' : ''; ?>" id="mUsu" data-aos="zoom-in">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <span>Usuarios Online (<?php echo $usuarios_online_count; ?>)</span>
                    </div>

                    <div class="menu-item usu2 <?php echo !$esAdmin ? 'd-none' : ''; ?>" id="mUsu2" data-aos="zoom-in" data-aos-delay="360">
                        <div class="icon"><i class="fas fa-user-edit"></i></div>
                        <span>Usuarios</span>
                    </div>


                    <div class="menu-item pro" id="mPro" data-aos="zoom-in" data-aos-delay="50">
                        <div class="icon"><i class="fas fa-truck"></i></div>
                        <span>Proveedores</span>
                    </div>

                    <div class="menu-item com" id="mCom" data-aos="zoom-in" data-aos-delay="100">
                        <div class="icon"><i class="fas fa-store"></i></div>
                        <span>Comprar Telas</span>
                    </div>

                    <div class="menu-item mat" id="mMat" data-aos="zoom-in" data-aos-delay="150">
                        <div class="icon"><i class="fas fa-cubes"></i></div>
                        <span>Inventario</span>
                    </div>

                    <div class="menu-item produ" id="mProd" data-aos="zoom-in" data-aos-delay="200">
                        <div class="icon"><i class="fas fa-shirt"></i></div>
                        <span>Productos</span>
                    </div>

                    <div class="menu-item cli" id="mCli" data-aos="zoom-in" data-aos-delay="250">
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                        <span>Clientes</span>
                    </div>

                    <div class="menu-item ven" id="mVentas" data-aos="zoom-in" data-aos-delay="300">
                        <div class="icon"><i class="fas fa-chart-bar"></i></div>
                        <span>Ventas</span>
                    </div>

                    <div class="menu-item chat <?php echo !$esAdmin ? 'd-none' : ''; ?>" id="mChat" data-aos="zoom-in" data-aos-delay="320">
                        <div class="icon"><i class="fas fa-robot"></i></div>
                        <span>Ainhoa AI</span>
                    </div>

                    <!-- Solo Admin -->
                    <div class="menu-item abo <?php echo !$esAdmin ? 'd-none' : ''; ?>" id="mAbo" data-aos="zoom-in" data-aos-delay="350">
                        <div class="icon"><i class="fas fa-receipt"></i></div>
                        <span>Abonos</span>
                    </div>
                    <div class="menu-item cre <?php echo !$esAdmin ? 'd-none' : ''; ?>" id="mCre" data-aos="zoom-in" data-aos-delay="400">
                        <div class="icon"><i class="fas fa-file-invoice"></i></div>
                        <span>Créditos</span>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade timeout-modal" id="timeoutModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-clock me-2"></i>Sesión Expirada
                        </h5>
                    </div>
                    <div class="modal-body">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p class="my-3">Tu sesión ha expirado por <strong>inactividad</strong>.</p>
                        <p>Por seguridad, inicia sesión nuevamente para continuar.</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="../index.php" class="btn btn-reintentar">
                            <i class="fas fa-sign-in-alt me-2"></i>Reintentar Login
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <!-- Scripts -->
        <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
        <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

        <script>
            // TODO con DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function() {

                // Elementos del menú
                let modulos = document.getElementById('menu');
                let icono = document.getElementById('mUsu');
                let abonosMenu = document.getElementById('mAbo');
                let creditosMenu = document.getElementById('mCre');
                let chatbotMenu = document.getElementById('mChat');
                let delegacionInput = document.getElementById('delegation');
                let esAdminInput = document.getElementById('es_admin');

                let delegacion = delegacionInput ? delegacionInput.value : '0';
                let esAdmin = esAdminInput && esAdminInput.value == '1';

                // Ocultar por rol (con chequeo null)
                if (!esAdmin) {
                    if (icono) icono.style.display = "none";
                    if (abonosMenu) abonosMenu.style.display = "none";
                }

                // Clicks del menú
                if (modulos) {
                    modulos.addEventListener('click', e => {
                        let target = e.target.closest('.menu-item');
                        if (!target) return;

                        if (target.classList.contains('usu')) window.location = "usuarios_online.php";
                        else if (target.classList.contains('usu2')) window.location = "modulo_usuario.php";
                        else if (target.classList.contains('pro')) window.location = "modulo_proveedor.php";
                        else if (target.classList.contains('mat')) window.location = "compras_material.php";
                        else if (target.classList.contains('produ')) window.location = "productos.php";
                        else if (target.classList.contains('cli')) window.location = "clientes.php";
                        else if (target.classList.contains('ven')) window.location = "ventas.php";
                        else if (target.classList.contains('com')) window.location = "compras.php";
                        else if (target.classList.contains('abo')) window.location = "pagar_abonos.php";
                        else if (target.classList.contains('cre')) window.location = "finanzas.php";
                        else if (target.classList.contains('chat') && esAdmin) {
                            window.open('chatbot.php', 'ainhoaChat',
                                'width=500,height=700,scrollbars=yes,resizable=yes,top=100,left=100');
                        } else if (target.classList.contains('chat')) {
                            alert('🚫 Ainhoa AI solo para ADMINISTRADORES');
                        }
                    });
                }

                // Mensaje bienvenida
                var msg = document.getElementById('welcome-msg');
                if (msg) {
                    setTimeout(function() {
                        msg.style.transition = 'opacity 0.5s';
                        msg.style.opacity = '0';
                        setTimeout(function() {
                            msg.style.display = 'none';
                        }, 500);
                    }, 3000);
                }
            });

            //  AOS (independiente del DOM)
            AOS.init({
                duration: 900,
                easing: 'ease-in-out'
            });

            //  Modal timeout (independiente)
            <?php if (isset($_GET['timeout'])): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    var timeoutModal = new bootstrap.Modal(document.getElementById('timeoutModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    timeoutModal.show();
                });
            <?php endif; ?>
        </script>

    </div>
    <?php include("inferior.html"); ?>
</body>

</html>