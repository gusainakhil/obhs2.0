<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

// DB connection
require_once __DIR__ . '/../includes/connection.php';

// config
$uploadDir = __DIR__ . '/../uploads/employee/';
$maxSize = 5 * 1024 * 1024; // 5MB
$allowed = ['image/jpeg','image/png','image/jpg'];

// ensure upload dir
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// helper
function jsonErr($msg, $code = 400){ http_response_code($code); echo json_encode(['status'=>'error','message'=>$msg]); exit; }

// required minimal field
$station_id = isset($_POST['station_id']) ? trim($_POST['station_id']) : '';
if ($station_id === '') jsonErr('station_id is required.');

// read other fields (optional)
$employee_name      = isset($_POST['employee_name']) ? trim($_POST['employee_name']) : '';
$employee_id        = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
$type_of_attendance = isset($_POST['type_of_attendance']) ? trim($_POST['type_of_attendance']) : '';
$train_no           = isset($_POST['train_no']) ? trim($_POST['train_no']) : '';
$desination         = isset($_POST['desination']) ? trim($_POST['desination']) : '';
$grade              = isset($_POST['grade']) ? trim($_POST['grade']) : '';
$location           = isset($_POST['location']) ? trim($_POST['location']) : '';
$toc                = isset($_POST['toc']) ? trim($_POST['toc']) : '';

// handle photo (optional) — store filename only, empty string if none
$photo_filename = '';
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK) jsonErr('Upload error.');
    if ($f['size'] > $maxSize) jsonErr('File too large.');
    // detect mime
    $mime = @getimagesize($f['tmp_name'])['mime'] ?? (function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) : '');
    if (!in_array($mime, $allowed)) jsonErr('Invalid file type.');

    $ext = pathinfo($f['name'], PATHINFO_EXTENSION) ?: ($mime === 'image/png' ? 'png' : 'jpg');
    $ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext));
    $filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) jsonErr('Failed to save file.', 500);
    $photo_filename = $filename;
}

// insert into DB (store filename only)
$sql = "INSERT INTO base_attendance
    (employee_name, employee_id, station_id, type_of_attendance, train_no, desination, grade, location, photo, toc, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $mysqli->prepare($sql) or jsonErr('DB prepare failed: ' . $mysqli->error, 500);

$stmt->bind_param(
    'ssssssssss',
    $employee_name,
    $employee_id,
    $station_id,
    $type_of_attendance,
    $train_no,
    $desination,
    $grade,
    $location,
    $photo_filename,
    $toc
) or jsonErr('DB bind failed: ' . $stmt->error, 500);

if (!$stmt->execute()) {
    // cleanup uploaded file on DB failure
    if ($photo_filename && file_exists($uploadDir . $photo_filename)) @unlink($uploadDir . $photo_filename);
    jsonErr('DB execute failed: ' . $stmt->error, 500);
}

$id = $stmt->insert_id;
$stmt->close();
$mysqli->close();

echo json_encode([
    'status' => 'success',
    'id' => (int)$id,
    'photo_filename' => $photo_filename
]);
exit;
