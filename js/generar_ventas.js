// ===== GESTIÓN DE PRODUCTOS CON PAGINACIÓN =====
$(document).ready(function() {
    
    let paginaActual = 1;
    let cargando = false;
    let hayMasProductos = true;
    
    // Cargar productos iniciales
    cargarProductos(1);
    cargarCategorias();
    
    // Evento búsqueda
    $('#btnBuscar').click(function() {
        paginaActual = 1;
        cargarProductos(1);
    });
    
    $('#buscarProducto').keypress(function(e) {
        if(e.which == 13) {
            paginaActual = 1;
            cargarProductos(1);
        }
    });
    
    // Evento filtro categoría
    $('#filtroCategoria').change(function() {
        paginaActual = 1;
        cargarProductos(1);
    });
    
    // Limpiar filtros
    $('#btnLimpiarFiltros').click(function() {
        $('#buscarProducto').val('');
        $('#filtroCategoria').val('');
        paginaActual = 1;
        cargarProductos(1);
    });
    
    // Cargar más productos (botón)
    $('#btnCargarMas').click(function() {
        if (!cargando && hayMasProductos) {
            paginaActual++;
            cargarProductos(paginaActual, true);
        }
    });
    
    // Función para cargar categorías
    function cargarCategorias() {
        $.ajax({
            url: '../ajax/get_productos_ventas.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.filtros && response.filtros.categorias) {
                    let select = $('#filtroCategoria');
                    select.empty().append('<option value="">Todas las categorías</option>');
                    response.filtros.categorias.forEach(function(categoria) {
                        select.append('<option value="' + categoria + '">' + categoria + '</option>');
                    });
                }
            }
        });
    }
    
    // Función principal para cargar productos
    function cargarProductos(pagina, append = false) {
        if (cargando) return;
        
        cargando = true;
        
        if (!append) {
            $('#productosGrid').hide();
            $('#productosNotFound').hide();
            $('#productosLoading').show();
            $('#paginacionContainer').hide();
            $('#btnVerMasContainer').hide();
        }
        
        let buscar = $('#buscarProducto').val();
        let categoria = $('#filtroCategoria').val();
        
        $.ajax({
            url: '../ajax/get_productos_ventas.php',
            method: 'GET',
            data: {
                pagina: pagina,
                buscar: buscar,
                categoria: categoria
            },
            dataType: 'json',
            success: function(response) {
                cargando = false;
                $('#productosLoading').hide();
                
                if (response.success) {
                    // Actualizar contador
                    let total = response.paginacion.total_productos;
                    $('#totalProductosBadge').text(total + ' productos');
                    
                    if (response.productos.length > 0) {
                        mostrarProductos(response.productos, append);
                        
                        // Actualizar paginación
                        actualizarPaginacion(response.paginacion);
                        
                        hayMasProductos = pagina < response.paginacion.total_paginas;
                        
                        if (hayMasProductos) {
                            $('#btnVerMasContainer').show();
                            $('#btnCargarMas').prop('disabled', false);
                        } else {
                            $('#btnVerMasContainer').hide();
                        }
                    } else {
                        $('#productosGrid').empty().hide();
                        $('#productosNotFound').show();
                        $('#paginacionContainer').hide();
                        $('#btnVerMasContainer').hide();
                        $('#totalProductosBadge').text('0 productos');
                    }
                } else {
                    console.error('Error:', response.error);
                    Swal.fire('Error', 'No se pudieron cargar los productos', 'error');
                }
            },
            error: function(xhr, status, error) {
                cargando = false;
                $('#productosLoading').hide();
                console.error('AJAX Error:', error);
                Swal.fire('Error', 'Error de conexión al cargar productos', 'error');
            }
        });
    }
    
    // Función para mostrar productos en el grid
    function mostrarProductos(productos, append = false) {
        let grid = $('#productosGrid');
        
        if (!append) {
            grid.empty();
        }
        
        const template = document.getElementById('producto-card-template');
        
        productos.forEach(function(prod) {
            const card = template.content.cloneNode(true);
            
            // Imagen
            const img = card.querySelector('.card-img-top');
            img.src = prod.imagen;
            img.alt = prod.nombre;
            
            // Badge de stock
            const badge = card.querySelector('.badge');
            badge.className = `badge stock-badge ${prod.badge_color} px-3 py-2 rounded-pill`;
            badge.textContent = prod.badge_text;
            
            // Código
            card.querySelector('.producto-codigo').textContent = prod.codigo;
            
            // Nombre
            card.querySelector('.producto-nombre').textContent = prod.nombre;
            
            // Talla y tela
            card.querySelector('.producto-talla').textContent = prod.talla || 'Std';
            card.querySelector('.producto-tela').textContent = prod.tipo_tela || 'N/A';
            
            // Descripción (limitada)
            const desc = card.querySelector('.producto-descripcion');
            desc.textContent = prod.descripcion ? 
                (prod.descripcion.length > 60 ? prod.descripcion.substring(0, 60) + '...' : prod.descripcion) : 
                'Sin descripción';
            
            // Precio
            card.querySelector('.producto-precio').textContent = prod.precio;
            
            // Stock
            card.querySelector('.producto-stock').textContent = prod.stock_texto;
            
            // Botón agregar (deshabilitar si agotado)
            const btnAgregar = card.querySelector('.btn-agregar-carrito');
            if (prod.estado_stock === 'agotado') {
                btnAgregar.disabled = true;
                btnAgregar.className = 'btn btn-outline-secondary rounded-pill px-4 py-2';
                btnAgregar.innerHTML = '<i class="fas fa-ban me-2"></i>Agotado';
            } else {
                btnAgregar.setAttribute('data-id', prod.id);
                btnAgregar.setAttribute('data-codigo', prod.codigo || '');
                btnAgregar.setAttribute('data-nombre', prod.nombre);
                btnAgregar.setAttribute('data-precio', prod.precio);
                btnAgregar.setAttribute('data-stock', prod.stock);

                btnAgregar.dataset.id = prod.id;
                btnAgregar.dataset.codigo = prod.codigo || '';
                btnAgregar.dataset.nombre = prod.nombre;
                btnAgregar.dataset.precio = prod.precio;
                btnAgregar.dataset.stock = prod.stock;
            }
            
            grid.append(card);
        });
        
        grid.show();
    }
    
    // Función para actualizar paginación
    function actualizarPaginacion(paginacion) {
        let { pagina_actual, total_paginas, total_productos, por_pagina } = paginacion;
        
        // Mostrar contenedor
        $('#paginacionContainer').show();
        
        // Información de resultados
        let desde = ((pagina_actual - 1) * por_pagina) + 1;
        let hasta = Math.min(pagina_actual * por_pagina, total_productos);
        $('#infoPaginacion').html(`Mostrando <span class="text-white fw-bold">${desde}-${hasta}</span> de <span class="text-white fw-bold">${total_productos}</span> productos`);
        
        // Generar botones de paginación
        let html = '';
        
        // Botón anterior
        html += `<li class="page-item ${pagina_actual === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-pagina="${pagina_actual - 1}" aria-label="Anterior">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>`;
        
        // Botones numéricos (mostrar máximo 5)
        let inicio = Math.max(1, pagina_actual - 2);
        let fin = Math.min(total_paginas, pagina_actual + 2);
        
        if (inicio > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-pagina="1">1</a></li>`;
            if (inicio > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = inicio; i <= fin; i++) {
            html += `<li class="page-item ${i === pagina_actual ? 'active' : ''}">
                        <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                    </li>`;
        }
        
        if (fin < total_paginas) {
            if (fin < total_paginas - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-pagina="${total_paginas}">${total_paginas}</a></li>`;
        }
        
        // Botón siguiente
        html += `<li class="page-item ${pagina_actual === total_paginas ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-pagina="${pagina_actual + 1}" aria-label="Siguiente">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>`;
        
        $('#paginacionList').html(html);
        
        // Eventos de paginación
        $('#paginacionList .page-link[data-pagina]').off('click').on('click', function(e) {
            e.preventDefault();
            let nuevaPagina = $(this).data('pagina');
            if (nuevaPagina && nuevaPagina !== paginaActual && !$(this).parent().hasClass('disabled')) {
                paginaActual = nuevaPagina;
                cargarProductos(paginaActual);
            }
        });
    }
    
});

