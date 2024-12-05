<?php
session_start();
// Iniciar la sesión
date_default_timezone_set('America/Bogota'); 



if (!isset($_SESSION['ge_email'])) {
    header('Location: login.php');
    exit();
}

// Deshabilitar el almacenamiento en caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../modelo/conexion.php';

// Obtener el código y la ciudad del gestor logueado
$ge_cod = $_SESSION['ge_cod'];
$ge_ciudad = $_SESSION['ge_ciudad'];
$rol = $_SESSION['rol'];

// Consultar los turnos pendientes filtrados por ciudad
$query = "SELECT * FROM turno WHERE tu_est = 'pendiente' AND tu_ciudad = :ge_ciudad AND NOT EXISTS (
    SELECT 1 FROM asignacion WHERE turno.tu_nro_tur = asignacion.as_nro_tur AND turno.tu_ciudad = asignacion.as_ciudad AND asignacion.as_est = 'asignado'
)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
$stmt->execute();
$turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consultar las asignaciones
$queryAsignaciones = "SELECT asignacion.*, turno.requeriments FROM asignacion JOIN turno ON asignacion.as_nro_tur = turno.tu_nro_tur AND asignacion.as_est = 'asignado' AND asignacion.as_cod = :ge_cod AND asignacion.as_ciudad = turno.tu_ciudad";
$stmtAsignaciones = $conn->prepare($queryAsignaciones);
$stmtAsignaciones->bindParam(':ge_cod', $ge_cod, PDO::PARAM_INT);
// $stmtAsignaciones->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
$stmtAsignaciones->execute();
$asignaciones = $stmtAsignaciones->fetchAll(PDO::FETCH_ASSOC);

