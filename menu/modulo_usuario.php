<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");
include("../database/cifrado.php");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || !isset($_SESSION['usuario_nombre'])) {
  $_SESSION = array();
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
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
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: ../index.php?session_killed=1");
    exit();
  }
  $stmt->close();
}

$usuarioId = $_SESSION['usuario_id'];
$usuarioNombre = $_SESSION['usuario_nombre'];
$usuarioTipo = $_SESSION['usuario_tipo'];

$query = "SELECT id_rec, nombre, correo, telefono, carnet, tipo, estado FROM usuario";
$result = $conex->query($query);
$usuarios = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
  }
}

foreach ($usuarios as &$usuario) {
  $usuario['id_encriptado'] = encryptId($usuario['id_rec']);
  error_log("ID cifrado: " . $usuario['id_encriptado']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Usuarios</title>
  <link rel="stylesheet" href="../css/menu.css" />
  <link rel="stylesheet" href="../css/botones.css" />
  <link rel="stylesheet" href="../css/tablas.css" />
  <link rel="stylesheet" href="../css/modal_usuario.css" />
  <link rel="stylesheet" href="../DataTables/datatables.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" />
  <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../css/correciones.css" />


  <style>
    body {
      background:
        linear-gradient(rgba(26, 26, 26, 0.95), rgba(15, 15, 15, 0.98)),
        radial-gradient(circle at 20% 80%, rgba(209, 0, 27, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(197, 2, 2, 0.12) 0%, transparent 50%),
        linear-gradient(90deg, transparent 48%, rgba(209, 0, 27, 0.03) 50%, rgba(209, 0, 27, 0.03) 52%, transparent 54%);
      background-attachment: fixed;
      background-size: cover, auto, auto, 50px 50px;
      min-height: 100vh;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image:
        linear-gradient(45deg, transparent 49%, rgba(209, 0, 27, 0.04) 50%, rgba(209, 0, 27, 0.04) 51%, transparent 52%),
        radial-gradient(circle at 25% 25%, rgba(197, 2, 2, 0.08) 1px, transparent 1px),
        radial-gradient(circle at 75% 75%, rgba(197, 2, 2, 0.08) 1px, transparent 1px);
      background-size: 100px 100px, 50px 50px, 50px 50px;
      pointer-events: none;
      z-index: -1;
    }

    .dt-table-responsive {
      background: rgba(35, 35, 35, 0.95);
      backdrop-filter: blur(25px);
      border-radius: 15px;
      border: 1px solid rgba(209, 0, 27, 0.2);
      overflow: hidden;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
      margin: 30px auto;
      max-width: 95%;
    }

    .password-strength {
      height: 8px;
      background: rgba(60, 60, 60, 0.6);
      border-radius: 4px;
      overflow: hidden;
      margin-top: 8px;
    }

    .strength-bar {
      height: 100%;
      background: rgba(80, 80, 80, 0.8);
      border-radius: 4px;
      overflow: hidden;
    }

    .strength-fill {
      height: 100%;
      width: 0%;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 4px;
    }

    .strength-weak { background: linear-gradient(90deg, #dc3545, #ff6b6b); }
    .strength-medium { background: linear-gradient(90deg, #ffc107, #ffd93d); }
    .strength-strong { background: linear-gradient(90deg, #28a745, #51cf66); }

    .password-toggle:hover { color: #d1001b !important; }

    .table thead {
      background: linear-gradient(135deg, #d1001b 0%, #a10412 100%);
      color: #fff;
    }

    .table td {
      padding: 16px 15px;
      border-color: rgba(209, 0, 27, 0.1);
      color: #e0e0e0;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(209, 0, 27, 0.05);
    }

     .offcanvas {
            background: linear-gradient(145deg, #1a1a1a 0%, #231921 100%) !important;
            border-right: 1px solid rgba(209, 0, 27, 0.3) !important;
            box-shadow: 5px 0 30px rgba(209, 0, 27, 0.2) !important;
        }

        .offcanvas .nav-link {
            color: #e0e0e0 !important;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s ease;
        }

        .offcanvas .nav-link:hover,
        .offcanvas .nav-link.active {
            background: linear-gradient(135deg, #d1001b, #a10412) !important;
            color: white !important;
            transform: translateX(5px);
        }


    @media (max-width: 768px) {
      .botones-usuarios { flex-direction: column; padding: 20px; }
      .dt-table-responsive { margin: 20px 10px; font-size: 0.9rem; }
    }

    .form-control.is-valid {
      border-color: #28a745 !important;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.7-.7c.3-.3.8-.3 1.1 0L5.4 7.3c.4.4.9.4 1.2 0l2.2-2.2c.3-.3.3-.8 0-1.1l-.7-.7c-.3-.3-.8-.3-1.1 0L5 5.4c-.3.3-.8.3-1.1 0L2.3 3.7c-.3-.3-.8-.3-1.1 0l-.7.7c-.3.3-.3.8 0 1.1L2.3 6.73z'/%3e%3c/svg%3e") !important;
      box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, .25) !important;
    }

    .form-control.is-invalid {
      border-color: #dc3545 !important;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, .25) !important;
    }
  </style>
</head>

<body>
  <!-- Navbar superior -->
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
      <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1">
        <span class="nav-link">Roʞka System</span>
      </a>
      <a class="navbar-brand" href="http://localhost/Roka_Sports/menu/menu.php">Inicio</a>
    </div>
  </nav>

  <!-- Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menú</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body p-0">
      <a class="nav-link" href="../menu/modulo_proveedor.php"><i class="bi bi-truck me-2"></i>Proveedores</a>
      <a class="nav-link" href="../menu/compras.php"><i class="bi bi-shop me-2"></i>Compras de telas</a>
      <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Inventario</a>
      <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
      <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
      <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
      <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
      <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
      <hr class="my-2">
      <a class="nav-link text-danger" href="../database/cerrar.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-3 my-4 justify-content-center botones-usuarios">
    <button id="btnNuevoRegistro" class="btn btn-primary btn-custom btn-lg px-5">
      <i class="fas fa-users me-2"></i>Agregar Usuario
      <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
    </button>
  </div>

  <!-- Tabla DataTables -->
  <table id="usuariosTable" class="table table-striped dt-table-responsive nowrap" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Teléfono</th>
        <th>Carnet</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <!-- MODAL UNIFICADO: formulario dividido en 3 pestañas (Persona / Empleado / Usuario) -->
  <div class="modal fade" id="modalUnificado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl justify-content-center">
      <!-- Formulario principal: novalidate para manejar validaciones con JS/Bootstrap -->
      <form class="w-50" id="formUnificado" enctype="multipart/form-data" novalidate>
        <div class="modal-content">
          <div class="modal-header">
            <!-- Pestañas para navegar entre secciones del formulario -->
            <ul class="nav nav-tabs border-0 flex-fill mb-0" id="pestañasUnificado" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="persona-tab" data-bs-toggle="tab" data-bs-target="#persona-tab-pane" type="button" role="tab" aria-controls="persona-tab-pane" aria-selected="true">Persona</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="usuario-tab" data-bs-toggle="tab" data-bs-target="#usuario-tab-pane" type="button" role="tab" aria-controls="usuario-tab-pane" aria-selected="false">Usuario</button>
              </li>
            </ul>
            <h5 class="modal-title flex-fill text-center mb-0 d-none d-md-block" id="modalTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div class="tab-content">
              <!-- PESTAÑA PERSONA: datos básicos de la persona -->
              <div class="tab-pane fade show active" id="persona-tab-pane" role="tabpanel" aria-labelledby="persona-tab">
                <!-- Documento (cédula) -->
                <div class="mb-3">
                  <label for="ci" class="form-label">Cédula <span class="text-danger">*</span></label>
                  <input type="number" id="ci" name="ci" class="form-control" pattern="[0-9]{6,12}" title="6-12 dígitos numéricos" required maxlength="15" autocomplete="off">
                  <div class="form-text">Solo números (6-12 dígitos)</div>
                </div>

                <!-- Nombre -->
                <div class="mb-3">
                  <label for="nombre_p" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                  <input type="text" id="nombre_p" name="nombre_p" class="form-control" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" title="Solo letras y espacios (3-100 caracteres)" required minlength="3" maxlength="100" autocomplete="off">
                  <div class="form-text">Nombres completos (solo letras)</div>
                </div>
                <div class="mb-3">
                  <label for="nombre_s" class="form-label">Segundo Nombre</label>
                  <input type="text" id="nombre_s" name="nombre_s" class="form-control" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" title="Solo letras y espacios (3-100 caracteres)" required minlength="3" maxlength="100" autocomplete="off">
                </div>

                <!-- Apellido (opcional) -->
                <div class="mb-3">
                  <label for="apellido" class="form-label">Primer Apellido <span class="text-danger">*</span></label>
                  <input type="text" id="apellido" name="apellido" class="form-control" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" minlength="3" maxlength="100" autocomplete="off">
                </div>
                <div class="mb-3">
                  <label for="apellido_s" class="form-label">SegundoApellido</label>
                  <input type="text" id="apellido_s" name="apellido_s" class="form-control" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}" minlength="3" maxlength="100" autocomplete="off">
                </div>

                <!-- Sexo -->
                <div class="mb-3">
                  <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                  <select id="sexo" name="sexo" class="form-select" required>
                    <option value="">Seleccione sexo</option>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                  </select>
                </div>
                <!-- Telefono -->
                <div class="mb-3">
                  <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                  <input type="tel" id="telefono_persona" name="telefono_persona" class="form-control" placeholder="Número de contacto" pattern="[0-9]{7,15}" required minlength="7" maxlength="15" inputmode="tel" autocomplete="tel">
                  <div class="form-text">Número de contacto (7-15 dígitos)</div>
                </div>
                <!-- Correo -->
                <div class="mb-3">
                  <label for="email_persona" class="form-label">Correo <span class="text-danger">*</span></label>
                  <input type="email" id="email_persona" name="email" class="form-control" placeholder="usuario@dominio.com" required autocomplete="email">
                  <div class="form-text">Correo electrónico válido</div>
                </div>
              </div>

              <!-- PESTAÑA USUARIO: credenciales y datos de acceso -->
              <div class="tab-pane fade" id="usuario-tab-pane" role="tabpanel" aria-labelledby="usuario-tab">

                <!-- Nombre de Usuario -->
                <div class="mb-3">
                  <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                  <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control" placeholder="" pattern="[A-Za-zÁÉÍÑÓÚÜáéíñóúü]{3,25}" title="Solo letras (3-25 caracteres)" required minlength="3" maxlength="25" autocomplete="off">
                </div>
                
                <!-- Tipo de usuario -->
                <div class="mb-3">
                  <label for="tipo_usuario" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                  <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                    <option value="" hidden>Seleccione tipo</option>
                    <option value="1">Administrador</option>
                    <option value="0">Estándar</option>
                  </select>
                  <div class="form-text">Tipo de acceso del usuario</div>
                </div>

                <!-- Contraseña: control simple + icono de mostrar/ocultar (Bootstrap icon) -->
                <div class="mb-3 position-relative" id="password-group">
                  <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña segura" required minlength="6" maxlength="9" pattern="[A-Za-z\d!@#$%^&*.]{6,9}" title="Ingrese 6-9 caracteres con Mayúscula + Número + Especial">
                    <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                      <i class="bi bi-eye" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div class="form-text">Longitud 6-9. Use mayúscula, número y carácter especial.</div>
                  <label for="password_confirm" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Confirmar contraseña segura" required minlength="6" maxlength="9" pattern="[A-Za-z\d!@#$%^&*.]{6,9}" title="Ingrese 6-9 caracteres con Mayúscula + Número + Especial">
                    <span class="input-group-text" id="togglePasswordConfirm" style="cursor:pointer;">
                      <i class="bi bi-eye" aria-hidden="true"></i>
                    </span>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              Siguiente
              <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../js/jquery-3.4.1.min.js"></script>
  <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
  <script src="../DataTables/datatables.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/menu_modulo_usuario.js"></script>

</body>
</html>