// ===== SISTEMA DE CARRITO DE VENTAS =====
let carritoActual = [];
let totalVenta = 0;
let clienteSeleccionado = null;

$(document).ready(function() {
    
    // Inicializar Select2 para clientes
    inicializarSelectClientes();
    
    // Cargar carrito si existe en sesión
    cargarCarritoDesdeSesion();
    
    // Evento para agregar producto (desde los botones de productos)
    $(document).on('click', '.btn-agregar-carrito', function() {
        if ($(this).is(':disabled')) return;
        
        const producto = {
            id: $(this).data('id'),
            codigo: $(this).data('codigo'),
            nombre: $(this).data('nombre'),
            precio: $(this).data('precio'),
            stock: $(this).data('stock')
        };
        
        agregarProductoCarrito(producto);
    });
    
    // Evento personalizado para agregar producto
    $(document).on('productoAgregadoVenta', function(e, producto) {
        agregarProductoCarrito(producto);
    });
    
    // Procesar venta
    $('#btnProcesarVenta').click(function() {
        procesarVenta();
    });
    
    // Limpiar carrito
    $('#btnLimpiarCarrito').click(function() {
        limpiarCarrito();
    });
    
    // Cambiar tipo de pago
    $('#tipoPago').change(function() {
        if ($(this).val() === 'Crédito') {
            Swal.fire({
                icon: 'info',
                title: 'Venta a Crédito',
                text: 'El cliente tendrá 7 días para pagar',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
    
    // Cambiar moneda
    $('#monedaPago').change(function() {
        actualizarTotalesMoneda();
    });
    
    // Guardar nuevo cliente rápido
    $('#btnGuardarCliente').click(function() {
        guardarNuevoCliente();
    });
});

// ===== FUNCIONES DEL CARRITO =====

function agregarProductoCarrito(producto) {
    $.ajax({
        url: '../database/procesar_venta.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify({
            accion: 'agregar_carrito',
            producto_id: producto.id,
            cantidad: 1,
            precio: producto.precio
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                carritoActual = response.carrito;
                totalVenta = response.total_venta;
                
                actualizarVistaCarrito();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Agregado',
                    text: `${producto.nombre} - $ ${producto.precio}`,
                    timer: 1500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
                
                // Mostrar carrito flotante
                $('#carritoFlotante').fadeIn();
                
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo agregar el producto'
            });
        }
    });
}

function cargarCarritoDesdeSesion() {
    $.ajax({
        url: '../database/procesar_venta.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify({ accion: 'obtener_carrito' }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                carritoActual = response.carrito || [];
                totalVenta = response.total_venta || 0;
                
                if (carritoActual.length > 0) {
                    actualizarVistaCarrito();
                    $('#carritoFlotante').show();
                }
            }
        }
    });
}

