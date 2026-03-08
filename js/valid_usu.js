$(document).ready(function() {
    // Validaciones en tiempo real con clases Bootstrap
    // Validación robusta para cédula: acepta formatos con V- o guiones y también números planos 7-10 dígitos
    function validarCedula(value) {
        if (!value) return false;
        const v = value.replace(/[^0-9Vv-]/g, '').replace(/-+/g, '-');
        const complejo = /^[Vv]?[0-9]{1,2}[-]?\d{7,9}([\-]\d{1,2})?$/.test(v);
        const plano = /^\d{7,10}$/.test(v);
        return complejo || plano;
    }

    // Helper para aplicar clases de validación y soportar Select2
    function applyValidity($el, valid) {
        $el.toggleClass('is-valid', valid).toggleClass('is-invalid', !valid);
        if ($el.hasClass('select2-hidden-accessible')) {
            // Select2 puede renderizar su propio contenedor; marcar también la selección visible
            const $sel2 = $el.next('.select2-container, .select2').find('.select2-selection');
            $sel2.toggleClass('is-valid', valid).toggleClass('is-invalid', !valid);
        }
    }

    $('#ci, #cedula_empleado').on('input', function() {
        // Normalizar entrada
        const cleaned = $(this).val().replace(/[^0-9Vv-]/g, '').replace(/-+/g, '-');
        $(this).val(cleaned);
        const valido = validarCedula(cleaned);
        applyValidity($(this), valido);
    });

    // Cuando se selecciona con Select2, actualizar clases
    $('#cedula_empleado').on('change', function() {
        applyValidity($(this), !!$(this).val());
    });

    // Validar en blur para corregir formato si es necesario
    $('#ci, #cedula_empleado').on('blur', function() {
        const val = $(this).val();
        const valid = val && validarCedula(val);
        applyValidity($(this), !!valid);
    });

    $('#nombre_p, #apellido, #nombre_usuario').on('input', function() {
        $(this).val($(this).val().replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]/g, '').replace(/\s{2,}/g, ' ').trim());
        const valido = /^[A-Za-zÁÉÍÑÓÚÜáéíñóúü\s]{3,100}$/.test($(this).val());
        applyValidity($(this), valido);
    });

    $('#carnet, #carnet_usuario').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
        const valido = /^\d{6,9}$/.test($(this).val());
        applyValidity($(this), valido);
    });

    $('#telefono').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
        const valido = /^\d{7,15}$/.test($(this).val());
        applyValidity($(this), valido);
    });

    $('#correo, #email').on('input blur', function() {
        const valido = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test($(this).val());
        applyValidity($(this), valido);
    });

    // Toggle contraseña (mantenido aquí)
    $('.password-toggle').on('click', function() {
        const target = $('#' + $(this).data('target'));
        target.attr('type', target.attr('type') === 'password' ? 'text' : 'password');
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
});