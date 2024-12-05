<?php
date_default_timezone_set('America/Bogota');
require_once '../modelo/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['as_nro_tur'], $_POST['as_ciudad'], $_POST['observaciones'], $_POST['us_email'], $_POST['us_tel'])) {
        $nroTurno = $_POST['as_nro_tur'];
        $ciudad = $_POST['as_ciudad'];
        $observaciones = $_POST['observaciones'];
        $email = $_POST['us_email'];
        $telefono = $_POST['us_tel'];
        $fechaActualizacion = date('Y-m-d H:i:s');

        try {
            // Iniciar la transaccion
            $conn->beginTransaction();

            // Actualizar las observaciones en la tabla 'asignacion'
            $query1 = "UPDATE asignacion SET as_obs = :observaciones WHERE as_nro_tur = :nroTurno AND as_ciudad = :ciudad";
            $stmt1 = $conn->prepare($query1);
            $stmt1->bindParam(':observaciones', $observaciones);
            $stmt1->bindParam(':nroTurno', $nroTurno);
            $stmt1->bindParam(':ciudad', $ciudad);
            $stmt1->execute();

            // Actualizar los datos del usuario en la tabla 'usuarios'
            $query2 = "UPDATE usuarios SET us_email = :email, us_tel = :telefono, us_fec_act = :fechaActualizacion WHERE us_nro_doc = (SELECT as_nro_doc FROM asignacion WHERE as_nro_tur = :nroTurno AND as_ciudad = :ciudad)";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindParam(':email', $email);
            $stmt2->bindParam(':telefono', $telefono);
            $stmt2->bindParam(':fechaActualizacion', $fechaActualizacion);
            $stmt2->bindParam(':nroTurno', $nroTurno);
            $stmt2->bindParam(':ciudad', $ciudad);
            $stmt2->execute();

            // Actualizar los datos del usuario en la tabla 'turno' solo si el estado es 'asignado'
            $query3 = "UPDATE turno SET tu_email = :email, tu_tel = :telefono WHERE tu_nro_doc = (SELECT as_nro_doc FROM asignacion WHERE as_nro_tur = :nroTurno AND as_ciudad = :ciudad) AND tu_est = 'asignado'";
            $stmt3 = $conn->prepare($query3);
            $stmt3->bindParam(':email', $email);
            $stmt3->bindParam(':telefono', $telefono);
            $stmt3->bindParam(':nroTurno', $nroTurno);
            $stmt3->bindParam(':ciudad', $ciudad);
            $stmt3->execute();

            // Confirmar la transaccion
            $conn->commit();
            echo "Datos actualizados correctamente.";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "Error al actualizar los datos: " . $e->getMessage();
        }
    } else {
        echo "Datos incompletos enviados en el formulario.";
    }
} else {
    echo "M�todo de solicitud no permitido.";
}
?>
