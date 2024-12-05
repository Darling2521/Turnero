<?php
session_start();
date_default_timezone_set('America/Bogota');
require_once '../modelo/conexion.php';
if (isset($_SESSION['ge_email'])) {
    header('location:');
}

$errores = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ge_nro_doc = $_POST['ge_nro_doc'];
    $ge_cod = $_POST['ge_cod'];
    $ge_nom = $_POST['ge_nom'];
    $ge_email = $_POST['ge_email'];
$ge_ciudad = isset($_POST['ge_ciudad']) ? $_POST['ge_ciudad'] : '';
    $rol = $_POST['rol'];
    $ge_clave = $_POST['ge_clave'];
    $ge_caja = isset($_POST['ge_caja']) ? $_POST['ge_caja'] : '';

    if (!empty($ge_nom) && !empty($ge_nro_doc) && !empty($ge_cod) && !empty($ge_email) && (!empty($ge_ciudad) || $rol === 'supervisor') && !empty($rol) && (!empty($ge_caja) || $rol === 'supervisor') && !empty($ge_clave)) {
        $ge_email = filter_var(trim($ge_email), FILTER_SANITIZE_EMAIL);
        $ge_clave = trim($ge_clave);

        $query = "SELECT * FROM gestor WHERE ge_email=:ge_email LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':ge_email', $ge_email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $errores .= 'El usuario ya existe </br>';
        }

        if (!$errores) {
            $hash = password_hash($ge_clave, PASSWORD_DEFAULT);
            $query = "INSERT INTO gestor(ge_nro_doc, ge_cod, ge_nom, ge_email, ge_ciudad, rol, ge_clave, ge_caja) VALUES (:ge_nro_doc, :ge_cod, :ge_nom, :ge_email, :ge_ciudad, :rol, :hash, :ge_caja)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':ge_nro_doc', $ge_nro_doc);
            $stmt->bindParam(':ge_cod', $ge_cod);
            $stmt->bindParam(':ge_nom', $ge_nom);
            $stmt->bindParam(':ge_email', $ge_email);
            $stmt->bindParam(':ge_ciudad', $ge_ciudad);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':hash', $hash);
            $stmt->bindParam(':ge_caja', $ge_caja);

            if ($stmt->execute()) {
                $_SESSION['ge_email'] = $ge_email;
                header('location: ');
            } else {
                $errores .= 'Error al registrar el usuario';
            }
        }
    } else {
        $errores .= 'Todos los datos son obligatorios';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jersey+15&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>REGISTRO GESTORES</title>
    <style>
        body {
            background-image: linear-gradient(to bottom, rgb(91, 94, 83), rgb(201, 129, 129));
            opacity: 0.9;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
        }

        h2 {
            font-family: 'Roboto', sans-serif;
        }

        .formulario-container {
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        button[type="submit"] {
            background-color: #000000;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #FF8C00;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-2xl p-8 mx-auto bg-white bg-opacity-75 rounded-lg shadow-lg formulario-container">
        <h2 class="mb-6 text-2xl font-bold text-center text-gray-800 uppercase">Formulario de registro</h2>
        <form action="../php/registro_gestor.php" method="POST" id="registroForm">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="form-group">
                    <label for="ge_nro_doc" class="block mb-1 font-bold">Número de Documento:</label>
                    <input type="text" id="ge_nro_doc" name="ge_nro_doc" pattern="[0-9]+" title="por favor ingrese solo números" placeholder="1765489765" required class="w-full p-2 border border-gray-300 rounded-md">
                    <small id="doc-error" class="text-red-600"></small>
                </div>
                <div class="form-group">
                    <label for="ge_nom" class="block mb-1 font-bold">Nombres:</label>
                    <input type="text" id="ge_nom" name="ge_nom" readonly class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="form-group">
                    <label for="ge_cod" class="block mb-1 font-bold">Código:</label>
                    <input type="text" id="ge_cod" name="ge_cod" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="form-group">
                    <label for="ge_caja" class="block mb-1 font-bold">Nro Caja:</label>
                    <input type="text" id="ge_caja" name="ge_caja" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <div class="form-group">
                    <label for="ge_email" class="block mb-1 font-bold">Email:</label>
                    <input type="email" id="ge_email" name="ge_email" required class="w-full p-2 border border-gray-300 rounded-md">
                    <span id="emailError" style="color: red; display: none;">Por favor, introduce un correo electrónico válido.</span>
                </div>
                <div class="form-group">
                    <label for="ge_clave" class="block mb-1 font-bold">Contraseña:</label>
                    <input type="password" id="ge_clave" name="ge_clave" required class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                <!-- Dentro del <form> -->
                    <div class="form-group col-span-full">
                        <label for="ge_ciudad" class="block mb-1 font-bold">Ciudad:</label>
                        <select id="ge_ciudad" name="ge_ciudad" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="" disabled selected>Seleccione una ciudad</option>
                            <option value="Quito">Quito</option>
                            <option value="Guayaquil">Guayaquil</option>
                        </select>
                    </div>
                <div class="form-group col-span-full">
                    <label for="rol" class="block mb-1 font-bold">Rol:</label>
                    <select id="rol" name="rol" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="" disabled selected>Seleccione una rol</option>
                        <option value="gestor">Gestor</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 text-center">
                <button type="submit" class="w-full px-4 py-2 font-bold text-white rounded-md">Registrarse</button>
            </div>
        </form>
    </div>
    <script src="../js/jquery.js" type="text/javascript"></script>
    <script>
   
        document.getElementById('ge_nro_doc').addEventListener('input', function (event) {
            var input = event.target.value;
            var sanitizedInput = input.replace(/\D/g, '');
            event.target.value = sanitizedInput;

            if (!/^\d{10}$/.test(sanitizedInput)) {
                document.getElementById('doc-error').textContent = 'El nro de documento debe tener exactamente 10 dígitos, solo números.';
                event.target.setCustomValidity('El nro de documento debe tener exactamente 10 dígitos.');
            } else {
                document.getElementById('doc-error').textContent = '';
                event.target.setCustomValidity('');
            }
        });

        document.getElementById('ge_email').addEventListener('input', function () {
            const emailInput = document.getElementById('ge_email');
            const emailError = document.getElementById('emailError');

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(emailInput.value)) {
                emailError.style.display = 'block';
            } else {
                emailError.style.display = 'none';
            }
        });

        document.getElementById('registroForm').addEventListener('submit', function (event) {
            const emailInput = document.getElementById('ge_email');
            const emailError = document.getElementById('emailError');

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(emailInput.value)) {
                event.preventDefault();
                emailError.style.display = 'block';
            } else {
                emailError.style.display = 'none';
            }
        });

        document.getElementById('ge_nro_doc').addEventListener('blur', function (event) {
            getName();
        });

        document.getElementById('ge_nro_doc').addEventListener('keypress', function (event) {
            if (event.which == 13) {
                event.preventDefault();
                getName();
            }
        });

        function getName() {
            var valido = true;

            if (document.getElementById('ge_nro_doc').value == "") {
                document.getElementById('ge_nro_doc').value = "";
                valido = false;
                return;
            }

            if (valido) {
                var parametros = {
                    "ge_nro_doc": document.getElementById('ge_nro_doc').value
                };

                $.ajax({
                    data: parametros,
                    url: '../php/coGetName1.php',
                    type: 'POST',
                    beforeSend: function () {
                        document.getElementById('ge_nom').value = "Buscando nombres...";
                    },
                    success: function (response) {
                        document.getElementById('ge_nom').value = "";
                        if ($.trim(response) != '') {
                            document.getElementById('ge_nom').value = response;
                        } else {
                            alert("Número de documento no encontrado en la base de datos.");
                        }
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        console.error("Error del sistema: " + textStatus + " - " + errorThrown);
                    }
                });
            }
        }

        document.getElementById('rol').addEventListener('change', function (event) {
    const geCajaInput = document.getElementById('ge_caja');
    const geCiudadInput = document.getElementById('ge_ciudad');

    if (event.target.value === 'supervisor') {
        geCajaInput.value = '';
        geCajaInput.disabled = true;
        geCiudadInput.required = false; // Remover el atributo required
    } else {
        geCajaInput.disabled = false;
        geCiudadInput.required = true; // Agregar el atributo required
    }
});

        document.getElementById('turnoForm').addEventListener('submit', function(event) {
            if (document.getElementById('us_nombres').value == "" || document.getElementById('ge_nom').value == "Buscando nombres...") {
                event.preventDefault();
                alert("No se puede solicitar un turno sin un nombre válido asociado al número de documento.");
            }
        });
    </script>
</body>

</html>
