<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ejemplo DataTables Responsive con Bootstrap 5</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet" />

</head>
<body>

<div class="container mt-4">
  <table id="tablaEjemplo" class="table table-striped dt-responsive nowrap" style="width:100%">
    <thead>
      <tr>
        <th>Id</th>
        <th>Nombre</th>
        <th>Profesión</th>
        <th>Edad</th>
        <th>Ciudad</th>
      </tr>
    </thead>
    <tbody>
      <tr><td>1</td><td>Juan</td><td>Desarrollador</td><td>25</td><td>Buenos Aires</td></tr>
      <tr><td>2</td><td>Maria</td><td>Diseñadora</td><td>30</td><td>Madrid</td></tr>
      <tr><td>3</td><td>Carlos</td><td>Analista</td><td>28</td><td>Ciudad de México</td></tr>
      <tr><td>4</td><td>Ana</td><td>Project Manager</td><td>35</td><td>Santiago</td></tr>
    </tbody>
  </table>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Responsive extension JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
  $('#tablaEjemplo').DataTable({
    responsive: true,
    autoWidth: false,
    pageLength: 5,
    lengthMenu: [5, 10, 25, 50]
  });
});
</script>

</body>
</html>
