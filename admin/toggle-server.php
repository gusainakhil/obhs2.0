<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/connection.php';

// Basic auth / session check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$station_id = isset($input['station_id']) ? (int)$input['station_id'] : 0;
$server = isset($input['server']) ? (int)$input['server'] : null;

if ($station_id <= 0 || ($server !== 0 && $server !== 1)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

// Update the DB safely
$stmt = $conn->prepare("UPDATE OBHS_station SET url = ? WHERE station_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB prepare failed']);
    exit;
}

$stmt->bind_param('ii', $server, $station_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Server updated']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB update failed']);
}
$stmt->close();
$conn->close();