function actualizarVistaCarrito() {
    // Actualizar tabla del modal
    let tbody = $('#carritoBody');
    tbody.empty();
    
    if (carritoActual.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-white-50 mb-3"></i>
                    <p class="text-white-50">El carrito está vacío</p>
                </td>
            </tr>
        `);
        $('#carritoFlotante').fadeOut();
    } else {
        carritoActual.forEach(function(item) {
            tbody.append(`
                <tr>
                    <td>${item.codigo || 'N/A'}</td>
                    <td>${item.nombre}</td>
                    <td>$ ${parseFloat(item.precio).toFixed(2)}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-light btn-cantidad" 
                                    onclick="actualizarCantidad(${item.id}, ${item.cantidad - 1})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-control form-control-sm mx-2 cantidad-input" 
                                   value="${item.cantidad}" min="1" max="999" 
                                   onchange="actualizarCantidad(${item.id}, this.value)"
                                   style="width: 60px;">
                            <button class="btn btn-sm btn-outline-light btn-cantidad"
                                    onclick="actualizarCantidad(${item.id}, ${item.cantidad + 1})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td>$ ${parseFloat(item.subtotal).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="eliminarItem(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }
    
    // Calcular y mostrar totales
    let subtotal = totalVenta;
    let iva = subtotal * 0.16;
    let total = subtotal + iva;
    
    $('#totalSinIVA').text('$ ' + subtotal.toFixed(2));
    $('#totalIVA').text('$ ' + iva.toFixed(2));
    $('#totalVenta').text('$ ' + total.toFixed(2));
    
    // Actualizar badge y total flotante
    $('#carritoBadge').text(carritoActual.length);
    $('#carritoTotalFlotante').text('$ ' + subtotal.toFixed(2));
    
    // Actualizar resumen flotante
    actualizarResumenFlotante();
}

function actualizarResumenFlotante() {
    let resumen = $('#carritoResumen');
    resumen.empty();
    
    if (carritoActual.length > 0) {
        let primeros = carritoActual.slice(0, 3);
        primeros.forEach(function(item) {
            resumen.append(`
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-truncate" style="max-width: 180px;">${item.nombre}</span>
                    <span class="text-white">x${item.cantidad}</span>
                </div>
            `);
        });
        
        if (carritoActual.length > 3) {
            resumen.append(`
                <div class="text-white-50 small text-center mt-1">
                    +${carritoActual.length - 3} productos más
                </div>
            `);
        }
    } else {
        resumen.html('<p class="text-white-50 small mb-1 text-center">Carrito vacío</p>');
    }
}

function actualizarCantidad(productoId, cantidad) {
    if (cantidad < 1) return;
    
    $.ajax({
        url: '../database/procesar_venta.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify({
            accion: 'actualizar_cantidad',
            producto_id: productoId,
            cantidad: parseInt(cantidad)
        }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                carritoActual = response.carrito;
                totalVenta = response.total_venta;
                actualizarVistaCarrito();
                if ($('#monedaPago').val() === 'bolivares') {
                    actualizarPreciosBolivares();
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        }
    });
}

