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

$station_id = $input['station_id'] ?? "";

if ($station_id === "" || !is_numeric($station_id)) {
    $response["message"] = "station_id is required!";
    echo json_encode($response);
    exit;
}

// ******** FETCH TRAIN NUMBERS ********
$sql = "
    SELECT DISTINCT train_no 
    FROM base_fb_target 
    WHERE station = ?
    ORDER BY train_no ASC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();

$trains = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Add comma before train number as you requested
        $trains[] = [
            "train_no" =>  $row["train_no"]
        ];
    }

    $response["status"] = true;
    $response["message"] = "Train numbers fetched successfully";
    $response["data"] = $trains;

} else {
    $response["message"] = "No train numbers found!";
}

$stmt->close();
$mysqli->close();

echo json_encode($response);
exit;
?>
