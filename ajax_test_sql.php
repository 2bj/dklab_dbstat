<?php
require_once "overall.php";
$MAX_LEN = 256;

$dsnId = $_POST['dsn_id'];
$sql = $_POST['sql'];

$error = testSqlAndReturnError($dsnId, $sql);
if (strlen($error) > $MAX_LEN) $error = substr($error, 0, $MAX_LEN - 15) . "...";

header("Content-type: application/json");
echo json_encode(array("error" => $error ));
