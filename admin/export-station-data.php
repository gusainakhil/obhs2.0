<?php
require_once __DIR__ . '/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$exportType = $_GET['type'] ?? '';
$allowedExports = [
    'feedback' => ['table' => 'OBHS_passenger', 'file' => 'feedback', 'date_column' => 'created_at'],
    'attendance' => ['table' => 'base_attendance', 'file' => 'attendance', 'date_column' => 'created_at'],
    'photo_report' => ['table' => 'base_photo_report', 'file' => 'photo-report', 'date_column' => 'created_at'],
];

function exportPhotoDetails(string $photoName, string $folder): array
{
    $photoName = trim($photoName);
    $fileName = basename(str_replace('\\', '/', $photoName));
    $allowedFolders = ['attendence', 'photos'];
    if (!in_array($folder, $allowedFolders, true)) { return ['available' => false, 'file_name' => '', 'path' => '', 'absolute_path' => '', 'size_bytes' => 0, 'public_url' => '']; }
    $photoDirectory = realpath(__DIR__ . '/../uploads/' . $folder);
    $details = [
        'available' => false,
        'file_name' => $fileName,
        'path' => $fileName !== '' ? 'uploads/' . $folder . '/' . $fileName : '',
        'absolute_path' => '',
        'size_bytes' => 0,
        'public_url' => $fileName !== '' ? 'https://obhs.beatleanalytics.in/uploads/' . $folder . '/' . rawurlencode($fileName) : '',
    ];

    if ($fileName === '' || $photoDirectory === false) {
        return $details;
    }

    $candidate = realpath($photoDirectory . DIRECTORY_SEPARATOR . $fileName);
    $directoryPrefix = rtrim($photoDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if ($candidate === false || !is_file($candidate) || strncmp($candidate, $directoryPrefix, strlen($directoryPrefix)) !== 0) {
        return $details;
    }

    $details['available'] = true;
    $details['absolute_path'] = $candidate;
    $details['size_bytes'] = (int) filesize($candidate);
    return $details;
}

function openPublicPhoto(string $fileName, string $folder)
{
    $fileName = basename(str_replace('\\', '/', trim($fileName)));
    if ($fileName === '' || !in_array($folder, ['attendence', 'photos'], true)) { return false; }

    $url = 'https://obhs.beatleanalytics.in/uploads/' . $folder . '/' . rawurlencode($fileName);
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'timeout' => 45,
        'follow_location' => 1,
        'max_redirects' => 3,
        'ignore_errors' => true,
        'header' => "User-Agent: OBHS-Attendance-Exporter/1.0\r\n",
    ]]);
    $stream = @fopen($url, 'rb', false, $context);
    if ($stream === false && function_exists('curl_init')) {
        $temporaryStream = fopen('php://temp/maxmemory:5242880', 'w+b');
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FILE => $temporaryStream,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'OBHS-Attendance-Exporter/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $success = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentLength = (int) curl_getinfo($curl, CURLINFO_SIZE_DOWNLOAD);
        curl_close($curl);
        if ($success && $statusCode >= 200 && $statusCode < 300) {
            rewind($temporaryStream);
            return ['stream' => $temporaryStream, 'url' => $url, 'size_bytes' => $contentLength];
        }
        fclose($temporaryStream);
        return false;
    }
    if ($stream === false) { return false; }

    $metadata = stream_get_meta_data($stream);
    $headers = $metadata['wrapper_data'] ?? [];
    $statusCode = 0;
    $contentLength = 0;
    foreach ((array) $headers as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $matches)) { $statusCode = (int) $matches[1]; }
        if (stripos($header, 'Content-Length:') === 0) { $contentLength = (int) trim(substr($header, 15)); }
    }
    if ($statusCode < 200 || $statusCode >= 300) { fclose($stream); return false; }

    return ['stream' => $stream, 'url' => $url, 'size_bytes' => $contentLength];
}

function readableBytes(int $bytes): string
{
    if ($bytes >= 1073741824) { return number_format($bytes / 1073741824, 2) . ' GB'; }
    if ($bytes >= 1048576) { return number_format($bytes / 1048576, 2) . ' MB'; }
    if ($bytes >= 1024) { return number_format($bytes / 1024, 2) . ' KB'; }
    return $bytes . ' B';
}

if (!$userId || !isset($allowedExports[$exportType])) {
    http_response_code(400);
    die('Invalid export request.');
}

$stationStmt = $conn->prepare('SELECT u.station_id, s.station_name FROM OBHS_users u INNER JOIN OBHS_station s ON s.station_id = u.station_id WHERE u.user_id = ? AND u.type = 2 LIMIT 1');
$stationStmt->bind_param('i', $userId);
$stationStmt->execute();
$station = $stationStmt->get_result()->fetch_assoc();
$stationStmt->close();
if (!$station) { http_response_code(404); die('Station not found.'); }

