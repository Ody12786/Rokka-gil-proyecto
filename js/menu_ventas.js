$(document).ready(function () {
    console.log("🚀 menu_ventas.js CARGADO - VALIDACIÓN STOCK");

    let productosVenta = [];
    let chartMes = null;
    let chartDias = null;

    // SweetAlert2 Toast Config
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // ===== FUNCIÓN MOSTRAR ALERTA =====
    function mostrarAlerta(icono, titulo, mensaje, timer = 4000) {
        return Toast.fire({
            icon: icono,
            title: titulo,
            text: mensaje,
            timer: timer,
            timerProgressBar: true,
            toast: true,
            position: 'top-end'
        });
    }

    // DataTable configuración (sin cambios)
    $('#tablaVentas').DataTable({
        ajax: { url: '../eventosVenta/listar_ventas.php', dataSrc: 'data' },
        columns: [
            { data: 'id', width: "70px", className: 'text-center fw-bold' },
            { data: 'fecha_venta', width: "110px", render: data => data ? new Date(data).toLocaleDateString('es-VE') : '-' },
            { data: 'cliente_nombre', width: "180px" },
            { data: 'total', width: "100px", className: 'text-center fw-bold text-success', render: data => `$${parseFloat(data || 0).toFixed(2)}` },
           { 
    data: 'tipo_pago', 
    width: "100px", 
    render: data => {
        const tipo = (data || '').toString().toLowerCase().trim();
        return tipo === 'contado' || tipo === 'contado' ? 
            '<span class="badge bg-success"><i class="fas fa-cash-register me-1"></i>Contado</span>' : 
            '<span class="badge bg-warning text-dark"><i class="fas fa-credit-card me-1"></i>Crédito</span>';
    }
},
            { data: 'estado_pago', width: "90px", render: data => data === 'Pagada' ? '<span class="badge bg-success">Pagada</span>' : '<span class="badge bg-danger">Pendiente</span>' },
            { data: 'usuario_nombre', width: "120px" },
            {
                data: 'id', width: "140px", orderable: false, searchable: false, className: 'text-center',
                render: data => `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-sm btnVerFactura" data-idventa="${data}" title="Ver Factura">
                            <i class="fas fa-file-invoice"></i>
                        </button>
                    </div>`
            }
        ],
        language: {
            emptyTable: "No hay ventas registradas",
            info: "Mostrando _START_ a _END_ de _TOTAL_ ventas",
            infoEmpty: "Mostrando 0 a 0 de 0 ventas",
            infoFiltered: "(filtrado de _MAX_ ventas totales)",
            lengthMenu: "Mostrar _MENU_ registros por página",
            loadingRecords: "Cargando ventas...",
            processing: "Procesando...",
            search: "Buscar ventas:",
            zeroRecords: "No se encontraron ventas",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        },
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 10,
        scrollX: true,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel me-1"></i>Excel', className: 'btn btn-success me-2 px-3', title: 'Ventas_RoKa_' + new Date().toISOString().slice(0,10) },
            { text: '<i class="fas fa-file-pdf me-1"></i>PDF', className: 'btn btn-danger px-3', action: function(e, dt, node, config) { window.open('../vistas/exportar_ventas_fpdf.php', '_blank'); } }
        ]
    });

    // Ver Factura
    $(document).on('click', '.btnVerFactura', function() {
        const idVenta = $(this).data('idventa');
        $.ajax({
            url: 'factura.php',
            method: 'GET',
            data: { id: idVenta },
            success: function(response) {
                $('#contenidoFactura').html(response);
                $('#ver').on('click', function() {
                    window.open('factura.php?id=' + idVenta, '_blank');
                });
                new bootstrap.Modal(document.getElementById('modalFactura')).show();
            },
            error: function() {
                mostrarAlerta('error', 'Error', 'No se pudo cargar la factura.');
            }
        });
    });

    // ===== GRÁFICOS ESTADÍSTICOS DE VENTAS =====
$('#btnVerGraficoEstadistico').click(function() {
    console.log('🔥 CLICK ESTADÍSTICAS - DATOS REALES');
    
    // 1. Abrir modal
    $('#modalGraficoEstadistico').modal('show');
    
    setTimeout(() => {
        // 2. FUERZA altura contenedores
        $('.chart-container, [style*="position: relative"]').css({
            'height': '320px !important',
            'min-height': '320px !important'
        });
        
        // 3. Loading
        $('.chart-container').html(`
            <div class="text-center p-4 text-white" style="height:100%">
                <i class="fas fa-spinner fa-spin fa-3x mb-3 text-danger"></i>
                <div>Cargando datos reales...</div>
            </div>
        `);
        
        // 4. AJAX DATOS REALES
        $.ajax({
            url: '../eventosVenta/obtener_datos_graficos.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('✅ DATOS REALES:', data);
                
                // 5. VERIFICAR datos válidos
                if (!data.meses || !data.dias) {
                    console.error('❌ DATOS INVÁLIDOS:', data);
                    return mostrarError();
                }
                
                crearGraficosReales(data);
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX FAIL:', status, xhr.responseText?.substring(0, 100));
                mostrarError();
            }
        });
    }, 500);
});

