<?php
require_once __DIR__ . '/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ((int) ($_SESSION['type'] ?? 0) !== 1 && empty($_SESSION['admin_logged_in'])) { http_response_code(403); die('Admin access required.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die('Method not allowed.'); }
if (!isset($_SESSION['data_delete_csrf']) || !hash_equals($_SESSION['data_delete_csrf'], (string) ($_POST['csrf_token'] ?? ''))) { http_response_code(403); die('Invalid security token.'); }
if (($_POST['confirmation_text'] ?? '') !== 'DELETE DATA' || !isset($_POST['confirm_delete'])) { http_response_code(400); die('Delete confirmation was not completed.'); }

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$requestedScopes = array_values(array_intersect((array) ($_POST['scopes'] ?? []), ['feedback', 'attendance', 'photo_report']));
if (!$userId || !$requestedScopes) { http_response_code(400); die('Select at least one data type.'); }

$stationStmt = $conn->prepare('SELECT u.station_id, s.station_name FROM OBHS_users u INNER JOIN OBHS_station s ON s.station_id = u.station_id WHERE u.user_id = ? AND u.type = 2 LIMIT 1');
$stationStmt->bind_param('i', $userId);
$stationStmt->execute();
$station = $stationStmt->get_result()->fetch_assoc();
$stationStmt->close();
if (!$station) { http_response_code(404); die('Station not found.'); }

$filters = [
    'from_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['from_date'] ?? '')) ? (string) $_POST['from_date'] : '',
    'to_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['to_date'] ?? '')) ? (string) $_POST['to_date'] : '',
    'train_no' => substr(trim((string) ($_POST['train_no'] ?? '')), 0, 100),
    'grade' => substr(trim((string) ($_POST['grade'] ?? '')), 0, 100),
    'coach_no' => substr(trim((string) ($_POST['coach_no'] ?? '')), 0, 100),
    'employee_id' => substr(trim((string) ($_POST['employee_id'] ?? '')), 0, 100),
    'attendance_type' => substr(trim((string) ($_POST['attendance_type'] ?? '')), 0, 100),
    'verified' => in_array((string) ($_POST['verified'] ?? ''), ['0', '1'], true) ? (string) $_POST['verified'] : '',
    'cleaning_area' => substr(trim((string) ($_POST['cleaning_area'] ?? '')), 0, 100),
];
$hasFilter = count(array_filter($filters, static fn($value) => $value !== '')) > 0;
if (!$hasFilter && !isset($_POST['confirm_all_station_data'])) { http_response_code(400); die('No filters selected. Confirm full station deletion explicitly.'); }

function destructiveWhere(mysqli $conn, string $stationId, string $scope, array $filters): string
{
    $where = "station_id = '" . $conn->real_escape_string($stationId) . "'";
    if ($filters['from_date'] !== '') { $where .= " AND created_at >= '" . $conn->real_escape_string($filters['from_date']) . " 00:00:00'"; }
    if ($filters['to_date'] !== '') { $where .= " AND created_at < DATE_ADD('" . $conn->real_escape_string($filters['to_date']) . "', INTERVAL 1 DAY)"; }
    if ($filters['train_no'] !== '') { $where .= " AND train_no = '" . $conn->real_escape_string($filters['train_no']) . "'"; }
    if ($filters['grade'] !== '') { $where .= " AND grade = '" . $conn->real_escape_string($filters['grade']) . "'"; }
    if ($scope === 'feedback') {
        if ($filters['coach_no'] !== '') { $where .= " AND coach_no = '" . $conn->real_escape_string($filters['coach_no']) . "'"; }
        if ($filters['verified'] !== '') { $where .= ' AND verified = ' . (int) $filters['verified']; }
    } elseif ($scope === 'attendance') {
        if ($filters['employee_id'] !== '') { $where .= " AND employee_id = '" . $conn->real_escape_string($filters['employee_id']) . "'"; }
        if ($filters['attendance_type'] !== '') { $where .= " AND type_of_attendance = '" . $conn->real_escape_string($filters['attendance_type']) . "'"; }
    } elseif ($scope === 'photo_report') {
        if ($filters['coach_no'] !== '') { $where .= " AND coach_no = '" . $conn->real_escape_string($filters['coach_no']) . "'"; }
        if ($filters['cleaning_area'] !== '') { $where .= " AND cleaning_area = '" . $conn->real_escape_string($filters['cleaning_area']) . "'"; }
    }
    return $where;
}

