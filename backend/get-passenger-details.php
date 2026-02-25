<?php
session_start();
header('Content-Type: application/json');

include '../includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$passenger_id = $_GET['id'] ?? '';
$station_id = $_SESSION['station_id'] ?? 0;

if (empty($passenger_id)) {
    echo json_encode(['error' => 'Passenger ID required']);
    exit;
}

$response = [
    'passenger' => null,
    'feedback' => [],
    'markings' => []
];

// Get passenger details
$p_sql = "SELECT * FROM OBHS_passenger WHERE id = ? AND station_id = ?";
if ($stmt = $mysqli->prepare($p_sql)) {
    $stmt->bind_param("si", $passenger_id, $station_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['passenger'] = $result->fetch_assoc();
    $stmt->close();
}

if (!$response['passenger']) {
    echo json_encode(['error' => 'Passenger not found']);
    exit;
}

// Get feedback with questions
$f_sql = "SELECT f.feed_param, f.value, 
          COALESCE(q.eng_question, q.hin_question, 'Question') as question
          FROM OBHS_feedback f
          LEFT JOIN OBHS_questions q ON q.id = f.feed_param
          WHERE f.passenger_id = ?
          ORDER BY f.feed_param ASC";

if ($stmt = $mysqli->prepare($f_sql)) {
    $stmt->bind_param("s", $passenger_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['feedback'][] = $row;
    }
    $stmt->close();
}

// Get marking options
$m_sql = "SELECT category, value FROM OBHS_marking WHERE station_id = ? ORDER BY value DESC";
if ($stmt = $mysqli->prepare($m_sql)) {
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['markings'][] = $row;
    }
    $stmt->close();
}

echo json_encode($response);
