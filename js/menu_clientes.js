$(document).ready(function() {
    console.log('🚀 JS Clientes iniciado - Bootstrap 5');
    
    // Desactivar warnings DataTables
    $.fn.dataTable.ext.errMode = 'none';
    
    // Validaciones
    window.validarCedula = function(valor) {
        return /^\d{7,8}$/.test(valor.toString().trim());
    };
    window.validarNombre = function(valor) {
        const cleaned = valor.trim();
        return cleaned.length >= 3 && /^[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]+$/.test(cleaned);
    };
    
    window.validarTelefono = function(valor) {
    if (typeof valor !== 'string') return false;
    const cleaned = valor.trim();
    
    // Formatos aceptados:
    // 1234567890
    // 123-456-7890
    // 123 456 7890
    // (123) 456-7890
    // +52 123 456 7890
    // 521234567890
    
    const telefonoRegex = /^0(412|414|416|424|426)[0-9]{7}$/;
    return telefonoRegex.test(cleaned);
    };

    // LocalStorage
    window.getEliminados = function() {
        return JSON.parse(localStorage.getItem('clientesEliminados') || '[]');
    };
    window.agregarEliminado = function(id) {
        let eliminados = window.getEliminados();
        if (!eliminados.includes(id)) {
            eliminados.push(id);
            localStorage.setItem('clientesEliminados', JSON.stringify(eliminados));
        }
    };
    
    // DataTable
    window.table = $('#clienteTable').DataTable({
        ajax: {
            url: "../eventosCliente/fetch_clientes.php",
            dataSrc: function(json) {
                const eliminados = window.getEliminados();
                return (json.data || []).filter(function(item) {
                    return !eliminados.includes(item.N_afiliacion);
                });
            }
        },
        columns: [
            { data: "N_afiliacion" },
            { data: "Cid" },
            { data: "nombre" },
            { data: "direccion", defaultContent: "" },
            { data: "telefono", defaultContent: "" },
            {
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm gap-2">
                            <button type="button" class="btn btn-outline-primary btnEditarCliente" 
                                    data-id="${row.N_afiliacion}" 
                                    data-cedula="${row.Cid}" 
                                    data-nombre="${row.nombre}"
                                    data-direccion="${row.direccion || ''}"
                                    data-telefono="${row.telefono || ''}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btnEliminarCliente" 
                                    data-id="${row.N_afiliacion}" 
                                    data-nombre="${row.nombre}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            emptyTable: "No hay clientes activos",
            search: "Buscar cliente:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        },
        pageLength: 10,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            { extend: "excelHtml5", text: '<i class="fas fa-file-excel"></i> Excel', className: "btn btn-success btn-sm" },
            { 
                text: '<i class="fas fa-file-pdf"></i> PDF', 
                className: 'btn btn-danger btn-sm',
                action: function() { window.open('../vistas/exportar_clientes_pdf.php', '_blank'); }
            }
        ]
    });
    
    // Alertas
    window.mostrarAlerta = function(icono, titulo, texto) {
        Swal.fire({
            icon: icono, 
            title: titulo, 
            text: texto, 
            toast: true, 
            position: 'top-end', 
            timer: 3500, 
            showConfirmButton: false,
            customClass: { popup: 'animate__animated animate__fadeInRight' }
        });
    };
    
    // Validaciones input
    $('#registrar_Cid, #edit_Cid').on('input', function() {
        let val = $(this).val().replace(/[^0-9]/g, '').slice(0,8);
        $(this).val(val)
            .toggleClass('is-valid', window.validarCedula(val))
            .toggleClass('is-invalid', val && !window.validarCedula(val));
    });
    
    $('#registrar_nombre, #edit_nombre').on('input', function() {
        let val = $(this).val().replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]/g, '')  // Solo letras y espacios
                        .replace(/\s{2,}/g, ' ')            // Evita múltiples espacios
                        .replace(/^\s+/, '')                 // No permite espacios al inicio
                        .trimStart();                         // Elimina espacios al inicio
    
        // Limitar longitud si es necesario (ej: 50 caracteres)
        if (val.length > 50) {
            val = val.slice(0, 50);
        }
        
        // Capitalizar primera letra de cada palabra (opcional)
        val = val.replace(/\b\w/g, l => l.toUpperCase());

        $(this).val(val)
            .toggleClass('is-valid', window.validarNombre(val))
            .toggleClass('is-invalid', val && !window.validarNombre(val));
    });

    
    
    $('#registrar_telefono, #edit_telefono').on('input', function() {
        let val = $(this).val().replace(/[^\d\s\-+()]/g, '')  // Solo números, espacios, guiones, +, ()
                        .replace(/\s{2,}/g, ' ')            // Evita múltiples espacios
                        .trim();
        // Limitar a 11 dígitos
        if (val.length > 11) {
            val = val.slice(0, 11);
        }

        $(this).val(val)
            .toggleClass('is-valid', window.validarTelefono(val) && val == "")
            .toggleClass('is-invalid', val && !window.validarTelefono(val));
    });

    // Validar formato al salir del input
    $('#registrar_telefono, #edit_telefono').on('blur', function() {
        let val = $(this).val().trim();
        if (!window.validarTelefono(val) && val) {
            window.mostrarAlerta('error', 'Formato inválido', 'El teléfono no tiene un formato válido. Ej: 123-456-7890, (123) 456-7890, +52 123 456 7890');
            $(this).addClass('is-invalid').removeClass('is-valid');
        }
    });

    
    // Nuevo cliente
    $('#btnNuevoCliente').on('click', function() {
        $('#formRegistrarCliente')[0].reset();
        $('#mensajeRegistrarCliente').addClass('d-none');
        $('#registrar_Cid, #registrar_nombre, #registrar_telefono', '#registrar_direccion').removeClass('is-valid is-invalid');
    });
    
    // Registrar
    $('#formRegistrarCliente').on('submit', function(e) {
        e.preventDefault();
        const cid = $('#registrar_Cid').val().trim();
        const nombre = $('#registrar_nombre').val().trim();
        const telefono = $('#registrar_telefono').val().trim();
        const direccion = $('#registrar_direccion').val().trim();

        
        if (!cid || !nombre || !window.validarCedula(cid) || !window.validarNombre(nombre)) {
            return window.mostrarAlerta('error', 'Error', 'Complete todos los campos correctamente');
        }

        if (telefono && !window.validarTelefono(telefono)) {
            return window.mostrarAlerta('error', 'Error', 'El teléfono no tiene un formato válido. Ej: 04121234567');
        }
        
        $.post('../eventosCliente/registrar_cliente.php', { Cid: cid, nombre, telefono, direccion })
            .done(function(resp) {
                if (resp.success) {
                    bootstrap.Modal.getInstance($('#modalRegistrarCliente')[0]).hide();
                    window.table.ajax.reload();
                    window.mostrarAlerta('success', '¡Registrado!', 'Cliente creado correctamente');
                    window.location.reload();
                } else {
                    $('#mensajeRegistrarCliente').html(`<i class="fas fa-exclamation-triangle"></i> ${resp.error}`).removeClass('d-none');
                }
            })
            .fail(function(resp) {
                window.mostrarAlerta('error', 'Error', resp.responseJSON?.error || 'Error al registrar');
            });
    });
    
    // ✅ EDITAR - BOOTSTRAP 5 CORRECTO
    $(document).on('click', '.btnEditarCliente', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = $(this).data('id');
        const cedula = $(this).data('cedula');
        const nombre = $(this).data('nombre');
        const telefono = $(this).data('telefono');
        const direccion = $(this).data('direccion');
        
        console.log('Datos:', {id, cedula, nombre, telefono, direccion  });
        
        $('#edit_N_afiliacion_original').val(id);
        $('#edit_Cid').val(cedula || '').trigger('input');
        $('#edit_nombre').val(nombre || '').trigger('input');
        $('#edit_telefono').val(telefono || '').trigger('input');
        $('#edit_direccion').val(direccion || '').trigger('input');
        
        $('#edit_telefono').removeClass('is-valid is-invalid');
        // ✅ BOOTSTRAP 5 NATIVE
        const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarCliente'));
        modalEditar.show();
        
    });
    
    // Guardar edición
    $('#formEditarCliente').on('submit', function(e) {
        e.preventDefault();
        const data = {
            N_afiliacion_original: $('#edit_N_afiliacion_original').val(),
            Cid: $('#edit_Cid').val().trim(),
            nombre: $('#edit_nombre').val().trim(),
            telefono: $('#edit_telefono').val().trim(),
            direccion: $('#edit_direccion').val().trim()
        };
        
        $.post('../eventosCliente/editar_cliente.php', data)
            .done(function(resp) {
                if (resp.success) {
                    bootstrap.Modal.getInstance($('#modalEditarCliente')[0]).hide();
                    window.table.ajax.reload();
                    window.mostrarAlerta('success', '¡Actualizado!', 'Cliente modificado correctamente');
                } else {
                    $('#mensajeEditarCliente').html(`<i class="fas fa-exclamation-triangle"></i> ${resp.error}`).removeClass('d-none');
                }
            })
            .fail(function() {
                window.mostrarAlerta('error', 'Error', 'Error al actualizar');
            });
    });
    
    // Eliminar
    $(document).on('click', '.btnEliminarCliente', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        $.post('../eventosCliente/eliminar_cliente.php', { id: id })
            .done(function(resp) {
                if (resp.success) {
                    window.table.ajax.reload();
                    window.mostrarAlerta('success', 'Eliminado', `${nombre} eliminado correctamente`);
                } else {
                    $('#mensajeEditarCliente').html(`<i class="fas fa-exclamation-triangle"></i> ${resp.error}`).removeClass('d-none');
                }
            })
            .fail(function() {
                window.mostrarAlerta('error', 'Error', 'Error al actualizar');
            });
    });
    
    // Cerrar modales
    $('#modalRegistrarCliente, #modalEditarCliente').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#mensajeRegistrarCliente, #mensajeEditarCliente').addClass('d-none');
        $('#registrar_Cid, #registrar_nombre, #registrar_telefono, #registrar_direccion, #edit_Cid, #edit_nombre, #edit_telefono, #edit_direccion').removeClass('is-valid is-invalid');
    });
    
    console.log('✅ Sistema Clientes 100% funcional');
});
