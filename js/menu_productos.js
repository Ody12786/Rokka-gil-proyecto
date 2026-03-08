// ===== MENU PRODUCTOS - COMPLETO CON ZOOM =====
$(document).ready(function () {
  console.log("🚀 menu_productos.js CARGADO");

  // ===== VARIABLES GLOBALES =====
  const METROS_POR_CAMISA = 1.7;
  const METROS_POR_CATEGORIA = {
    // ← NUEVO
    Franelas: 1.0,
    "Camisas Columbia": 1.6,
    Jerseys: 1.4,
    Pantalones: 0.7,
    "Uniforme deportivo": 2.0,
    "Producto Distribucion": 1.0,
    Otro: 1.0,
  };
  let stockDisponible = 0;
  let stockTelaDisponible = 0;
  let lightboxOpen = false;
  const modalProducto = new bootstrap.Modal($("#modalProducto")[0]);
  cargarOrdenes(); // Cargar órdenes al iniciar

  // ===== 1. SELECT2 MATERIA PRIMA =====
  $("#materialCompradoSelect").select2({
    dropdownParent: $("#modalProducto"),
    ajax: {
      url: "../api/compras_material_api.php",
      dataType: "json",
      delay: 250,
      processResults: function (data) {
        return {
          results:
            data.status === "success"
              ? data.data.map((item) => ({
                  id: item.id,
                  text: `${item.nombre_materia} - Stock: ${item.stock}`,
                }))
              : [],
        };
      },
      cache: true,
    },
    minimumInputLength: 0,
    placeholder: "Seleccione materia prima",
    allowClear: true,
    width: "100%",
  });

  // ===== 1.5 SELECT2 TIPO TELA =====
  $("#tipoTelaSelect").select2({
    dropdownParent: $("#modalProducto .modal-body"),
    ajax: {
      url: "../api/compras_telas_api.php",
      type: "GET",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return { search: params.term || "" };
      },
      processResults: function (data) {
        console.log("🔍 API TELA:", data); // DEBUG
        return {
          results:
            data.status === "success"
              ? data.data.map((item) => ({
                  id: item.id,
                  text: `${item.tipo_tela} (${item.metros}m)`,
                  metros: parseFloat(item.metros) || 0,
                }))
              : [],
        };
      },
      cache: true,
    },
    minimumInputLength: 0,
    placeholder: "Buscar tipo de tela...",
    allowClear: true,
    width: "100%",
  });

  // ===== CARGA SELECT ÓRDENES =====
  function cargarOrdenes() {
    $.getJSON("../api/api_listar_ordenes.php", function (data) {
      console.log("🔍 API ÓRDENES CON AUTO-NOMBRE:", data);
      let options = '<option value="" data-nombre="">-- Sin orden --</option>';

      if (Array.isArray(data) && data.length > 0) {
        data.forEach(function (orden) {
          options += `<option value="${orden.id}" data-nombre="${orden.nombre}">
                    ${orden.nombre} - ${orden.categoria}
                </option>`;
        });
      } else if (data.status === "success" && Array.isArray(data.data)) {
        data.data.forEach(function (orden) {
          options += `<option value="${orden.id}" data-nombre="${orden.nombre}">
                    ${orden.nombre} - ${orden.categoria}
                </option>`;
        });
      }

      $("#ordenSelect").html(options);
      console.log("✅ Órdenes cargadas con data-nombre:", options);
    }).fail(function (xhr) {
      console.error("❌ Error API órdenes:", xhr.status);
    });
  }

  // 🔥 AUTO-REFILL NOMBRE DESDE ORDEN (NUEVO)
  $("#ordenSelect").on("change", function () {
    const $this = $(this);
    const ordenId = $this.val();
    const nombreOrden = $this.find("option:selected").data("nombre") || "";

    console.log("🔄 Orden seleccionada:", ordenId, "Nombre:", nombreOrden);

    if (ordenId && nombreOrden) {
      // Auto-llenar nombre producto
      $("#nombreProducto")
        .val(nombreOrden)
        .addClass("is-valid")
        .removeClass("is-invalid")
        .trigger("input"); // Trigger validación

      // Hint visual (si existe)
      if ($("#autoNombreHint").length) {
        $("#autoNombreHint").html(
          `<i class="fas fa-magic me-1 text-success"></i>Nombre autocompletado: "${nombreOrden}"`
        );
      }

      console.log("🎯 Auto-nombre aplicado:", nombreOrden);
    } else {
      // Limpiar si "Sin orden"
      $("#nombreProducto")
        .val("")
        .removeClass("is-valid")
        .removeClass("is-invalid");

      if ($("#autoNombreHint").length) {
        $("#autoNombreHint").html("Seleccione orden → Nombre se autocompleta");
      }
    }
  });

  // ===== 2. VALIDACIONES INPUTS =====
  $("#nombreProducto").on("input", function () {
    const $this = $(this);
    let valor = $this
      .val()
      .replace(/[^A-Za-zÁÉÍÑÓÚÜáéíñóúü0-9\s\.\,\-_]/g, "")
      .replace(/\s{2,}/g, " ")
      .trim();
    $this.val(valor);
    $this.toggleClass("is-valid", valor.length >= 3 && valor.length <= 100);
  });

  // ===== 2. STOCK - SINCROMATIZACIÓN AUTOMÁTICA =====
  $("#stockProducto").on("input", function () {
    const $this = $(this);
    const stock = parseInt($this.val()) || 0;
    $this.toggleClass("is-valid", stock >= 1 && stock <= 99999);

    // 🔥 SINCROMATIZAR CANTIDADES AUTOMÁTICO
    sincronizarCantidades(stock);
    calcularMetrosTotal();
    validarStockCompleto();
  });

  $("#tallaProducto").on("input", function () {
    $(this).val(
      $(this)
        .val()
        .replace(/[^A-Za-z0-9\s\.\,\-_]/g, "")
        .replace(/\s{2,}/g, " ")
        .trim()
    );
  });

  // 🔥 VALIDACIÓN TIPO TELA

  $("#tipoTelaSelect").on("select2:select", function () {
    $(this).removeClass("is-invalid").addClass("is-valid");
  });

  $("#tipoTelaSelect").on("select2:close", function () {
    const valor = $(this).val();
    if (valor) {
      $(this).removeClass("is-invalid").addClass("is-valid");
    } else {
      $(this).addClass("is-invalid");
    }
  });

  // ===== 3. PREVIEW IMAGEN =====
  $("#imagenProducto").on("change", function (e) {
    const file = e.target.files[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        Swal.fire("Error", "Imagen máxima 5MB", "error");
        $(this).val("");
        return;
      }
      if (!file.type.match("image/")) {
        Swal.fire("Error", "Solo imágenes JPG/PNG/WebP", "error");
        $(this).val("");
        return;
      }
      const reader = new FileReader();
      reader.onload = function (e) {
        $("#imgPreview").attr("src", e.target.result);
        $("#previewImagen").show();
      };
      reader.readAsDataURL(file);
    }
  });

  // 🔥 VALIDACIÓN DOBLE CON AUTO-SUGERENCIA
  function validarStockCompleto() {
    const stock = parseInt($("#stockProducto").val()) || 0;
    const errores = [];

    // MATERIA PRIMA
    const cantidadMaterial = parseFloat($("#cantidadMaterial").val()) || 0;
    const autoMaterial = stock * 1.0;
    if (cantidadMaterial > stockDisponible) {
      $("#cantidadMaterial").addClass("is-invalid");
      errores.push(
        `❌ Materia: ${cantidadMaterial.toFixed(2)} > ${stockDisponible.toFixed(
          2
        )} (Auto: ${autoMaterial.toFixed(2)})`
      );
    } else if (cantidadMaterial > 0) {
      $("#cantidadMaterial").removeClass("is-invalid").addClass("is-valid");
    }

    // TELAS
    const cantidadTela = parseFloat($("#cantidadTela").val()) || 0;
    const categoria = $("#categoriaProducto").val();
    const metrosUnit = METROS_POR_CATEGORIA[categoria] || 1.0;
    const autoTela = stock * metrosUnit;
    if (cantidadTela > stockTelaDisponible) {
      $("#cantidadTela").addClass("is-invalid");
      errores.push(
        `❌ Tela: ${cantidadTela.toFixed(2)}m > ${stockTelaDisponible.toFixed(
          2
        )}m (Auto: ${autoTela.toFixed(2)}m)`
      );
    } else if (cantidadTela > 0) {
      $("#cantidadTela").removeClass("is-invalid").addClass("is-valid");
    }

    return errores.length === 0;
  }

  // ===== 4. LÓGICA CATEGORÍA + METROS AUTOMÁTICO =====
  $("#categoriaProducto").on("change", function () {
    const categoria = $(this).val();

    // CONTROL VISIBILIDAD TALLA
    if (["Franelas", "Camisas Columbia", "Jerseys"].includes(categoria)) {
        $("#divTalla").slideDown();
        $("#tallaProducto").prop("required", true);
    } else {
        $("#divTalla").slideUp();
        $("#tallaProducto").prop("required", false).val("");
    }

    // 🔥 METROS AUTOMÁTICO
    const metros = METROS_POR_CATEGORIA[categoria] || 1.0;
    if ($("#divMetros").length === 0) {
      $("#stockProducto").before(`
            <div class="mb-3" id="divMetros">
                <label class="form-label"><i class="fas fa-ruler me-1 text-info"></i>Metros/unidad</label>
                <input type="text" class="form-control shadow-sm bg-light" id="metrosEstimado" readonly value="${metros.toFixed(
                  2
                )}m">
                <div class="form-text text-success">Automático por categoría</div>
            </div>
        `);
    } else {
      $("#metrosEstimado").val(`${metros.toFixed(2)}m`);
    }
    calcularMetrosTotal();
    const stockActual = parseInt($("#stockProducto").val()) || 0;
    sincronizarCantidades(stockActual);
  });

  // 🔥 FUNCIÓN TOTAL METROS
  function calcularMetrosTotal() {
    const categoria = $("#categoriaProducto").val();
    const stock = parseInt($("#stockProducto").val()) || 0;
    const metrosUnit = METROS_POR_CATEGORIA[categoria] || 1.0;
    const totalMetros = metrosUnit * stock;

    if ($("#divTotalMetros").length === 0) {
      $("#stockProducto").after(`
            <div class="mb-3" id="divTotalMetros">
                <label class="form-label"><i class="fas fa-calculator me-1 text-primary"></i>Total metros</label>
                <div class="form-control shadow-sm bg-gradient text-center fw-bold fs-5 text-white" 
                     id="totalMetros" style="background: linear-gradient(135deg, #d1001b, #a10412);">
                    0.00m
                </div>
                <div class="form-text text-success">${metrosUnit.toFixed(
                  2
                )}m × ${stock} = ${totalMetros.toFixed(2)}m</div>
            </div>
        `);
    }
    $("#totalMetros").text(totalMetros.toFixed(2) + "m");
    $("#divTotalMetros .form-text").html(
      `${metrosUnit.toFixed(2)}m × ${stock} = <strong>${totalMetros.toFixed(
        2
      )}m</strong>`
    );
  }

  // 🔥 SINCROMATIZAR CANTIDADES vs STOCK
  function sincronizarCantidades(stock) {
    // 1. MATERIA PRIMA (Stock × 1)
    if ($("#cantidadMaterial").length) {
      const factorMaterial = 1.0; // 1 unidad materia por producto
      const cantidadMaterialAuto = stock * factorMaterial;
      $("#cantidadMaterial").val(cantidadMaterialAuto.toFixed(2));
    }

    // 2. TELA (Stock × Metros por categoría)
    if ($("#cantidadTela").length && stockTelaDisponible > 0) {
      const categoria = $("#categoriaProducto").val();
      const metrosUnit = METROS_POR_CATEGORIA[categoria] || 1.0;
      const cantidadTelaAuto = stock * metrosUnit;
      $("#cantidadTela").val(cantidadTelaAuto.toFixed(2));

      console.log(
        `📏 Auto: ${stock} × ${metrosUnit}m = ${cantidadTelaAuto.toFixed(2)}m`
      );
    }
  }

  // ===== 5. MATERIA PRIMA SELECCIONADA =====
  $("#materialCompradoSelect").on("select2:select", function (e) {
    const texto = e.params.data.text;
    const match = texto.match(/Stock:\s*(\d+(?:\.\d+)?)/i);
    stockDisponible = match ? parseFloat(match[1]) : 0;

    if ($("#divCantidadMaterial").length === 0) {
      $("#materialCompradoSelect").after(`
                <div class="mb-3" id="divCantidadMaterial">
                    <label class="form-label">Cantidad Materia Prima</label>
                    <input type="number" min="1" class="form-control" id="cantidadMaterial" name="cantidadMaterialInput" />
                    <div class="invalid-feedback">No puede superar stock disponible</div>
                </div>
            `);
    }
    $("#divCantidadMaterial").show();
    $("#cantidadMaterial").prop("required", true);
    $("#cantidadMaterial")
      .off("input.validar")
      .on("input.validar", function () {
        validarStockCompleto();
      });
    $("#stockMatDisp").text(stockDisponible.toFixed(2));
  });

  // 🔥 SELECT TELA SELECCIONADA (CORREGIDO)
  $("#tipoTelaSelect").on("select2:select", function (e) {
    const data = e.params.data;
    stockTelaDisponible = data.metros || 0;

    if ($("#divCantidadTela").length === 0) {
      $("#tipoTelaSelect").after(`
            <div class="mb-3" id="divCantidadTela">
                <label class="form-label">
                    <i class="fas fa-fabric me-1 text-warning"></i>Cantidad Tela (m) <span class="text-danger">*</span>
                </label>
                <input type="number" step="0.01" min="0.01" class="form-control" 
                       id="cantidadTela" name="cantidadTela" placeholder="Ej: 25.50" />
                <div class="invalid-feedback">
                    Stock disponible: <span id="stockTelaDisp">0.00</span>m
                </div>
            </div>
        `);
    }
    $("#stockTelaDisp").text(stockTelaDisponible.toFixed(2));
    $("#divCantidadTela").show();
    $("#cantidadTela").prop("required", true);
    $(document)
      .off("input.validar", "#cantidadTela")
      .on("input.validar", "#cantidadTela", function () {
        validarStockCompleto();
      });
    $("#stockTelaDisp").text(stockTelaDisponible.toFixed(2));
  });

  // ===== 6. LIMPIAR MODAL - CORREGIDO COMPLETO =====
  $("#modalProducto").on("hidden.bs.modal", function () {
    // RESET FORMULARIO
    $("#formProducto")[0].reset();
    $("#productoId").val("");

    //  CLASES CSS
    $(".form-control, .form-select").removeClass("is-valid is-invalid");

    // ✅ RE-Activar Select2 para NUEVO
    $("#materialCompradoSelect, #tipoTelaSelect").prop("disabled", false);

    // ✅ Remover info no editable
    $("#divInfoNoEditable").remove();

    //  OCULTAR ELEMENTOS
    $("#previewImagen").hide();
    $("#modalProductoLabel").html(
      '<i class="fas fa-box me-2"></i>Registrar Producto'
    );

    //  LIMPIAR SELECT2
    $("#materialCompradoSelect").val(null).trigger("change");
    $("#tipoTelaSelect").val(null).trigger("change");

    //  REMOVER DYNAMIC DIVS
    $(
      "#divTalla, #divCantidadMaterial, #divCantidadTela, #divMetros, #divTotalMetros"
    ).remove();

    //  REMOVER EVENTOS
    $("#cantidadMaterial").off("input.validar");
    $(document).off("input.validar", "#cantidadTela");

    //  RESET REQUIRED - SOLO INPUTS REALES
    $("#tallaProducto, #cantidadMaterial, #cantidadTela").prop(
      "required",
      false
    );

    //  RESET VARIABLES GLOBALES
    stockDisponible = 0;
    stockTelaDisponible = 0;
  });

  // ===== 7. BOTÓN NUEVO =====
  $("#btnNuevoProducto").on("click", function () {
    $("#modalProducto").modal("show");
  });

  // ===== 8. DATATABLES CON ZOOM =====
  const tabla = $("#tablaProductos").DataTable({
    ajax: {
      url: "../api/productos.php",
      dataSrc: function (resp) {
        console.log("📦 API Response:", resp);
        return resp.status === "success" ? resp.data : [];
      },
      error: function (xhr) {
        console.error("❌ API Error:", xhr.status, xhr.responseText);
        Swal.fire("Error API", `Status: ${xhr.status}`, "error");
      },
    },
    columns: [
      {
        data: "imagen",
        orderable: false,
        searchable: false,
        width: "80px",
        render: function (data) {
          if (data && data !== null) {
            return `<img src="${data}" 
                                     class="table-img rounded shadow cursor-zoom" 
                                     data-fullsrc="${data}"
                                     style="max-height:60px;max-width:60px;object-fit:contain;transition:all 0.3s ease;"
                                     title="🔍 Click para ampliar">`;
          }
          return '<i class="fas fa-image fa-2x text-muted opacity-50"></i>';
        },
      },
      { data: "id", width: "60px" },
      { data: "codigo", width: "100px" },
      {
        data: null,
        width: "120px",
        render: function (data, type, row) {
          return row.orden_id && row.orden_id > 0
            ? `#${row.orden_id}`
            : "Sin orden";
        },
      },
      { data: "nombre" },
      { data: "descripcion", defaultContent: "", width: "150px" },
      { data: "tipo_tela", width: "110px" },
      { data: "categoria", width: "120px" },
      { data: "diseno", width: "120px" },
      { 
    data: 'talla',
    width: '80px',
    render: function(data, type, row) {
        // Mostrar talla o '-' si vacía/null/0
        const talla = data ? data.toString().trim() : '';
        return (talla && talla !== '0') ? 
               talla.toUpperCase() : '-';
    },
    defaultContent: '-',
    className: 'text-center fw-semibold text-info'
},
      { data: "stock", className: "text-center fw-bold", width: "90px" },
      {
        data: "fecha_creacion",
        width: "110px",
        render: function (data) {
          return data ? new Date(data).toLocaleDateString("es-VE") : "-";
        },
      },
      {
        data: null,
        orderable: false,
        width: "140px",
        render: function (data) {
          return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-editar" data-id="${data.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-eliminar" data-id="${data.id}" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
        },
      },
    ],
    language: {
      lengthMenu: "Mostrar _MENU_ registros por página",
      emptyTable: "No hay datos disponibles",
      info: "Mostrando _START_ a _END_ de _TOTAL_ productos",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "No se encontraron registros",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior",
      },
    },
    pageLength: 15,
    responsive: true,
    order: [[1, "desc"]],
  });

  // ===== 9. LIGHTBOX ZOOM - DATA TABLES =====
  // CSS Lightbox
  if (!$("#lightbox-style").length) {
    $("head").append(`
            <style id="lightbox-style">
                .lightbox-overlay {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.95); z-index: 9999; display: none;
                    backdrop-filter: blur(15px);
                }
                .lightbox-img {
                    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                    max-width: 90vw; max-height: 90vh; border-radius: 20px;
                    box-shadow: 0 25px 70px rgba(209,0,27,0.8); cursor: zoom-out;
                    border: 3px solid rgba(209,0,27,0.6);
                }
                .cursor-zoom {
                    cursor: zoom-in !important; border: 2px solid transparent;
                    transition: all 0.3s ease !important;
                }
                .cursor-zoom:hover {
                    transform: scale(1.1) !important;
                    border-color: rgba(209,0,27,0.6) !important;
                    box-shadow: 0 8px 25px rgba(209,0,27,0.4) !important;
                }
            </style>
        `);
  }

  // Funciones Lightbox
  function abrirLightbox(src) {
    if (lightboxOpen) return;
    lightboxOpen = true;

    $("body").append(`
            <div id="lightbox-overlay" class="lightbox-overlay"></div>
            <img id="lightbox-img" src="${src}" class="lightbox-img">
        `);

    $("#lightbox-overlay, #lightbox-img").fadeIn(300);
  }

  function cerrarLightbox() {
    $("#lightbox-overlay, #lightbox-img").fadeOut(300, function () {
      $(this).remove();
      lightboxOpen = false;
    });
  }

  // Eventos Lightbox
  $(document).on("click", "#lightbox-overlay, #lightbox-img", cerrarLightbox);

  // Zoom en DataTables (CRUCIAL: cada draw)
  tabla.on("draw", function () {
    $(".cursor-zoom")
      .off("click.zoom")
      .on("click.zoom", function (e) {
        e.stopPropagation();
        const src = $(this).data("fullsrc");
        abrirLightbox(src);
      });
  });

  // ===== 10. FORM SUBMIT =====
  $("#formProducto").on("submit", function (e) {
    e.preventDefault();

    // 🎯 DETECTAR MODO EDITAR
    const isEdit = $("#productoId").val() !== "";
    console.log("📝 Modo:", isEdit ? "EDITAR" : "NUEVO");

    // ✅ VALIDACIÓN DIFERENCIADA
    if (!isEdit) {
      // NUEVO: Validar TODO
      const tieneMaterial = $("#materialCompradoSelect").val();
      const tieneTela = $("#tipoTelaSelect").val();

      if ((tieneMaterial || tieneTela) && !validarStockCompleto()) {
        Swal.fire({
          icon: "error",
          title: "❌ Stock Insuficiente",
          html: "Materia prima o tela insuficiente para la cantidad solicitada",
        });
        return;
      }

      // Validaciones NUEVO obligatorias
      const errores = [];
      const nombre = $("#nombreProducto").val().trim();
      const material = $("#materialCompradoSelect").val();
      const tipoTela = $("#tipoTelaSelect").val();
      const categoria = $("#categoriaProducto").val();
      const diseno = $("#disenoProducto").val();
      const stock = parseInt($("#stockProducto").val());

      if (nombre.length < 3) errores.push("Nombre mínimo 3 caracteres");
      if (!material) errores.push("✅ Seleccione materia prima");
      if (!tipoTela) errores.push("✅ Seleccione tipo de tela");
      if (!categoria) errores.push("Seleccione categoría");
      if (!diseno) errores.push("Seleccione tipo diseño");
      if (stock < 1 || stock > 99999) errores.push("Stock 1-99,999");

      if (errores.length > 0) {
        Swal.fire({
          icon: "error",
          title: "❌ Errores - NUEVO Producto",
          html: errores.map((err) => `• ${err}`).join("<br>"),
        });
        return;
      }
    } else {
      // EDITAR: Solo validar campos editables
      const errores = [];
      const nombre = $("#nombreProducto").val().trim();
      const categoria = $("#categoriaProducto").val();
      const diseno = $("#disenoProducto").val();
      const stock = parseInt($("#stockProducto").val());

      if (nombre.length < 3) errores.push("Nombre mínimo 3 caracteres");
      if (!categoria) errores.push("Seleccione categoría");
      if (!diseno) errores.push("Seleccione tipo diseño");
      if (stock < 1 || stock > 99999) errores.push("Stock 1-99,999");

      if (errores.length > 0) {
        Swal.fire({
          icon: "error",
          title: "❌ Errores - EDITAR Producto",
          html: errores.map((err) => `• ${err}`).join("<br>"),
        });
        return;
      }
    }

    // ENVÍO COMÚN (igual para nuevo/editar)
    const formData = new FormData(this);
    const productoId = $("#productoId").val();
    const url = productoId ? "../api/productos.php" : "../api/productos.php"; // ← SIEMPRE POST

    const $btnSubmit = $(this).find('button[type="submit"]');
    $btnSubmit
      .prop("disabled", true)
      .find(".spinner-border")
      .removeClass("d-none");

    $.ajax({
      url: url,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (resp) {
        if (resp.status === "success") {
          $("#modalProducto").modal("hide");
          tabla.ajax.reload(null, false);
          Swal.fire(
            "¡Éxito!",
            resp.message || (productoId ? "Actualizado" : "Registrado"),
            "success"
          );
        } else {
          Swal.fire("Error", resp.message || "Error al guardar", "error");
        }
      },
      error: function (xhr) {
        console.error("AJAX Error:", xhr.responseText);
        Swal.fire("Error", `Status: ${xhr.status}`, "error");
      },
      complete: function () {
        $btnSubmit
          .prop("disabled", false)
          .find(".spinner-border")
          .addClass("d-none");
      },
    });
  });
