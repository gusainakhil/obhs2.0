<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: update-calculation.php');
  exit;
}

$station_id = isset($_POST['station_id']) ? (int)$_POST['station_id'] : (isset($_POST['station']) ? (int)$_POST['station'] : 0);
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

$entries = [];

// Support arrays: category[] and value[]
if (!empty($_POST['category']) && is_array($_POST['category']) && !empty($_POST['value']) && is_array($_POST['value'])) {
  $cats = $_POST['category'];
  $vals = $_POST['value'];
  $count = min(count($cats), count($vals));
  for ($i = 0; $i < $count; $i++) {
    $c = trim($cats[$i]);
    $v = trim($vals[$i]);
    if ($c === '' && $v === '') continue;
    $entries[] = ['category' => $c, 'value' => $v];
  }
} else {
  // Fallback: value1..value5 and value1_rating..value5_rating
  for ($i = 1; $i <= 5; $i++) {
    $val_key = 'value' . $i;
    $cat_key = 'value' . $i . '_rating';
    if (isset($_POST[$val_key]) && isset($_POST[$cat_key])) {
      $v = trim((string)$_POST[$val_key]);
      $c = trim((string)$_POST[$cat_key]);
      if ($v === '' && $c === '') continue;
      $entries[] = ['category' => $c, 'value' => $v];
    }
  }
}

if ($station_id <= 0 || $user_id <= 0) {
  $_SESSION['calc_flash_error'] = 'Missing station or user selection.';
  header('Location: update-calculation.php'); exit;
}

if (empty($entries)) {
  $_SESSION['calc_flash_error'] = 'No marking values provided.';
  header('Location: update-calculation.php'); exit;
}

// prepare insert
$now = date('Y-m-d H:i:s');
$sql = "INSERT INTO `OBHS_marking` (`station_id`,`user_id`,`category`,`value`,`created_at`) VALUES (?,?,?,?,?)";
if (!$stmt = mysqli_prepare($conn, $sql)) {
  $_SESSION['calc_flash_error'] = 'Database error: ' . mysqli_error($conn);
  header('Location: update-calculation.php'); exit;
}

$ok_all = true;
foreach ($entries as $e) {
  $category = substr($e['category'], 0, 255);
  $value = substr($e['value'], 0, 10);
  mysqli_stmt_bind_param($stmt, 'iisss', $station_id, $user_id, $category, $value, $now);
  if (!mysqli_stmt_execute($stmt)) {
    $ok_all = false;
    break;
  }
}
mysqli_stmt_close($stmt);

if ($ok_all) {
  $_SESSION['calc_flash_success'] = 'Marking values saved.';
} else {
  $_SESSION['calc_flash_error'] = 'Failed to save some marking values.';
}
header('Location: update-calculation.php');
exit;