function destructivePhotoPaths(string $fileName, string $relativeFolder): array
{
    $fileName = basename(str_replace('\\', '/', trim($fileName)));
    if ($fileName === '' || !in_array($relativeFolder, ['passenger_photos', 'uploads/attendence', 'uploads/photos'], true)) { return []; }
    $roots = [__DIR__ . '/../' . $relativeFolder];
    if (!empty($_SERVER['DOCUMENT_ROOT'])) { $roots[] = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $relativeFolder; }
    $paths = [];
    foreach (array_unique($roots) as $root) {
        $directory = realpath($root);
        if ($directory === false) { continue; }
        $candidate = realpath($directory . DIRECTORY_SEPARATOR . $fileName);
        $prefix = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($candidate !== false && is_file($candidate) && strncmp($candidate, $prefix, strlen($prefix)) === 0) { $paths[$candidate] = true; }
    }
    return array_keys($paths);
}

$configs = [
    'feedback' => ['table' => 'OBHS_passenger', 'id' => 'unique_id', 'folder' => 'passenger_photos'],
    'attendance' => ['table' => 'base_attendance', 'id' => 'id', 'folder' => 'uploads/attendence'],
    'photo_report' => ['table' => 'base_photo_report', 'id' => 'id', 'folder' => 'uploads/photos'],
];
$deletedByScope = ['feedback' => 0, 'attendance' => 0, 'photo_report' => 0];
$filesDeleted = 0;
$filesMissing = 0;
$filesFailed = 0;
$processedFiles = [];
$filesToDelete = [];

$conn->begin_transaction();
try {
    foreach ($requestedScopes as $scope) {
        $config = $configs[$scope];
        $where = destructiveWhere($conn, (string) $station['station_id'], $scope, $filters);
        $result = $conn->query("SELECT `{$config['id']}` AS record_id, photo FROM `{$config['table']}` WHERE {$where} FOR UPDATE");
        if (!$result) { throw new RuntimeException('Unable to select records.'); }
        $photos = [];
        while ($row = $result->fetch_assoc()) { if (trim((string) $row['photo']) !== '') { $photos[] = (string) $row['photo']; } }
        if (!$conn->query("DELETE FROM `{$config['table']}` WHERE {$where}")) { throw new RuntimeException('Unable to delete records.'); }
        $deletedByScope[$scope] = $conn->affected_rows;

        foreach ($photos as $photoName) {
            $paths = destructivePhotoPaths($photoName, $config['folder']);
            if (!$paths) { $filesMissing++; continue; }
            foreach ($paths as $path) {
                if (isset($processedFiles[$path])) { continue; }
                $processedFiles[$path] = true;
                $filesToDelete[] = $path;
            }
        }
    }
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500); die('Data deletion failed: ' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'));
}

foreach ($filesToDelete as $path) {
    if (@unlink($path)) { $filesDeleted++; } else { $filesFailed++; }
}

$_SESSION['data_delete_result'] = ['counts' => $deletedByScope, 'files_deleted' => $filesDeleted, 'files_missing' => $filesMissing, 'files_failed' => $filesFailed];
$redirect = ['user_id' => $userId];
foreach ($filters as $key => $value) { if ($value !== '') { $redirect[$key] = $value; } }
header('Location: delete-station-data.php?' . http_build_query($redirect));
exit;
