<?php
require_once __DIR__ . '/connection.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Not authenticated']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid method']);
    exit;
}
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid id']);
    exit;
}
// confirm status column exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `OBHS_reports` LIKE 'status'");
if (!($colCheck && mysqli_num_rows($colCheck) > 0)) {
    echo json_encode(['status' => false, 'message' => 'Status column not available']);
    exit;
}
// toggle status
// read current
$cur = mysqli_prepare($conn, "SELECT status FROM OBHS_reports WHERE id = ? LIMIT 1");
if (!$cur) {
    echo json_encode(['status' => false, 'message' => 'Prepare failed']);
    exit;
}
mysqli_stmt_bind_param($cur, 'i', $id);
mysqli_stmt_execute($cur);
$res = mysqli_stmt_get_result($cur);
if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['status' => false, 'message' => 'Report not found']);
    exit;
}
$row = mysqli_fetch_assoc($res);
$next = ($row['status'] == 1) ? 0 : 1;
$up = mysqli_prepare($conn, "UPDATE OBHS_reports SET status = ? WHERE id = ?");
if (!$up) {
    echo json_encode(['status' => false, 'message' => 'Prepare failed (update)']);
    exit;
}
mysqli_stmt_bind_param($up, 'ii', $next, $id);
$ok = mysqli_stmt_execute($up);
if ($ok) {
    echo json_encode(['status' => true, 'message' => 'Updated', 'status_value' => $next]);
} else {
    echo json_encode(['status' => false, 'message' => 'Update failed']);
}
exit;
?>