// FUNCIÓN: Gráficos con datos REALES
function crearGraficosReales(data) {
    // MES - Primer contenedor
    const contMes = $('.chart-container').first();
    contMes.html('<canvas id="chartMes" style="height:300px;width:100%"></canvas>');
    
    // DÍAS - Segundo contenedor  
    const contDias = $('.chart-container').last();
    contDias.html('<canvas id="chartDias" style="height:300px;width:100%"></canvas>');
    
    setTimeout(() => {
        // BARRAS MES - DATOS REALES
        const canvasMes = document.getElementById('chartMes');
        if (canvasMes && data.meses.labels?.length > 0) {
            new Chart(canvasMes.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.meses.labels.slice(0, 6), // Máx 6 meses
                    datasets: [{
                        label: 'Ventas $',
                        data: data.meses.valores.slice(0, 6),
                        backgroundColor: '#d1001b',
                        borderColor: '#ff4757',
                        borderWidth: 3,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { color: '#fff' } },
                        x: { ticks: { color: '#fff' } }
                    }
                }
            });
            console.log('✅ BARRAS MES REALES:', data.meses.labels);
        }
        
        // LÍNEA DÍAS - DATOS REALES
        const canvasDias = document.getElementById('chartDias');
        if (canvasDias && data.dias.labels?.length > 0) {
            new Chart(canvasDias.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.dias.labels,
                    datasets: [{
                        label: 'Ventas $',
                        data: data.dias.valores,
                        borderColor: '#d1001b',
                        backgroundColor: 'rgba(209, 0, 27, 0.2)',
                        borderWidth: 4,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { color: '#fff' } },
                        x: { ticks: { color: '#fff' } }
                    }
                }
            });
            console.log('✅ LÍNEA DÍAS REALES:', data.dias.labels);
        }
        
        console.log('🎉 GRÁFICOS REALES CREADOS');
    }, 200);
}

