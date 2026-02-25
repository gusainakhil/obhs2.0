<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once "../includes/connection.php";

// Get stationId from query param
$stationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Decide table based on conditionstation_id
if ($stationId === 17) {
    $table = "base_employees_jodhpur";
} else {
    $table = "base_employees";
}

// SQL JOIN
$sql = "
    SELECT b.id, b.employee_id, b.name, b.desination, b.station_id,
           s.station_name
    FROM $table b
    LEFT JOIN OBHS_station s 
        ON b.station_id = s.station_id
";

// Filter if stationId given
if ($stationId > 0) {
    $sql .= " WHERE b.station_id = $stationId";
}

$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {

    $employees = [];

    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            "id"           => intval($row["id"]),
            "employee_id"  => $row["employee_id"],
            "name"         => $row["name"],
            "desination"   => $row["desination"],
            "station_id"   => intval($row["station_id"]),
            "station_name" => $row["station_name"]
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
