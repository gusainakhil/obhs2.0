<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

// DB connection
require_once __DIR__ . '/../includes/connection.php';

// config
$uploadDir = __DIR__ . '/../uploads/attendence/';
$maxSize = 5 * 1024 * 1024; // 5MB
$allowed = ['image/jpeg', 'image/png', 'image/jpg'];

// ensure folder exists
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// helper
function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// required field
$station_id = isset($_POST['station_id']) ? trim($_POST['station_id']) : '';
if ($station_id === '') jsonErr('station_id is required.');

// read other fields
$employee_name      = $_POST['employee_name']      ?? '';
$employee_id        = $_POST['employee_id']        ?? '';
$type_of_attendance = $_POST['type_of_attendance'] ?? '';
$train_no           = $_POST['train_no']           ?? '';
$desination         = $_POST['desination']         ?? '';
$grade              = $_POST['grade']              ?? '';
$location           = $_POST['location']           ?? '';
$toc                = $_POST['toc']                ?? '';

// -----------------------------------------
// HANDLE PHOTO
// -----------------------------------------
$photo_filename = "";

if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    $f = $_FILES['photo'];

    if ($f['error'] !== UPLOAD_ERR_OK) jsonErr('Upload error.');
    if ($f['size'] > $maxSize) jsonErr('File too large.');

    // mime detect
    $mime = @getimagesize($f['tmp_name'])['mime'] ?? '';
    if (!in_array($mime, $allowed)) jsonErr('Invalid file type.');

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($ext == '') $ext = ($mime === 'image/png') ? 'png' : 'jpg';

    $filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        jsonErr('Failed to save file.', 500);
    }

    $photo_filename = $filename;
}


// UNIQUE EMPLOYEE NAME LOGIC 


$employeeNameUnique = $employee_id; // default

if (
    strcasecmp($type_of_attendance, 'Start Of journey') === 0 
) {
    // generate unique
    $employeeNameUnique = 'EMP-' . $employee_name . '-' . time() . '-' . bin2hex(random_bytes(4));
}
else {
    // fetch last from base_attendance
    $stmt = $mysqli->prepare("
        SELECT employee_name_unique 
        FROM base_attendance 
        WHERE employee_id = ? AND station_id = ?
        ORDER BY id DESC LIMIT 1
    ");

    $stmt->bind_param("ss", $employee_id, $station_id);
    $stmt->execute();
    $stmt->bind_result($lastUnique);

    if ($stmt->fetch()) {
        $employeeNameUnique = $lastUnique;
    }

    $stmt->close();
}

// -----------------------------------------------------------
// INSERT INTO base_attendance
// -----------------------------------------------------------

$sql = "INSERT INTO base_attendance
    (employee_name, employee_id, station_id, type_of_attendance, train_no, desination, grade, location, photo, toc, employee_name_unique, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $mysqli->prepare($sql);
if (!$stmt) jsonErr("DB prepare failed: ".$mysqli->error, 500);

$stmt->bind_param(
    "sssssssssss",
    $employee_name,
    $employee_id,
    $station_id,
    $type_of_attendance,
    $train_no,
    $desination,
    $grade,
    $location,
    $photo_filename,
    $toc,
    $employeeNameUnique
);

if (!$stmt->execute()) {

    if ($photo_filename && file_exists($uploadDir . $photo_filename)) {
        @unlink($uploadDir . $photo_filename);
    }

    jsonErr("DB execute failed: ".$stmt->error, 500);
}

$id = $stmt->insert_id;

$stmt->close();
$mysqli->close();

// SUCCESS RESPONSE
echo json_encode([
    'status' => 'success',
    'id' => (int)$id,
    'photo_filename' => $photo_filename,
    'employee_name_unique' => $employeeNameUnique
]);
exit;
?>
