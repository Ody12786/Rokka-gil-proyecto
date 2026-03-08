<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$orden_id = $_GET['id'] ?? 0;

// Obtener datos de la orden
$stmt = $conex->prepare("SELECT * FROM ordenes WHERE id = ?");
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obtener detalles de la orden
$stmt_det = $conex->prepare("SELECT * FROM orden_detalle WHERE orden_id = ?");
$stmt_det->bind_param("i", $orden_id);
$stmt_det->execute();
$detalles = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_det->close();

// Obtener stock telas
$stmt_stock = $conex->query("SELECT tipo_tela, SUM(metros) as stock_total FROM compras_telas GROUP BY tipo_tela");
$stock_telas = $stmt_stock->fetch_all(MYSQLI_ASSOC);

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $tecnica = $_POST['tecnica'];
    $tipo_tela = $_POST['tipo_tela'] ?? null;
    $estado_entrega = $_POST['estado_entrega'];
    $metros_totales = 0;
    
    // Procesar nueva imagen si se subió
    $imagen = $orden['imagen']; // Mantener imagen actual por defecto
    
    if (!empty($_FILES['imagen']['name'])) {
        $targetDir = "../uploads/";
        
        // Eliminar imagen anterior si existe
        if (!empty($orden['imagen']) && file_exists($targetDir . $orden['imagen'])) {
            unlink($targetDir . $orden['imagen']);
        }
        
        // Subir nueva imagen
        $imagenNombre = time() . "_" . basename($_FILES['imagen']['name']);
        $targetFile = $targetDir . $imagenNombre;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFile)) {
            $imagen = $imagenNombre;
        }
    }

    // Actualizar cabecera (incluyendo imagen si cambió)
    $stmt_upd = $conex->prepare("UPDATE ordenes SET nombre = ?, tecnica = ?, tipo_tela = ?, estado_entrega = ?, imagen = ? WHERE id = ?");
    $stmt_upd->bind_param("sssssi", $nombre, $tecnica, $tipo_tela, $estado_entrega, $imagen, $orden_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    if ($orden['tipo_orden'] === 'Fabricado') {
        // Actualizar cantidades por talla
        $tallas = ['S' => 1.40, 'M' => 1.45, 'L' => 1.50, 'XL' => 1.55, 'XXL' => 1.60];
        $mangas = $_POST['mangas'] ?? '';
        
        // Primero, eliminar registros existentes para esta orden (opcional - depende de tu lógica)
        // Si prefieres actualizar en lugar de eliminar, usa el código anterior
        
        foreach ($tallas as $talla => $metros_base) {
            $cantidad = (int)($_POST["cantidad_$talla"] ?? 0);
            
            // Buscar si existe el detalle
            $stmt_check = $conex->prepare("SELECT id FROM orden_detalle WHERE orden_id = ? AND talla = ?");
            $stmt_check->bind_param("is", $orden_id, $talla);
            $stmt_check->execute();
            $existe = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if ($cantidad > 0) {
                $metros_prenda = $metros_base;
                if ($mangas === 'Larga' && $talla !== 'XXL') {
                    $metros_prenda = 1.60;
                }
                $metros_consumidos = $metros_prenda * $cantidad;
                $metros_totales += $metros_consumidos;

                if ($existe) {
                    // Actualizar
                    $stmt_det_upd = $conex->prepare("UPDATE orden_detalle SET cantidad = ?, metros_por_prenda = ?, mangas = ? WHERE orden_id = ? AND talla = ?");
                    $stmt_det_upd->bind_param("idsis", $cantidad, $metros_prenda, $mangas, $orden_id, $talla);
                    $stmt_det_upd->execute();
                    $stmt_det_upd->close();
                } else {
                    // Insertar
                    $stmt_det_ins = $conex->prepare("INSERT INTO orden_detalle (orden_id, talla, cantidad, metros_por_prenda, mangas) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_det_ins->bind_param("isidd", $orden_id, $talla, $cantidad, $metros_prenda, $mangas);
                    $stmt_det_ins->execute();
                    $stmt_det_ins->close();
                }
            } else if ($existe) {
                // Eliminar si cantidad es 0
                $stmt_del = $conex->prepare("DELETE FROM orden_detalle WHERE orden_id = ? AND talla = ?");
                $stmt_del->bind_param("is", $orden_id, $talla);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }

        // Validar stock
        $stmt_stock_tela = $conex->prepare("SELECT COALESCE(SUM(metros), 0) as stock FROM compras_telas WHERE tipo_tela = ?");
        $stmt_stock_tela->bind_param("s", $tipo_tela);
        $stmt_stock_tela->execute();
        $stock_tela = $stmt_stock_tela->get_result()->fetch_assoc()['stock'];
        $metros_faltantes = $metros_totales > $stock_tela ? $metros_totales - $stock_tela : 0;

        // Actualizar totales
        $stmt_tot = $conex->prepare("UPDATE ordenes SET metros_totales = ?, metros_faltantes = ? WHERE id = ?");
        $stmt_tot->bind_param("ddi", $metros_totales, $metros_faltantes, $orden_id);
        $stmt_tot->execute();
        $stmt_tot->close();
    } else {
        // Para órdenes distribuidas, actualizar cantidad_total si existe el campo
        $cantidad_dist = (int)($_POST['cantidad_distribuido'] ?? 0);
        if ($cantidad_dist > 0) {
            $stmt_cant = $conex->prepare("UPDATE ordenes SET cantidad_total = ? WHERE id = ?");
            $stmt_cant->bind_param("ii", $cantidad_dist, $orden_id);
            $stmt_cant->execute();
            $stmt_cant->close();
        }
    }

    $_SESSION['success_msg'] = '¡Orden actualizada correctamente!';
    header("Location: ordenes.php");
    exit;
}

// Obtener cantidades actuales por talla
$cantidades_actuales = [];
foreach ($detalles as $det) {
    $cantidades_actuales[$det['talla']] = $det['cantidad'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Orden - Roka Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #1e1e2e 100%);
            color: #fff;
            min-height: 100vh;
        }
        .card {
            background: #212529;
            border: 1px solid #444;
            color: #fff;
        }
        .card-header {
            background: #2c3e50;
            color: #fff;
        }
        .form-control, .form-select {
            background: #2c3034;
            border: 1px solid #555;
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            background: #343a40;
            color: #fff;
        }
        label {
            color: #fff !important;
        }
        .current-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #0d6efd;
            margin-bottom: 10px;
        }
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px dashed #0d6efd;
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Orden #<?= $orden['id'] ?> - <?= htmlspecialchars($orden['nombre']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <!-- Imagen actual y nueva imagen -->
                                <div class="col-12 text-center mb-3">
                                    <label class="form-label fw-bold">Imagen Actual</label><br>
                                    <img src="../uploads/<?= htmlspecialchars($orden['imagen']) ?>" 
                                         class="current-image" alt="Imagen actual">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Nombre Producto</label>
                                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($orden['nombre']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Cambiar Imagen (opcional)</label>
                                    <input type="file" name="imagen" id="imagen" accept="image/*" class="form-control">
                                    <small class="text-secondary">Dejar vacío para mantener la imagen actual</small>
                                </div>
                                
                                <!-- Preview de nueva imagen -->
                                <div class="col-12 text-center">
                                    <img id="imagePreview" class="image-preview" alt="Vista previa">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Técnica</label>
                                    <select name="tecnica" class="form-select">
                                        <option value="Sublimación" <?= $orden['tecnica'] == 'Sublimación' ? 'selected' : '' ?>>Sublimación</option>
                                        <option value="Bordado" <?= $orden['tecnica'] == 'Bordado' ? 'selected' : '' ?>>Bordado</option>
                                        <option value="DTF" <?= $orden['tecnica'] == 'DTF' ? 'selected' : '' ?>>DTF</option>
                                        <option value="Vinil" <?= $orden['tecnica'] == 'Vinil' ? 'selected' : '' ?>>Vinil</option>
                                        <option value="Otro" <?= $orden['tecnica'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                                    </select>
                                </div>

                                <?php if ($orden['tipo_orden'] === 'Fabricado'): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Tipo Tela</label>
                                    <select name="tipo_tela" class="form-select">
                                        <?php foreach ($stock_telas as $tela): ?>
                                            <option value="<?= htmlspecialchars($tela['tipo_tela']) ?>" 
                                                <?= $orden['tipo_tela'] == $tela['tipo_tela'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tela['tipo_tela']) ?> (<?= number_format($tela['stock_total'], 1) ?>m)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mangas</label>
                                    <select name="mangas" class="form-select">
                                        <option value="">Corta</option>
                                        <option value="Larga" <?= ($detalles[0]['mangas'] ?? '') == 'Larga' ? 'selected' : '' ?>>Larga</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3">Cantidades por Talla</h6>
                                    <div class="row g-2">
                                        <?php
                                        $tallas = ['S' => 1.40, 'M' => 1.45, 'L' => 1.50, 'XL' => 1.55, 'XXL' => 1.60];
                                        foreach ($tallas as $talla => $metros):
                                        ?>
                                        <div class="col-md-2">
                                            <label class="form-label small"><?= $talla ?> (<?= $metros ?>m)</label>
                                            <input type="number" name="cantidad_<?= $talla ?>" min="0" 
                                                value="<?= $cantidades_actuales[$talla] ?? 0 ?>" 
                                                class="form-control text-center">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Para órdenes distribuidas -->
                                <div class="col-md-6">
                                    <label class="form-label">Cantidad Total</label>
                                    <input type="number" name="cantidad_distribuido" min="1" 
                                           value="<?= $orden['cantidad_total'] ?? 0 ?>" 
                                           class="form-control">
                                </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label class="form-label">Estado de Entrega</label>
                                    <select name="estado_entrega" class="form-select">
                                        <option value="Por entregar" <?= ($orden['estado_entrega'] ?? 'Por entregar') == 'Por entregar' ? 'selected' : '' ?>>Por entregar</option>
                                        <option value="Parcial" <?= ($orden['estado_entrega'] ?? '') == 'Parcial' ? 'selected' : '' ?>>Parcial</option>
                                        <option value="Entregado" <?= ($orden['estado_entrega'] ?? '') == 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-save"></i> Guardar Cambios
                                    </button>
                                    <a href="ordenes.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Volver
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview de imagen antes de subir
        document.getElementById('imagen').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        });
    </script>
</body>
</html>