<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
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
// PROCESAR NUEVA ORDEN DISTRIBUIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $tecnica = $_POST['tecnica'];
    $categoria = $_POST['categoria'];
    $cantidad = (int)$_POST['cantidad'];

    if (empty($nombre) || empty($tecnica) || $cantidad <= 0 || empty($_FILES['imagen']['name'])) {
        $_SESSION['error_msg'] = 'Complete todos los campos correctamente';
        header("Location: productos_distribuidos.php");
        exit();
    }

    $targetDir = "../uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $imagenNombre = time() . "_" . basename($_FILES['imagen']['name']);
    $targetFile = $targetDir . $imagenNombre;

    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFile)) {
        $stmt = $conex->prepare("INSERT INTO distribucion_lista (imagen, nombre, categoria, cantidad, tecnica, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
        $stmt->bind_param("sssis", $imagenNombre, $nombre, $categoria, $cantidad, $tecnica);
        $stmt->execute();
        $orden_id = $conex->insert_id;
        $stmt->close();

        $_SESSION['success_msg'] = "¡Producto Distribuido #$orden_id ($cantidad und) registrado!";
        header("Location: productos_distribuidos.php");
        exit();
    } else {
        $_SESSION['error_msg'] = 'Error subiendo imagen. Verifique permisos uploads/';
        header("Location: productos_distribuidos.php");
        exit();
    }
}

// LISTAR DISTRIBUIDOS PENDIENTES
$distribuidos = $conex->query("
    SELECT * FROM distribucion_lista 
    WHERE estado = 'pendiente'
    ORDER BY fecha_registro DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Productos Distribuidos - Roka Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/orden.css">
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="http://localhost/Roka_Sports/menu/menu.php">
                <img src="../img/IMG_4124login.png" alt="Logo" width="50" class="me-2 rounded-circle">
                Roʞka System
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- ALERTAS -->
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_msg'] ?> <i class="bi bi-check-circle-fill"></i>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_msg'] ?> <i class="bi bi-exclamation-triangle-fill"></i>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

         <a href="catalog_modelos.php" class="btn btn-outline-warning btn-lg">
            <i class="bi bi-star-fill me-2"></i>Ver Modelos Standby
        </a>

        <a href="productos.php" class="btn btn-outline-primary">
            <i class="bi bi-box me-1"></i>Productos
        </a>

        <h1><i class="bi bi-truck"></i> Productos Distribuidos </h1>

        <!-- FORMULARIO SIMPLE -->
        <form method="POST" enctype="multipart/form-data" class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Orden Distribuida</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Imagen <span class="text-danger">*</span></label>
                        <input type="file" name="imagen" accept="image/*" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required maxlength="100">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" min="1" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Técnica <span class="text-danger">*</span></label>
                        <select name="tecnica" class="form-select" required>
                            <option value="Sublimación">Sublimación</option>
                            <option value="Bordado">Bordado</option>
                            <option value="DTF">DTF</option>
                            <option value="Vinil">Vinil</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                        <select name="categoria" class="form-select" required>
                            <option value="">Seleccionar Categoría</option>
                            <!--  DEPORTIVA -->
                            <optgroup label=" DEPORTIVA">
                                <option value="Conjuntos Olímpicos">Conjuntos Olímpicos</option>
                                <option value="Franelillas Equipos">Franelillas Equipos</option>
                                <option value="Camisetas Selecciones">Camisetas Selecciones</option>
                                <option value="Shorts Fútbol">Shorts Fútbol</option>
                                <option value="Medias Deportivas">Medias Deportivas</option>
                                <option value="Mangas Compresión">Mangas Compresión</option>
                            </optgroup>
                            <!--  CASUAL -->
                            <optgroup label="CASUAL">
                                <option value="Camisetas Casual">Camisetas Casual</option>
                                <option value="Polo Casual">Polo Casual</option>
                                <option value="Hoddies Urban">Hoddies Urban</option>
                                <option value="Chaquetas Deportivas">Chaquetas Deportivas</option>
                                <option value="Pantalones Jogger">Pantalones Jogger</option>
                                <option value="Gorras/Bandanas">Gorras/Bandanas</option>
                            </optgroup>
                            <!--  EMPRESARIAL -->
                            <optgroup label=" EMPRESARIAL">
                                <option value="Uniformes Empresas">Uniformes Empresas</option>
                                <option value="Polos Corporativos">Polos Corporativos</option>
                                <option value="Camisas Personalizadas">Camisas Personalizadas</option>
                                <option value="Delantales Industriales">Delantales Industriales</option>
                                <option value="Overoles Técnicos">Overoles Técnicos</option>
                                <option value="Camisetas Seguridad">Camisetas Seguridad</option>
                            </optgroup>
                            <!--  EVENTOS -->
                            <optgroup label=" EVENTOS">
                                <option value="Camisetas Eventos">Camisetas Eventos</option>
                                <option value="Playeras Promocionales">Playeras Promocionales</option>
                                <option value="Kits Ferias">Kits Ferias</option>
                                <option value="Camisetas Conmemorativas">Camisetas Conmemorativas</option>
                                <option value="Uniformes Voluntarios">Uniformes Voluntarios</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="bi bi-check-lg"></i> Registrar Orden
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- TABLA DISTRIBUIDOS -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-table"></i> Productos Distribuidos Pendientes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaDistribuidos" class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Técnica</th>
                                <th>Categoría</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distribuidos as $distribuido): ?>
                                <tr>
                                    <td><?= $distribuido['id'] ?></td>
                                    <td><img src="../uploads/<?= htmlspecialchars($distribuido['imagen']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;"></td>
                                    <td><?= htmlspecialchars($distribuido['nombre']) ?></td>
                                    <td><span class="badge bg-info"><?= number_format($distribuido['cantidad']) ?> und</span></td>
                                    <td><?= htmlspecialchars($distribuido['tecnica']) ?></td>
                                    <td><?= htmlspecialchars($distribuido['categoria']) ?></td>
                                    <td><?= date('d/m H:i', strtotime($distribuido['fecha_registro'])) ?></td>
                                    <td>
                                        <button class="btn btn-outline-warning btn-sm" onclick="guardarStandby(<?= $distribuido['id'] ?>)"
                                            title="Guardar diseño para catálogo de ventas">
                                            <i class="bi bi-star-fill"></i> Standby
                                        </button>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaDistribuidos').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [0, 'desc']
                ]
            });
        });

        function producirOrden(id) {
            alert('Producir distribuido #' + id);
        }
        function guardarStandby(id) {
    if (confirm('¿Guardar este diseño en STANDBY para mostrar a futuros clientes?')) {
        const fila = $(`button[onclick="guardarStandby(${id})"]`).closest('tr');
        const nombre = fila.find('td:nth-child(3)').text();
        const categoria = fila.find('td:nth-child(6)').text();
        const tecnica = fila.find('td:nth-child(5)').text();
        const imgSrc = fila.find('img').attr('src');
        
        $.post('guardar_modelo.php', {
            distribucion_id: id,
            nombre: nombre,
            categoria: categoria,
            tecnica: tecnica,
            imagen: imgSrc
        }, function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: 'Diseño agregado al catálogo Standby',
                    timer: 2000
                });
              
            } else {
                Swal.fire('Error', response.error, 'error');
            }
        }, 'json').fail(function() {
            alert('Error de conexión');
        });
    }
}

    </script>
</body>

</html>