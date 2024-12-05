<?php
// Establecer la zona horaria
date_default_timezone_set('America/Bogota');

// Incluir el archivo de conexión a la base de datos
require_once '../modelo/conexion.php';

// Iniciar la sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['ge_email'])) {
    // Si no ha iniciado sesión, redirigir a la página de inicio de sesión
    header("Location: login.php");
    exit();
}

// Obtener el código del usuario
$log_cod = $_SESSION['ge_cod'];

// Verificar si el gestor tiene un turno activo
$query = "SELECT * FROM turno WHERE tu_cod = :log_cod AND tu_est = 'asignado'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':log_cod', $log_cod);
$stmt->execute();
$turno = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el gestor tiene un turno activo, finalizarlo
if ($turno) {
    // Actualizar el estado del turno a "finalizado"
    $queryUpdateTurno = "UPDATE turno SET tu_est = 'finalizado' WHERE tu_cod = :log_cod AND tu_est = 'asignado'";
    $stmtUpdateTurno = $conn->prepare($queryUpdateTurno);
    $stmtUpdateTurno->bindParam(':log_cod', $log_cod);
    $stmtUpdateTurno->execute();

    //Actualizar el estado del turno a "finalizado"
    $queryUpdateAsignacion = "UPDATE asignacion SET as_est = 'finalizado' WHERE as_cod = :log_cod AND as_est = 'asignado'";
    $stmtUpdateAsignacion = $conn->prepare($queryUpdateAsignacion);
    $stmtUpdateAsignacion->bindParam(':log_cod', $log_cod);
    $stmtUpdateAsignacion->execute();
}

// Obtener la fecha actual para el registro de cierre de sesión
$log_fec_cierre = date('Y-m-d H:i:s');

// Actualizar el registro en la tabla login con la fecha de cierre y el estado 'desconectado'
$query = "INSERT INTO login (log_cod, log_fec_cierre, log_est) 
          VALUES (:log_cod, :log_fec_cierre, 'desconectado')
          ON CONFLICT (log_cod) DO UPDATE SET log_fec_cierre = EXCLUDED.log_fec_cierre, log_est = EXCLUDED.log_est";

$stmt = $conn->prepare($query);
$stmt->bindParam(':log_cod', $log_cod);
$stmt->bindParam(':log_fec_cierre', $log_fec_cierre);
$stmt->execute();

// Destruir todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir al usuario a la página de inicio de sesión
header("Location: login.php");
exit();

?>