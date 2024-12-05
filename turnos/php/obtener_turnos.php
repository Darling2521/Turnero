<?php
require_once '../modelo/conexion.php';

// Aqui verifico si se ha enviado el parámetro 'ciudad' en la URL
$ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';

// Consultar los turnos asignados para la ciudad especificada
$queryAsignados = "SELECT t.tu_nro_tur, t.tu_nombres, a.as_caja
                   FROM turno t
                   INNER JOIN asignacion a ON t.tu_nro_tur = a.as_nro_tur
                   AND t.tu_ciudad = a.as_ciudad
                   WHERE t.tu_est = 'asignado'";

if (!empty($ciudad)) {
    $queryAsignados .= " AND t.tu_ciudad = '$ciudad'";
}

$stmtAsignados = $conn->query($queryAsignados);
$turnosAsignados = $stmtAsignados->fetchAll(PDO::FETCH_ASSOC);

// Consulto los turnos pendientes para la ciudad especificada
$queryPendientes = "SELECT t.tu_nro_tur, t.tu_nombres
                    FROM turno t
                    WHERE t.tu_est = 'pendiente'";

if (!empty($ciudad)) {
    $queryPendientes .= " AND t.tu_ciudad = '$ciudad'";
}

$stmtPendientes = $conn->query($queryPendientes);
$turnosPendientes = $stmtPendientes->fetchAll(PDO::FETCH_ASSOC);

// Prepar0 el arreglo para almacenar los resultados
$resultados = array(
    'asignados' => $turnosAsignados,
    'pendientes' => $turnosPendientes
);

// Devolver los resultados como JSON
header('Content-Type: application/json');
echo json_encode($resultados);
?>
