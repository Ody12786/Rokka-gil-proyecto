$(document).ready(function() {

    // Función para mostrar alertas Bootstrap con iconos y cierre automático
    function showAlert(message, type = 'success', duration = 3000) {
        const icons = {
            success: '<i class="bi bi-check-circle me-2"></i>',
            danger: '<i class="bi bi-x-octagon me-2"></i>',
            warning: '<i class="bi bi-exclamation-triangle me-2"></i>',
            info: '<i class="bi bi-info-circle me-2"></i>'
        };
        const icon = icons[type] || '';
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center" role="alert" style="min-width: 300px;">
                ${icon}<div>${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;

        const containerId = 'alert-container-distribucion';
        let container = document.getElementById(containerId);

        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.position = 'fixed';
            container.style.top = '80px';
            container.style.right = '25px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = alertHtml;
        container.appendChild(wrapper);

        const alertElem = bootstrap.Alert.getOrCreateInstance(wrapper.firstElementChild);

        setTimeout(() => {
            alertElem.close();
            setTimeout(() => wrapper.remove(), 500);
        }, duration);
    }

    // Inicializar DataTable con datos de API
    let table = $('#materiaTable').DataTable({
        ajax: {
            url: '../api/compras_distribucion_api.php',
            method: 'GET',
            dataSrc: function(json) {
                if (json.status === 'success') {
                    return json.data;
                }
                showAlert('Error cargando datos: ' + (json.message || ''), 'danger');
                return [];
            }
        },
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'categoria', title: 'Categoría' },
            { data: 'nombre', title: 'Nombre' },
            { data: 'cantidad', title: 'Cantidad' },
            { data: 'stock', title: 'Stock' },
            { data: 'tipo_tela', title: 'Tipo Tela' },
            { data: 'proveedor_nombre', title: 'Proveedor' },
            {
                data: null,
                title: 'Acciones',
                orderable: false,
                searchable: false,
                width: '140px',
                render: (data, type, row) => `
                    <button class="btn btn-primary btn-sm editar" data-id="${row.id}" data-categoria="${row.categoria}">Editar</button>
                    <button class="btn btn-danger btn-sm eliminar" data-id="${row.id}" data-categoria="${row.categoria}">Eliminar</button>`
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
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        },
        responsive: true,
        pageLength: 10,
        scrollX: true,
    });

    // Función para cargar proveedores en select del modal
    function cargarProveedoresDistribucion() {
        $.ajax({
            url: '../menu/proveedores_api.php',
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success' || resp.data) {
                    const $select = $('#proveedorDistribucion');
                    $select.empty().append('<option value="">-- Seleccione --</option>');
                    (resp.data || resp).forEach(prov => {
                        const nombre = prov.nombres || prov.nombre || 'Sin nombre';
                        $select.append(`<option value="${prov.id}">${nombre}</option>`);
                    });
                } else {
                    showAlert('Error al cargar proveedores.', 'danger');
                }
            },
            error: function() {
                showAlert('Error al cargar proveedores.', 'danger');
            }
        });
    }

    // Llamar carga proveedores al abrir modal productos a distribuir
    $('#btnNuevaCompraDistribucion').on('click', function() {
        cargarProveedoresDistribucion();
    });

    // Recarga tabla sin perder la página actual
    function recargarTabla() {
        table.ajax.reload(null, false);
    }

    // Variables para guardar ID y categoría al eliminar
    let idAEliminar = null;
    let categoriaAEliminar = null;

    // Delegar evento click para botón editar
    $('#materiaTable tbody').on('click', 'button.editar', function() {
        const id = $(this).data('id');
        const categoria = $(this).data('categoria');

        $.getJSON(`../api/compras_distribucion_api.php?id=${id}`, function(data) {
            if (data.status === 'success' && data.data.length) {
                const producto = data.data[0];
                console.log('Datos para editar:', producto);
                // Aquí mostrar modal y cargar campos con 'producto'
            } else {
                showAlert('No se encontró el producto para edición.', 'danger');
            }
        }).fail(function() {
            showAlert('Error al obtener datos para edición.', 'danger');
        });
    });

    // Delegar evento click para botón eliminar
    $('#materiaTable tbody').on('click', 'button.eliminar', function() {
        idAEliminar = $(this).data('id');
        categoriaAEliminar = $(this).data('categoria');
        new bootstrap.Modal(document.getElementById('confirmarEliminarModal')).show();
    });

    // Confirmar eliminación
    $('#btnConfirmarEliminar').on('click', function() {
        if (!idAEliminar || !categoriaAEliminar) return;

        $.ajax({
            url: '../api/compras_distribucion_api.php',
            method: 'DELETE',
            contentType: 'application/json',
            data: JSON.stringify({ id: idAEliminar }),
            dataType: 'json',
            success: function(resp) {
                bootstrap.Modal.getInstance(document.getElementById('confirmarEliminarModal')).hide();
                if (resp.status === 'success') {
                    showAlert(resp.message, 'success');
                    recargarTabla();
                } else {
                    showAlert('Error: ' + (resp.message || 'No se pudo eliminar'), 'danger');
                }
                idAEliminar = null;
                categoriaAEliminar = null;
            },
            error: function() {
                showAlert('Error de comunicación con la API.', 'danger');
                bootstrap.Modal.getInstance(document.getElementById('confirmarEliminarModal')).hide();
                idAEliminar = null;
                categoriaAEliminar = null;
            }
        });
    });

    // Limpiar variables al cerrar modal eliminación
    $('#confirmarEliminarModal').on('hidden.bs.modal', function() {
        idAEliminar = null;
        categoriaAEliminar = null;
    });

});