$safeStationName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($station['station_name']));
$safeStationName = trim($safeStationName, '-') ?: 'station-' . (int) $station['station_id'];
$stationId = (string) $station['station_id'];
$escapedStationId = $conn->real_escape_string($stationId);
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$trainNo = substr(trim((string) ($_GET['train_no'] ?? '')), 0, 100);
$grade = substr(trim((string) ($_GET['grade'] ?? '')), 0, 100);
$fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) ? $fromDate : '';
$toDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) ? $toDate : '';
$dateCondition = '';
$selectedDateColumn = $allowedExports[$exportType]['date_column'];
if ($fromDate !== '') { $dateCondition .= " AND `{$selectedDateColumn}` >= '" . $conn->real_escape_string($fromDate) . " 00:00:00'"; }
if ($toDate !== '') { $dateCondition .= " AND `{$selectedDateColumn}` < DATE_ADD('" . $conn->real_escape_string($toDate) . "', INTERVAL 1 DAY)"; }
if ($trainNo !== '') { $dateCondition .= " AND train_no = '" . $conn->real_escape_string($trainNo) . "'"; }
if ($grade !== '') { $dateCondition .= " AND grade = '" . $conn->real_escape_string($grade) . "'"; }

if (in_array($exportType, ['attendance', 'photo_report'], true) && ($_GET['download'] ?? '') === 'photos') {
    @set_time_limit(0);
    if (!class_exists('ZipArchive')) { http_response_code(500); die('ZIP support is not available on this server.'); }

    $photoFolder = $exportType === 'attendance' ? 'attendence' : 'photos';
    $sourceLabel = $exportType === 'attendance' ? 'attendance' : 'photo-report';
    if ($exportType === 'attendance') {
        $photoSql = "SELECT id, employee_id, employee_name, train_no, grade, '' AS coach_no, '' AS cleaning_area, '' AS janitor, photo, created_at FROM base_attendance WHERE station_id = '{$escapedStationId}' AND photo IS NOT NULL AND TRIM(photo) <> ''{$dateCondition} ORDER BY created_at DESC";
    } else {
        $photoSql = "SELECT id, '' AS employee_id, '' AS employee_name, train_no, grade, coach_no, cleaning_area, janitor, photo, created_at FROM base_photo_report WHERE station_id = '{$escapedStationId}' AND photo IS NOT NULL AND TRIM(photo) <> ''{$dateCondition} ORDER BY created_at DESC";
    }
    $photoResult = $conn->query($photoSql);
    if (!$photoResult) { http_response_code(500); die('Unable to read report photos.'); }

    $zipPath = tempnam(sys_get_temp_dir(), 'obhs-' . $sourceLabel . '-');
    if ($zipPath === false) { http_response_code(500); die('Unable to create temporary ZIP file.'); }
    $zip = new ZipArchive();
    $zipOpenResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($zipOpenResult !== true) { @unlink($zipPath); http_response_code(500); die('Unable to open ZIP file. Error: ' . $zipOpenResult); }

    $manifest = fopen('php://temp', 'w+');
    fputcsv($manifest, ['record_id', 'employee_id', 'employee_name', 'train_no', 'grade', 'coach_no', 'cleaning_area', 'janitor', 'record_date', 'database_photo', 'public_url', 'zip_photo', 'download_source', 'downloaded', 'size_bytes', 'size_readable']);
    $temporaryPhotos = [];
    $databaseReferences = 0;
    $addedPhotos = 0;
    $missingPhotos = 0;
    $totalPhotoBytes = 0;

    while ($attendance = $photoResult->fetch_assoc()) {
        $databaseReferences++;
        $photo = exportPhotoDetails((string) $attendance['photo'], $photoFolder);
        $zipPhotoName = $photo['file_name'] !== '' ? 'photos/' . (int) $attendance['id'] . '-' . $photo['file_name'] : '';
        $downloaded = false;
        $source = 'Missing';
        $sizeBytes = 0;

        if ($zipPhotoName !== '' && $photo['available']) {
            $downloaded = $zip->addFile($photo['absolute_path'], $zipPhotoName);
            if ($downloaded) { $source = 'Local filesystem'; $sizeBytes = $photo['size_bytes']; }
        } elseif ($zipPhotoName !== '') {
            $remotePhoto = openPublicPhoto($photo['file_name'], $photoFolder);
            if ($remotePhoto !== false) {
                $temporaryPhotoPath = tempnam(sys_get_temp_dir(), 'obhs-photo-');
                if ($temporaryPhotoPath !== false) {
                    $temporaryPhotoHandle = fopen($temporaryPhotoPath, 'wb');
                    $copiedBytes = $temporaryPhotoHandle ? stream_copy_to_stream($remotePhoto['stream'], $temporaryPhotoHandle) : false;
                    if ($temporaryPhotoHandle) { fclose($temporaryPhotoHandle); }
                    fclose($remotePhoto['stream']);
                    if ($copiedBytes !== false && $copiedBytes > 0 && $zip->addFile($temporaryPhotoPath, $zipPhotoName)) {
                        $temporaryPhotos[] = $temporaryPhotoPath;
                        $downloaded = true;
                        $source = 'Public attendance URL';
                        $sizeBytes = (int) $copiedBytes;
                    } else {
                        @unlink($temporaryPhotoPath);
                    }
                } else {
                    fclose($remotePhoto['stream']);
                }
            }
        }

        if ($downloaded) { $addedPhotos++; $totalPhotoBytes += $sizeBytes; }
        else { $missingPhotos++; }
        fputcsv($manifest, [(int) $attendance['id'], $attendance['employee_id'], $attendance['employee_name'], $attendance['train_no'], $attendance['grade'], $attendance['coach_no'], $attendance['cleaning_area'], $attendance['janitor'], $attendance['created_at'], $attendance['photo'], $photo['public_url'], $downloaded ? $zipPhotoName : '', $source, $downloaded ? 'Yes' : 'No', $sizeBytes, readableBytes($sizeBytes)]);
    }
    $photoResult->free();

    rewind($manifest);
    $zip->addFromString($sourceLabel . '-photo-manifest.csv', stream_get_contents($manifest));
    fclose($manifest);
    $readme = "Station: {$station['station_name']}\r\nStation ID: {$station['station_id']}\r\nReport: {$sourceLabel}\r\nDate filter: " . ($fromDate ?: 'Beginning') . " to " . ($toDate ?: 'Latest') . "\r\nTrain filter: " . ($trainNo ?: 'All') . "\r\nGrade filter: " . ($grade ?: 'All') . "\r\nDatabase photo references: {$databaseReferences}\r\nPhotos included: {$addedPhotos}\r\nMissing photo files: {$missingPhotos}\r\nTotal photo size: " . readableBytes($totalPhotoBytes) . "\r\nSource fallback: https://obhs.beatleanalytics.in/uploads/{$photoFolder}/\r\nGenerated: " . date('Y-m-d H:i:s') . " IST\r\n";
    $zip->addFromString('README.txt', $readme);
    $zipClosed = $zip->close();

    foreach ($temporaryPhotos as $temporaryPhotoPath) { @unlink($temporaryPhotoPath); }
    if (!$zipClosed || !is_file($zipPath) || filesize($zipPath) === 0) { @unlink($zipPath); http_response_code(500); die('ZIP creation failed.'); }

    $zipCheck = new ZipArchive();
    $zipCheckResult = $zipCheck->open($zipPath, ZipArchive::CHECKCONS);
    if ($zipCheckResult !== true) { @unlink($zipPath); http_response_code(500); die('ZIP validation failed. Error: ' . $zipCheckResult); }
    $zipEntryCount = $zipCheck->numFiles;
    $zipCheck->close();
    if ($zipEntryCount < 2) { @unlink($zipPath); http_response_code(500); die('ZIP validation failed: no archive contents.'); }

    $zipFilename = $safeStationName . '-' . $sourceLabel . '-photos-' . date('Y-m-d-His') . '.zip';
    while (ob_get_level() > 0) { ob_end_clean(); }
    @ini_set('zlib.output_compression', '0');
    if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Content-Encoding: identity');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($zipPath);
    @unlink($zipPath);
    $conn->close();
    exit;
}

