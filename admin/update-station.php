<?php
// Ensure no HTML (warnings/notices) are sent — return JSON only
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

header('Content-Type: application/json; charset=utf-8');

// Convert PHP errors/notices into JSON responses
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    $msg = "PHP error: [$errno] $errstr in $errfile on line $errline";
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
});
set_exception_handler(function ($ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    exit;
});

require_once __DIR__ . '/connection.php';

// Expect JSON body with station_id and station_name
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['station_id']) || !isset($data['station_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$station_id = (int)$data['station_id'];
$station_name = trim($data['station_name']);

if ($station_id <= 0 || $station_name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing station id or name']);
    exit;
}

$update_sql = "UPDATE `OBHS_station` SET `station_name` = ? WHERE `station_id` = ?";
if ($stmt = mysqli_prepare($conn, $update_sql)) {
    mysqli_stmt_bind_param($stmt, 'si', $station_name, $station_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB execute error']);
    }
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB prepare error']);
}

// restore previous handlers (optional)
restore_error_handler();
restore_exception_handler();
