<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ************ REQUIRED HEADERS FOR MOBILE APPS ************
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

// Handle preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once "../includes/connection.php";

// Default response
$response = [
    "status" => false,
    "message" => "",
    "data" => null
];

// Only POST allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response["message"] = "Invalid request method! Use POST with JSON.";
    echo json_encode($response);
    exit;
}

// ************ READ RAW JSON INPUT ************
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

$username = trim($input['username'] ?? "");
$password = trim($input['password'] ?? "");

// Check empty fields
if ($username === "" || $password === "") {
    $response["message"] = "Username and password are required!";
    echo json_encode($response);
    exit;
}

// ************ CHECK USER IN DATABASE ************
$stmt = $mysqli->prepare("
    SELECT 
        u.username,
        u.station_id,
        u.user_id,
        u.app_password,
        s.station_name,
        s.url
    FROM OBHS_users u
    LEFT JOIN OBHS_station s ON u.station_id = s.station_id
    WHERE u.username = ?
     AND (u.type = 2 OR u.type = 3)
");



$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// User found
if ($result->num_rows === 1) {

    $row = $result->fetch_assoc();

    if (password_verify($password, $row['app_password'])) {

        $_SESSION['station_id'] = $row['station_id'];
        $_SESSION['user_id'] = $row['user_id'];

        // Success response
        $response["status"] = true;
        $response["message"] = "Login successful";
        $response["data"] = [
            "user_id" => $row['user_id'],
            "station_id" => $row['station_id'],
            "username" => $row['username'],
            
            "station_name" => $row['station_name'],
            "url" => $row['url']
        ];
    } else {
        $response["message"] = "Invalid password!";
    }

} else {
    $response["message"] = "Username not found!";
}

$stmt->close();
$mysqli->close();

// ************ RETURN JSON RESPONSE ************
echo json_encode($response);
exit;
?>
