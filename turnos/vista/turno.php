<?php
session_start();

// Verificar si los datos están en la sesión
if (isset($_SESSION['turno_datos'])) {
    // Obtener los datos de la sesión
    $datos_desencriptados = base64_decode($_SESSION['turno_datos']);
    parse_str($datos_desencriptados, $datos);

    // Limpiar la sesión
    unset($_SESSION['turno_datos']);

    // Extraer la fecha y la hora de registro
    $fecha = substr($datos['us_fec_reg'], 0, 10);
    $hora = substr($datos['us_fec_reg'], 11, 5);
} else {
    // Si los datos no están en la sesión, redireccionar a otra página
    header('Location: ../form.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Envío</title>
    <style>
        body {
            background-image: linear-gradient(to bottom, rgb(91, 94, 83), rgb(201, 129, 129));
            opacity: 0.7;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            max-width: 500px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        h2 {
            color: #333;
        }
        p {
            color: #666;
            margin-bottom: 10px;
        }
        .turno {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 20px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="card">
        <h2>Su solicitud ha sido procesada</h2>
        <p>Bienvenid(a), <?php echo htmlspecialchars($datos['us_name']); ?>, su solicitud ha sido procesada correctamente.</p>
        <p>Su número de turno es:</p>
        <p class="turno"><?php echo htmlspecialchars($datos['tu_nro_tur']); ?></p>
        <p><strong>Fecha de turno:</strong> <?php echo htmlspecialchars($fecha); ?></p>
        <p><strong>Hora de turno:</strong> <?php echo htmlspecialchars($hora); ?></p>
        <p>Requerimiento: </p>
        <p class="turno">
    <?php 
    if ($datos['requeriments'] === 'Otro' && !empty($datos['otro_req'])) {
        echo htmlspecialchars($datos['otro_req']);
    } else {
        echo htmlspecialchars($datos['requeriments']);
    }
    ?>
        </p>
        <p>Gracias por usar el servicio de turnos de Recover.</p>
        <button onclick="window.location.href='../index.php'">OK</button>
    </div>
</body>
</html>
