<?php
require_once __DIR__ . '/connection.php';
session_start();

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: user-list.php');
  exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
// new_status field is set by the modal JS
$new_status = null;
if (isset($_POST['new_status'])) {
  $new_status = (int)$_POST['new_status'];
} elseif (isset($_POST['status_select'])) {
  $new_status = (int)$_POST['status_select'];
}

if ($user_id <= 0 || !is_int($new_status)) {
  // invalid input, redirect back
  header('Location: user-list.php');
  exit;
}

// Update using prepared statement
$sql = "UPDATE `OBHS_users` SET `status` = ? WHERE `user_id` = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
  mysqli_stmt_bind_param($stmt, 'ii', $new_status, $user_id);
  $exec = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

// Redirect back to the list (regardless of success) — UI can show failure later if needed
header('Location: user-list.php');
exit;
