$(document).ready(function () {
    // ✅ FUNCIONES DE VALIDACIÓN GLOBALES
    window.validarRIF = function(valor) { return /^[JGVE]-\d{8}-\d$/.test(valor); };
    window.validarNombres = function(valor) { 
        const cleaned = valor.trim(); 
        return cleaned.length >= 3 && /^[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]+$/.test(cleaned); 
    };
    window.validarEmpresa = function(valor) { 
        const cleaned = valor.trim(); 
        return cleaned.length >= 3 && /^[A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-]+$/.test(cleaned); 
    };
    window.validarContacto = function(valor) { return /^\d{7,11}$/.test(valor); };
    window.actualizarEstadoValidacion = function($input, esValido) {
        $input.removeClass('is-invalid is-valid');
        if (esValido && $input.val().trim().length > 0) $input.addClass('is-valid');
        else if ($input.val().trim().length > 0 && !esValido) $input.addClass('is-invalid');
    };

    // 🔹 FUNCIONES ELIMINAR VISUAL (LOCALSTORAGE PERMANENTE)
    function normalizeRif(rif) {
        return String(rif || '').toUpperCase().trim();
    }

    function getEliminados() {
        const raw = JSON.parse(localStorage.getItem('proveedoresEliminados') || '[]');
        const norm = Array.from(new Set(raw.map(normalizeRif).filter(x => x)));
        localStorage.setItem('proveedoresEliminados', JSON.stringify(norm));
        return norm;
    }

    function agregarEliminado(rif) {
        const normRif = normalizeRif(rif);
        let eliminados = getEliminados();
        if (!eliminados.includes(normRif)) {
            eliminados.push(normRif);
            localStorage.setItem('proveedoresEliminados', JSON.stringify(eliminados));
        }
    }

    function actualizarContadorEliminados() {
        const eliminados = getEliminados();
        $('#countEliminados').text(eliminados.length);
        return eliminados;
    }

    // Inicializar contador
    actualizarContadorEliminados();

    // DataTable - FILTRA ELIMINADOS DESDE CARGA
    var table = $("#proveeTable").DataTable({
        ajax: {
            url: "../eventosProveedor/fetch_proveedor.php",
            dataSrc: function(json) {
                const eliminados = getEliminados();
                if (!json || !Array.isArray(json.data)) return [];
                return json.data.filter(function(item) {
                    return !eliminados.includes(normalizeRif(item.rif));
                });
            }
        },
        language: {
            emptyTable: "No hay datos disponibles en la tabla",
            info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            infoEmpty: "Mostrando 0 a 0 de 0 entradas",
            lengthMenu: "Mostrar _MENU_ entradas",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "No se encontraron registros coincidentes",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior",
            },
        },
        responsive: true,
        pageLength: 10,
        dom: "Bfrtilp",
        buttons: [
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel"></i> Excel',
                titleAttr: "Exportar a Excel",
                className: "btn btn-success",
            },
            {
                text: '<i class="fas fa-file-pdf me-1"></i>PDF',
                className: 'btn btn-danger shadow-sm px-3',
                action: function(e, dt, node, config) {
                    window.open('../vistas/exportar_proveedores_pdf.php', '_blank');
                }
            }
        ],
        columns: [
            { data: "rif" },
            { data: "nombres" },
            { data: "empresa" },
            { data: "direccion" },
            { data: "fecha_creación" },
            { data: "contacto" },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary btnEditar me-1" data-rif="${row.rif}" title="Editar ${row.rif}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btnEliminar" data-rif="${row.rif}" title="Eliminar ${row.rif}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                },
            },
        ],
    });

    // 🗑️ ELIMINAR VISUAL (PERMANENTE LOCALSTORAGE)
    $("#proveeTable tbody").on("click", ".btnEliminar", function () {
        const rif = $(this).data("rif");
        const row = table.row($(this).parents('tr'));
        
        Swal.fire({
            title: '🗑️ Eliminar proveedor',
            text: `El proveedor ${rif} se eliminará permanentemente de esta vista`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // ❌ ELIMINAR FILA VISUALMENTE
                row.remove().draw(false);
                
                // 💾 GUARDAR EN LOCALSTORAGE
                agregarEliminado(rif);
                actualizarContadorEliminados();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Proveedor eliminado',
                    text: `${rif} ya no aparecerá en la lista`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });

    // ✅ EDITAR PROVEEDOR
    $("#proveeTable tbody").on("click", ".btnEditar", function () {
        var data = table.row($(this).parents("tr")).data();
        $('#edit_nombres, #edit_empresa, #edit_direccion, #edit_contacto').removeClass('is-valid is-invalid');
        $("#mensajeEditar").text("");
        $("#edit_rif_original").val(data.rif);
        $("#edit_rif").val(data.rif);
        $("#edit_nombres").val(data.nombres);
        $("#edit_empresa").val(data.empresa);
        $("#edit_direccion").val(data.direccion);
        $("#edit_contacto").val(data.contacto);
        window.actualizarEstadoValidacion($('#edit_nombres'), window.validarNombres(data.nombres));
        window.actualizarEstadoValidacion($('#edit_empresa'), window.validarEmpresa(data.empresa));
        window.actualizarEstadoValidacion($('#edit_contacto'), window.validarContacto(data.contacto));
        var modalEditar = new bootstrap.Modal(document.getElementById("modalEditarProveedor"));
        modalEditar.show();
    });

    // ✅ FORM EDITAR PROVEEDOR
    $("#formEditarProveedor").on("submit", function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var $spinner = $btn.find('.spinner-border');
        $spinner.removeClass('d-none');
        $btn.prop('disabled', true);
        var formData = {
            rif_original: $("#edit_rif_original").val(),
            nombres: $("#edit_nombres").val(),
            empresa: $("#edit_empresa").val(),
            direccion: $("#edit_direccion").val(),
            contacto: $("#edit_contacto").val(),
        };
        $.ajax({
            url: "../eventosProveedor/editar_proveedor.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                $spinner.addClass('d-none');
                $btn.prop('disabled', false);
                if (response.success) {
                    var modalEditar = bootstrap.Modal.getInstance(document.getElementById("modalEditarProveedor"));
                    modalEditar.hide();
                    table.ajax.reload(null, false);
                    Swal.fire('✅', 'Proveedor actualizado correctamente', 'success');
                } else {
                    Swal.fire('❌', response.error || 'Error desconocido', 'error');
                }
            },
            error: function (xhr) {
                $spinner.addClass('d-none');
                $btn.prop('disabled', false);
                let msg = "Error al actualizar proveedor.";
                try {
                    const json = JSON.parse(xhr.responseText);
                    msg = json.error || json.message || msg;
                } catch (e) {
                    msg = xhr.responseText || msg;
                }
                Swal.fire('❌', msg, 'error');
            },
        });
    });

    // ✅ FORM REGISTRAR PROVEEDOR
    $("#formRegistrarProveedor").on("submit", function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var $spinner = $btn.find('.spinner-border');
        $spinner.removeClass('d-none');
        $btn.prop('disabled', true);
        var formData = {
            rif: $("#registrar_rif").val(),
            nombres: $("#registrar_nombres").val(),
            empresa: $("#registrar_empresa").val(),
            direccion: $("#registrar_direccion").val(),
            contacto: $("#registrar_contacto").val(),
        };
        $.ajax({
            url: "../eventosProveedor/registrar_proveedor.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                $spinner.addClass('d-none');
                $btn.prop('disabled', false);
                if (response.success) {
                    var modalRegistrar = bootstrap.Modal.getInstance(document.getElementById("modalRegistrarProveedor"));
                    modalRegistrar.hide();
                    $("#formRegistrarProveedor")[0].reset();
                    table.ajax.reload(null, false);
                    Swal.fire('✅', 'Proveedor registrado correctamente', 'success');
                } else {
                    Swal.fire('❌', response.error || 'Error desconocido', 'error');
                }
            },
            error: function (xhr) {
                $spinner.addClass('d-none');
                $btn.prop('disabled', false);
                let msg = "Error al registrar proveedor.";
                try {
                    const json = JSON.parse(xhr.responseText);
                    msg = json.error || json.message || msg;
                } catch (e) {
                    msg = xhr.responseText || msg;
                }
                Swal.fire('❌', msg, 'error');
            },
        });
    });
});