// Manejar la asignación de turnos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign'])) {
    // Verificar si el gestor tiene un turno asignado actualmente
    $queryTurnoAsignado = "SELECT * FROM turno WHERE tu_ges_as = :ge_nom AND tu_est = 'asignado' AND tu_ciudad = :ge_ciudad";
    $stmtTurnoAsignado = $conn->prepare($queryTurnoAsignado);
    $stmtTurnoAsignado->bindParam(':ge_nom', $_SESSION['ge_nom'], PDO::PARAM_STR);
    $stmtTurnoAsignado->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
    $stmtTurnoAsignado->execute();
    $turnoActual = $stmtTurnoAsignado->fetch(PDO::FETCH_ASSOC);

    // Proceder con la asignación o finalización según el estado actual del gestor y del turno
    if ($turnoActual) {
        // Si el gestor tiene un turno asignado, finalizarlo
        $queryFinalizarTurno = "UPDATE turno SET tu_est = 'finalizado' WHERE tu_nro_tur = :tu_nro_tur AND tu_ciudad = :ge_ciudad";
        $stmtFinalizarTurno = $conn->prepare($queryFinalizarTurno);
        $stmtFinalizarTurno->bindParam(':tu_nro_tur', $turnoActual['tu_nro_tur'], PDO::PARAM_INT);
        $stmtFinalizarTurno->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
        $stmtFinalizarTurno->execute();

        // Actualizar el estado del turno a "finalizado" en la tabla "asignacion"
        $queryUpdateAsignacion = "UPDATE asignacion SET as_est = 'finalizado' WHERE as_nro_tur = :tu_nro_tur AND as_ciudad = :ge_ciudad";
        $stmtUpdateAsignacion = $conn->prepare($queryUpdateAsignacion);
        $stmtUpdateAsignacion->bindParam(':tu_nro_tur', $turnoActual['tu_nro_tur'], PDO::PARAM_INT);
        $stmtUpdateAsignacion->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
        $stmtUpdateAsignacion->execute();
    }

    // Asignar el siguiente turno pendiente
    $querySiguienteTurno = "SELECT tu_nro_tur, tu_nro_doc FROM turno WHERE tu_est = 'pendiente' AND tu_ciudad = :ge_ciudad AND NOT EXISTS (
        SELECT 1 FROM asignacion WHERE turno.tu_nro_tur = asignacion.as_nro_tur AND turno.tu_ciudad = asignacion.as_ciudad AND asignacion.as_est = 'asignado'
    ) LIMIT 1";
    $stmtSiguienteTurno = $conn->prepare($querySiguienteTurno);
    $stmtSiguienteTurno->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
    $stmtSiguienteTurno->execute();
    $siguienteTurno = $stmtSiguienteTurno->fetch(PDO::FETCH_ASSOC);

    if ($siguienteTurno) {
        // Asignar el siguiente turno pendiente al gestor
        $queryAsignarTurno = "UPDATE turno SET tu_est = 'asignado', tu_ges_as = :ge_nom, tu_cod = :ge_cod WHERE tu_nro_tur = :tu_nro_tur AND tu_ciudad = :ge_ciudad";
        $stmtAsignarTurno = $conn->prepare($queryAsignarTurno);
        $stmtAsignarTurno->bindParam(':tu_nro_tur', $siguienteTurno['tu_nro_tur'], PDO::PARAM_INT);
        $stmtAsignarTurno->bindParam(':ge_nom', $_SESSION['ge_nom'], PDO::PARAM_STR);
        $stmtAsignarTurno->bindParam(':ge_cod', $_SESSION['ge_cod'], PDO::PARAM_INT);
        $stmtAsignarTurno->bindParam(':ge_ciudad', $ge_ciudad, PDO::PARAM_STR);
        $stmtAsignarTurno->execute();

        // Insertar los datos de la asignación en la tabla asignacion
        $queryInsertarAsignacion = "INSERT INTO asignacion (as_nro_doc, as_nro_tur, as_caja, as_est, as_cod, as_obs, as_fec_reg, as_ciudad) VALUES (:as_nro_doc, :as_nro_tur, :as_caja, :as_est, :as_cod, NULL, :as_fec_reg, :as_ciudad)";
        $stmtInsertarAsignacion = $conn->prepare($queryInsertarAsignacion);
        $stmtInsertarAsignacion->bindParam(':as_nro_doc', $siguienteTurno['tu_nro_doc'], PDO::PARAM_STR); 
        $stmtInsertarAsignacion->bindParam(':as_nro_tur', $siguienteTurno['tu_nro_tur'], PDO::PARAM_INT);
        $stmtInsertarAsignacion->bindParam(':as_caja', $_SESSION['ge_caja'], PDO::PARAM_STR);
        $stmtInsertarAsignacion->bindValue(':as_est', 'asignado', PDO::PARAM_STR);
        $stmtInsertarAsignacion->bindValue(':as_cod', $ge_cod, PDO::PARAM_INT);
        $stmtInsertarAsignacion->bindValue(':as_fec_reg', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmtInsertarAsignacion->bindValue(':as_ciudad', $ge_ciudad, PDO::PARAM_STR);
        $stmtInsertarAsignacion->execute();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnos Pendientes</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- <meta http-equiv="refresh" content="15"> -->
</head>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.modal-content {
    background-color: #fff;
    margin: auto;
    padding: 20px;
    border: 1px solid #888;
    width: 90%;
    max-width: 400px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.input-field {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

.submit-button {
    background-color: #1d72b8;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.submit-button:hover {
    background-color: #155a8a;
}


.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    border-radius: 10px;
}


.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}


.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: bold;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="ciudad"] {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

.submit-button {
    display: block;
    margin: 20px auto;
    padding: 10px 20px;
    background-color: #ff69b4;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.submit-button:hover {
    background-color: #ff1493;
}
</style>

<body class="bg-gray-100">
    <nav class="bg-gray-800 py-4">
        <div class="container mx-auto flex justify-between items-center px-4">
            <div class="flex items-center">
                <div class="h-2 w-2 rounded-full bg-green-500 mr-2"></div>
                <span
                    class="text-white font-semibold mr-2"><?php echo $_SESSION['ge_nom'] . ' (' . $_SESSION['ge_ciudad'] . ')'; ?></span>
            </div>

            <a href="#" class="text-white font-bold text-xl"></a>
            <div>
                <div class="flex items-center">
                    <!-- Botón de Actualizar -->
                    <button id="refreshButton"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-4">
                        <ion-icon name="refresh-outline" class="mr-2"></ion-icon>
                        Actualizar
                    </button>
                    <!-- Botón de Asignar -->
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="inline">
                        <?php if (!empty($turnos)) { ?>
                        <?php $primerTurno = $turnos[0]; ?>
                        <input type="hidden" name="tu_nro_tur" value="<?php echo $primerTurno['tu_nro_tur']; ?>">
                        <button type="submit" name="assign"
                            class="bg-pink-500 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-4">
                            <ion-icon name="person-add-outline" class="mr-2"></ion-icon>
                            Asignar
                        </button>
                        <?php } ?>
                    </form>
                    <!-- Botón de Cerrar Sesión -->
                    <form action="logout.php" method="post" class="inline">
                        <button type="submit" name="logout"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <ion-icon name="log-out-outline" style="vertical-align: middle;"></ion-icon>
                            <span style="vertical-align: middle;">Salir</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>


    <div class="container mx-auto py-8">


        <h1 class="text-3xl font-bold mb-4">Turnos Pendientes</h1>

        <!-- Primera tabla: Turnos Pendientes -->
        <div class="overflow-x-auto">
            <table class="table-auto w-full bg-white shadow-md rounded-lg overflow-hidden">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Número de Documento</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Nombres</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Requerimiento</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Número de Turno</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Fecha de Registro</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Estado</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($turnos as $turno) { ?>
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $turno['tu_nro_doc']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $turno['tu_nombres']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $turno['requeriments']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $turno['tu_nro_tur']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <?php 
    $fechaTurno = new DateTime($turno['tu_fec_reg']); 
    echo $fechaTurno->format('d/m/Y H:i:s'); 
    ?>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <?php
                            $estado = $turno['tu_est'];
                            $color = 'gray';
                            switch ($estado) {
                                case 'pendiente':
                                    $color = 'yellow';
                                    break;
                                case 'asignado':
                                    $color = 'green';
                                    break;
                                case 'finalizado':
                                    $color = 'pink';
                                    break;
                            }
                            ?>
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                <?php echo $estado; ?>
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <!-- Segunda tabla: Asignaciones -->
        <div class="overflow-x-auto mt-8">
            <table class="table-auto w-full bg-white shadow-md rounded-lg overflow-hidden">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Número de Documento</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Número de Turno</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Requerimientos</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Fecha de Registro</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Estado</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <!-- Cuerpo de la tabla -->
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($asignaciones as $asignacion) { ?>
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $asignacion['as_nro_doc']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $asignacion['as_nro_tur']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap"><?php echo $asignacion['requeriments']; ?></td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <?php 
                        $fecha = new DateTime($asignacion['as_fec_reg']); 
                        echo $fecha->format('d/m/Y H:i:s'); 
                        ?>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <?php
                        $estado = $asignacion['as_est'];
                        $color = 'gray';
                        switch ($estado) {
                            case 'pendiente':
                                $color = 'yellow';
                                break;
                            case 'asignado':
                                $color = 'green';
                                break;
                            case 'finalizado':
                                $color = 'pink';
                                break;
                        }
                        ?>
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                <?php echo $estado; ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <button
                                class="flex items-center justify-center bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline showModal"
                                data-nro-tur="<?php echo $asignacion['as_nro_tur']; ?>"
                                data-ciudad="<?php echo $asignacion['as_ciudad']; ?>">
                                <ion-icon name="eye-outline" class="text-xl mr-2"></ion-icon>
                                Mostrar
                            </button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <form id="observacionesForm">
                    <input type="hidden" name="as_nro_tur" id="as_nro_tur" value="">
                    <input type="hidden" name="as_ciudad" id="as_ciudad" value="">

                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <input type="text" name="observaciones" id="observaciones" class="input-field" value="">
                    </div>
                    <div class="form-group">
                        <label for="us_email">Correo Electrónico:</label>
                        <input type="email" name="us_email" id="us_email" class="input-field" value="">
                    </div>
                    <div class="form-group">
                        <label for="us_tel">Teléfono Celular:</label>
                        <input type="tel" name="us_tel" id="us_tel" class="input-field" value="">
                    </div>
                    <button type="submit" class="submit-button">Actualizar</button>
                </form>
                <div id="message" class="hidden mt-4 p-2 rounded text-white"></div>
            </div>
        </div>
        <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
        <!-- Incluye el script de Ionicons -->
        <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

        <script>
        // Agrega un controlador de eventos para el clic en el botón
        document.getElementById('refreshButton').addEventListener('click', function() {
            // Refresca la página
            location.reload();
        });
        setInterval(function() {
            location.reload();
        }, 15000);

        var modal = document.getElementById("myModal");
        var btns = document.getElementsByClassName("modalBtn");
        var span = document.getElementsByClassName("close")[0];

        for (var i = 0; i < btns.length; i++) {
            btns[i].onclick = function() {
                modal.style.display = "flex";
                var turno = this.getAttribute("data-turno");
                document.getElementById("as_nro_tur").value = turno;
            }
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }


        var modal = document.getElementById("myModal");
        var btns = document.querySelectorAll(".showModal");
        var span = document.getElementsByClassName("close")[0];

        function setAsNroTurValue(nroTur) {
            document.getElementById("as_nro_tur").value = nroTur;
            document.getElementById("as_nro_tur").value = nroTur;

        }

        btns.forEach(function(btn) {
            btn.onclick = function() {
                modal.style.display = "block";
                var nroTur = this.getAttribute("data-nro-tur");
                var ciudad = this.getAttribute("data-ciudad");
                setAsNroTurValue(nroTur);
                document.getElementById("as_ciudad").value = ciudad;
            }
        });

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        document.getElementById("observacionesForm").onsubmit = function(event) {
            event.preventDefault();

            var formData = new FormData(this);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "actualizar_observaciones.php", true);
            xhr.onload = function() {
                var messageDiv = document.getElementById("message");
                if (xhr.status === 200) {
                    messageDiv.innerHTML = xhr.responseText;
                    messageDiv.classList.remove("hidden");
                    messageDiv.classList.add("bg-green-500");
                } else {
                    messageDiv.innerHTML = "Error al enviar los datos.";
                    messageDiv.classList.remove("hidden");
                    messageDiv.classList.add("bg-red-500");
                }
                setTimeout(function() {
                    messageDiv.classList.add("hidden");
                }, 3000);
            };
            xhr.send(formData);
        }




        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault();

            var formData = new FormData(this);
            formData.append('assign', 'true');

            fetch('asignacion_gestor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Error al procesar la solicitud');
                })
                .then(data => {

                    console.log(data);
                    location.reload();
                })
                .catch(error => {

                    console.error('Error:', error.message);
                });
        });
        </script>
</body>

</html>