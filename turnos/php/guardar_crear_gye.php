<?php
date_default_timezone_set('America/Bogota');
require_once '../modelo/conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $us_nro_doc = $_POST['us_nro_doc'];
    $us_nombres = $_POST['us_nombres'];
    $requeriments = $_POST['requeriments'];
    $us_fec_reg = date('Y-m-d H:i:s');
    $us_ciudad = "Guayaquil";

    if ($requeriments === 'Otro') {
        $otro_req = $_POST['otro_requerimiento'];
    } else {
        $otro_req = NULL;
    }

    $query_check_usuario = "SELECT * FROM usuarios WHERE us_nro_doc = ?";
    $stmt_check_usuario = $conn->prepare($query_check_usuario);
    $stmt_check_usuario->bindParam(1, $us_nro_doc);
    $stmt_check_usuario->execute();
    $usuario_existente = $stmt_check_usuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_existente) {
        $query_usuario = "INSERT INTO usuarios (us_nro_doc, us_nombres, us_fec_reg, us_ciudad) VALUES (?, ?, ?, ?)";
        $stmt_usuario = $conn->prepare($query_usuario);
        $stmt_usuario->bindParam(1, $us_nro_doc);
        $stmt_usuario->bindParam(2, $us_nombres);
        $stmt_usuario->bindParam(3, $us_fec_reg);
        $stmt_usuario->bindParam(4, $us_ciudad);
        $stmt_usuario->execute();
    }

    $tu_nro_tur = generateTurnNumber($us_ciudad);

    $query_turno = "INSERT INTO turno (tu_nro_doc, tu_nombres, tu_nro_tur, tu_fec_reg, tu_est, requeriments, otro_req, tu_ciudad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_turno = $conn->prepare($query_turno);
    $stmt_turno->bindParam(1, $us_nro_doc);
    $stmt_turno->bindParam(2, $us_nombres);
    $stmt_turno->bindParam(3, $tu_nro_tur);
    $stmt_turno->bindParam(4, $us_fec_reg);
    $tu_est = "pendiente";
    $stmt_turno->bindParam(5, $tu_est);
    $stmt_turno->bindParam(6, $requeriments);
    $stmt_turno->bindParam(7, $otro_req);
    $stmt_turno->bindParam(8, $us_ciudad);
    $stmt_turno->execute();

    $_SESSION['turno_datos'] = base64_encode("us_name=" . urlencode($us_nombres) . "&tu_nro_tur=" . urlencode($tu_nro_tur) . "&requeriments=" . urlencode($requeriments) . "&otro_req=" . urlencode($otro_req) . "&us_fec_reg=" . urlencode($us_fec_reg));

    header('Location: ../vista/turno.php');
    exit();
} else {
    header('Location: ../vista/form_gye.php');
    exit();
}

function generateTurnNumber($ciudad) {
    global $conn;

    $query_last_turn = "SELECT tu_nro_tur FROM turno WHERE tu_ciudad = :ciudad ORDER BY tu_nro_tur DESC LIMIT 1";
    $stmt_last_turn = $conn->prepare($query_last_turn);
    $stmt_last_turn->bindParam(':ciudad', $ciudad);
    $stmt_last_turn->execute();
    $last_turn = $stmt_last_turn->fetch(PDO::FETCH_ASSOC);

    if ($last_turn) {
        $last_number = intval(substr($last_turn['tu_nro_tur'], 5));

        if ($last_number >= 100) {
            $new_number = '001';
        } else {
            $new_number = sprintf('%03d', $last_number + 1);
        }
    } else {
        $new_number = '001';
    }

    return "TURN-" . $new_number;
}
?>
