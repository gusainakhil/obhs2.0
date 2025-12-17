<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "../includes/connection.php"; // $mysqli

$req = json_decode(file_get_contents("php://input"), true);

$grade      = $req["grade"] ?? null;
$train_no   = $req["train_up"] ?? null;
$from       = $req["from"] ?? null;
$to         = $req["to"] ?? null;
$station_id = $req["station_id"] ?? null;

if (!$grade || !$train_no || !$from || !$to || !$station_id) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

$sql = "SELECT employee_id, employee_name, type_of_attendance, location, photo, created_at
        FROM base_attendance
        WHERE station_id=? AND grade=? AND train_no=?
          AND DATE(created_at) BETWEEN ? AND ?
        ORDER BY employee_id, FIELD(type_of_attendance,'Start of journey','Mid of journey','End of journey')";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("isiss", $station_id, $grade, $train_no, $from, $to);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
    $id = $row['employee_id'];

    if (!isset($data[$id])) {
        $data[$id] = [
            "employee_id" => $id,
            "employee_name" => $row["employee_name"],
            "train_no" => $train_no,
            "checkpoints" => [
                "Start of journey" => null,
                "Mid of journey" => null,
                "End of journey" => null
            ]
        ];
    }

    $checkpoint = $row["type_of_attendance"];
    $data[$id]["checkpoints"][$checkpoint] = [
        "location" => $row["location"],
        "photo" => "/uploads/attendence/" . $row["photo"],
        "created_at" => $row["created_at"]
    ];
}

echo json_encode([
    "status" => "success",
    "data" => array_values($data)
]);
