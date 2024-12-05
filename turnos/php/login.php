<?php
date_default_timezone_set('America/Bogota'); 

require_once '../modelo/conexion.php';

// Iniciar la sesión
session_start();

// Si ya hay una sesión iniciada, redirigir al usuario
// if (isset($_SESSION['ge_email'])) {
//     $response = array(
//         'estado' => 'redirect',
//         'url' => 'asignacion_gestor.php'
//     );
//     echo json_encode($response);
//     exit();
// }

$errores = '';

// Manejar el envío del formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ge_email = $_POST['ge_email'];
    $ge_clave = $_POST['ge_clave'];

    // Consultar el usuario en la base de datos
    $query = "SELECT * FROM gestor WHERE ge_email = :ge_email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':ge_email', $ge_email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hash_db = $row['ge_clave'];

        // Verificar la contraseña
        if (password_verify($ge_clave, $hash_db)) {
            // Verificar si el usuario tiene una sesión activa en otro navegador
            $log_cod = $row['ge_cod'];
            $query = "SELECT * FROM login WHERE log_cod = :log_cod AND log_est = 'conectado'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':log_cod', $log_cod);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Si ya tiene una sesión activa, no permitir iniciar sesión nuevamente
                $errores .= 'Ya has iniciado sesión en otro navegador';
                $response = array(
                    'estado' => 'error',
                    'mensaje' => $errores
                );
                echo json_encode($response);
                exit();
            } else {
                // Insertar el inicio de sesión en la tabla login
                $log_fec_inicio = date('Y-m-d H:i:s');
                $log_est = 'conectado';

                $query = "INSERT INTO login (log_cod, log_fec_inicio, log_est) VALUES (:log_cod, :log_fec_inicio, :log_est)
                          ON CONFLICT (log_cod) DO UPDATE SET log_fec_inicio = EXCLUDED.log_fec_inicio, log_est = EXCLUDED.log_est";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':log_cod', $log_cod);
                $stmt->bindParam(':log_fec_inicio', $log_fec_inicio);
                $stmt->bindParam(':log_est', $log_est);
                $stmt->execute();

                // Iniciar sesión
                $_SESSION['ge_email'] = $ge_email;
                $_SESSION['ge_nom'] = $row['ge_nom']; 
                $_SESSION['ge_cod'] = $row['ge_cod'];
                $_SESSION['ge_caja'] = $row['ge_caja'];
                $_SESSION['ge_ciudad'] = $row['ge_ciudad'];
                $_SESSION['rol'] = $row['rol']; 
                // Redirigir según el rol
                $response = array(
                    'estado' => 'success',
                    'rol' => $row['rol']
                );
                echo json_encode($response);
                exit();
            }
        } else {
            $errores .= 'Contraseña incorrecta';
            $response = array(
                'estado' => 'error',
                'mensaje' => $errores
            );
            echo json_encode($response);
            exit();
        }
    } else {
        $errores .= 'Usuario no encontrado';
        $response = array(
            'estado' => 'error',
            'mensaje' => $errores
        );
        echo json_encode($response);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Iniciar Sesión</title>
    <style>
        body {
            background-image: linear-gradient(to bottom, rgb(91, 94, 83), rgb(201, 129, 129));
            opacity: 0.7;
        }
        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #0c181c;
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            color: #fef6cd;
            font-size: 15px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="flex justify-center items-center h-screen">
    <div class="container mx-auto">
        <div class="flex justify-center">
            <div class="w-full md:w-1/2 lg:w-1/3">
                <form action="login.php" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <div class="text-center">
                        <img src="../img/recover.jpg" width="100px" height="100px" alt="">
                    </div>
                    <?php if (!empty($exito)) { ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $exito; ?>
                    </div>
                <?php } ?>
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Usuario</label>
                        <input id="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 hover:border-blue-500" type="email" name="ge_email" required>
                    </div>
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña</label>
                        <input id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 hover:border-blue-500" type="password" name="ge_clave" required>
                    </div>
                    <?php if (!empty($errores)) { ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold"><?php echo $errores; ?></strong>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-yellow-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 5.652a.5.5 0 0 1 .708.708l-4.95 4.95a.5.5 0 0 1-.708 0l-4.95-4.95a.5.5 0 0 1 .708-.708l4.242 4.242 4.242-4.242z"/></svg>
                        </span>
                    </div>
                <?php } ?>
                    <div class="text-center">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Ingresar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script>
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
            Swal.fire({
                icon: 'success',
                title: '¡Inicio de sesión exitoso!',
                text: 'Redirigiendo...',
                allowOutsideClick: false,
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                if (data.rol === 'gestor') {
                    window.location.href = 'asignacion_gestor.php';
                } else if (data.rol === 'supervisor') {
                    window.location.href = 'estadisticas.php';
                }
            });
        } else if (data.estado === 'redirect') {
            // Redirigir si ya hay una sesión iniciada
            Swal.fire({
                icon: 'info',
                title: 'Sesión ya iniciada',
                text: 'Redirigiendo...',
                allowOutsideClick: false,
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                window.location.href = data.url;
            });
        } else {
            // Mostrar mensaje de error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.mensaje,
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema con la solicitud.',
            confirmButtonText: 'Aceptar'
        });
    });
});
</script>

</body>
</html>