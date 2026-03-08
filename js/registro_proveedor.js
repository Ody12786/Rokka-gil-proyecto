$('#proveedorForm').submit(function(e) {
    e.preventDefault();

    let rif = $('#rif').val().trim();
    let nombres = $('#nombres').val().trim();
    let empresa = $('#empresa').val().trim();
    let contacto = $('#contacto').val().trim();

    if (rif === "" || nombres === "" || empresa === "" || contacto === "") {
        alert("Por favor, complete todos los campos.");
        return;
    }

    $.ajax({
        url: '../registro/registro_proveedor.php', 
        type: 'POST',
        data: { rif: rif, nombres: nombres, empresa: empresa, contacto: contacto },
        dataType: 'text',
        success: function(response) {
            if (response.trim() == "1") {
                alert("Proveedor registrado correctamente.");
                $('#proveedorForm')[0].reset();
                buscarProveedor(); // recarga la tabla
            } else {
                alert(response);
            }
        },
        error: function() {
            alert("Error en la comunicación con el servidor.");
        }
    });
});
