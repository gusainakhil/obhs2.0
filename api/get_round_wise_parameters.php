<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");
require_once "../includes/connection.php";   // mysqli connection
$station_id = 0;
$type = "";

// From GET
if (isset($_GET["station_id"])) {
    $station_id = intval($_GET["station_id"]);
}
if (isset($_GET["type"])) {
    $type = $_GET["type"];
}

// From POST (JSON or form)
$input = json_decode(file_get_contents("php://input"), true);

if (isset($input["station_id"])) {
    $station_id = intval($input["station_id"]);
}

if (isset($input["type"])) {
    $type = $input["type"];
}

// Validate input
if ($station_id === 0) {
    echo json_encode(["error" => "station_id missing"]);
    exit;
}
if ($type === "") {
    echo json_encode(["error" => "type missing"]);
    exit;
}

$response = [
    "questions" => [],
    "marking"   => []
];



$sql_q = "
    SELECT eng_question,id , hin_question , type  FROM OBHS_questions 
    WHERE station_id = $station_id
      AND type = '$type'
    ORDER BY id ASC
";
$res_q = mysqli_query($mysqli, $sql_q);

while ($row = mysqli_fetch_assoc($res_q)) {
    $response["questions"][] = $row;
    $response["id"][] = $row;
}


$sql_m = "
    SELECT category , value FROM OBHS_marking 
    WHERE station_id = $station_id
     
    ORDER BY id ASC
";
$res_m = mysqli_query($mysqli, $sql_m);

while ($row = mysqli_fetch_assoc($res_m)) {
    $response["marking"][] = $row;
}

echo json_encode($response, JSON_PRETTY_PRINT);