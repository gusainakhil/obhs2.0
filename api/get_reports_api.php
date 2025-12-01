<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ******** API HEADERS ********
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../includes/connection.php";

$response = [
    "status" => false,
    "message" => "",
    "data" => []
];

// Allow only POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response["message"] = "Invalid request method!";
    echo json_encode($response);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents("php://input"), true);

$user_id = $input['user_id'] ?? "";

if ($user_id === "" || !is_numeric($user_id)) {
    $response["message"] = "user_id is required!";
    echo json_encode($response);
    exit;
}

// ******** FETCH MENU / REPORTS ********
$sql = "
    SELECT reports_name, link 
    FROM OBHS_reports 
    WHERE user_id = ? 
    ORDER BY id ASC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $reports = [];

    while ($row = $result->fetch_assoc()) {
        $reports[] = [
            "reports_name" => $row['reports_name'],
            "link" => $row['link']
        ];
    }

    $response["status"] = true;
    $response["message"] = "Reports fetched successfully";
    $response["data"] = $reports;

} else {
    $response["message"] = "No reports found!";
}

$stmt->close();
$mysqli->close();

echo json_encode($response);
exit;
?>
