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
    "data" => [],
    "pnr" => [],
    "station_url" => []   // 🔹 New Station URL array
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

// ******** FETCH REPORTS + USERS + STATION URL ********
$sql = "
    SELECT 
        r.type ,
        r.app_link,
        r.link,
        u.PNR,
        u.pnr_skip,
        u.otp,
        u.otp_skip,
        u.photo,
        u.photo_skip,
        u.station_id,
        s.url AS station_url
    FROM OBHS_reports AS r
    LEFT JOIN OBHS_users AS u ON r.user_id = u.user_id
    LEFT JOIN OBHS_station AS s ON u.station_id = s.station_id
    WHERE r.user_id = ?
    ORDER BY r.id ASC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $reports = [];
    $pnr = "";
    $station_url = "";

    while ($row = $result->fetch_assoc()) {

        // Reports list
        $reports[] = [
            "reports_name" => $row['app_link'],
            "type" => $row['type'],
            "link" => $row['link']
            
        ];

        // PNR
        $pnr = $row["PNR"];
        $pnr_skip = $row["pnr_skip"];
        $otp = $row["otp"];
        $otp_skip = $row["otp_skip"];
        $photo = $row["photo"];
        $photo_skip = $row["photo_skip"];

        // Station URL
        $station_url = $row["station_url"];
    }

    $response["status"] = true;
    $response["message"] = "Data fetched successfully";
    $response["data"] = $reports;

    // Separate PNR array
    $response["pnr"] = [
        "PNR" => $pnr ,
        "pnr_skip" => $pnr_skip ,
        "otp" => $otp ,
        "otp_skip" => $otp_skip ,
        "photo" => $photo ,
        "photo_skip" => $photo_skip
        
        
    ];

    // Separate Station URL array
    $response["station_url"] = [
        "url" => $station_url
    ];

} else {
    $response["message"] = "No data found!";
}

$stmt->close();
$mysqli->close();

echo json_encode($response);
exit;
?>