// ===== 11. EDITAR - CAMPOS CORREGIDOS =====
$("#tablaProductos tbody").on("click", ".btn-editar", function () {
    const data = tabla.row($(this).closest("tr")).data();
    console.log("🔍 EDITAR DATA:", data); // DEBUG TALLA

    // 🎯 TÍTULO + ID
    $("#modalProductoLabel").html(
        '<i class="fas fa-edit me-2"></i>Editar Producto'
    );
    $("#productoId").val(data.id);

    // ✅ CAMPOS EDITABLES - TALLA FIJA
    $("#nombreProducto").val(data.nombre || "");
    $("#descripcionProducto").val(data.descripcion || "");
    $("#categoriaProducto")
        .val(data.categoria || "")
        .trigger("change");
    $("#disenoProducto").val(data.diseno || "");
    
    // 🔥 TALLA CORREGIDA - MUESTRA SIEMPRE
    const tallaValor = data.talla || "";
    $("#tallaProducto").val(tallaValor).trigger("input");
    console.log("🎯 TALLA asignada:", tallaValor); // DEBUG
    
    $("#stockProducto").val(data.stock || 0);

    // ✅ MOSTRAR DIV TALLA si tiene categoría que la requiere
    const categoria = data.categoria || "";
    if (["Franelas", "Camisas Columbia", "Jerseys"].includes(categoria)) {
        $("#divTalla").show();
        $("#tallaProducto").prop("required", true);
    } else {
        $("#divTalla").hide();
        $("#tallaProducto").prop("required", false);
    }

    // 🎯 INFO NO EDITABLE
    if ($("#divInfoNoEditable").length === 0) {
        $("#categoriaProducto").after(`
            <div class="alert alert-info mt-2 mb-3" id="divInfoNoEditable">
                <h6 class="mb-2"><i class="fas fa-lock me-2 text-warning"></i><strong>Información fija:</strong></h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Código:</strong><br>
                        <span id="codigoDisplay" class="badge bg-primary fs-6">${data.codigo || "N/A"}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Tipo Tela:</strong><br>
                        <span id="telaDisplay">${data.tipo_tela || "N/A"}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Materia Prima:</strong><br>
                        <span id="materiaDisplay">${data.material_comprado_nombre || "N/A"}</span>
                    </div>
                </div>
            </div>
        `);
    } else {
        $("#codigoDisplay").text(data.codigo || "N/A");
        $("#telaDisplay").text(data.tipo_tela || "N/A");
        $("#materiaDisplay").text(data.material_comprado_nombre || "N/A");
    }

    // ✅ IMAGEN
    if (data.imagen) {
        $("#previewImagen").show();
        $("#imgPreview").attr("src", data.imagen);
    }

    // ❌ DESACTIVAR Select2 NO EDITABLES
    $("#materialCompradoSelect, #tipoTelaSelect").prop("disabled", true).val(null).trigger("change");

    modalProducto.show();
});


  // ===== 12. ELIMINAR =====
  $("#tablaProductos tbody").on("click", ".btn-eliminar", function () {
    const idProducto = $(this).data("id");
    const row = tabla.row($(this).closest("tr"));
    const nombreProducto = row.data().nombre;

    Swal.fire({
      title: "¿Eliminar producto?",
      html: `<strong>${nombreProducto}</strong><br><small>Esta acción no se puede deshacer</small>`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: '<i class="fas fa-trash me-1"></i>Sí, eliminar',
      cancelButtonText: '<i class="fas fa-times me-1"></i>Cancelar',
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: "../api/productos.php",
          method: "DELETE",
          contentType: "application/json",
          data: JSON.stringify({ id: idProducto }),
          success: function (resp) {
            if (resp.status === "success") {
              tabla.ajax.reload(null, false);
              Swal.fire(
                "Eliminado",
                "Producto eliminado correctamente",
                "success"
              );
            } else {
              Swal.fire(
                "Error",
                resp.message || "No se pudo eliminar",
                "error"
              );
            }
          },
          error: function (xhr) {
            Swal.fire("Error", "Error de conexión", "error");
          },
        });
      }
    });
  });

  console.log("✅ menu_productos.js COMPLETO - Zoom activado");
});
