

  $(document).ready(function() {
  
    var tabla = $('#tablaOrdenes').DataTable({
        responsive: true,
        language: {
            lengthMenu: "Mostrar _MENU_ Registros por página",
            search: "Buscar:",
            zeroRecords: "No se encontraron órdenes",
            info: "Mostrando _START_ a _END_ de _TOTAL_ órdenes",
            infoEmpty: "No hay órdenes registradas",
            infoFiltered: "(filtrado de _MAX_ órdenes totales)",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        pageLength: 8
    });

       var alertSuccess = $('.alert-success');
     if (alertSuccess.length) {
     alertSuccess.fadeTo(2000, 500).slideUp(300, function(){
    $(this).slideUp(300);
  });
  }
    if (history.replaceState) {
    var url = new URL(window.location);
    url.searchParams.delete('msg');
    window.history.replaceState({}, document.title, url.toString());
  }

    // Evento click eliminar orden
    $('#tablaOrdenes').on('click', '.btn-delete', function() {
        var deleteRow = $(this).closest('tr');
        var deleteId = $(this).data('id');

        $.post('../eventosProducto/cancelar_orden.php', { id: deleteId }, function(response) {
            if (response.success) {
                tabla.row(deleteRow).remove().draw();
            } else {
                alert('Error: ' + (response.error || 'No se pudo eliminar'));
            }
        }, "json").fail(function() {
            alert('Error en la petición AJAX.');
        });
    });
});
