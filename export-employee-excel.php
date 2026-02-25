<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];

// Fetch all employees
$query = "SELECT * FROM base_employees WHERE station_id = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
$stmt->close();

// Set headers for CSV download (Excel compatible)
$filename = "employees_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 encoding (helps Excel recognize special characters)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add title row
fputcsv($output, ['Employee List - ' . $station_name]);
fputcsv($output, ['Generated on: ' . date('d-M-Y H:i:s') . ' | Total Employees: ' . count($employees)]);
fputcsv($output, []); // Empty row

// Add header row
fputcsv($output, ['SR. No.', 'Employee Name', 'Employee ID', 'Designation', 'Photo', 'Created Date']);

// Add data rows
$sr_no = 1;
foreach ($employees as $employee) {
    fputcsv($output, [
        $sr_no++,
        $employee['name'],
        $employee['employee_id'],
        $employee['desination'],
        !empty($employee['photo']) ? 'Yes' : 'No',
        date('d-M-Y', strtotime($employee['created_at']))
    ]);
}

fclose($output);
exit();
