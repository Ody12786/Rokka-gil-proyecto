$(document).ready(function() {

    // Mover modal y contenedor de toasts al body para evitar problemas de stacking/z-index
    try {
        $('#modalPagarAbono').appendTo(document.body);
        $('.toast-container').appendTo(document.body);
        console.log('DEBUG: modal y toast-container movidos a body');
    } catch (e) {
        console.error('DEBUG: error moviendo modal/toast al body', e);
    }

    // ========== TOAST NOTIFICATIONS ==========
    var $inputBuscarNombre = $('#buscarClienteNombre');
    var $listaResultados = $('#listaResultadosCliente');
    var $inputCedula = $('#cedulaCliente');
    var $clienteNombreTexto = $('#clienteNombre');

    function mostrarToast(mensaje, tipo = 'success') {
        const tipos = {
            success: { clase: 'bg-success text-dark border-success shadow', icono: 'bi bi-check-circle-fill' },
            error: { clase: 'bg-danger text-white border-danger shadow', icono: 'bi bi-x-circle-fill' },
            warning: { clase: 'bg-warning text-dark border-warning shadow', icono: 'bi bi-exclamation-triangle-fill' },
            info: { clase: 'bg-primary text-white border-primary shadow', icono: 'bi bi-info-circle-fill' }
        };
        
        const configTipo = tipos[tipo] || tipos.success;

        const toastHtml = `
            <div class="toast align-items-center border-0 ${configTipo.clase}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="${configTipo.icono} me-2 fs-5"></i>
                        ${mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        const $contenedor = $('.toast-container');
        $contenedor.append(toastHtml);

        const toastEl = $contenedor.find('.toast').last()[0];
        const toast = new bootstrap.Toast(toastEl, {
            animation: true,
            autohide: true,
            delay: 5000
        });
        
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', function () {
            $(this).remove();
        });
    }

    // ========== BUSCAR CLIENTES ==========
    function limpiarResultados() {
        $listaResultados.empty().hide();
    }

    // Debounce búsqueda por nombre
    let debounceTimer;
    $inputBuscarNombre.on('input', function() {
        const query = $(this).val().trim();

        limpiarResultados();
        $clienteNombreTexto.text('').css('color', '');

        if (query.length < 2) return; 

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            $.ajax({
                url: '/Roka_Sports/eventosCliente/buscar_cliente_nombre.php',
                method: 'GET',
                data: { nombre: query },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success' && resp.data.length > 0) {
                        resp.data.forEach(cliente => {
                            const $item = $('<a href="#" class="list-group-item list-group-item-action"></a>');
                            $item.text(cliente.nombre + " (C.I.: " + cliente.cedula + ")");
                            $item.data('cedula', cliente.cedula);
                            $item.data('nombre', cliente.nombre);
                            $listaResultados.append($item);
                        });
                        $listaResultados.show();
                    } else {
                        $listaResultados.append('<div class="list-group-item disabled">No se encontraron resultados</div>').show();
                    }
                },
                error: function() {
                    $listaResultados.append('<div class="list-group-item disabled">Error al buscar clientes</div>').show();
                }
            });
        }, 300);
    });

    // Seleccionar cliente de resultados
    $listaResultados.on('click', 'a.list-group-item-action', function(e) {
        e.preventDefault();

        const cedulaSeleccionada = $(this).data('cedula');
        const nombreSeleccionado = $(this).data('nombre');

        $inputCedula.val(cedulaSeleccionada).trigger('input');
        $clienteNombreTexto.text('Cliente: ' + nombreSeleccionado).css('color', 'green');

        limpiarResultados();
        $inputBuscarNombre.val('');
    });

    // Ocultar resultados al click fuera
    $(document).click(function(event) {
        if (!$(event.target).closest('#buscarClienteNombre, #listaResultadosCliente').length) {
            limpiarResultados();
        }
    });

    // ========== DATATABLES + MODAL ABONOS ==========
    var tabla = $('#tablaAbonosPendientes').DataTable({
        ajax: {
            url: '/Roka_Sports/eventosVenta/listar_ventas_pendientes.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id_venta' },
            { data: 'cliente_nombre' },
            {
                data: 'monto_total',
                render: $.fn.dataTable.render.number(',', '.', 2, '$ ')
            },
            {
                data: 'saldo_pendiente',
                render: $.fn.dataTable.render.number(',', '.', 2, '$ ')
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-primary btnPagarAbono" data-id="${row.id_venta}">Pagar</button>`;
                }
            }
        ],
        language: {
            emptyTable: "No hay cuentas pendientes",
            info: "Mostrando _START_ a _END_ de _TOTAL_ ventas",
            lengthMenu: "Mostrar _MENU_ ventas",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        scrollX: true,
        drawCallback: function() {
            bindModalEvents();
        }
    });

    // Modal Bootstrap 5
    var modal = new bootstrap.Modal(document.getElementById('modalPagarAbono'));

    // ✅ FUNCIÓN GLOBAL: Bind eventos después del AJAX
    function bindModalEvents() {
        $('.btnPagarAbono').off('click').on('click', function() {
            var idVenta = $(this).data('id');
            var fila = $(this).closest('tr');
            var dataVenta = tabla.row(fila).data();

            if (!dataVenta) {
                mostrarToast('Venta no encontrada.', 'error');
                return;
            }

            // Parsear valores numéricos
            var montoTotal = parseFloat(dataVenta.monto_total) || 0;
            var saldoPendiente = parseFloat(dataVenta.saldo_pendiente) || 0;

            // Llenar modal
            $('#modalVentaId').val(dataVenta.id_venta);
            $('#modalClienteNombre').val(dataVenta.cliente_nombre);
            $('#modalMontoTotal').val(montoTotal.toFixed(2));
            $('#modalSaldoPendiente').val(saldoPendiente.toFixed(2));
            $('#modalMontoPago').val(saldoPendiente.toFixed(2));
            $('#modalMontoPago').attr('max', saldoPendiente.toFixed(2));
            $('#modalMontoPago').attr('min', '0.01');

            // Limpiar validación (NO resetear el formulario aquí para no borrar los valores llenados)
            $('#formPagarAbono').removeClass('was-validated');

            // DEBUG: comprobar estado de los campos al abrir modal
            console.log('DEBUG abrir modal - idVenta:', dataVenta.id_venta);
            console.log('DEBUG modalMontoPago readonly?', $('#modalMontoPago').prop('readonly'));
            console.log('DEBUG modalMontoPago disabled?', $('#modalMontoPago').prop('disabled'));
            console.log('DEBUG modal backdrop/visible? modal element z-index:', $('#modalPagarAbono').css('z-index'));

            modal.show();
        });
    }

    // Bind inicial
    bindModalEvents();

    // ✅ FORM SUBMIT ABONO
    $('#formPagarAbono').on('submit', function(e) {
        e.preventDefault();

        var form = this;
        if (!form.checkValidity()) {
            e.stopPropagation();
            $(form).addClass('was-validated');
            return;
        }

        var idVenta = $('#modalVentaId').val();
        var montoPago = parseFloat($('#modalMontoPago').val()) || 0;
        var saldoPendiente = parseFloat($('#modalSaldoPendiente').val()) || 0;

        if (montoPago > saldoPendiente || montoPago <= 0) {
            mostrarToast('El monto a pagar debe ser mayor a 0 y no puede superar el saldo pendiente.', 'error');
            return;
        }

        $.ajax({
            url: '/Roka_Sports/eventosVenta/abonar_venta.php',
            method: 'POST',
            dataType: 'json',
            data: { id_venta: idVenta, monto_pago: montoPago },
            success: function(resp) {
                if (resp.status === 'success') {
                    mostrarToast('✅ Pago registrado exitosamente.', 'success');
                    modal.hide();
                    tabla.ajax.reload(null, false);
                } else {
                    mostrarToast('❌ Error: ' + (resp.message || 'Desconocido'), 'error');
                }
            },
            error: function(xhr) {
                mostrarToast('❌ Error conexión: ' + xhr.status, 'error');
            }
        });
    });

});