function eliminarItem(productoId) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: 'El producto será removido del carrito',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d1001b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../database/procesar_venta.php',
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({
                    accion: 'eliminar_item',
                    producto_id: productoId
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        carritoActual = response.carrito;
                        totalVenta = response.total_venta;
                        actualizarVistaCarrito();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'Producto eliminado del carrito',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }
    });
}

function limpiarCarrito() {
    Swal.fire({
        title: '¿Limpiar carrito?',
        text: 'Se eliminarán todos los productos',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d1001b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../database/procesar_venta.php',
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({ accion: 'limpiar_carrito' }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        carritoActual = [];
                        totalVenta = 0;
                        actualizarVistaCarrito();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Carrito limpiado',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }
    });
}

// ===== FUNCIONES DE CLIENTES =====

function inicializarSelectClientes() {
    $('.select2-cliente').select2({
        dropdownParent: $('#modalCarritoVenta'),
        placeholder: 'Buscar cliente por nombre o cédula',
        allowClear: true,
        ajax: {
            url: '../database/procesar_venta.php',
            type: 'POST',
            dataType: 'json',
            data: function(params) {
                return JSON.stringify({
                    accion: 'buscar_clientes',
                    termino: params.term || ''
                });
            },
            processResults: function(response) {
                if (response.success) {
                    return {
                        results: response.clientes.map(function(cliente) {
                            return {
                                id: cliente.id,
                                text: `${cliente.nombre} - CI: ${cliente.cedula}`
                            };
                        })
                    };
                }
                return { results: [] };
            }
        }
    });
    
    $('.select2-cliente').on('select2:select', function(e) {
        clienteSeleccionado = e.params.data;
    });
}

function guardarNuevoCliente() {
    let cedula = $('#clienteCedula').val();
    let nombre = $('#clienteNombre').val();
    
    if (!cedula || !nombre) {
        Swal.fire('Error', 'Complete todos los campos', 'error');
        return;
    }
    
    $.ajax({
        url: '../database/crud_clientes.php', // Ajusta según tu archivo de clientes
        method: 'POST',
        data: {
            accion: 'crear',
            cedula: cedula,
            nombre: nombre
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', 'Cliente registrado correctamente', 'success');
                $('#modalNuevoCliente').modal('hide');
                $('#clienteCedula').val('');
                $('#clienteNombre').val('');
                
                // Recargar select
                $('.select2-cliente').append(new Option(`${nombre} - CI: ${cedula}`, response.id, true, true));
                $('.select2-cliente').trigger('change');
            }
        }
    });
}

