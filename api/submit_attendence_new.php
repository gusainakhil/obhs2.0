<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

// DB connection
require_once __DIR__ . '/../includes/connection.php';

// config
$uploadDir = __DIR__ . '/../uploads/attendence/';
$maxSize   = 5 * 1024 * 1024; // 5MB
$allowed   = ['image/jpeg', 'image/png', 'image/jpg'];

// ensure folder exists
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    jsonErr('Upload directory could not be created.', 500);
}

// helper
function jsonErr($error, $code = 400, $extra = [])
{
    http_response_code($code);
    echo json_encode(array_merge([
        'status' => 'error',
        'error'  => $error
    ], $extra));
    exit;
}

// required field
$station_id = isset($_POST['station_id']) ? trim($_POST['station_id']) : '';
if ($station_id === '') {
    jsonErr('station_id is required.');
}

// read other fields
$employee_name      = isset($_POST['employee_name']) ? trim($_POST['employee_name']) : '';
$employee_id        = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
$type_of_attendance = isset($_POST['type_of_attendance']) ? trim($_POST['type_of_attendance']) : '';
$train_no           = isset($_POST['train_no']) ? trim($_POST['train_no']) : '';
$desination         = isset($_POST['desination']) ? trim($_POST['desination']) : ''; // kept same as your DB/input
$grade              = isset($_POST['grade']) ? trim($_POST['grade']) : '';
$location           = isset($_POST['location']) ? trim($_POST['location']) : '';
$toc                = isset($_POST['toc']) ? trim($_POST['toc']) : '';
$fullLocationInput  = $_POST['fullLocation'] ?? '';
$fullLocation       = '';

// optional required checks
if ($employee_id === '') {
    jsonErr('employee_id is required.');
}

if ($type_of_attendance === '') {
    jsonErr('type_of_attendance is required.');
}

