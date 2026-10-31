<?php
require_once __DIR__ . '/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$exportType = $_GET['type'] ?? '';
$allowedExports = [
    'feedback' => ['table' => 'OBHS_passenger', 'file' => 'feedback'],
    'attendance' => ['table' => 'base_attendance', 'file' => 'attendance'],
    'photo_report' => ['table' => 'base_photo_report', 'file' => 'photo-report'],
];

if (!$userId || !isset($allowedExports[$exportType])) {
    http_response_code(400);
    die('Invalid export request.');
}

$stationStmt = $conn->prepare('SELECT u.station_id, s.station_name FROM OBHS_users u INNER JOIN OBHS_station s ON s.station_id = u.station_id WHERE u.user_id = ? AND u.type = 2 LIMIT 1');
$stationStmt->bind_param('i', $userId);
$stationStmt->execute();
$station = $stationStmt->get_result()->fetch_assoc();
$stationStmt->close();
if (!$station) { http_response_code(404); die('Station not found.'); }

$export = $allowedExports[$exportType];
$stationId = (string) $station['station_id'];
$escapedStationId = $conn->real_escape_string($stationId);
$result = $conn->query(
    "SELECT * FROM `{$export['table']}` WHERE station_id = '{$escapedStationId}'",
    MYSQLI_USE_RESULT
);
if (!$result) {
    http_response_code(500);
    die('Unable to prepare export.');
}

$safeStationName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($station['station_name']));
$safeStationName = trim($safeStationName, '-') ?: 'station-' . (int) $station['station_id'];
$filename = $safeStationName . '-' . $export['file'] . '-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'w');
if ($output === false) { http_response_code(500); exit('Unable to create export file.'); }

// UTF-8 BOM ensures Hindi and other Unicode text displays correctly in Excel.
fwrite($output, "\xEF\xBB\xBF");
$headers = [];
foreach ($result->fetch_fields() as $field) { $headers[] = $field->name; }
fputcsv($output, $headers);

while ($row = $result->fetch_assoc()) {
    // Prevent spreadsheet software from treating database text as a formula.
    foreach ($row as &$value) {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) { $value = "\t" . $value; }
    }
    unset($value);
    fputcsv($output, array_values($row));
}

fclose($output);
$result->free();
$conn->close();
exit;