// ===== PROCESAR VENTA =====

function procesarVenta() {
    // Validaciones
    if (carritoActual.length === 0) {
        Swal.fire('Error', 'El carrito está vacío', 'error');
        return;
    }
    
    let cliente_id = $('#selectCliente').val();
    if (!cliente_id) {
        Swal.fire('Error', 'Debe seleccionar un cliente', 'error');
        return;
    }
    
    Swal.fire({
        title: '¿Procesar venta?',
        html: `
            <div class="text-start">
                <p><strong>Total:</strong> $ ${totalVenta.toFixed(2)}</p>
                <p><strong>IVA (16%):</strong> $ ${(totalVenta * 0.16).toFixed(2)}</p>
                <p><strong>TOTAL:</strong> $ ${(totalVenta * 1.16).toFixed(2)}</p>
                <hr>
                <p><strong>Cliente:</strong> ${$('#selectCliente option:selected').text()}</p>
                <p><strong>Tipo pago:</strong> ${$('#tipoPago').val()}</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d1001b',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../database/procesar_venta.php',
                method: 'POST',
                dataType: 'json',
                data: JSON.stringify({
                    accion: 'registrar_venta',
                    cliente_id: cliente_id,
                    tipo_pago: $('#tipoPago').val(),
                    moneda_pago: $('#monedaPago').val(),
                    tasa_dolar: $('#tasaDolar').val()
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                                    icon: 'success',
                                    title: '¡Venta registrada!',
                                    html: `
                                        <div class="text-center">
                                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                            <p class="fs-4">N° de venta: <strong>${response.venta_id}</strong></p>
                                            <p class="fs-5">Total: $ ${response.total.toFixed(2)}</p>
                                        </div>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonColor: '#d1001b',
                                    confirmButtonText: '<i class="fas fa-file-invoice me-2"></i> Ver Factura',
                                    cancelButtonText: '<i class="fas fa-times me-2"></i> Cerrar',
                                    cancelButtonColor: '#6c757d',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // ✅ FUNCIÓN CORREGIDA - Abre factura en nueva ventana
                                        abrirFactura(response.venta_id);
                                    }
                                });
                        
                        // Limpiar carrito y cerrar modal
                        carritoActual = [];
                        totalVenta = 0;
                        actualizarVistaCarrito();
                        $('#modalCarritoVenta').modal('hide');
                        
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo procesar la venta', 'error');
                }
            });
        }
    });
}

function actualizarTotalesMoneda() {
    let moneda = $('#monedaPago').val();
    if (moneda === 'bolivares') {
        $('#divTasaDolar').show();
        fetch('https://api.exchangerate-api.com/v4/latest/USD')
            .then(response => response.json())
            .then(data => {
                $('#tasaDolar').val(data.rates.VES);
                actualizarPreciosBolivares();
            })
            .catch(error => console.error('Error:', error));
    } else {
        $('#divTasaDolar').hide();
        actualizarPreciosDolares();
    }
}

