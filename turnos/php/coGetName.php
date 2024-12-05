<?php
require_once("../api/apiRC.php");

session_start();

$us_nro_doc=$_REQUEST['us_nro_doc'];

echo getNomCI($us_nro_doc);


?>
