$(document).ready(function() {
    // ✅ SweetAlert TOAST - IGUAL USUARIO/PROVEEDOR
    function mostrarToast(mensaje, tipo = 'success', timer = 3000) {
        const icono = tipo === 'success' ? '✅' : tipo === 'error' ? '❌' : '⚠️';
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            icon: tipo === 'success' ? 'success' : tipo === 'error' ? 'error' : 'warning',
            title: `${icono} ${mensaje}`,
            customClass: {
                popup: 'animated bounceInRight',
                title: 'fw-bold'
            }
        });
    }

    // ✅ VALIDACIÓN FECHA - SOLO HOY
    function validarSoloHoy() {
        const fechaSeleccionada = new Date($('#fechaPago').val() + 'T00:00:00');
        const fechaHoy = new Date();
        fechaHoy.setHours(0, 0, 0, 0);
        
        if (fechaSeleccionada.getTime() !== fechaHoy.getTime()) {
            mostrarToast('❌ Solo se permite fecha de HOY', 'error');
            return false;
        }
        return true;
    }

    function bloquearCalendarioSoloHoy() {
        const fechaHoy = new Date().toISOString().slice(0, 10);
        $('#fechaPago').attr({
            min: fechaHoy,
            max: fechaHoy,
            value: fechaHoy
        });
    }

    // ✅ DataTable PRINCIPAL (8 columnas con expandir)
   const tabla = $('#tablaComprasPendientes').DataTable({
    ajax: {
        url: '../api/finanzas_compras_pendientes.php',
        dataSrc: 'data'
    },
    columns: [
        { className: 'dt-control', orderable: false, data: null, defaultContent: '', width: "15px" },
        { data: 'id', width: "8%" },
        { data: 'proveedor_nombre', width: "20%" },
        { data: 'fecha_adquisicion', width: "12%" },
        { data: 'total', render: $.fn.dataTable.render.number(',', '.', 2, '$ '), width: "12%" },
        { data: 'saldo', render: $.fn.dataTable.render.number(',', '.', 2, '$ '), width: "12%" },
        { data: 'estado_pago', width: "12%" },
        {
            data: null,
            orderable: false,
            width: "12%",  // ✅ Ancho fijo
            className: 'never',  // ✅ NUNCA ocultar en responsive
            render: (data, type, row) => 
                `<button class="btn btn-success btn-sm btnPagar shadow-sm w-100" data-id="${row.id}" data-saldo="${row.saldo}" title="Registrar pago">
                    <i class="fas fa-money-bill-wave"></i>
                </button>`
        }
    ],
    // ✅ CONFIGURACIÓN RESPONSIVE CORREGIDA
    responsive: {
        details: {
            type: 'column',
            target: 0  // Expandir en columna 1 (ícono +)
        }
    },
    order: [[1, 'desc']],
        language: {
            emptyTable: "No hay créditos pendientes",
            info: "Mostrando _START_ a _END_ de _TOTAL_ créditos",
            infoEmpty: "No hay datos disponibles",
            lengthMenu: "Mostrar _MENU_ créditos",
            loadingRecords: "Cargando créditos...",
            processing: "Procesando...",
            search: "Buscar proveedor:",
            zeroRecords: "No se encontraron créditos pendientes",
            paginate: {
                first: "Primero",
                last: "Último", 
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        scrollX: true,
        responsive: true,
        pageLength: 10,
        dom: 'Bfrtilp',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel me-1"></i>Excel',
                className: 'btn btn-success btn-sm shadow-sm me-1',
                titleAttr: 'Exportar Excel'
            }
        ]
    });

    // ✅ EXPANDIR FILA - Historial pagos (5 columnas CON OBSERVACIONES)
    $('#tablaComprasPendientes tbody').on('click', 'td.dt-control', function() {
        const tr = $(this).closest('tr');
        const row = tabla.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            row.child(formatPagosTable()).show();
            tr.addClass('shown');

            const pagosTable = row.child().find('table.pagosTable').DataTable({
                ajax: {
                    url: '../api/get_pagos_compra.php',
                    type: 'GET',
                    data: { compra_id: row.data().id },
                    dataSrc: ''
                },
                columns: [
                    { data: 'id', width: "8%" },
                    { data: 'monto', render: $.fn.dataTable.render.number(',', '.', 2, '$ '), width: "20%" },
                    { data: 'fecha_pago', width: "18%" },
                    { data: 'metodo_pago', width: "22%" },
                    { data: 'observaciones', width: "32%", render: function(data) {
                        return data ? data.substring(0, 30) + (data.length > 30 ? '...' : '') : '-';
                    }}
                ],
                paging: false,
                searching: false,
                info: false,
                destroy: true,
                scrollX: true,
                language: {
                    emptyTable: "No hay pagos registrados"
                }
            });
        }
    });

    // ✅ TABLA EXPANDIDA CON 5 COLUMNAS (AGREGADA OBSERVACIONES)
    function formatPagosTable() {
        return `
            <div class="p-3 bg-dark bg-opacity-75 rounded mt-2">
                <h6 class="text-white mb-3"><i class="fas fa-history me-2"></i>Historial de Pagos</h6>
                <table class="table table-bordered table-sm table-dark pagosTable mb-0" style="font-size:0.85rem">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Observaciones</th> <!-- ✅ COLUMNA AGREGADA -->
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>`;
    }

    // ✅ MODAL PAGO
    const modalEl = document.getElementById('modalRegistrarPago');
    const modal = new bootstrap.Modal(modalEl);

    // Click botón Pagar
    $('#tablaComprasPendientes tbody').on('click', '.btnPagar', function() {
        const compraId = $(this).data('id');
        const saldo = parseFloat($(this).data('saldo'));

        if (saldo <= 0) {
            mostrarToast('Esta compra ya está pagada completamente', 'error');
            return;
        }

        // ✅ BLOQUEAR CALENDARIO SOLO HOY
        bloquearCalendarioSoloHoy();

        // Cargar datos modal
        $('#compraIdPago').val(compraId);
        $('#montoPago').attr('max', saldo).val('').attr('max', saldo);
        $('#maxMontoDisplay').text(saldo.toLocaleString('es-VE'));
        $('#metodoPago').val('');
        $('#obsPago').val('');
        $('#formRegistrarPago').removeClass('was-validated');
        $('#labelSaldoPendiente').html(`💰 Saldo pendiente: ${saldo.toLocaleString('es-VE', {style: 'currency', currency: 'VES'})}`);

        modal.show();
    });

    // ✅ FORM SUBMIT PAGO - VALIDACIÓN COMPLETA
    $('#formRegistrarPago').on('submit', function(e) {
        e.preventDefault();

        // ✅ VALIDAR FECHA SOLO HOY
        if (!validarSoloHoy()) {
            return;
        }

        const form = this;
        if (!form.checkValidity()) {
            $(form).addClass('was-validated');
            mostrarToast('Complete todos los campos requeridos', 'warning');
            return;
        }

        const monto = parseFloat($('#montoPago').val());
        const maxMonto = parseFloat($('#montoPago').attr('max'));
        const metodo = $('#metodoPago').val();

        if (monto <= 0 || monto > maxMonto) {
            mostrarToast('❌ Monto inválido (0 < monto ≤ saldo pendiente)', 'error');
            return;
        }

        if (!metodo) {
            mostrarToast('Seleccione un método de pago', 'warning');
            return;
        }

        // Spinner loading
        Swal.fire({
            title: 'Procesando pago...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        const datosPago = {
            compra_id: $('#compraIdPago').val(),
            monto,
            fecha_pago: $('#fechaPago').val(),
            metodo_pago: metodo,
            observaciones: $('#obsPago').val()
        };

        $.ajax({
            url: '../api/finanzas_registrar_pago.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datosPago),
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    mostrarToast(`✅ Pago registrado! Nuevo saldo pendiente: ${resp.saldo.toLocaleString('es-VE', {style: 'currency', currency: 'VES'})}`, 'success', 4000);
                    modal.hide();
                    tabla.ajax.reload(null, false);
                } else {
                    mostrarToast(`❌ Error: ${resp.message || 'No se pudo registrar el pago'}`, 'error');
                }
            },
            error: function(xhr) {
                let msg = 'Error de conexión';
                try {
                    const error = JSON.parse(xhr.responseText);
                    msg = error.message || error.error || msg;
                } catch(e) {}
                mostrarToast(msg, 'error');
            }
        });
    });
});
