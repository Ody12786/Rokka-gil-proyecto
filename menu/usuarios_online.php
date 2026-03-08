<?php
session_start();
date_default_timezone_set('America/Caracas');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

include("../database/connect_db.php");

if (isset($_SESSION['asistente_id'])) {
    $stmt = $conex->prepare("SELECT estado FROM usuario_asistente WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || $result->fetch_assoc()['estado'] !== 'activo') {
        // SESIÓN MUERTA EN BD - FORZAR LOGOUT
        session_destroy();
        session_unset();
        $stmt->close();
        header("Location: ../index.php?session_killed=1");
        exit();
    }
    $stmt->close();
}

// Actualizar actividad del usuario actual
if (isset($_SESSION['asistente_id'])) {
    $stmt = $conex->prepare("UPDATE usuario_asistente SET ultima_actividad = NOW() WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['asistente_id']);
    $stmt->execute();
    $stmt->close();
}

// Limpiar sesiones inactivas (>2 horas)
$stmt = $conex->prepare("
    DELETE FROM usuario_asistente 
    WHERE TIMESTAMPDIFF(HOUR, ultima_actividad, NOW()) > 2
");
$stmt->execute();
$stmt->close();

$esAdmin = ($_SESSION['usuario_tipo'] == 1);
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';

// Solo admin puede ver
if (!$esAdmin) {
    header("Location: menu.php");
    exit();
}

// Obtener usuarios online CON asistente_id
$stmt = $conex->prepare("
    SELECT 
        u.id_rec as usuario_id,
        u.nombre, 
        u.correo,
        ua.ip_address, 
        ua.dispositivo,
        ua.user_agent,
        DATE_FORMAT(ua.fecha_login, '%d/%m %H:%i') as fecha_login,
        CASE 
            WHEN TIMESTAMPDIFF(SECOND, ua.ultima_actividad, NOW()) < 60 THEN '🔴 AHORA'
            ELSE TIMESTAMPDIFF(MINUTE, ua.ultima_actividad, NOW())
        END as minutos_inactivo,
        CASE 
            WHEN TIMESTAMPDIFF(MINUTE, ua.ultima_actividad, NOW()) < 5 THEN '🟢 ACTIVO'
            WHEN TIMESTAMPDIFF(MINUTE, ua.ultima_actividad, NOW()) < 30 THEN '🟡 RECIENTE'
            ELSE '🔴 INACTIVO'
        END as estado_visual,
        ua.sesion_id,
        ua.id as asistente_id
    FROM usuario_asistente ua
    JOIN usuario u ON ua.usuario_id = u.id_rec
    WHERE ua.estado = 'activo'
    ORDER BY ua.ultima_actividad DESC
");

$stmt->execute();
$result_usuarios = $stmt->get_result();
$usuarios_online = $result_usuarios->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===== PHP PARA CERRAR SESIONES (AJAX Handler) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cerrar_sesion') {
    header('Content-Type: application/json; charset=utf-8');
    
    $ids_sesiones = json_decode($_POST['ids_sesiones'] ?? '[]', true);
    
    if (empty($ids_sesiones)) {
        echo json_encode(['status' => 'error', 'message' => 'No se seleccionaron sesiones']);
        exit;
    }
    
    $stmt = $conex->prepare("UPDATE usuario_asistente SET estado = 'inactivo' WHERE id = ?");
    $exitos = 0;
    
    foreach ($ids_sesiones as $id_sesion) {
        if (is_numeric($id_sesion)) {
            $stmt->bind_param("i", $id_sesion);
            if ($stmt->execute()) $exitos++;
        }
    }
    
    $stmt->close();
    $conex->close();
    
    echo json_encode([
        'status' => 'success', 
        'message' => "✅ Cerradas $exitos sesión(es) exitosamente",
        'exitos' => $exitos
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios Online - Roʞka System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
         body {
            --fondo-1: 
                url('../img/DANIEL_FONDO4.JPG'),
                linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #1a252f 100%);
            --fondo-2: 
                url('../img/DANIEL_FONDO5.JPG'),
                linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #1a252f 100%);
            --fondo-3: 
                url('../img/DANIEL_FONDO6.JPG'),
                linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #1a252f 100%);
            /* Fondo actual */
            background: var(--fondo-1);
            background-size: cover, 100% 100%, 100% 100%, 50px 50px !important;
            background-position: center, 20% 80%, 80% 20%, 0 0 !important;
            background-repeat: no-repeat, no-repeat, no-repeat, repeat !important;
            background-attachment: fixed !important;
            min-height: 100vh;
            color: #ecf0f1 !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 1s cubic-bezier(0.4, 0, 0.2, 1) !important; /* TRANSICIÓN SUAVE */
        }

        /* ===== BOTONES FLECHAS FLOTANTES ===== */
        .fondo-prev, .fondo-next {
            position: fixed !important;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,0.15) !important;
            backdrop-filter: blur(20px);
            color: #fff;
            font-size: 20px;
            z-index: 9999;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .fondo-prev {
            left: 30px;
            animation: slideInLeft 0.5s ease-out;
        }

        .fondo-next {
            right: 30px;
            animation: slideInRight 0.5s ease-out;
        }

        .fondo-prev:hover, .fondo-next:hover {
            background: rgba(255,255,255,0.25) !important;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }

        @keyframes slideInLeft {
            from { left: -80px; opacity: 0; }
            to { left: 30px; opacity: 1; }
        }

        @keyframes slideInRight {
            from { right: -80px; opacity: 0; }
            to { right: 30px; opacity: 1; }
        }

        .status-active {
            background: linear-gradient(135deg, rgba(46,204,113,0.3), rgba(39,174,96,0.4)) !important;
            border: 2px solid #27ae60 !important;
            color: #000 !important;
        }
        .status-recent {
            background: linear-gradient(135deg, rgba(241,196,15,0.3), rgba(243,156,18,0.4)) !important;
            border: 2px solid #f39c12 !important;
            color: #000 !important;
        }
        .status-inactive {
            background: linear-gradient(135deg, rgba(231,76,60,0.3), rgba(192,57,43,0.4)) !important;
            border: 2px solid #e74c3c !important;
            color: #000 !important;
        }
        .status-active h5, .status-recent h5, .status-inactive h5,
        .status-active .card-text, .status-recent .card-text, .status-inactive .card-text {
            color: #000 !important;
            font-weight: 800 !important;
        }

        .navbar {
            background: linear-gradient(135deg, #8B0000 0%, #2c3e50 40%, #34495e 100%) !important;
            border-bottom: 3px solid #c0392b !important;
            color: #ecf0f1 !important;
        }
        .navbar-brand, .navbar-text, .nav-link { color: #ecf0f1 !important; }
        .navbar-brand .badge { background: #e74c3c !important; color: #fff !important; }

        .card.shadow-lg {
            background: rgba(44,62,80,0.95) !important;
            border: 1px solid rgba(52,152,219,0.3) !important;
            color: #ecf0f1 !important;
        }
        .table-dark th {
            background: linear-gradient(135deg, #34495e, #2c3e50) !important;
            color: #ecf0f1 !important;
        }
        .table td {
            color: #FFFFFF !important;
            background: rgba(0,0,0,0.6) !important;
            border-color: rgba(236,240,241,0.1) !important;
        }
        .table-hover tbody tr:hover { background: rgba(52,152,219,0.2) !important; }
        .text-muted { color: #bdc3c7 !important; }

        .refresh-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71) !important;
            border: none !important;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(46,204,113,0.7); }
            70% { box-shadow: 0 0 0 10px rgba(46,204,113,0); }
            100% { box-shadow: 0 0 0 0 rgba(46,204,113,0); }
        }

        .user-card { 
            transition: all 0.3s ease; 
            border-left: 4px solid transparent;
        }
        .user-card:hover { 
            transform: translateY(-3px); 
            border-left-color: #3498db !important;
        }
        .sesion-seleccionada {
            background: rgba(40, 167, 69, 0.2) !important;
        }
        .btn:disabled {
            opacity: 0.6;
        }
    </style>
</head>

<body class="bg-dark text-white">
<button class="fondo-prev" id="fondoPrev" title="Fondo anterior">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button class="fondo-next" id="fondoNext" title="Siguiente fondo">
        <i class="fas fa-chevron-right"></i>
    </button>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-lg">
        <div class="container">
            <a class="navbar-brand fw-bold" href="menu.php">
                <i class="fas fa-users me-2"></i>Usuarios Online 
                <span class="badge bg-danger"><?php echo count($usuarios_online); ?></span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 text-white">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($usuarioNombre); ?> 
                    <span class="badge bg-danger">ADMIN</span>
                </span>
                <a class="nav-link btn btn-outline-light btn-sm" href="menu.php">
                    <i class="fas fa-arrow-left me-1"></i>Menú Principal
                </a>
                <a class="nav-link btn btn-danger btn-sm ms-2" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center status-active shadow">
                            <div class="card-body">
                                <i class="fas fa-circle fa-2x text-success mb-2"></i>
                                <h5 class="card-title"><?php echo count(array_filter($usuarios_online, fn($u) => $u['minutos_inactivo'] === '🔴 AHORA' || (is_numeric($u['minutos_inactivo']) && $u['minutos_inactivo'] < 5))); ?></h5>
                                <p class="card-text">Activos ahora</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center status-recent shadow">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h5 class="card-title"><?php echo count(array_filter($usuarios_online, fn($u) => is_numeric($u['minutos_inactivo']) && $u['minutos_inactivo'] >= 5 && $u['minutos_inactivo'] < 30)); ?></h5>
                                <p class="card-text">Recientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center status-inactive shadow">
                            <div class="card-body">
                                <i class="fas fa-user-slash fa-2x text-danger mb-2"></i>
                                <h5 class="card-title"><?php echo count(array_filter($usuarios_online, fn($u) => is_numeric($u['minutos_inactivo']) && $u['minutos_inactivo'] >= 30)); ?></h5>
                                <p class="card-text">Inactivos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-primary text-white shadow">
                            <div class="card-body">
                                <i class="fas fa-laptop fa-2x mb-2"></i>
                                <h5 class="card-title"><?php echo count(array_filter($usuarios_online, fn($u) => $u['dispositivo'] == 'PC')); ?></h5>
                                <p class="card-text">PCs conectadas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!--  BOTONES CERRAR SESIONES -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow border-0 bg-dark">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-center justify-content-between">
                                    <div class="d-flex gap-2">
                                        <span class="h4 mb-0 text-white">
                                            <i class="fas fa-users me-2"></i>
                                            <span id="countTotal"><?php echo count($usuarios_online); ?></span> Sesiones Activas
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button id="btnCerrarSeleccionadas" class="btn btn-danger" disabled>
                                            <i class="fas fa-power-off me-2"></i>
                                            <span class="badge bg-white text-dark" id="countSeleccionadas">0</span> Seleccionadas
                                        </button>
                                        <button id="btnCerrarTodas" class="btn btn-outline-danger btn-lg">
                                            <i class="fas fa-ban me-2"></i>Cerrar TODAS
                                        </button>
                                        <button class="btn btn-success refresh-btn btn-lg" onclick="location.reload()">
                                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla CON BOTONES -->
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Gestión de Sesiones Activas
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tablaSesiones">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50"><input type="checkbox" id="selectAllSesiones"></th>
                                        <th><i class="fas fa-user me-1"></i>Usuario</th>
                                        <th><i class="fas fa-mobile-alt me-1"></i>Dispositivo</th>
                                        <th><i class="fas fa-network-wired me-1"></i>IP</th>
                                        <th><i class="fas fa-clock me-1"></i>Login</th>
                                        <th>Estado</th>
                                        <th width="100"><i class="fas fa-times me-1"></i>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios_online)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                                No hay usuarios conectados
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($usuarios_online as $usuario): ?>
                                        <tr class="user-card <?php echo strtolower(str_replace(' ', '-', $usuario['estado_visual'])); ?>" data-asistente-id="<?php echo $usuario['asistente_id']; ?>">
                                            <td>
                                                <input type="checkbox" class="sesion-checkbox form-check-input" value="<?php echo $usuario['asistente_id']; ?>" 
                                                       data-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($usuario['correo']); ?></small>
                                            </td>
                                            <td>
                                                <i class="fas <?php echo $usuario['dispositivo'] == 'PC' ? 'fa-desktop' : 'fa-mobile-alt'; ?> me-1 text-info"></i>
                                                <?php echo htmlspecialchars($usuario['dispositivo']); ?>
                                            </td>
                                            <td><code class="text-warning"><?php echo htmlspecialchars($usuario['ip_address']); ?></code></td>
                                            <td><?php echo $usuario['fecha_login']; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $usuario['estado_visual'] === '🟢 ACTIVO' ? 'bg-success' : 
                                                         ($usuario['estado_visual'] === '🟡 RECIENTE' ? 'bg-warning' : 'bg-danger'); 
                                                ?>">
                                                    <?php echo $usuario['estado_visual']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger cerrarIndividual" 
                                                        data-id="<?php echo $usuario['asistente_id']; ?>"
                                                        title="Cerrar esta sesión de <?php echo htmlspecialchars($usuario['nombre']); ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let autoRefreshTimer = null;
        let fondoActual = 1;
        const totalFondos = 3;

          // ===== SISTEMA CAMBIO FONDOS =====
        $('#fondoNext').click(function() {
            fondoActual = (fondoActual % totalFondos) + 1;
            cambiarFondo(fondoActual);
        });

        $('#fondoPrev').click(function() {
            fondoActual = fondoActual === 1 ? totalFondos : fondoActual - 1;
            cambiarFondo(fondoActual);
        });

        function cambiarFondo(numero) {
            $('body').removeClass('fondo-1 fondo-2 fondo-3')
                    .addClass('fondo-' + numero)
                    .css('background', `var(--fondo-${numero})`);
            
            // Efecto de rotación suave en flechas
            $('.fondo-prev, .fondo-next').addClass('animate__animated animate__pulse');
            setTimeout(() => $('.fondo-prev, .fondo-next').removeClass('animate__animated animate__pulse'), 500);
        }

        // Auto-cambio cada 30 segundos (opcional)
        setInterval(() => {
            $('#fondoNext').click();
        }, 30000);
        
        //  Select All
        $('#selectAllSesiones').click(function() {
            $('.sesion-checkbox').prop('checked', this.checked);
            toggleBotonCerrar();
        });
        
        //  Individual checkboxes
        $(document).on('change', '.sesion-checkbox', function() {
            toggleBotonCerrar();
            $(this).closest('tr').toggleClass('sesion-seleccionada', $(this).is(':checked'));
        });
        
        function toggleBotonCerrar() {
            const seleccionadas = $('.sesion-checkbox:checked').length;
            $('#btnCerrarSeleccionadas').prop('disabled', seleccionadas === 0);
            $('#countSeleccionadas').text(seleccionadas);
        }
        
        //  CERRAR INDIVIDUAL - TOTALMENTE CORREGIDO
        $(document).on('click', '.cerrarIndividual', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const id = $btn.data('id');
            const nombre = $btn.closest('tr').find('.sesion-checkbox').data('nombre');
            const $fila = $btn.closest('tr');
            
            Swal.fire({
                title: '¿Cerrar sesión de <strong>' + nombre + '</strong>?',
                html: 'El usuario será desconectado inmediatamente',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, desconectar',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Spinner
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                    
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            action: 'cerrar_sesion',
                            ids_sesiones: JSON.stringify([id])
                        },
                        dataType: 'json',
                        timeout: 5000,
                        success: function(resp) {
                            if (resp.status === 'success') {
                                // Fila desaparece SUAVEMENTE
                                $fila.fadeOut(500, function() {
                                    $(this).remove();
                                    toggleBotonCerrar();
                                    Swal.fire('✅', resp.message, 'success');
                                });
                            } else {
                                Swal.fire('❌', resp.message || 'Error desconocido', 'error');
                                $btn.prop('disabled', false).html('<i class="fas fa-power-off"></i>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', xhr.responseText);
                            // ✅ AUN ASÍ LA SESIÓN SE CERRÓ (por eso funciona al refrescar)
                            Swal.fire('⚠️', 'Sesión cerrada pero error de respuesta. Refrescando...', 'warning');
                            $fila.fadeOut(500, function() {
                                $(this).remove();
                                setTimeout(() => location.reload(), 1000);
                            });
                        }
                    });
                } else {
                    $btn.prop('disabled', false).html('<i class="fas fa-power-off"></i>');
                }
            });
        });
        
        // Cerrar seleccionadas
        $('#btnCerrarSeleccionadas').click(function() {
            const ids = $('.sesion-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (ids.length === 0) {
                Swal.fire('⚠️', 'Selecciona al menos una sesión', 'warning');
                return;
            }
            
            Swal.fire({
                title: '¿Cerrar <strong>' + ids.length + '</strong> sesión(es)?',
                html: 'Los usuarios seleccionados serán desconectados',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Cerrando...');
                    
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            action: 'cerrar_sesion',
                            ids_sesiones: JSON.stringify(ids)
                        },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.status === 'success') {
                                $('.sesion-checkbox:checked').closest('tr').fadeOut(500, function() {
                                    $(this).remove();
                                });
                                toggleBotonCerrar();
                                Swal.fire('✅', resp.message, 'success');
                            }
                        },
                        error: function() {
                            $('.sesion-checkbox:checked').closest('tr').fadeOut(500, function() {
                                $(this).remove();
                            });
                            setTimeout(() => location.reload(), 1500);
                        }
                    }).always(function() {
                        $('#btnCerrarSeleccionadas').prop('disabled', false).html('<i class="fas fa-power-off me-2"></i><span class="badge bg-white text-dark" id="countSeleccionadas">0</span> Seleccionadas');
                    });
                }
            });
        });
        
        // Cerrar TODAS
        $('#btnCerrarTodas').click(function() {
            Swal.fire({
                title: '¿Cerrar <strong>TODAS</strong> las sesiones?',
                html: 'Todos los usuarios del sistema serán desconectados<br><small class="text-warning">¡Acción irreversible!</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, desconectar a TODOS',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    const ids = <?php echo json_encode(array_column($usuarios_online, 'asistente_id')); ?>;
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            action: 'cerrar_sesion',
                            ids_sesiones: JSON.stringify(ids)
                        },
                        success: function() {
                            Swal.fire('✅', 'Todas las sesiones cerradas', 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            location.reload();
                        }
                    });
                }
            });
        });
        
        //  Auto-refresh inteligente
        function iniciarAutoRefresh() {
            clearTimeout(autoRefreshTimer);
            if ($('.sesion-checkbox:checked').length === 0) {
                autoRefreshTimer = setTimeout(() => location.reload(), 30000);
            } else {
                autoRefreshTimer = setTimeout(iniciarAutoRefresh, 5000);
            }
        }
        iniciarAutoRefresh();
    });
    </script>
</body>
</html>
