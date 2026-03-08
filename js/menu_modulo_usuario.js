$(document).ready(function () {
  console.log("menu_modulo_usuario.js cargado y listo");

  // BARRA FUERZA CONTRASEÑA
  const $password = $("#password");
  const $strengthFill = $(".strength-fill");
  const $strengthText = $(".password-strength small");

  $password.on("input", function () {
    const val = $(this).val();
    let strength = 0;
    if (val.length >= 6) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/\d/.test(val)) strength++;
    if (/[!@#$%^&*.]/.test(val)) strength++;

    $strengthFill.removeClass("strength-weak strength-medium strength-strong").css("width", "0%");
    $strengthText.removeClass("text-success text-warning text-danger").text("");

    if (strength >= 3) {
      $strengthFill.addClass("strength-strong").css("width", "100%");
      $strengthText.text("Fuerte ✅").addClass("text-success");
    } else if (strength >= 2) {
      $strengthFill.addClass("strength-medium").css("width", "66%");
      $strengthText.text("Media ⚠️").addClass("text-warning");
    } else if (strength >= 1) {
      $strengthFill.addClass("strength-weak").css("width", "33%");
      $strengthText.text("Débil ❌").addClass("text-danger");
    }
  });

  // VALIDACIÓN AJAX CORREGIDA 
  function validarExistente(campo, valor, $input) {
    if (!valor.trim()) return;
    $.post('../database/valida_usuario.php', {[campo]: valor}, function(data) {
      $input.toggleClass('is-invalid is-valid', data.existe);
      if (data.existe) Swal.fire('Duplicado', `${campo.toUpperCase()} ya existe`, 'error');
    }, 'json');
  }

  $('#email_usuario, #email_persona').blur(function() { validarExistente('email', $(this).val(), $(this)); });
  $('#carnet_usuario, #carnet').blur(function() { validarExistente('carnet', $(this).val(), $(this)); });

  //  VALIDACIONES INPUT
  $("#email_usuario, #email_persona").on("input blur", function () {
    const valor = $(this).val();
    const tieneArroba = /@/g.test(valor);
    
    $(this).toggleClass("is-valid", tieneArroba && valor.length > 3);
    $(this).toggleClass("is-invalid", valor.length > 3 && !tieneArroba);
});
  $("#carnet, #carnet_usuario").on("input", function () {
    $(this).val($(this).val().replace(/[^0-9]/g, ""));
    $(this).toggleClass("is-valid", /^\d{6,9}$/.test($(this).val()));
  });

  $("#telefono").on("input", function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
    $(this).toggleClass("is-valid", /^04[1-9]\d{8}$/.test($(this).val()));
  });

  $("#cedula_empleado").on("change", function () {
    $(this).toggleClass("is-valid", !!$(this).val());
  });

  // TOGGLE PASSWORD
  $(document).on('click', '.password-toggle', function () {
    const $target = $("#" + $(this).data("target"));
    const type = $target.attr("type") === "password" ? "text" : "password";
    $target.attr("type", type);
    $(this).toggleClass("fa-eye fa-eye-slash");
  });

  // 5. DATATABLE
  const table = $("#usuariosTable").DataTable({
    ajax: { url: "../database/listar_usuarios.php", dataSrc: "data" },
    columns: [
      { data: "id_encriptado", visible: false },
      { data: "nombre" },
      { data: "email", title: "Correo" },
      { data: "telefono", title: "Teléfono" },
      { data: "carnet" },
      { data: "tipo", title: "Tipo", render: data =>  
        data == '1' ? '<span class="badge bg-primary"> Admin</span>' : 
        '<span class="badge bg-primary">Estándar</span>' },
      { data: "estado", title: "Estado", render: data => 
        data == 'A' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' },
      { data: null, title: "Acciones", orderable: false, render: (data, type, row) => `
        <div class="btn-group">
          <button class="btn btn-sm btn-warning btnEditar me-1" data-id="${row.id_encriptado}">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm ${data.estado == 'A' ? 'btn-danger' : 'btn-success'} toggle-estado" 
                  data-id="${row.id}">
            <i class="fas fa-${data.estado == 'A' ? 'toggle-off' : 'toggle-on'}"></i>
          </button>
        </div>` }
    ],
    responsive: false, pageLength: 10, order: [[1, 'asc']],
     language: {
            emptyTable: "No hay usuarios registrados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
            infoEmpty: "No hay datos disponibles",
            lengthMenu: "Mostrar _MENU_ usuarios",
            loadingRecords: "Cargando usuarios...",
            processing: "Procesando...",
            search: "Buscar Usuario:",
            zeroRecords: "No se encontraron usuarios registrados",
            paginate: {
                first: "Primero",
                last: "Último", 
                next: "Siguiente",
                previous: "Anterior"
            }
        },
  });

  // TOGGLE ESTADO
  $(document).on('click', '.toggle-estado', function() {
    const id = $(this).data('id');
    const nuevoEstado = $(this).hasClass('btn-danger') ? 'I' : 'A';
    const texto = nuevoEstado == 'A' ? 'Activar' : 'Inactivar';
    
    Swal.fire({
      title: `¿${texto} usuario?`, icon: 'warning', showCancelButton: true,
      confirmButtonText: texto
    }).then(result => {
      if (result.isConfirmed) {
        $.post('../database/toggle_estado.php', {id: id}, data => {
          if (data.status === 'success') {
            table.ajax.reload();
            Swal.fire('Listo', data.mensaje, 'success');
          } else Swal.fire('Error', data.mensaje, 'error');
        }, 'json').fail(() => Swal.fire('Error', 'Conexión falló', 'error'));
      }
    });
  });


  function obtenerPestanaActiva() {
    return $(".tab-pane.show.active").attr("id") || "persona-tab-pane";
  }

  function validarPassword(pwd) {
    if (!pwd) return { ok: false };
    return pwd.length >= 6 && /[A-Z]/.test(pwd) && /\d/.test(pwd) && /[!@#$%^&*.]/.test(pwd);
  }

 
  function obtenerDatosPestanaActiva() {
    const tab = obtenerPestanaActiva();
    const data = new FormData();
    
    if (tab === 'persona-tab-pane') {
      data.append('ci', $('#ci').val());
      data.append('nombre_p', $('#nombre_p').val());
      data.append('apellido', $('#apellido').val());
      data.append('sexo', $('#sexo').val());
      data.append('email', $('#email_persona').val());
    } else if (tab === 'empleado-tab-pane') {
      data.append('cedula_empleado', $('#cedula_empleado').val());
      data.append('carnet', $('#carnet').val());
    } else if (tab === 'usuario-tab-pane') {
      data.append('id_usuario', $('#id_usuario').val());
      data.append('nombre_usuario', $('#nombre_usuario').val());
      data.append('password', $('#password').val());
      data.append('carnet_usuario', $('#carnet_usuario').val());
      data.append('telefono', $('#telefono').val());
      data.append('email', $('#email_usuario').val());
      data.append('tipo_usuario', '1'); 
    }
    
    console.log('📤 Enviando:', tab, Array.from(data.entries()));
    return data;
  }

  //  CARGA CÉDULAS
  function cargarCedulas() {
    $.getJSON("../database/listar_personas.php", data => {
      const $select = $("#cedula_empleado").empty().append('<option value="">Selecciona...</option>');
      data.forEach(p => $select.append(`<option value="${p.ci}">${p.nombre_p} (${p.ci})</option>`));
      $select.select2({ placeholder: "Buscar cédula...", width: "100%", language: "es" });
    });
  }

  $("#empleado-tab").on("shown.bs.tab", cargarCedulas);
  
  //  NUEVO REGISTRO
  $("#btnNuevoRegistro").click(function() {
    $("#formUnificado")[0].reset();
    $("#id_usuario").val(""); $("#password-group").show();
    $("#persona-tab").tab("show"); $(".texto-guardar").text("Guardar Persona");
    $("#modalUnificado").modal("show");
  });

  // SUBMIT UNIFICADO 
  $("#formUnificado").submit(function(e) {
    e.preventDefault();
    const tab = obtenerPestanaActiva();
    const urls = {
      'persona-tab-pane': "../database/registro_persona.php",
      'empleado-tab-pane': "../database/registro_empleado.php", 
      'usuario-tab-pane': "../database/usuario.php"
    };
    
    const $btn = $(this).find('button[type="submit"]');
    const $spinner = $btn.find(".spinner-border").removeClass("d-none");
    $btn.prop("disabled", true);

    $.ajax({
      url: urls[tab],
      type: "POST", data: obtenerDatosPestanaActiva(),
      contentType: false, processData: false, dataType: "json",
      success: resp => {
        console.log("✅", resp);
        $spinner.addClass("d-none"); $btn.prop("disabled", false);
        
        if (resp.status === "success") {
          Swal.fire("✅", resp.message, "success");
          
          // FLUJO AUTOMÁTICO
          if (tab === "persona-tab-pane") {
            $("#empleado-tab").tab("show");
            $(".texto-guardar").text("Guardar Empleado");
            if (resp.ci) setTimeout(() => $("#cedula_empleado").val(resp.ci).trigger("change"), 300);
            cargarCedulas();
          } else if (tab === "empleado-tab-pane") {
            $("#usuario-tab").tab("show");
            $(".texto-guardar").text("Guardar Usuario");
            $("#carnet_usuario").val(resp.carnet || '');
            $("#nombre_usuario").val(resp.nombre || '');
            $("#email_usuario").val(resp.correo || '');
            $("#password").focus();
          } else {
            $("#modalUnificado").modal("hide");
            table.ajax.reload();
          }
        } else {
          Swal.fire("❌", resp.message || "Error", "error");
        }
      },
      error: xhr => {
        $spinner.addClass("d-none"); $btn.prop("disabled", false);
        Swal.fire("❌", xhr.responseJSON?.message || "Error de servidor", "error");
      }
    });
  });

  //  EDITAR USUARIO
  $("#usuariosTable tbody").on("click", ".btnEditar", function() {
    const row = table.row($(this).parents("tr")).data();
    $("#id_usuario").val(row.id);
    $("#nombre_usuario").val(row.nombre);
    $("#email_usuario").val(row.email);
    $("#telefono").val(row.telefono || "");
    $("#carnet_usuario").val(row.carnet);
    $("#password-group").hide();
    $(".texto-guardar").text("Actualizar Usuario");
    $("#usuario-tab").tab("show");
    $("#modalUnificado").modal("show");
    $("#tipo_usuario").val(row.tipo || '2').trigger('change');
  });
});
