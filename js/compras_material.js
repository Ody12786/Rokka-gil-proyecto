// ===== JS COMPLETO - MATERIA PRIMA CORREGIDO =====
$(document).ready(function() {
    // CDN SweetAlert2 (agrega en HTML antes de este script)
    // <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    // ===== VALIDACIONES VISUALES (ROJO/VERDE) =====
    function actualizarValidacion($input, esValido) {
        $input.removeClass('is-invalid is-valid');
        if (esValido && $input.val().trim().length > 0) {
            $input.addClass('is-valid');
        } else if ($input.val().trim().length > 0 && !esValido) {
            $input.addClass('is-invalid');
        }
    }

    // 1. VALIDAR NOMBRE MATERIA (PERMITE ESPACIOS)
    $('#nombreMateriaInput').on('input', function() {
        const $this = $(this);
        let valor = $this.val()
            .replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-_]/g, '')
            .replace(/\s{2,}/g, ' ')
            .trim();
        $this.val(valor);
        actualizarValidacion($this, valor.length >= 3 && valor.length <= 50);
    });

    // 2. VALIDAR PRECIO
    $('#precioUnitarioCompra').on('input', function() {
        const $this = $(this);
        const valor = parseFloat($this.val()) || 0;
        actualizarValidacion($this, valor >= 0.01 && valor <= 99999.99);
    });

    // 3. CALCULAR STOCK AUTOMÁTICO
    function calcularStock() {
        const cantidad = parseFloat($('#cantidadCompra').val()) || 0;
        const unidad = $('#unidadCompra').val();
        let stock = cantidad;
        const multiplicadores = {
            'docena': 12,
            'paquete_100': 100,
            'unidad': 1,
            'metro': 1,
            'rollo': 1
        };
        if (multiplicadores[unidad]) stock = cantidad * multiplicadores[unidad];
        $('#stockCompra').val(stock.toFixed(2));
    }
    $('#cantidadCompra, #unidadCompra').on('input change', calcularStock);

    // 4. CARGAR PROVEEDORES
    function llenarSelectProveedores() {
        $.ajax({
            url: '../menu/proveedores_api.php',
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success' && resp.data) {
                    const $select = $('#proveedorSelect');
                    $select.empty().append('<option value="" disabled selected>Seleccione proveedor</option>');
                    resp.data.forEach(prov => {
                        const nombre = prov.nombres || prov.nombre || "Proveedor";
                        $select.append(`<option value="${prov.id}">${nombre}</option>`);
                    });
                } else {
                    Swal.fire('Error', 'No se pudieron cargar proveedores', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    }
    llenarSelectProveedores();

    // 5. LIMPIAR MODAL AL CERRAR
    $('#modalNuevaCompra').on('hidden.bs.modal', function() {
        $('#formNuevaCompra')[0].reset();
        $('#compraId').val('');
        $('.form-control, .form-select').removeClass('is-valid is-invalid');
        $('#modalNuevaCompraLabel').text('Registrar Nueva Compra');
        $('#stockCompra').val('');
    });

    // 6. FORMULARIO PRINCIPAL CON SWEETALERT
    $('#formNuevaCompra').on('submit', function(e) {
        e.preventDefault();
        
        const errores = [];
        const proveedor = $('#proveedorSelect').val();
        const nombre = $('#nombreMateriaInput').val().trim();
        const cantidad = parseFloat($('#cantidadCompra').val());
        const unidad = $('#unidadCompra').val();
        const precio = parseFloat($('#precioUnitarioCompra').val());

        if (!proveedor) errores.push('Seleccione un proveedor');
        if (nombre.length < 3) errores.push('Nombre materia (mínimo 3 caracteres)');
        if (isNaN(cantidad) || cantidad < 0.01 || cantidad > 999999.99) errores.push('Cantidad entre 0.01 y 999,999.99');
        if (!unidad) errores.push('Seleccione unidad');
        if (isNaN(precio) || precio < 0.01 || precio > 99999.99) errores.push('Precio entre $0.01 y $99,999.99');

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: '❌ Errores en el formulario',
                html: errores.map(err => `• ${err}`).join('<br>')
            });
            return;
        }

        // ENVIAR AJAX
        const compraId = $('#compraId').val();
        const data = {
            id: compraId || null,
            nombre_materia: nombre,
            descripcion: $('#descripcionCompra').val().trim(),
            cantidad: cantidad,
            unidad: unidad,
            stock: parseFloat($('#stockCompra').val()),
            precio_unitario: precio,
            proveedor_id: proveedor || null
        };

        $.ajax({
            url: '../api/compras_material_api.php',
            method: compraId ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(resp) {
                if (resp.status === 'success') {
                    $('#modalNuevaCompra').modal('hide');
                    tabla.ajax.reload(null, false);
                    Swal.fire('¡Éxito!', compraId ? 'Material actualizado' : 'Material registrado', 'success');
                } else {
                    Swal.fire('Error', resp.message || 'Error al guardar', 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Error de conexión: ' + xhr.status, 'error');
            }
        });
    });

    // 7. DATATABLES COMPLETO
    const tabla = $('#tablaCompras').DataTable({
        ajax: {
            url: '../api/compras_material_api.php',
            method: 'GET',
            dataSrc: function(resp) {
                if (resp.status === 'success') return resp.data;
                Swal.fire('Error', 'No se pudieron cargar los datos', 'error');
                return [];
            }
        },
        columns: [
            { data: 'id' },
            { data: 'nombre_materia' },
            { data: 'descripcion', defaultContent: '' },
            { data: 'cantidad' },
            { data: 'unidad' },
            { data: 'stock' },
            { data: 'precio_unitario', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
            { data: 'fecha_compra' },
            { data: 'nombre_proveedor', defaultContent: 'Sin proveedor' },
            {
                data: null, 
                orderable: false, 
                searchable: false,
                render: function(data) {
                    return `
                        <button class="btn btn-primary btn-sm btn-editar me-1" data-id="${data.id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        
                    `;
                }
            }
        ],
        language: {
            emptyTable: "No hay datos disponibles",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            lengthMenu: "Mostrar _MENU_ registros",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "No se encontraron registros",
            paginate: { 
                first: '<i class="bi bi-chevron-double-left"></i>',
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>',
                last: '<i class="bi bi-chevron-double-right"></i>'
            }
        },
        responsive: true,
        pageLength: 10,
        dom: 'Bfrtilp',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success shadow-sm px-3'
            },
            {
                text: '<i class="fas fa-file-pdf me-1"></i>PDF',
                className: 'btn btn-danger shadow-sm px-3',
                action: function() {
                    window.open('../vistas/exportar_materia_prima.php', '_blank');
                }
            }
        ]
    });

    // 8. AJUSTAR RESPONSIVE
    tabla.on('draw', function() {
        tabla.columns.adjust().responsive.recalc();
    });

    // 9. EDITAR REGISTRO
    $('#tablaCompras tbody').on('click', '.btn-editar', function() {
        const data = tabla.row($(this).parents('tr')).data();
        if (!data) {
            Swal.fire('Error', 'No se pudieron cargar los datos', 'warning');
            return;
        }

        $('#modalNuevaCompraLabel').text('Editar Material');
        $('#compraId').val(data.id);
        $('#nombreMateriaInput').val(data.nombre_materia || '');
        $('#descripcionCompra').val(data.descripcion || '');
        $('#cantidadCompra').val(data.cantidad || 0);
        $('#unidadCompra').val(data.unidad || '').trigger('change');
        $('#stockCompra').val(data.stock || 0);
        $('#precioUnitarioCompra').val(data.precio_unitario || 0);
        $('#proveedorSelect').val(data.proveedor_id || '');
        
        $('#modalNuevaCompra').modal('show');
        calcularStock(); // Recalcular stock
    });

   /*  // 10. ELIMINAR CON SWEETALERT (CORREGIDO)
    $('#tablaCompras tbody').on('click', '.btn-eliminar', function() {
        const idCompra = $(this).data('id');
        
        Swal.fire({
            title: '¿Eliminar material?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d1001b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/compras_material_api.php',
                    method: 'DELETE',
                    contentType: 'application/json',
                    data: JSON.stringify({ id: idCompra }),
                    success: function(resp) {
                        if (resp.status === 'success') {
                            tabla.ajax.reload(null, false);
                            Swal.fire('Eliminado', 'Material eliminado correctamente', 'success');
                        } else {
                            Swal.fire('Error', resp.message || 'No se pudo eliminar', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Error de conexión: ' + xhr.status, 'error');
                    }
                });
            }
        });
    }); */
});