function actualizarPreciosBolivares() {
    let tasa = parseFloat($('#tasaDolar').val());
    if (!tasa || tasa <= 0) return;

    // Actualizar totales
    let subtotal = totalVenta * tasa;
    let iva = subtotal * 0.16;
    let total = subtotal + iva;

    $('#totalSinIVA').text('Bs ' + subtotal.toFixed(2));
    $('#totalIVA').text('Bs ' + iva.toFixed(2));
    $('#totalVenta').text('Bs ' + total.toFixed(2));
    $('#carritoTotalFlotante').text('Bs ' + subtotal.toFixed(2));
}

function actualizarPreciosDolares() {
    // Restaurar totales en dólares
    let subtotal = totalVenta;
    let iva = subtotal * 0.16;
    let total = subtotal + iva;

    $('#totalSinIVA').text('$ ' + subtotal.toFixed(2));
    $('#totalIVA').text('$ ' + iva.toFixed(2));
    $('#totalVenta').text('$ ' + total.toFixed(2));
    $('#carritoTotalFlotante').text('$ ' + subtotal.toFixed(2));
}

// Actualizar precios al cambiar tasa manualmente
$('#tasaDolar').on('input', function() {
    if ($('#monedaPago').val() === 'bolivares') {
        actualizarPreciosBolivares();
    }
});


// Inicializar al cargar
$(document).ready(function() {
    actualizarTotalesMoneda();
});

// ===== FUNCIONES PARA VER FACTURA =====

/**
 * Abrir factura en nueva ventana (desde venta exitosa)
 */
function abrirFactura(ventaId) {
    window.open(`factura.php?id=${ventaId}`, '_blank', 'width=900,height=700,scrollbars=yes');
}

/**
 * Ver factura en modal (desde historial)
 */
function verFactura(ventaId) {
    $('#modalVerFactura').modal('show');
    
    $.ajax({
        url: 'get_factura_detalle.php',
        method: 'GET',
        data: { id: ventaId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarDetalleFactura(response);
            } else {
                $('#contenedorDetalleFactura').html(`
                    <div class="text-center py-5 text-danger">
                        <i class="fas fa-exclamation-triangle fa-4x mb-3"></i>
                        <h4>Error</h4>
                        <p>${response.message}</p>
                    </div>
                `);
            }
        },
        error: function() {
            $('#contenedorDetalleFactura').html(`
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-times-circle fa-4x mb-3"></i>
                    <h4>Error de conexión</h4>
                    <p>No se pudo cargar la factura</p>
                </div>
            `);
        }
    });
}

/**
 * Mostrar detalle de factura en el modal
 */
