<?php
// upload_photo_report_simple.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

// DB connection (must set $mysqli)
require_once __DIR__ . '/../includes/connection.php';

// config
$uploadDir   = __DIR__ . '/../uploads/photos/';
$maxSize     = 5 * 1024 * 1024; // 5 MB
$allowedMime = ['image/jpeg', 'image/png', 'image/jpg'];

// ensure upload dir
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

// helper
function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['status'=>'error','message'=>$msg]);
    exit;
}

// required field
$station_id = isset($_POST['station_id']) ? trim($_POST['station_id']) : '';
if ($station_id === '') jsonErr('station_id is required.');

// read other fields (optional)
$train_no         = isset($_POST['train_no']) ? trim($_POST['train_no']) : '';
$grade            = isset($_POST['grade']) ? trim($_POST['grade']) : '';
$coach_no         = isset($_POST['coach_no']) ? trim($_POST['coach_no']) : '';
$coach_type       = isset($_POST['coach_type']) ? trim($_POST['coach_type']) : '';
$cleaning_area    = isset($_POST['cleaning_area']) ? trim($_POST['cleaning_area']) : '';
$time_of_cleaning = isset($_POST['time_of_cleaning']) ? trim($_POST['time_of_cleaning']) : '';
$janitor          = isset($_POST['janitor']) ? trim($_POST['janitor']) : '';
$location         = isset($_POST['location']) ? trim($_POST['location']) : '';
$location_link    = isset($_POST['location_link']) ? trim($_POST['location_link']) : '';

// photo is required (as in your original)
if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
    jsonErr('Photo file is required.');
}

$f = $_FILES['photo'];
if ($f['error'] !== UPLOAD_ERR_OK) jsonErr('Upload error (code ' . $f['error'] . ').');
if ($f['size'] > $maxSize) jsonErr('File too large. Max 5 MB.');

// determine mime
$mime = @getimagesize($f['tmp_name'])['mime'] ?? '';
if (empty($mime) && function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $fi ? finfo_file($fi, $f['tmp_name']) : '';
    if ($fi) finfo_close($fi);
}
if (!in_array($mime, $allowedMime)) jsonErr('Invalid file type. Only JPG/PNG allowed.');

// build filename: YYYYMMDD_His_rand.ext
$ext = pathinfo($f['name'], PATHINFO_EXTENSION) ?: ($mime === 'image/png' ? 'png' : 'jpg');
$ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext));
$filename = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(6)), 0, 12) . '.' . $ext;
if (strlen($filename) > 100) $filename = substr($filename, 0, 96) . '.' . $ext;

$dest = $uploadDir . $filename;
if (!move_uploaded_file($f['tmp_name'], $dest)) jsonErr('Failed to save uploaded file.', 500);

// Insert into DB (store filename only)
$sql = "INSERT INTO base_photo_report
        (train_no, grade, coach_no, coach_type, station_id, photo, cleaning_area, time_of_cleaning, janitor, location, location_link, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $mysqli->prepare($sql) or jsonErr('DB prepare failed: ' . $mysqli->error, 500);

$bind = $stmt->bind_param(
    'sssssssssss',
    $train_no,
    $grade,
    $coach_no,
    $coach_type,
    $station_id,
    $filename,           // store only filename
    $cleaning_area,
    $time_of_cleaning,
    $janitor,
    $location,
    $location_link
);

if (!$bind) {
    // cleanup file on error
    if (file_exists($dest)) @unlink($dest);
    jsonErr('DB bind failed: ' . $stmt->error, 500);
}

if (!$stmt->execute()) {
    if (file_exists($dest)) @unlink($dest);
    jsonErr('DB execute failed: ' . $stmt->error, 500);
}

$id = $stmt->insert_id;
$stmt->close();
$mysqli->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Photo report uploaded',
    'id' => (int)$id,
    'photo_filename' => $filename
]);
exit;
