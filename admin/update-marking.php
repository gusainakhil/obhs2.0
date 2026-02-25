<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/connection.php';

// Basic auth check
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

$marking_id = isset($input['marking_id']) ? (int)$input['marking_id'] : 0;
$action = isset($input['action']) ? trim($input['action']) : 'update';
$value = isset($input['value']) ? trim($input['value']) : '';

if ($marking_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid marking ID']);
    exit;
}

// Handle delete action
if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM OBHS_marking WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB prepare failed']);
        exit;
    }
    
    $stmt->bind_param('i', $marking_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Marking deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB delete failed']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Handle update action
if ($value === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing value']);
    exit;
}

// Update the marking value
$stmt = $conn->prepare("UPDATE OBHS_marking SET value = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB prepare failed']);
    exit;
}

$stmt->bind_param('si', $value, $marking_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Marking updated']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB update failed']);
}
$stmt->close();
$conn->close();
