<?php
require_once("../api/apiRC.php");

session_start();

$ge_nro_doc=$_REQUEST['ge_nro_doc'];

echo getNomCI($ge_nro_doc);


?>