$export = $allowedExports[$exportType];
$result = $conn->query(
    "SELECT * FROM `{$export['table']}` WHERE station_id = '{$escapedStationId}'{$dateCondition}",
    MYSQLI_USE_RESULT
);
if (!$result) {
    http_response_code(500);
    die('Unable to prepare export.');
}

$filename = $safeStationName . '-' . $export['file'] . '-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'w');
if ($output === false) { http_response_code(500); exit('Unable to create export file.'); }

// UTF-8 BOM ensures Hindi and other Unicode text displays correctly in Excel.
fwrite($output, "\xEF\xBB\xBF");
$headers = [];
foreach ($result->fetch_fields() as $field) { $headers[] = $field->name; }
if (in_array($exportType, ['attendance', 'photo_report'], true)) {
    array_push($headers, 'photo_available', 'photo_relative_path', 'photo_url', 'photo_size_bytes', 'photo_size_readable');
}
fputcsv($output, $headers);

while ($row = $result->fetch_assoc()) {
    if (in_array($exportType, ['attendance', 'photo_report'], true)) {
        $photo = exportPhotoDetails((string) ($row['photo'] ?? ''), $exportType === 'attendance' ? 'attendence' : 'photos');
        $photoUrl = $photo['public_url'];
        $row['photo_available'] = $photo['available'] ? 'Local file' : 'Check public URL';
        $row['photo_relative_path'] = $photo['path'];
        $row['photo_url'] = $photoUrl;
        $row['photo_size_bytes'] = $photo['size_bytes'];
        $row['photo_size_readable'] = readableBytes($photo['size_bytes']);
    }
    // Prevent spreadsheet software from treating database text as a formula.
    foreach ($row as &$value) {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) { $value = "\t" . $value; }
    }
    unset($value);
    fputcsv($output, array_values($row));
}

fclose($output);
$result->free();
$conn->close();
exit;
