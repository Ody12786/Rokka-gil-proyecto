document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form');
  const btnSubmit = form.querySelector('button[type="submit"]');

  // Contenedor para alertas Bootstrap
  const alertPlaceholder = document.createElement('div');
  form.prepend(alertPlaceholder);

  // Función para mostrar alertas dinámicas Bootstrap
  function showAlert(message, type) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>`;
    alertPlaceholder.innerHTML = ''; // limpiar alertas previas
    alertPlaceholder.append(wrapper);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    alertPlaceholder.innerHTML = '';

    // Validar campos
    const nombre = form.nombre.value.trim();
    const usuario = form.usuario.value.trim();
    const email = form.email.value.trim();
    const telefono = form.telefono.value.trim();
    const carnet = form.carnet.value.trim();
    const password = form.password.value;

    const errors = [];

    if (!nombre) errors.push('El nombre es obligatorio.');
    if (!usuario) errors.push('El usuario es obligatorio.');
    if (!email) errors.push('El correo es obligatorio.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Correo no válido.');
    if (!telefono) errors.push('El teléfono es obligatorio.');
    if (!/^\d{6,9}$/.test(carnet)) errors.push('Carnet debe tener entre 6 y 9 dígitos numéricos.');
    if (!password || password.length < 6) errors.push('La contraseña debe tener al menos 6 caracteres.');

    if (errors.length) {
      showAlert(errors.join('<br>'), 'danger');
      return;
    }

    // Mostrar spinner en botón
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = `
      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registrando...
    `;

    // Enviar por fetch con FormData
    const formData = new FormData(form);
    fetch(form.action || '', {
      method: 'POST',
      body: formData
    })
    .then(resp => resp.text())
    .then(text => {
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Registrar';
      if (text.toLowerCase().includes('error')) {
        showAlert(text, 'danger');
      } else {
        showAlert('Usuario registrado correctamente. Redirigiendo...', 'success');
        setTimeout(() => window.location.href = 'login.php', 2000);
      }
    })
    .catch(() => {
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Registrar';
      showAlert('Error en la conexión con el servidor.', 'danger');
    });
  });
});
