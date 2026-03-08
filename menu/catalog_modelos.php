<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php?error=sesion");
    exit();
}

$modelos = $conex->query("SELECT * FROM modelos_standby WHERE activo = 1 ORDER BY fecha_actualizacion DESC")->fetch_all(MYSQLI_ASSOC);
$total_modelos = count($modelos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⭐ Catálogo Premium - Roka Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/stand.css">
    <style>
        .specifications {
            background: rgba(0, 0, 0, 0.03);
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid rgba(220, 53, 69, 0.1);
        }

        .specifications .badge {
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .specifications .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .color-badge {
            transition: all 0.3s ease;
        }

        .color-badge:hover {
            transform: scale(1.1);
        }
    </style>


</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="menu.php">
                <i class="bi bi-star-fill text-warning me-2"></i>
                Roʞka Catálogo StandBy
            </a>
            <div class="d-flex gap-2">
                <a href="productos_distribuidos.php" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-arrow-left"></i> Distribuidos
                </a>
                <a href="productos.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box"></i> Productos
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section pt-5 mt-5">
        <div class="container text-center animate__animated animate__fadeInDown">
            <h1 class="display-4 fw-bold mb-4 text-white" style="text-shadow: 0 0 30px rgba(255,255,255,0.5);">
                <i class="bi bi-stars"></i> Modelos StandBy Exclusivos
            </h1>
            <p class="lead text-white-50 mb-0" style="font-size: 1.3rem;">
                Diseños listos para conquistar clientes
            </p>
            <div class="mt-4">
                <span class="badge bg-dark fs-5 px-4 py-2 fw-bold shadow-lg">
                    <?= $total_modelos ?> Modelos Disponibles
                </span>
            </div>
        </div>
    </section>

    <div class="container my-5">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($modelos)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-star display-1 text-muted mb-4"></i>
                <h3 class="text-muted">No hay modelos aún</h3>
                <p class="text-muted">Guarda diseños desde <strong>Productos Distribuidos</strong></p>
                <a href="productos_distribuidos.php" class="btn btn-outline-warning btn-lg">
                    <i class="bi bi-arrow-right"></i> Ir a Distribuidos
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($modelos as $index => $modelo): ?>
            <div class="col animate__animated animate__fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                <div class="modelo-card h-100 position-relative overflow-hidden">
                    <!-- Overlay Hover -->
                    <div class="card-overlay d-flex flex-column align-items-center justify-content-center text-white">
                        <i class="bi bi-star-fill fs-1 mb-2 animate__animated animate__pulse animate__infinite"></i>
                        <h4 class="mb-0 fw-bold animate__animated animate__zoomIn">¡Listo para Vender!</h4>
                    </div>

                    <img src="../uploads/<?= htmlspecialchars($modelo['imagen']) ?>"
                         class="card-img-top modelo-img"
                         alt="<?= htmlspecialchars($modelo['nombre']) ?>"
                         loading="lazy">

                    <div class="card-body p-4">
                        <h5 class="card-title"><?= htmlspecialchars($modelo['nombre']) ?></h5>
                        <p class="card-text text-muted small lh-sm mb-4">
                            <?= htmlspecialchars($modelo['descripcion'] ?: 'Diseño profesional listo para personalización rápida') ?>
                        </p>

                        <!-- ESPECIFICACIONES -->
                        <div class="specifications mb-4 p-3 bg-dark bg-opacity-10 rounded-3 border border-danger border-opacity-25">
                            <h6 class="fw-bold text-dark mb-3 text-center"><i class="bi bi-gear-fill me-2"></i>Especificaciones</h6>
                            <div class="row g-2 text-center">
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block mb-1 fw-semibold">TELA</small>
                                    <span class="badge bg-secondary text-white px-3 py-2 fw-bold fs-6 shadow-sm">
                                        <?= htmlspecialchars($modelo['tela'] ?: 'Pendiente') ?>
                                    </span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block mb-1 fw-semibold">TALLA</small>
                                    <span class="badge bg-primary px-3 py-2 fw-bold fs-6 shadow-sm">
                                        <?= htmlspecialchars($modelo['talla'] ?: 'Pendiente') ?>
                                    </span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block mb-1 fw-semibold">COLOR</small>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="badge rounded-circle" style="width: 28px; height: 28px; background-color: <?= $modelo['color'] ?>; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></span>
                                        <small class="fw-bold text-dark"><?= strtoupper(substr($modelo['color'], 1)) ?: '—' ?></small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block mb-1 fw-semibold">STOCK</small>
                                    <span class="badge <?= $modelo['cantidad'] > 0 ? 'bg-success' : 'bg-warning' ?> text-white px-3 py-2 fw-bold fs-6 shadow-sm">
                                        <?= $modelo['cantidad'] ?> und
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Categoría + Técnica -->
                        <div class="d-flex gap-2 mb-3 flex-wrap">
                            <span class="badge badge-custom bg-info text-dark px-3 py-2">
                                <?= htmlspecialchars($modelo['categoria']) ?>
                            </span>
                            <span class="badge badge-custom bg-success px-3 py-2">
                                <?= htmlspecialchars($modelo['tecnica']) ?>
                            </span>
                        </div>

                        <!-- Fecha -->
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>
                                <?= date('d/m/Y', strtotime($modelo['fecha_creacion'])) ?>
                            </small>
                        </div>
                    </div>

                   
                    <div class="card-footer bg-transparent border-0 p-3">
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-primary-custom btn-custom flex-fill me-1"
                                    onclick="editarModelo(<?= $modelo['id'] ?>)">
                                <i class="bi bi-pencil-square"></i> Editar
                            </button>
                            <button class="btn btn-danger-custom btn-custom flex-fill"
                                    onclick="eliminarModelo(<?= $modelo['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

                    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                    <script>
                        // Smooth scroll y animaciones
                        document.addEventListener('DOMContentLoaded', function() {
                            // Intersection Observer para animaciones
                            const observer = new IntersectionObserver((entries) => {
                                entries.forEach(entry => {
                                    if (entry.isIntersecting) {
                                        entry.target.style.opacity = '1';
                                        entry.target.style.transform = 'translateY(0)';
                                    }
                                });
                            });

                            document.querySelectorAll('.modelo-card').forEach(card => {
                                card.style.opacity = '0';
                                card.style.transform = 'translateY(50px)';
                                card.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                                observer.observe(card);
                            });
                        });

                     function editarModelo(id) {
    // 🔥 OBTENER TODOS los datos actuales de la tarjeta
    const button = $(`button[onclick="editarModelo(${id})"]`);
    const card = button.closest('.modelo-card');
    
    const nombreActual = card.find('.card-title').text().trim();
    const descActual = card.find('.card-text').text().trim();
    
    // 🔥 OBTENER TELA actual del badge
    const telaBadge = card.find('.specifications .col-6:nth-child(1) .badge').text().trim();
    const tallaBadge = card.find('.specifications .col-6:nth-child(2) .badge').text().trim();
    
    // 🔥 Color del círculo
    const colorBadge = card.find('.specifications .rounded-circle').attr('style');
    const colorMatch = colorBadge ? colorBadge.match(/background-color:\s*(#[0-9a-fA-F]{6})/) : null;
    const colorActual = colorMatch ? colorMatch[1] : '#dc3545';

    Swal.fire({
        title: '✏️ Editar Modelo #' + id,
        html: `
            <div class="p-4">
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="nombre_edit" class="form-control" value="${nombreActual}" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2">Tipo de Tela</label>
                    <select id="tela_edit" class="form-select">
                        <option value="">Selecciona tela</option>
                        <option value="Polyester" ${telaBadge === 'Polyester' || telaBadge === 'Pendiente' ? 'selected' : ''}>Polyester</option>
                        <option value="Algodón" ${telaBadge === 'Algodón' ? 'selected' : ''}>Algodón</option>
                        <option value="Licra" ${telaBadge === 'Licra' ? 'selected' : ''}>Licra</option>
                        <option value="Polilicra" ${telaBadge === 'Polilicra' ? 'selected' : ''}>Polilicra</option>
                        <option value="Drill" ${telaBadge === 'Drill' ? 'selected' : ''}>Drill</option>
                        <option value="Atlética" ${telaBadge === 'Atlética' ? 'selected' : ''}>Atlética</option>
                        <option value="Muselina" ${telaBadge === 'Muselina' ? 'selected' : ''}>Muselina</option>
                        <option value="Superpoly" ${telaBadge === 'Superpoly' ? 'selected' : ''}>Superpoly</option>
                        <option value="Tafeta" ${telaBadge === 'Tafeta' ? 'selected' : ''}>Tafeta</option>
                        <option value="Memory" ${telaBadge === 'Memory' ? 'selected' : ''}>Memory</option>
                        <option value="Mono" ${telaBadge === 'Mono' ? 'selected' : ''}>Mono</option>
                        <option value="SPT" ${telaBadge === 'SPT' ? 'selected' : ''}>SPT</option>
                        <option value="Driffit" ${telaBadge === 'Driffit' ? 'selected' : ''}>Driffit</option>
                        <option value="Grandes Ligas" ${telaBadge === 'Grandes Ligas' ? 'selected' : ''}>Grandes Ligas</option>
                        <option value="Elastano" ${telaBadge === 'Elastano' ? 'selected' : ''}>Elastano</option>
                        <option value="Microfibra" ${telaBadge === 'Microfibra' ? 'selected' : ''}>Microfibra</option>
                        <option value="Mezcla" ${telaBadge === 'Mezcla' ? 'selected' : ''}>Mezcla</option>
                    </select>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-2">Talla <span class="text-danger">*</span></label>
                        <select id="talla_edit" class="form-select" required>
                            <option value="">Selecciona talla</option>
                            <option value="S" ${tallaBadge === 'S' ? 'selected' : ''}>S</option>
                            <option value="M" ${tallaBadge === 'M' ? 'selected' : ''}>M</option>
                            <option value="L" ${tallaBadge === 'L' ? 'selected' : ''}>L</option>
                            <option value="XL" ${tallaBadge === 'XL' ? 'selected' : ''}>XL</option>
                            <option value="XXL" ${tallaBadge === 'XXL' ? 'selected' : ''}>XXL</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark mb-2">Color</label>
                        <input type="color" id="color_edit" class="form-control form-control-color" value="${colorActual}">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-2">Descripción</label>
                    <textarea id="descripcion_edit" class="form-control" rows="3" placeholder="Detalles del diseño...">${descActual}</textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-lg"></i> Guardar Cambios',
        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        buttonsStyling: false,
        width: '600px',
        customClass: {
            popup: 'animate__animated animate__zoomIn',
            confirmButton: 'btn btn-danger px-4 fw-bold me-2',
            cancelButton: 'btn btn-secondary px-4 fw-bold'
        },
        preConfirm: () => {
            const nombre = document.getElementById('nombre_edit').value;
            const tela = document.getElementById('tela_edit').value;
            const talla = document.getElementById('talla_edit').value;
            const color = document.getElementById('color_edit').value;
            const descripcion = document.getElementById('descripcion_edit').value;

            if (!nombre || !talla) {
                Swal.showValidationMessage('Nombre y Talla son obligatorios');
                return false;
            }

            return { id, nombre, tela, talla, color, descripcion };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            
            // 🔥 ACTUALIZAR MÁS ELEMENTOS de la UI
            $.post('actualizar_modelo.php', data, function(response) {
                if (response.success) {
                    const card = button.closest('.modelo-card');
                    
                    // Actualizar TODOS los campos visibles
                    card.find('.card-title').text(data.nombre);
                    card.find('.card-text').text(data.descripcion || 'Diseño profesional listo para personalización rápida');
                    
                    // Actualizar especificaciones
                    card.find('.specifications .col-6:nth-child(1) .badge').text(tela || 'Pendiente');
                    card.find('.specifications .col-6:nth-child(2) .badge').text(talla || 'Pendiente');
                    card.find('.specifications .rounded-circle').css('background-color', color);
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: 'Modelo editado correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', response.error, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        }
    });
}



                       function eliminarModelo(id) {
    Swal.fire({
        title: '¿Eliminar este modelo?',
        text: 'No podrás recuperarlo después',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Eliminar',
        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger px-4 fw-bold',
            cancelButton: 'btn btn-secondary px-4 fw-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('eliminar_modelo.php', { id: id }, function(response) {
                if (response.success) {
                    
                    $(`[onclick="eliminarModelo(${id})"]`).closest('.col').fadeOut(500, function() {
                        $(this).remove();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado!',
                            text: 'Modelo eliminado correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    });
                } else {
                    Swal.fire('Error', response.error, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        }
    });
}

                    </script>
</body>

</html>