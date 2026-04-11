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
$fullLocationInput  = $_POST['fullLocation']       ?? '';
$fullLocation = '';

$formatFullLocation = function(array $locationArray): string {
    $parts = [];

    foreach ($locationArray as $key => $value) {
        if (is_null($value)) {
            $displayValue = 'null';
        } elseif (is_bool($value)) {
            $displayValue = $value ? 'true' : 'false';
        } elseif (is_scalar($value)) {
            $displayValue = trim((string)$value);
        } else {
            $displayValue = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $parts[] = $key . ': ' . $displayValue;
    }

    return implode(', ', $parts);
};

if (is_array($fullLocationInput)) {
    $fullLocation = $formatFullLocation($fullLocationInput);
} else {
    $rawFullLocation = trim((string)$fullLocationInput);

    if ($rawFullLocation !== '') {
        $decodedFullLocation = json_decode($rawFullLocation, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFullLocation)) {
            $fullLocation = $formatFullLocation($decodedFullLocation);
        } else {
            $fullLocation = $rawFullLocation;
        }
    }
}

// -------------------------------------------------
// ONLY CHANGE: FORMAT LOCATION
// -------------------------------------------------
$formatted_location = $location;

if (!empty($location)) {
    $parts = array_map('trim', explode(',', $location));

    if (count($parts) >= 3) {
        $lat  = $parts[0];
        $long = $parts[1];
        $place = implode(', ', array_slice($parts, 2));

        $formatted_location = "lati: {$lat} longi: {$long} {$place}";
    }
}

// -----------------------------------------
// HANDLE PHOTO - Compress and convert to WebP
// -----------------------------------------
$photo_filename = "";

if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    $f = $_FILES['photo'];

    if ($f['error'] !== UPLOAD_ERR_OK) jsonErr('Upload error.');
    if ($f['size'] > $maxSize) jsonErr('File too large.');

    $mime = @getimagesize($f['tmp_name'])['mime'] ?? '';
    if (!in_array($mime, $allowed)) jsonErr('Invalid file type.');

    // Build filename with .webp extension
    $filename = $station_id . '_' . date('Ymd_His') . '_' . uniqid() . '.webp';
    $dest     = $uploadDir . $filename;

    // Create image resource from uploaded file
    $sourceImage = null;
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        $sourceImage = @imagecreatefromjpeg($f['tmp_name']);
    } elseif ($mime === 'image/png') {
        $sourceImage = @imagecreatefrompng($f['tmp_name']);
    }

    if (!$sourceImage) {
        jsonErr('Failed to process image. Unsupported or corrupted file.', 500);
    }

    // Get original dimensions
    $origWidth = imagesx($sourceImage);
    $origHeight = imagesy($sourceImage);

    // Resize if image is too large (max 1920px on longest side)
    $maxDimension = 1920;
    $newWidth = $origWidth;
    $newHeight = $origHeight;

    if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
        if ($origWidth > $origHeight) {
            $newWidth = $maxDimension;
            $newHeight = (int)($origHeight * ($maxDimension / $origWidth));
        } else {
            $newHeight = $maxDimension;
            $newWidth = (int)($origWidth * ($maxDimension / $origHeight));
        }
        
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        // Preserve transparency for PNG
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagedestroy($sourceImage);
        $sourceImage = $resizedImage;
    }

    // Convert and compress to WebP (quality 80)
    $webpQuality = 80;
    if (!imagewebp($sourceImage, $dest, $webpQuality)) {
        imagedestroy($sourceImage);
        jsonErr('Failed to save image as WebP.', 500);
    }

    imagedestroy($sourceImage);
    $photo_filename = $filename;
}

// UNIQUE EMPLOYEE NAME LOGIC 
$employeeNameUnique = $employee_id;

if (strcasecmp($type_of_attendance, 'Start Of journey') === 0) {
    $employeeNameUnique = 'EMP-' . $employee_name . '-' . time() . '-' . bin2hex(random_bytes(4));
} else {

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
    (employee_name, employee_id, station_id, type_of_attendance, train_no, desination, grade, location, photo, toc, employee_name_unique, fullLocation, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $mysqli->prepare($sql);
if (!$stmt) jsonErr("DB prepare failed: ".$mysqli->error, 500);

$stmt->bind_param(
    "ssssssssssss",
    $employee_name,
    $employee_id,
    $station_id,
    $type_of_attendance,
    $train_no,
    $desination,
    $grade,
    $formatted_location, // ONLY CHANGE USED HERE
    $photo_filename,
    $toc,
    $employeeNameUnique,
    $fullLocation
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
