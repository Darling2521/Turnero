document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);

    fetch(this.action, {
        method: this.method,
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.estado === 'success') {
            // Redirigir basado en el rol
            if (data.rol === 'gestor') {
                window.location.href = 'asignacion_gestor.php';
            } else if (data.rol === 'supervisor') {
                window.location.href = 'estadisticas.php';
            }
        } else if (data.estado === 'redirect') {
            // Redirigir si ya hay una sesión iniciada
            window.location.href = data.url;
        } else {
            // Mostrar mensaje de error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.mensaje,
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema con la solicitud.',
        });
    });
});
