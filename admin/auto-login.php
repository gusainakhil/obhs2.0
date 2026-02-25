<?php
require_once __DIR__ . '/connection.php';

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_GET['user_id'];

// Start session
session_start();

// Prepare query to fetch user details
$stmt = $conn->prepare("SELECT user_id, station_id, status FROM OBHS_users WHERE user_id = ? AND type = 2");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Fetch the record
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Store user data in session
    $_SESSION['station_id'] = $row['station_id'];
    $_SESSION['user_id'] = $row['user_id'];
    $_SESSION['status'] = $row['status'];
    
    // Redirect to dashboard
    header("Location: ../dashboard.php");
    exit;
} else {
    // User not found
    header('Location: login.php');
    exit;
}

$stmt->close();
?>