function mostrarDetalleFactura(data) {
    let v = data.venta;
    let productos = data.productos;
    let abonos = data.abonos || [];
    
    let html = `
        <div class="factura-detalle">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
                <div>
                    <h4 class="fw-bold" style="color: #d1001b;">FACTURA #${v.id}</h4>
                    <p class="mb-0 text-white-50">${v.fecha} - ${v.hora}</p>
                </div>
                <div>
                    <span class="badge ${v.estado_pago === 'Pagada' ? 'bg-success' : 'bg-warning text-dark'} p-3">
                        <i class="fas ${v.estado_pago === 'Pagada' ? 'fa-check-circle' : 'fa-clock'} me-2"></i>
                        ${v.estado_pago}
                    </span>
                    <span class="badge bg-secondary p-3 ms-2">
                        <i class="fas fa-credit-card me-2"></i>
                        ${v.tipo_pago}
                    </span>
                </div>
            </div>
            
            <!-- CLIENTE Y VENDEDOR -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="p-3" style="background: rgba(209,0,27,0.1); border-radius: 10px;">
                        <h6 class="fw-bold" style="color: #d1001b;">
                            <i class="fas fa-user me-2"></i> CLIENTE
                        </h6>
                        <p class="mb-1 text-white"><strong>${v.cliente_nombre}</strong></p>
                        <p class="mb-0 text-white-50">C.I: ${v.cliente_cedula}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3" style="background: rgba(255,255,255,0.05); border-radius: 10px;">
                        <h6 class="fw-bold" style="color: #d1001b;">
                            <i class="fas fa-user-tie me-2"></i> VENDEDOR
                        </h6>
                        <p class="mb-1 text-white"><strong>${v.vendedor}</strong></p>
                        <p class="mb-0 text-white-50">ID: ${v.id}</p>
                    </div>
                </div>
            </div>
            
            <!-- PRODUCTOS -->
            <h6 class="fw-bold mb-3" style="color: #d1001b;">
                <i class="fas fa-box me-2"></i> PRODUCTOS
            </h6>
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead style="background: #d1001b;">
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    productos.forEach(p => {
        html += `
            <tr>
                <td>${p.codigo}</td>
                <td>${p.nombre}</td>
                <td class="text-center">${p.cantidad}</td>
                <td class="text-end">$ ${p.precio.toFixed(2)}</td>
                <td class="text-end">$ ${p.subtotal.toFixed(2)}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <!-- TOTALES -->
            <div class="row mt-4">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-sm table-borderless text-white">
                        <tr>
                            <td>SUBTOTAL:</td>
                            <td class="text-end">$ ${v.subtotal.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td>IVA (16%):</td>
                            <td class="text-end">$ ${v.iva.toFixed(2)}</td>
                        </tr>
                        <tr style="border-top: 2px solid #d1001b;">
                            <td class="fw-bold fs-5">TOTAL:</td>
                            <td class="text-end fw-bold fs-5" style="color: #d1001b;">
                                $ ${v.total.toFixed(2)}
                            </td>
                        </tr>
    `;
    
    if (v.moneda_pago === 'dolares' && v.tasa_dolar > 0) {
        html += `
            <tr>
                <td>TASA BCV:</td>
                <td class="text-end">Bs ${v.tasa_dolar.toFixed(2)}</td>
            </tr>
            <tr>
                <td>TOTAL EN DÓLARES:</td>
                <td class="text-end">$ ${(v.total / v.tasa_dolar).toFixed(2)}</td>
            </tr>
        `;
    }
    
    html += `
                    </table>
                </div>
            </div>
    `;
    
    // INFORMACIÓN DE CRÉDITO
    if (v.tipo_pago === 'Crédito') {
        html += `
            <div class="mt-4 p-3" style="background: rgba(255,193,7,0.1); border-left: 4px solid #ffc107; border-radius: 5px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1" style="color: #ffc107;">
                            <i class="fas fa-clock me-2"></i> VENTA A CRÉDITO
                        </h6>
                        <p class="mb-0 small text-white-50">
                            Vence: <strong>${v.fecha_vencimiento}</strong>
                        </p>
                    </div>
                    <div class="text-end">
                        <p class="mb-0 text-white">Abonado: $ ${v.total_abonado.toFixed(2)}</p>
                        <p class="mb-0 fw-bold" style="color: #ffc107;">
                            Saldo: $ ${v.saldo_pendiente.toFixed(2)}
                        </p>
                    </div>
                </div>
    `;
    
    if (abonos.length > 0) {
        html += `
            <div class="mt-3">
                <p class="mb-2 small fw-bold text-white-50">ABONOS REALIZADOS:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-dark table-borderless small">
                        <thead>
                            <tr style="border-bottom: 1px solid #ffc107;">
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        abonos.forEach(a => {
            html += `
                <tr>
                    <td class="text-white-50">${a.fecha}</td>
                    <td class="text-white">$ ${a.monto.toFixed(2)}</td>
                    <td class="text-white-50">${a.usuario}</td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    html += `</div>`;
    }
    
    html += `
            <div class="mt-4 text-center text-white-50 small">
                <i class="fas fa-qrcode me-2"></i>
                Gracias por su compra en Roʞka Sports
            </div>
        </div>
    `;
    
    $('#contenedorDetalleFactura').html(html);
    
    // Configurar botón de impresión
    $('#btnImprimirFactura').off('click').on('click', function() {
        window.open(`factura.php?id=${data.venta.id.replace(/\D/g, '')}`, '_blank');
    });
}