$formatFullLocation = function (array $locationArray): string {
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
// DUPLICATE ATTENDANCE CHECK FOR SAME DAY
// now includes station_id also
// -------------------------------------------------
$duplicateSql = "
    SELECT id 
    FROM base_attendance 
    WHERE employee_id = ?
      AND station_id = ?
      AND type_of_attendance = ?
      AND train_no = ?
      AND grade = ?
      AND DATE(created_at) = CURDATE()
    ORDER BY id DESC
    LIMIT 1
";

$duplicateStmt = $mysqli->prepare($duplicateSql);

if (!$duplicateStmt) {
    jsonErr('DB prepare failed: ' . $mysqli->error, 500);
}

$duplicateStmt->bind_param(
    "sssss",
    $employee_id,
    $station_id,
    $type_of_attendance,
    $train_no,
    $grade
);

if (!$duplicateStmt->execute()) {
    $duplicateStmt->close();
    jsonErr('Duplicate check failed: ' . $duplicateStmt->error, 500);
}

$duplicateStmt->store_result();

if ($duplicateStmt->num_rows > 0) {
    $duplicateStmt->close();
    jsonErr(
        "Your {$type_of_attendance} attendance has already been marked today. Please submit another attendance type.",
        409,
        ['type_of_attendance' => $type_of_attendance]
    );
}

$duplicateStmt->close();

// -------------------------------------------------
// FORMAT LOCATION
// expected: lat,long,place
// -------------------------------------------------
$formatted_location = $location;

if (!empty($location)) {
    $parts = array_map('trim', explode(',', $location));

    if (count($parts) >= 3) {
        $lat   = $parts[0];
        $long  = $parts[1];
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

    if ($f['error'] !== UPLOAD_ERR_OK) {
        jsonErr('Upload error.');
    }

    if ($f['size'] > $maxSize) {
        jsonErr('File too large.');
    }

    $imageInfo = @getimagesize($f['tmp_name']);
    $mime = $imageInfo['mime'] ?? '';

    if (!in_array($mime, $allowed, true)) {
        jsonErr('Invalid file type.');
    }

    // Build filename with .webp extension
    $filename = $station_id . '_' . date('Ymd_His') . '_' . uniqid('', true) . '.webp';
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
    $origWidth  = imagesx($sourceImage);
    $origHeight = imagesy($sourceImage);

    // Resize if too large
    $maxDimension = 1920;
    $newWidth  = $origWidth;
    $newHeight = $origHeight;

    if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
        if ($origWidth > $origHeight) {
            $newWidth  = $maxDimension;
            $newHeight = (int) round($origHeight * ($maxDimension / $origWidth));
        } else {
            $newHeight = $maxDimension;
            $newWidth  = (int) round($origWidth * ($maxDimension / $origHeight));
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        imagedestroy($sourceImage);
        $sourceImage = $resizedImage;
    }

    // Convert and compress to WebP
    $webpQuality = 80;

    if (!imagewebp($sourceImage, $dest, $webpQuality)) {
        imagedestroy($sourceImage);
        jsonErr('Failed to save image as WebP.', 500);
    }

    imagedestroy($sourceImage);
    $photo_filename = $filename;
}

// -------------------------------------------------
// UNIQUE EMPLOYEE NAME LOGIC
// -------------------------------------------------
$normalizedAttendanceType = strtolower(trim(preg_replace('/\s+/', ' ', $type_of_attendance)));
$employeeNameUnique = $employee_id;

// Start of journey => create new unique token
if ($normalizedAttendanceType === 'start of journey') {
    $employeeNameUnique = 'EMP-' . preg_replace('/[^a-zA-Z0-9]/', '', $employee_name) . '-' . time() . '-' . bin2hex(random_bytes(4));
} else {
    // fetch previous employee_name_unique
    $uniqueSql = "
        SELECT employee_name_unique
        FROM base_attendance
        WHERE employee_id = ? AND station_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";

    $uniqueStmt = $mysqli->prepare($uniqueSql);

    if (!$uniqueStmt) {
        if ($photo_filename && file_exists($uploadDir . $photo_filename)) {
            @unlink($uploadDir . $photo_filename);
        }
        jsonErr('DB prepare failed: ' . $mysqli->error, 500);
    }

    $uniqueStmt->bind_param("ss", $employee_id, $station_id);

    if (!$uniqueStmt->execute()) {
        $uniqueStmt->close();
        if ($photo_filename && file_exists($uploadDir . $photo_filename)) {
            @unlink($uploadDir . $photo_filename);
        }
        jsonErr('Failed to fetch employee unique name: ' . $uniqueStmt->error, 500);
    }

    $uniqueStmt->bind_result($lastUnique);

    if ($uniqueStmt->fetch() && !empty($lastUnique)) {
        $employeeNameUnique = $lastUnique;
    }

    $uniqueStmt->close();
}

// -----------------------------------------------------------
// INSERT INTO base_attendance
// -----------------------------------------------------------
$sql = "INSERT INTO base_attendance
    (
        employee_name,
        employee_id,
        station_id,
        type_of_attendance,
        train_no,
        desination,
        grade,
        location,
        photo,
        toc,
        employee_name_unique,
        fullLocation,
        created_at,
        updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    if ($photo_filename && file_exists($uploadDir . $photo_filename)) {
        @unlink($uploadDir . $photo_filename);
    }
    jsonErr('DB prepare failed: ' . $mysqli->error, 500);
}

$stmt->bind_param(
    "ssssssssssss",
    $employee_name,
    $employee_id,
    $station_id,
    $type_of_attendance,
    $train_no,
    $desination,
    $grade,
    $formatted_location,
    $photo_filename,
    $toc,
    $employeeNameUnique,
    $fullLocation
);

if (!$stmt->execute()) {
    if ($photo_filename && file_exists($uploadDir . $photo_filename)) {
        @unlink($uploadDir . $photo_filename);
    }

    $stmt->close();
    jsonErr('DB execute failed: ' . $stmt->error, 500);
}

$id = $stmt->insert_id;

$stmt->close();
$mysqli->close();

// SUCCESS RESPONSE
echo json_encode([
    'status'               => 'success',
    'message'              => 'Attendance submitted successfully.',
    'id'                   => (int)$id,
    'type_of_attendance'   => $type_of_attendance,
    'photo_filename'       => $photo_filename,
    'employee_name_unique' => $employeeNameUnique
]);
exit;
?>