<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// Database connection
require_once "../includes/connection.php"; // this file sets $mysqli

// SQL JOIN to get station_name for ALL employees
$sql = "SELECT b.id, b.employee_id, b.name, b.desination, b.station_id,
               s.station_name
        FROM base_employees b
        LEFT JOIN OBHS_station s ON b.station_id = s.station_id";

$result = $mysqli->query($sql);

// Check if records found
if ($result->num_rows > 0) {

    $employees = [];

    while ($row = $result->fetch_assoc()) {

        $employees[] = [
            "desination"   => $row["desination"],
            "employee_id"  => $row["name"],
            "id"           => intval($row["id"]),
            "name"         => $row["employee_id"],
            "station_name" => $row["station_name"],
            "station_id"   => intval($row["station_id"])
        ];
    }

    echo json_encode([
        "status" => "success",
        "total"  => count($employees),
        "data"   => $employees
    ]);

} else {

    echo json_encode([
        "status" => "not_found",
        "message" => "No employees found"
    ]);
}

$mysqli->close();
?>
