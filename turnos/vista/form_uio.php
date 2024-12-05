<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Turno</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-image: linear-gradient(to bottom, rgb(91, 94, 83), rgb(201, 129, 129));
            opacity: 0.7;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeInDown 0.5s ease-out;
        }

        @keyframes fadeInDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bg-orange-500 {
            background-color: #f97316;
        }

        .hover\:bg-orange-600:hover {
            background-color: #ea580c;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="max-w-md w-full p-8 bg-white rounded-lg shadow-md form-container">
        <h2 class="text-2xl font-bold mb-4 text-center">Formulario de Solicitud de Turno</h2>
        <form id="turnoForm" action="../php/guardar_crear_uio.php" method="POST">
            <div class="mb-4">
                <label for="us_nro_doc" class="block text-sm font-semibold mb-2">Número de Documento:</label>
                <input type="text" id="us_nro_doc" name="us_nro_doc" title="Por favor ingrese solo números"
                    placeholder="Número de documento" required
                    class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:border-blue-300 transition duration-300">
                <small id="doc-error" class="text-red-600"></small>
            </div>
            <div class="mb-4">
                <label for="us_nombres" class="block text-sm font-semibold mb-2">Nombre y Apellido:</label>
                <input type="text" id="us_nombres" name="us_nombres"  readonly
                    class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:border-blue-300 transition duration-300">
            </div>
            <div class="mb-4">
                <label for="requeriments" class="block text-sm font-semibold mb-2">Requerimiento:</label>
                <select id="requeriments" name="requeriments" required
                    class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:border-blue-300 transition duration-300"
                    onchange="mostrarInputOtro(this)">
                    <option value="" disabled selected>Seleccione un requerimiento</option>
                    <option value="Consulta de Deudas">Consulta de Deudas</option>
                    <option value="Pago de Deudas o Convenios">Pago de Deudas o Convenios</option>
                    <option value="Actualización de Datos">Actualización de Datos</option>
                    <option value="Reclamo">Reclamo</option>
                    <option value="Solicitud de Certificado">Solicitud de Certificado</option>
                    <option value="Otro">Otro</option>
                </select>
                <input type="text" id="otro_requerimiento" name="otro_requerimiento"
                    placeholder="Especifique otro requerimiento"
                    class="hidden w-full border-gray-300 rounded-md px-3 py-2 mt-2 focus:outline-none focus:ring focus:border-blue-300 transition duration-300">
            </div>
            <button type="submit"
                class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition duration-300">Solicitar
                Turno</button>
        </form>
    </div>

    <script src="../js/jquery.js" type="text/javascript"></script>
    <script>
        document.getElementById('us_nro_doc').addEventListener('input', function(event) {
            var input = event.target.value;
            var sanitizedInput = input.replace(/\D/g, '');
            event.target.value = sanitizedInput;

            if (!/^\d{10}$/.test(sanitizedInput)) {
                document.getElementById('doc-error').textContent =
                    'El nro de documento debe tener exactamente 10 dígitos, solo números.';
                event.target.setCustomValidity('El nro de documento debe tener exactamente 10 dígitos.');
            } else {
                document.getElementById('doc-error').textContent = '';
                event.target.setCustomValidity('');
            }
        });

        function mostrarInputOtro(select) {
            var otroInput = document.getElementById('otro_requerimiento');
            if (select.value === 'Otro') {
                otroInput.classList.remove('hidden');
            } else {
                otroInput.classList.add('hidden');
            }
        }

        document.getElementById('us_nro_doc').addEventListener('blur', function(event) {
            getName();
        });

        document.getElementById('us_nro_doc').addEventListener('keypress', function(event) {
            if (event.which == 13) {
                event.preventDefault();
                getName();
            }
        });

        function getName() {
            var valido = true;

            if (document.getElementById('us_nro_doc').value == "") {
                document.getElementById('us_nro_doc').value = "";
                valido = false;
                return;
            }

            if (valido) {
                var parametros = {
                    "us_nro_doc": document.getElementById('us_nro_doc').value
                };

                $.ajax({
                    data: parametros,
                    url: '../php/coGetName.php',
                    type: 'POST',
                    beforeSend: function() {
                        document.getElementById('us_nombres').value = "Buscando nombres...";
                    },
                    success: function(response) {
                        document.getElementById('us_nombres').value = "";
                        if ($.trim(response) != '') {
                            document.getElementById('us_nombres').value = response;
                        } else {
                            alert("Número de documento no encontrado en la base de datos.");
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        console.error("Error del sistema: " + textStatus + " - " + errorThrown);
                    }
                });
            }
        }

        document.getElementById('turnoForm').addEventListener('submit', function(event) {
            if (document.getElementById('us_nombres').value == "" || document.getElementById('us_nombres').value == "Buscando nombres...") {
                event.preventDefault();
                alert("No se puede solicitar un turno sin un nombre válido asociado al número de documento.");
            }
        });
    </script>
</body>

</html>
