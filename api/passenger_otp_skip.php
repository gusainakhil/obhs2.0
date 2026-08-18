<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../includes/connection.php";

$response = [
    "status" => false,
    "message" => "",
    "data" => null
];



$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    $input = [];
}

$user_id = $input['user_id']
    ?? $_POST['user_id']
    ?? $_GET['user_id']
    ?? "";



if ($user_id === "" || !is_numeric($user_id)) {
    $response["message"] = "user_id is required!";
    echo json_encode($response);
    exit;
}



$columns = [];

$column_result = $mysqli->query("SHOW COLUMNS FROM `OBHS_users`");

if (!$column_result) {
    $response["message"] = "Unable to read OBHS_users columns!";
    echo json_encode($response);
    $mysqli->close();
    exit;
}

while ($column = $column_result->fetch_assoc()) {
    $columns[strtolower($column['Field'])] = $column['Field'];
}



$skip_column = null;

if (isset($columns['passenger_otp_skip'])) {
    $skip_column = $columns['passenger_otp_skip'];
} elseif (isset($columns['otp_skip'])) {
    $skip_column = $columns['otp_skip'];
}

if ($skip_column === null) {
    $response["message"] = "passenger_otp_skip column not found!";
    echo json_encode($response);
    $mysqli->close();
    exit;
}



if (!isset($columns['otp_form_app'])) {
    $response["message"] = "otp_form_app column not found!";
    echo json_encode($response);
    $mysqli->close();
    exit;
}

$otp_form_app_column = $columns['otp_form_app'];



$sql = "
    SELECT
        `user_id`,
        `username`,
        `station_id`,
        `$skip_column` AS passenger_otp_skip,
        `$otp_form_app_column` AS otp_form_app
    FROM `OBHS_users`
    WHERE `user_id` = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    $response["message"] = "Unable to prepare request!";
    echo json_encode($response);
    $mysqli->close();
    exit;
}

$user_id = (int) $user_id;

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    $response["message"] = "Unable to execute request!";
    echo json_encode($response);
    $stmt->close();
    $mysqli->close();
    exit;
}

$result = $stmt->get_result();


if ($result && $result->num_rows === 1) {

    $row = $result->fetch_assoc();

    $response["status"] = true;
    $response["message"] = "Data fetched successfully";

    $response["data"] = [
        "user_id" => (int) $row["user_id"],
        "username" => $row["username"],
        "station_id" => (int) $row["station_id"],
        "passenger_otp_skip" => (int) $row["passenger_otp_skip"],
        "otp_form_app" => (int) $row["otp_form_app"]
    ];

} else {

    $response["message"] = "No data found!";
}



$stmt->close();
$mysqli->close();

echo json_encode($response);
exit;

?>