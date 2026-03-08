<?php
session_start();
date_default_timezone_set('America/Caracas');
include("../database/connect_db.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
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

$usuarioId = $_SESSION['usuario_id'];
$usuarioNombre = $_SESSION['usuario_nombre'];
$usuarioTipo = $_SESSION['usuario_tipo'];
$esAdmin = isset($_SESSION['usuario_rol']) && ($_SESSION['usuario_rol'] == 'admin' || $_SESSION['usuario_tipo'] == 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Compras de Telas con Factura</title>
    <link rel="stylesheet" href="../css/menu.css" />
    <link rel="stylesheet" href="../css/tablas.css" />
    <link rel="stylesheet" href="../DataTables/datatables.min.css" />
    <link rel="stylesheet" href="../Bootstrap5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.6/css/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../Font_awesome/css/all.min.css" />
    
    <style>
        .background-wrapper {
            background: linear-gradient(rgba(26, 26, 26, 0.95), rgba(15, 15, 15, 0.98));
            min-height: 100vh;
            padding-top: 70px;
        }
        
        #tablaComprasTelas_wrapper {
            background: rgba(35, 35, 35, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 15px;
            border: 1px solid rgba(209, 0, 27, 0.2);
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
            margin: 30px auto;
            /* max-width: 95%; */
            width: 100%;
            padding: 20px;
        }
        .detalle-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #d1001b;
        }
        
        .btn-agregar-detalle {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-remover-detalle {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .total-calculado {
            font-size: 1.2em;
            font-weight: bold;
            color: #d1001b;
        }
        
        .modal-lg-custom {
            max-width: 800px;
        }
        
        /* Estilos para el offcanvas (copiados de compras_material.php) */
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
    </style>
</head>
<body>
    <!-- Navbar superior (similar a compras_material.php) -->
    <nav class="navbar navbar-dark bg-dark position-sticky" style="top:0; z-index: 1030;">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../img/IMG_4124login.png" alt="Logo" width="70" height="70" class="me-6 rounded-circle bg-primary p-1" />
                <span class="nav-link">Roʞka System - Compras de Telas</span>
            </a>
            <a class="navbar-brand" href="../menu/menu.php">Inicio</a>
        </div>
    </nav>

    <!-- Offcanvas Sidebar (copiado de compras_material.php) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link" href="../menu/modulo_proveedor.php"><i class="bi bi-truck me-2"></i>Proveedores</a>
                <a class="nav-link" href="../menu/compras.php"><i class="bi bi-shop me-2"></i>Compras de telas</a>
                <a class="nav-link" href="../menu/compras_material.php"><i class="bi bi-cart me-2"></i>Inventario</a>
                <a class="nav-link" href="../menu/productos.php"><i class="bi bi-tags me-2"></i>Productos</a>
                <a class="nav-link" href="../menu/clientes.php"><i class="bi bi-people me-2"></i>Clientes</a>
                <a class="nav-link" href="../menu/ventas.php"><i class="bi bi-cash-stack me-2"></i>Ventas</a>
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 'estandar' && $esAdmin) { ?>
                    <a class="nav-link" href="../menu/finanzas.php"><i class="bi bi-wallet2 me-2"></i>Créditos Pendientes</a>
                    <a class="nav-link" href="../menu/pagar_abonos.php"><i class="bi bi-receipt me-2"></i>Cuentas por cobrar</a>
                <?php } ?>
                <hr class="my-2" />
                <a class="nav-link text-danger" href="http://localhost/Roka_Sports/login.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
            </nav>
        </div>
    </div>

    <div class="background-wrapper">
        <div class="container mt-4">
            <div class="text-center mb-5">
                <h1 class="text-white display-5 fw-bold">
                    <i class="fas fa-receipt text-warning me-3"></i>
                    Gestión de Compras de Telas con Factura
                </h1>
                <p class="text-white-50">Control de facturas y múltiples telas por compra</p>
            </div>

            <div class="d-flex flex-wrap gap-3 my-4 justify-content-center">
                <button class="btn btn-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#modalNuevaCompraTela">
                    <i class="fas fa-plus-circle me-2"></i>Nueva Factura de Tela
                </button>
            </div>

            <table id="tablaComprasTelas" class="table table-striped dt-responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>N° Factura</th>
                        <th>Proveedor</th>
                        <th>Cant. Telas</th>
                        <th>Total</th>
                        <th>Condición</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal para nueva compra con múltiples telas -->
    <div class="modal fade" id="modalNuevaCompraTela" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-lg-custom">
            <form id="formCompraTela" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice me-2"></i>Registrar Factura de Tela
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Datos de la factura -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número de Factura *</label>
                            <input type="text" id="numeroFactura" name="numero_factura" 
                                   class="form-control" required 
                                   placeholder="Ej: F001-00012345">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Compra *</label>
                            <input type="date" id="fechaCompra" name="fecha_adquisicion" 
                                   class="form-control" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Proveedor *</label>
                            <select id="proveedorTela" name="proveedor_id" class="form-select" required>
                                <option value="">Seleccione proveedor</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Condición de Pago *</label>
                            <select id="condicionPago" name="condicion_pago" class="form-select" required>
                                <option value="Contado">Contado</option>
                                <option value="Crédito">Crédito</option>
                            </select>
                        </div>
                    </div>

                    <!-- Detalles de telas -->
                    <div class="card mt-3">
                        <div class="card-header bg-secondary text-white">
                            <i class="fas fa-list me-2"></i>Detalle de Telas
                            <button type="button" class="btn btn-sm btn-light float-end" id="btnAgregarDetalle">
                                <i class="fas fa-plus"></i> Agregar Tela
                            </button>
                        </div>
                        <div class="card-body" id="detallesContainer">
                            <!-- Los detalles se agregarán aquí dinámicamente -->
                            <div class="detalle-row" id="detalle-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Tela *</label>
                                        <input type="text" name="detalles[0][tipo_tela]" 
                                               class="form-control tipo-tela" required
                                               placeholder="Ej: Algodón, Poliéster">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Metros *</label>
                                        <input type="number" step="0.01" min="0.01" 
                                               name="detalles[0][metros]" 
                                               class="form-control metros-detalle" required
                                               onchange="calcularTotalDetalle(0)">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio x Metro *</label>
                                        <input type="number" step="0.01" min="0.01" 
                                               name="detalles[0][precio_unitario]" 
                                               class="form-control precio-detalle" required
                                               onchange="calcularTotalDetalle(0)">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Subtotal</label>
                                        <input type="text" class="form-control subtotal-detalle" 
                                               readonly value="$0.00">
                                        <button type="button" class="btn btn-remover-detalle mt-2 d-none"
                                                onclick="removerDetalle(0)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Totales y observaciones -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" 
                                      class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="total-calculado">Total Factura: <span id="totalFactura">$0.00</span></h5>
                                    <input type="hidden" id="totalHidden" name="total" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Factura
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.6/js/dataTables.responsive.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    let detalleCount = 1;

    // Cargar proveedores
    function cargarProveedores() {
        $.ajax({
            url: '../menu/proveedores_api.php',
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success' && resp.data) {
                    const $select = $('#proveedorTela');
                    $select.empty().append('<option value="">Seleccione proveedor</option>');
                    resp.data.forEach(prov => {
                        const nombre = prov.nombres || prov.nombre || "Proveedor";
                        $select.append(`<option value="${prov.id}">${nombre}</option>`);
                    });
                }
            }
        });
    }

    // Agregar nuevo detalle de tela
    $('#btnAgregarDetalle').click(function() {
        const nuevoDetalle = `
            <div class="detalle-row mt-2" id="detalle-${detalleCount}">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="detalles[${detalleCount}][tipo_tela]" 
                               class="form-control tipo-tela" required
                               placeholder="Tipo de tela">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" min="0.01" 
                               name="detalles[${detalleCount}][metros]" 
                               class="form-control metros-detalle" required
                               onchange="calcularTotalDetalle(${detalleCount})">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" min="0.01" 
                               name="detalles[${detalleCount}][precio_unitario]" 
                               class="form-control precio-detalle" required
                               onchange="calcularTotalDetalle(${detalleCount})">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control subtotal-detalle" 
                               readonly value="$0.00">
                        <button type="button" class="btn btn-remover-detalle mt-2"
                                onclick="removerDetalle(${detalleCount})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#detallesContainer').append(nuevoDetalle);
        detalleCount++;
    });

    // Remover detalle
    window.removerDetalle = function(index) {
        if ($('.detalle-row').length > 1) {
            $(`#detalle-${index}`).remove();
            calcularTotalGeneral();
        } else {
            Swal.fire('Error', 'Debe haber al menos un detalle', 'warning');
        }
    };

    // Calcular subtotal de un detalle
    window.calcularTotalDetalle = function(index) {
        const metros = parseFloat($(`#detalle-${index} .metros-detalle`).val()) || 0;
        const precio = parseFloat($(`#detalle-${index} .precio-detalle`).val()) || 0;
        const subtotal = metros * precio;
        $(`#detalle-${index} .subtotal-detalle`).val('$' + subtotal.toFixed(2));
        calcularTotalGeneral();
    };

    // Calcular total general
    function calcularTotalGeneral() {
        let total = 0;
        $('.subtotal-detalle').each(function() {
            const valor = parseFloat($(this).val().replace('$', '')) || 0;
            total += valor;
        });
        $('#totalFactura').text('$' + total.toFixed(2));
        $('#totalHidden').val(total.toFixed(2));
    }

    // DataTable
    const tabla = $('#tablaComprasTelas').DataTable({
        ajax: {
            url: '../api/compras_telas_api.php',
            method: 'GET',
            dataSrc: function(resp) {
                if (resp.status === 'success') return resp.data;
                return [];
            }
        },
        columns: [
            { data: 'id' },
            { data: 'fecha_adquisicion' },
            { data: 'numero_factura', defaultContent: 'Sin factura' },
            { data: 'nombre_proveedor', defaultContent: 'N/A' },
            { data: 'num_telas', defaultContent: '0' },
            { 
                data: 'total',
                render: function(data) {
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { data: 'condicion_pago' },
            { 
                data: 'saldo',
                render: function(data, type, row) {
                    if (row.condicion_pago === 'Contado') return 'Pagado';
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'estado_pago',
                render: function(data) {
                    const badgeClass = data === 'Pagada' ? 'success' : 'warning';
                    return `<span class="badge bg-${badgeClass}">${data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return `
                        <button class="btn btn-info btn-sm btn-ver" data-id="${data.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                        
                    `;
                }
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 10,
        dom: 'Bfrtilp',
        buttons: ['excel', 'pdf']
    });

    // Ver detalles de compra
    $('#tablaComprasTelas tbody').on('click', '.btn-ver', function() {
        const id = $(this).data('id');
        $.ajax({
            url: `../api/compras_telas_api.php?id=${id}`,
            method: 'GET',
            success: function(resp) {
                if (resp.status === 'success') {
                    let detallesHtml = '<h5>Detalles de la Factura</h5><table class="table table-sm">';
                    detallesHtml += '<thead><tr><th>Tipo Tela</th><th>Metros</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead><tbody>';
                    
                    resp.data.detalles.forEach(d => {
                        detallesHtml += `<tr>
                            <td>${d.tipo_tela}</td>
                            <td>${d.metros}</td>
                            <td>$${parseFloat(d.precio_unitario).toFixed(2)}</td>
                            <td>$${parseFloat(d.subtotal).toFixed(2)}</td>
                        </tr>`;
                    });
                    
                    detallesHtml += `</tbody>
                        <tfoot>
                            <tr><th colspan="3" class="text-end">Total:</th><th>$${parseFloat(resp.data.total).toFixed(2)}</th></tr>
                        </tfoot>
                    </table>`;
                    
                    Swal.fire({
                        title: `Factura: ${resp.data.numero_factura || 'N/A'}`,
                        html: detallesHtml,
                        width: '600px'
                    });
                }
            }
        });
    });

    // Eliminar compra
    $('#tablaComprasTelas tbody').on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar compra?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/compras_telas_api.php',
                    method: 'DELETE',
                    contentType: 'application/json',
                    data: JSON.stringify({ id: id }),
                    success: function(resp) {
                        if (resp.status === 'success') {
                            tabla.ajax.reload();
                            Swal.fire('Eliminado', 'Compra eliminada', 'success');
                        } else {
                            Swal.fire('Error', resp.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Enviar formulario
    $('#formCompraTela').submit(function(e) {
        e.preventDefault();
        
        // Validar que haya al menos un detalle con datos
        let detallesValidos = true;
        $('.detalle-row').each(function() {
            const tipo = $(this).find('.tipo-tela').val();
            const metros = $(this).find('.metros-detalle').val();
            const precio = $(this).find('.precio-detalle').val();
            if (!tipo || !metros || !precio) {
                detallesValidos = false;
            }
        });

        if (!detallesValidos) {
            Swal.fire('Error', 'Complete todos los detalles de telas', 'error');
            return;
        }

        // Construir datos para enviar
        const detalles = [];
        $('.detalle-row').each(function(index) {
            const tipo = $(this).find('.tipo-tela').val();
            const metros = parseFloat($(this).find('.metros-detalle').val());
            const precio = parseFloat($(this).find('.precio-detalle').val());
            
            if (tipo && metros && precio) {
                detalles.push({
                    tipo_tela: tipo,
                    metros: metros,
                    precio_unitario: precio
                });
            }
        });

        const data = {
            proveedor_id: $('#proveedorTela').val(),
            fecha_adquisicion: $('#fechaCompra').val(),
            numero_factura: $('#numeroFactura').val(),
            condicion_pago: $('#condicionPago').val(),
            observaciones: $('#observaciones').val(),
            detalles: detalles
        };

        $.ajax({
            url: '../api/compras_telas_api.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(resp) {
                if (resp.status === 'success') {
                    $('#modalNuevaCompraTela').modal('hide');
                    tabla.ajax.reload();
                    Swal.fire('Éxito', 'Compra registrada', 'success');
                    $('#formCompraTela')[0].reset();
                    // Reiniciar detalles a un solo detalle vacío
                    $('#detallesContainer').html(`
                        <div class="detalle-row" id="detalle-0">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="detalles[0][tipo_tela]" class="form-control tipo-tela" required placeholder="Tipo de tela">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.01" min="0.01" name="detalles[0][metros]" class="form-control metros-detalle" required onchange="calcularTotalDetalle(0)">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.01" min="0.01" name="detalles[0][precio_unitario]" class="form-control precio-detalle" required onchange="calcularTotalDetalle(0)">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control subtotal-detalle" readonly value="$0.00">
                                    <button type="button" class="btn btn-remover-detalle mt-2 d-none" onclick="removerDetalle(0)"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    `);
                    detalleCount = 1;
                    calcularTotalGeneral();
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Error de conexión: ' + xhr.status, 'error');
            }
        });
    });

    // Inicializar
    $(document).ready(function() {
        cargarProveedores();
        calcularTotalGeneral();
    });
    </script>
</body>
</html>