// FUNCIÓN: Error elegante
function mostrarError() {
    $('.chart-container').html(`
        <div class="text-center p-4 text-danger" style="height:100%">
            <i class="fas fa-database fa-3x mb-3"></i>
            <div>Sin datos de ventas</div>
            <small class="text-muted">Registra primeras ventas</small>
        </div>
    `);
    console.log('⚠️ Sin datos - Error mostrado');
}



   

    // Select2 Clientes (sin cambios)
    $('#selectClienteNombre').select2({
        placeholder: "🔍 Escribe nombre del cliente",
        dropdownParent: $('#modalRegistrarVenta'),
        ajax: {
            url: '../eventosCliente/fetch_clientes.php',
            dataType: 'json',
            delay: 250,
            data: params => ({ term: params.term || '' }),
            processResults: response => ({
                results: (response?.data || []).map(item => ({
                    id: item.N_afiliacion || item.Cid || item.nombre,
                    text: `${item.nombre || ''} (${item.Cid || 'Sin cédula'})`,
                    cedula: item.Cid || ''
                }))
            }),
            cache: true
        }
    }).on('select2:select', function(e) {
        $('#cedulaCliente').val(e.params.data.cedula).addClass('is-valid');
    });

    // Tipo pago y moneda (sin cambios)
    $('#tipoPago').on('change', function() {
        if (this.value === 'credito') {
            $('#divFechaVencimiento, #divAbonos').slideDown(300);
            $('#fechaVencimiento').attr('required', true);
        } else {
            $('#divFechaVencimiento, #divAbonos').slideUp(300);
            $('#fechaVencimiento').removeAttr('required');
            $('#primerAbono, #segundoAbono').val('');
        }
    });

    $('#monedaPago').on('change', function() {
        const esBolivares = this.value === 'bolivares';
        $('#divPrecioDolar').toggle(esBolivares, 300);
        $('#precioDolar').prop('required', esBolivares);
        
        calcularSubtotalProducto();
    });

    // 🔥 CARGAR PRODUCTOS CON STOCK
    function cargarProductos(tipo = 'productos') {
        if ($('#codigoProducto').hasClass('select2-hidden-accessible')) {
            $('#codigoProducto').select2('destroy');
        }
        $('#codigoProducto').select2({
            placeholder: "🔍 Código o nombre (solo disponibles)",
            dropdownParent: $('#modalRegistrarVenta .modal-content'),
            minimumInputLength: 0,
            allowClear: true,
            width: '100%',
            ajax: {
                url: '../eventosProducto/fetch_productos_select2.php',
                dataType: 'json',
                delay: 250,
                data: params => ({ term: params.term || '', tipo }),
                processResults: response => ({
                    results: (response?.status === 'success' && response.data ? response.data.map(item => ({
                        id: item.id,
                        text: `${item.codigo || ''} - ${item.nombre || ''}${item.talla ? ` (${item.talla})` : ''} <strong class="text-success">[${item.stock || 0}]</strong>`,
                        codigo: item.codigo,
                        nombre: item.nombre,
                        stock: parseInt(item.stock) || 0,
                        talla: item.talla || '',
                        precio: parseFloat(item.precio) || 0
                    })) : [])
                }),
                cache: true
            },
            templateResult: data => !data.id ? data.text : $(`<span>${data.text}</span>`),
            templateSelection: data => !data.id ? data.text : $(`<span>${data.codigo || data.id} - ${data.nombre}</span>`)
        });
    }

    $('#codigoProducto').on('select2:select', function(e) {
        const data = e.params.data;
        if (data.precio > 0) {
            $('#precioUnitario').val(data.precio).addClass('is-valid');
        }
        // 🔥 MOSTRAR STOCK DISPONIBLE
        if (data.stock !== undefined) {
            $(this).attr('data-stock-disponible', data.stock);
            const stockHtml = `<small class="text-warning d-block mt-1">
                📦 Stock disponible: <strong>${data.stock}</strong>
            </small>`;
            $('#codigoProducto').after(stockHtml);
        }
        $('#subtotalProducto').html('$ 0.00');
    });

    // Calcular subtotal
    function calcularSubtotalProducto() {
        const cantidad = parseFloat($('#cantidadProducto').val()) || 0;
        const precio = parseFloat($('#precioUnitario').val()) || 0;
        const moneda = $('#monedaPago').val();
        const tasa = parseFloat($('#precioDolar').val()) || 0;
        let subtotal = 0, simbolo = '$';

        if (moneda === 'bolivares' && tasa > 0) {
            subtotal = cantidad * precio * tasa * 1.16;
            simbolo = 'Bs';
        } else if (moneda === 'dolares') {
            subtotal = cantidad * precio * 1.16;
        }
        $('#subtotalProducto').html(`${simbolo} ${subtotal.toFixed(2)}`);
        return subtotal;
    }

    $('#cantidadProducto, #precioUnitario, #monedaPago, #precioDolar').on('input change', calcularSubtotalProducto);

    // 🔥 AGREGAR PRODUCTO CON VALIDACIÓN STOCK
    $('#btnAgregarProducto').on('click', function() {
        const idProd = $('#codigoProducto').val();
        const dataProducto = $('#codigoProducto').select2('data')[0];
        const nombreProd = dataProducto?.nombre || '';
        const stockDisponible = parseInt(dataProducto?.stock) || 0;
        const cantidad = parseInt($('#cantidadProducto').val()) || 0;
        const precio = parseFloat($('#precioUnitario').val()) || 0;
        const moneda = $('#monedaPago').val();
        const tasa = parseFloat($('#precioDolar').val()) || 0;

        // VALIDACIONES
        if (!idProd) {
            mostrarAlerta('warning', 'Producto', 'Selecciona un producto válido.');
            return;
        }
        if (cantidad <= 0) {
            mostrarAlerta('warning', 'Cantidad', 'Debe ser mayor a 0.');
            return;
        }
        if (precio <= 0) {
            mostrarAlerta('warning', 'Precio', 'Debe ser mayor a 0.');
            return;
        }
        if (moneda === 'bolivares' && tasa <= 0) {
            mostrarAlerta('warning', 'Tasa Dólar', 'Requerida para bolívares.');
            return;
        }

        // 🔥 VALIDACIÓN STOCK CRÍTICA ✅
        if (cantidad > stockDisponible) {
            Swal.fire({
                icon: 'error',
                title: '¡STOCK INSUFICIENTE!',
                html: `
                    <div class="text-start">
                        <strong>Producto:</strong> ${nombreProd}<br>
                        <strong>Solicitado:</strong> ${cantidad}<br>
                        <strong>Disponible:</strong> <span class="text-danger">${stockDisponible}</span>
                    </div>
                `,
                confirmButtonColor: '#d1001b'
            });
            return;
        }

        if (productosVenta.some(p => p.id === idProd)) {
            mostrarAlerta('warning', 'Producto', 'Ya está agregado.');
            return;
        }

        const subtotal = moneda === 'bolivares' ? (cantidad * precio * tasa * 1.16) : (cantidad * precio * 1.16);
        productosVenta.push({ id: idProd, nombre: nombreProd, cantidad, precio, subtotal, moneda, stockDisponible });

        const simMoneda = moneda === 'bolivares' ? 'Bs' : '$';
        const fila = `
            <tr data-id="${idProd}">
                <td><strong>${idProd}</strong></td>
                <td>${nombreProd || 'Sin nombre'}</td>
                <td class="text-center">${cantidad}</td>
                <td class="text-end">$${precio.toFixed(2)}</td>
                <td class="text-end fw-bold text-success">${simMoneda} ${subtotal.toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btnEliminarProducto">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;

        $('#productosVentaTable tbody').append(fila);
        calcularTotalVenta();
        limpiarCamposProducto();
        mostrarAlerta('success', '¡Perfecto!', `Producto agregado. Stock restante: ${stockDisponible - cantidad}`);
    });

    $(document).on('click', '.btnEliminarProducto', function() {
        const id = $(this).closest('tr').data('id');
        productosVenta = productosVenta.filter(p => p.id !== id);
        $(this).closest('tr').remove();
        calcularTotalVenta();
        mostrarAlerta('info', 'Eliminado', 'Producto removido.');
    });

    function calcularTotalVenta() {
        const total = productosVenta.reduce((acc, p) => acc + p.subtotal, 0);
        $('#totalVentaBs').html(`<strong>$${total.toFixed(2)}</strong>`);
    }

    function limpiarCamposProducto() {
        $('#codigoProducto').val(null).trigger('change');
        $('#cantidadProducto').val(1);
        $('#precioUnitario').val('').removeClass('is-valid');
        $('#subtotalProducto').html('$ 0.00');
        // Limpiar info stock
        $('#codigoProducto').nextAll('.text-warning').first().remove();
    }

    // Abonos (sin cambios)
    $('#abono_50_50, #abono_25_75').on('click', function() {
        const total = productosVenta.reduce((acc, p) => acc + p.subtotal, 0);
        if (this.id === 'abono_50_50') {
            $('#primerAbono').val(`$${ (total * 0.5).toFixed(2) }`);
            $('#segundoAbono').val(`$${ (total * 0.5).toFixed(2) }`);
        } else {
            $('#primerAbono').val(`$${ (total * 0.25).toFixed(2) }`);
            $('#segundoAbono').val(`$${ (total * 0.75).toFixed(2) }`);
        }
        mostrarAlerta('info', 'Abonos', `${this.id === 'abono_50_50' ? '50%/50%' : '25%/75%'} aplicado.`);
    });

    // Submit formulario (sin cambios en validación stock)
    $('#formRegistrarVenta').on('submit', function(e) {
        e.preventDefault();
        if (!$('#selectClienteNombre').val()) return mostrarAlerta('warning', 'Cliente', 'Debe seleccionar un cliente.');
        if (productosVenta.length === 0) return mostrarAlerta('warning', 'Productos', 'Debe agregar al menos un producto.');
        if (!$('#tipoPago').val()) return mostrarAlerta('warning', 'Pago', 'Seleccione tipo de pago.');
        if ($('#tipoPago').val() === 'credito' && !$('#fechaVencimiento').val()) return mostrarAlerta('warning', 'Crédito', 'Fecha de vencimiento requerida.');
        if ($('#monedaPago').val() === 'bolivares' && !$('#precioDolar').val()) return mostrarAlerta('warning', 'Tasa', 'Tasa del dólar requerida.');

        const total = productosVenta.reduce((acc, p) => acc + p.subtotal, 0);
        if (total <= 0) return mostrarAlerta('error', 'Total', 'El total debe ser mayor a 0.');

        const btnSubmit = $(this).find('button[type="submit"]');
        const originalText = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

        $.ajax({
            url: '../eventosVenta/guardar_ventas.php',
            type: 'POST',
            data: {
                cliente_id: $('#selectClienteNombre').val(),
                cedulaCliente: $('#cedulaCliente').val(),
                tipo_pago: $('#tipoPago').val(),
                fecha_vencimiento: $('#fechaVencimiento').val(),
                moneda_pago: $('#monedaPago').val(),
                tasa_dolar: $('#precioDolar').val(),
                total_venta: total,
                productos: JSON.stringify(productosVenta)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡VENTA REGISTRADA!',
                        text: `ID: ${response.idVenta}`,
                        confirmButtonColor: '#d1001b'
                    }).then(() => {
                        new bootstrap.Modal(document.getElementById('modalRegistrarVenta')).hide();
                        $('#tablaVentas').DataTable().ajax.reload(null, false);
                        resetModalVenta();
                    });
                } else {
                    mostrarAlerta('error', 'Error', response.error || 'Error desconocido.');
                }
            },
            error: function() {
                mostrarAlerta('error', 'Conexión', 'Error al guardar venta.');
            },
            complete: function() {
                btnSubmit.prop('disabled', false).html(originalText);
            }
        });
    });

    function resetModalVenta() {
        productosVenta = [];
        $('#productosVentaTable tbody').empty();
        $('#totalVentaBs').html('0.00');
        $('#formRegistrarVenta')[0].reset();
        $('#selectClienteNombre, #codigoProducto').val(null).trigger('change');
        $('#monedaPago').val('dolares').trigger('change');
        $('#divFechaVencimiento, #divAbonos, #divPrecioDolar').hide();
        $('.form-control, .form-select').removeClass('is-valid is-invalid');
    }

    $('#modalRegistrarVenta').on('hidden.bs.modal', resetModalVenta);
    $('#modalRegistrarVenta').on('shown.bs.modal', cargarProductos);

    console.log("✅ VALIDACIÓN STOCK IMPLEMENTADA");
});
