
        $(document).ready(function () {

            // Función para mostrar alertas con SweetAlert (fallback a Bootstrap si no está disponible)
            function showAlert(message, type = 'success', duration = 3000) {
                // Usar el mismo estilo que en los módulos Usuario/Proveedor: títulos con emojis y Swal modal
                if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                    if (type === 'success') {
                        Swal.fire('✅', message, 'success');
                    } else if (type === 'danger') {
                        Swal.fire('❌', message, 'error');
                    } else if (type === 'warning') {
                        Swal.fire('⚠️', message, 'warning');
                    } else {
                        Swal.fire('ℹ️', message, 'info');
                    }
                    return;
                }

                // Fallback: Bootstrap alert si Swal no está disponible
                const icons = {
                    success: '<i class="bi bi-check-circle me-2"></i>',
                    danger: '<i class="bi bi-x-octagon me-2"></i>',
                    warning: '<i class="bi bi-exclamation-triangle me-2"></i>',
                    info: '<i class="bi bi-info-circle me-2"></i>'
                };
                const icon = icons[type] || '';
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center" role="alert">
                        ${icon}<div>${message}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                `;
                const container = document.getElementById('alert-container');
                if (container) {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = alertHtml;
                    container.appendChild(wrapper);
                    setTimeout(() => {
                        const alertElem = bootstrap.Alert.getOrCreateInstance(wrapper.firstElementChild);
                        alertElem.close();
                        setTimeout(() => wrapper.remove(), 500);
                    }, duration);
                }
            }

            // Función para llenar los selects de proveedores
            function llenarSelectProveedores() {
                $.ajax({
                    url: '../menu/proveedores_api.php',
                    dataType: 'json',
                    success: function (resp) {
                        if (resp.status === 'success' || resp.data) {
                            const $selects = $('#proveedor, #edit_proveedor');
                            $selects.empty().append('<option value="">-- Seleccione --</option>');
                            (resp.data || resp).forEach(prov => {
                                $selects.append(`<option value="${prov.id}">${prov.nombres || prov.nombre}</option>`);
                            });
                        } else {
                            showAlert('Error al cargar proveedores.', 'danger');
                        }
                    },
                    error: function () {
                        showAlert('Error al cargar proveedores.', 'danger');
                    }
                });
            }
            llenarSelectProveedores();

            // Inicializar DataTable y guardar la instancia
            const table = $('#compraTable').DataTable({
                ajax: {
                    url: '../api/compras_api.php',
                    method: 'GET',
                    dataSrc: function (json) {
                        if (json.status === 'success') return json.data;
                        showAlert('Error cargando datos: ' + (json.message || ''), 'danger');
                        return [];
                    }
                },
                columns: [
                    { data: 'proveedor_nombre', title: 'Proveedor' },
                    { data: 'fecha_adquisicion', title: 'Fecha' },
                    { data: 'tipo_tela', title: 'Tipo de Tela' },
                    { data: 'metros', title: 'Metros' },
                    {
                        data: 'precio_unitario',
                        title: 'PrecioUnitario',
                        render: function (data) {
                            return data ? '$' + parseFloat(data).toFixed(2) : '-';
                        }
                    },
                    { data: 'condicion_pago', title: 'Condición' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return `
                                <button class="btn btn-primary btn-sm editar" data-id="${row.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-danger btn-sm eliminar" data-id="${row.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                language: {
                    emptyTable: "No hay datos disponibles",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ compras",
                    lengthMenu: "Mostrar _MENU_ registros",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron registros",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                responsive: true,
                pageLength: 10,
                scrollX: true,
                dom: 'Bfrtilp',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        titleAttr: 'Exportar a Excel',
                        className: 'btn btn-success'
                    },
                    {
                        text: '<i class="fas fa-file-pdf me-1"></i>PDF',
                        className: 'btn btn-danger shadow-sm px-3',
                        action: function(e, dt, node, config) {
                            window.open('../vistas/exportar_compras_pdf.php', '_blank');
                        }
                    }
                   
                ]
            });

            // Refrescar tabla
            function cargarCompras() {
                table.ajax.reload(null, false);
            }

            //Graficar gastos
                        $('#btnIrFinanzas').on('click', function () {
    const modalEl = document.getElementById('gastosGraficoModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    // Crear gráfico sólo después de que el modal esté totalmente visible (evita problemas de tamaño)
    $(modalEl).off('shown.bs.modal.gastos').on('shown.bs.modal.gastos', function () {
        cargarGraficoGastos();
    });
});

                        function cargarGraficoGastos() {
  $.ajax({
    url: '../api/compras_gastos_api.php',
    method: 'GET',
    dataType: 'json',
    success: function (data) {
            if (typeof Chart === 'undefined') {
                showAlert('Chart.js no está cargado. Verifique la inclusión de la librería.', 'danger');
                return;
            }

            const canvas = document.getElementById('gastosComprasChart');
            if (!canvas || !canvas.getContext) {
                showAlert('Elemento canvas para gráfico no encontrado.', 'danger');
                return;
            }

            if (!data || !Array.isArray(data.labels) || data.labels.length === 0) {
                const ctxEmpty = canvas.getContext('2d');
                ctxEmpty.clearRect(0, 0, canvas.width, canvas.height);
                mostrarPlaceholderSinDatos(canvas, 'No hay datos para el periodo seleccionado.');
                return;
            }

            // Si todos los valores son cero, mostrar placeholder y ofrecer datos de ejemplo
            const allZero = Array.isArray(data.gastos) && data.gastos.every(v => Number(v) === 0);
            if (allZero) {
                const ctxEmpty = canvas.getContext('2d');
                ctxEmpty.clearRect(0, 0, canvas.width, canvas.height);
                mostrarPlaceholderSinDatos(canvas, 'Los gastos para este año son todos $0.');
                return;
            }

            const ctx = canvas.getContext('2d');
      if (window.gastosChart) {
        window.gastosChart.destroy();
      }
      window.gastosChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.labels,
          datasets: [{
            label: 'Gastos Mensuales',
            data: data.gastos,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    },
    error: function () {
      showAlert('Error al cargar el gráfico de gastos.', 'danger');
    }
  });
}

// Mostrar placeholder en el canvas cuando no hay datos
function mostrarPlaceholderSinDatos(canvas, mensaje) {
    const parent = canvas.parentElement;
    // eliminar placeholder previo si existe
    const prev = parent.querySelector('.placeholder-grafico');
    if (prev) prev.remove();

    const div = document.createElement('div');
    div.className = 'placeholder-grafico text-center p-4';
    div.style.width = '100%';
    div.style.minHeight = '140px';
    div.innerHTML = `
        <p class="mb-2">${mensaje}</p>
        <button type="button" id="btnEjemploGastos" class="btn btn-sm btn-outline-primary">Mostrar datos de ejemplo</button>
    `;
    parent.appendChild(div);

    const btn = document.getElementById('btnEjemploGastos');
    if (btn) {
        btn.addEventListener('click', function () {
            // datos de ejemplo para verificar renderizado
            const ejemplo = {
                labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
                gastos: [1200,800,1500,900,0,200,450,300,600,1100,700,500]
            };
            // eliminar placeholder
            div.remove();
            // renderizar chart con datos de ejemplo
            const ctx = canvas.getContext('2d');
            if (window.gastosChart) window.gastosChart.destroy();
            window.gastosChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ejemplo.labels,
                    datasets: [{
                        label: 'Gastos Mensuales (ejemplo)',
                        data: ejemplo.gastos,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        });
    }
}

            // Manejar envío formulario registrar compra con validación html5
            $('#formCompra').on('submit', function (e) {
                e.preventDefault();

                const form = this;
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }

                const datos = {
                    fecha_adquisicion: $('#fechaCompra').val(),
                    tipo_tela: $('#tipoTela').val(),
                    metros: parseFloat($('#metros').val()),
                    condicion_pago: $('input[name="condicionPago"]:checked').val(),
                    proveedor_id: $('#proveedor').val(),
                    precio_unitario: parseFloat($('#precioUnitario').val())
                };

                $.ajax({
                    url: '../api/compras_api.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(datos),
                    dataType: 'json',
                    success: function (resp) {
                        if (resp.status === 'success') {
                            showAlert('Compra registrada correctamente.', 'success');
                            $('#formCompra')[0].reset();
                            $(form).removeClass('was-validated');
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCompra'));
                            modal.hide();
                            cargarCompras();
                        } else {
                            showAlert('Error: ' + (resp.message || 'No fue posible registrar la compra.'), 'danger');
                        }
                    },
                    error: function () {
                        showAlert('Error en la comunicación con la API.', 'danger');
                    }
                });
            });

            // Manejar clic en botón editar para abrir modal con datos cargados
            $('#compraTable tbody').on('click', '.editar', function () {
                const row = table.row($(this).closest('tr'));
                let data = row.data();
                if (!data) {
                    showAlert('No se pudo obtener la información para editar.', 'danger');
                    return;
                }

                $('#edit_id').val(data.id);
                $('#edit_fecha').val(data.fecha_adquisicion);
                $('#edit_tipo_tela').val(data.tipo_tela);
                $('#edit_metros').val(data.metros);
                $('#edit_condicion').val(data.condicion_pago);
                $('#edit_proveedor').val(data.proveedor_id);
                $('#edit_precio_unitario').val(data.precio_unitario);

                const editModal = new bootstrap.Modal(document.getElementById('editarCompraModal'));
                editModal.show();
            });

            // Manejar envío formulario editar compra con validación html5
            $('#editarCompraForm').on('submit', function (e) {
                e.preventDefault();

                const form = this;
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(form).addClass('was-validated');
                    return;
                }

                const datos = {
                    id: $('#edit_id').val(),
                    fecha_adquisicion: $('#edit_fecha').val(),
                    tipo_tela: $('#edit_tipo_tela').val(),
                    metros: parseFloat($('#edit_metros').val()),
                    condicion_pago: $('#edit_condicion').val(),
                    proveedor_id: $('#edit_proveedor').val(),
                    precio_unitario: parseFloat($('#edit_precio_unitario').val())
                };

                $.ajax({
                    url: '../api/compras_api.php',
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify(datos),
                    dataType: 'json',
                    success: function (resp) {
                        if (resp.status === 'success') {
                            showAlert('Compra actualizada correctamente.', 'success');
                            $('#editarCompraForm')[0].reset();
                            $(form).removeClass('was-validated');
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editarCompraModal'));
                            modal.hide();
                            cargarCompras();
                        } else {
                            showAlert('Error: ' + (resp.message || 'No fue posible actualizar la compra.'), 'danger');
                        }
                    },
                    error: function () {
                        showAlert('Error en la comunicación con la API.', 'danger');
                    }
                });
            });

            // Abrir modal confirmar eliminación
            let idAEliminar = null;
            $('#compraTable tbody').on('click', '.eliminar', function () {
                idAEliminar = $(this).data('id');
                const modal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
                modal.show();
            });

            // Confirmar eliminación
            $('#btnConfirmarEliminar').on('click', function () {
                if (idAEliminar === null) return;

                $.ajax({
                    url: '../api/compras_api.php',
                    method: 'DELETE',
                    contentType: 'application/json',
                    data: JSON.stringify({ id: idAEliminar }),
                    dataType: 'json',
                    success: function (resp) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmarEliminarModal'));
                        modal.hide();
                        idAEliminar = null;

                        if (resp.status === 'success') {
                            showAlert('Compra eliminada correctamente.', 'success');
                            cargarCompras();
                        } else {
                            showAlert('Error: ' + (resp.message || 'No fue posible eliminar la compra.'), 'danger');
                        }
                    },
                    error: function () {
                        showAlert('Error en la comunicación con la API.', 'danger');
                    }
                });
            });
        });