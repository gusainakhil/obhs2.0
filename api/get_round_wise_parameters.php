<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require_once "../includes/connection.php";   // mysqli connection

$station_id = 0;
$type = "";

// -------- GET --------
if (!empty($_GET["station_id"])) {
    $station_id = intval($_GET["station_id"]);
}

if (!empty($_GET["type"])) {
    $type = $_GET["type"] == "TTE" ? "AC" : $_GET["type"];
}

// -------- POST (JSON / form) --------
$input = json_decode(file_get_contents("php://input"), true);

if (is_array($input)) {

    if (!empty($input["station_id"])) {
        $station_id = intval($input["station_id"]);
    }

    if (!empty($input["type"])) {
        $type = $input["type"] == "TTE" ? "AC" : $input["type"];
    }
}

// -------- VALIDATION --------
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

// -------- QUESTIONS --------
$sql_q = "
    SELECT id, eng_question, hin_question, type
    FROM OBHS_questions 
    WHERE station_id = $station_id
      AND type = '" . mysqli_real_escape_string($mysqli, $type) . "'
    ORDER BY id ASC
";

$res_q = mysqli_query($mysqli, $sql_q);

if (!$res_q) {
    echo json_encode(["error" => "Query error (questions)"]);
    exit;
}

while ($row = mysqli_fetch_assoc($res_q)) {
    $response["questions"][] = $row;
}

// -------- MARKING --------
$sql_m = "
    SELECT category, value
    FROM OBHS_marking
    WHERE station_id = $station_id
    ORDER BY value DESC
";

$res_m = mysqli_query($mysqli, $sql_m);

if (!$res_m) {
    echo json_encode(["error" => "Query error (marking)"]);
    exit;
}

while ($row = mysqli_fetch_assoc($res_m)) {
    $response["marking"][] = $row;
}

echo json_encode($response, JSON_PRETTY_PRINT);
