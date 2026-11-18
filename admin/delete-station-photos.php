<?php
require_once __DIR__ . '/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ((int) ($_SESSION['type'] ?? 0) !== 1 && empty($_SESSION['admin_logged_in'])) { http_response_code(403); die('Admin access required.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die('Method not allowed.'); }

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!isset($_SESSION['photo_delete_csrf']) || !hash_equals($_SESSION['photo_delete_csrf'], $csrfToken)) {
    http_response_code(403); die('Invalid security token. Please reload the page and try again.');
}
if (($_POST['confirmation_text'] ?? '') !== 'DELETE' || !isset($_POST['confirm_delete'])) {
    http_response_code(400); die('Delete confirmation was not completed.');
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$scope = (string) ($_POST['scope'] ?? '');
if (!$userId || !in_array($scope, ['attendance', 'photo_report', 'both'], true)) {
    http_response_code(400); die('Invalid delete request.');
}

$stationStmt = $conn->prepare('SELECT u.station_id, s.station_name FROM OBHS_users u INNER JOIN OBHS_station s ON s.station_id = u.station_id WHERE u.user_id = ? AND u.type = 2 LIMIT 1');
$stationStmt->bind_param('i', $userId);
$stationStmt->execute();
$station = $stationStmt->get_result()->fetch_assoc();
$stationStmt->close();
if (!$station) { http_response_code(404); die('Station not found.'); }

$fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['from_date'] ?? '')) ? (string) $_POST['from_date'] : '';
$toDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['to_date'] ?? '')) ? (string) $_POST['to_date'] : '';
$trainNo = substr(trim((string) ($_POST['train_no'] ?? '')), 0, 100);
$grade = substr(trim((string) ($_POST['grade'] ?? '')), 0, 100);

function deleteWhere(mysqli $conn, string $stationId, string $dateColumn, string $fromDate, string $toDate, string $trainNo, string $grade): string
{
    $where = "station_id = '" . $conn->real_escape_string($stationId) . "'";
    if ($fromDate !== '') { $where .= " AND `{$dateColumn}` >= '" . $conn->real_escape_string($fromDate) . " 00:00:00'"; }
    if ($toDate !== '') { $where .= " AND `{$dateColumn}` < DATE_ADD('" . $conn->real_escape_string($toDate) . "', INTERVAL 1 DAY)"; }
    if ($trainNo !== '') { $where .= " AND train_no = '" . $conn->real_escape_string($trainNo) . "'"; }
    if ($grade !== '') { $where .= " AND grade = '" . $conn->real_escape_string($grade) . "'"; }
    return $where . " AND photo IS NOT NULL AND TRIM(photo) <> ''";
}

function safePhotoCandidates(string $fileName, string $folder): array
{
    $fileName = basename(str_replace('\\', '/', trim($fileName)));
    if ($fileName === '' || !in_array($folder, ['attendence', 'photos'], true)) { return []; }
    $roots = [__DIR__ . '/../uploads/' . $folder];
    if (!empty($_SERVER['DOCUMENT_ROOT'])) { $roots[] = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . '/uploads/' . $folder; }
    $files = [];
    foreach (array_unique($roots) as $root) {
        $directory = realpath($root);
        if ($directory === false) { continue; }
        $candidate = realpath($directory . DIRECTORY_SEPARATOR . $fileName);
        $prefix = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($candidate !== false && is_file($candidate) && strncmp($candidate, $prefix, strlen($prefix)) === 0) { $files[$candidate] = true; }
    }
    return array_keys($files);
}

$configs = [
    'attendance' => ['table' => 'base_attendance', 'folder' => 'attendence', 'clear' => 'NULL'],
    'photo_report' => ['table' => 'base_photo_report', 'folder' => 'photos', 'clear' => "''"],
];
$targets = $scope === 'both' ? ['attendance', 'photo_report'] : [$scope];
$referencesCleared = 0;
$filesDeleted = 0;
$filesMissing = 0;
$filesFailed = 0;
$processedPaths = [];

$conn->begin_transaction();
try {
    foreach ($targets as $target) {
        $config = $configs[$target];
        $where = deleteWhere($conn, (string) $station['station_id'], 'created_at', $fromDate, $toDate, $trainNo, $grade);
        $result = $conn->query("SELECT id, photo FROM `{$config['table']}` WHERE {$where} FOR UPDATE");
        if (!$result) { throw new RuntimeException('Unable to select photos for deletion.'); }
        $rows = [];
        while ($row = $result->fetch_assoc()) { $rows[] = $row; }

        if ($rows) {
            if (!$conn->query("UPDATE `{$config['table']}` SET photo = {$config['clear']} WHERE {$where}")) {
                throw new RuntimeException('Unable to clear photo references.');
            }
            $referencesCleared += $conn->affected_rows;
        }

        foreach ($rows as $row) {
            $candidates = safePhotoCandidates((string) $row['photo'], $config['folder']);
            if (!$candidates) { $filesMissing++; continue; }
            foreach ($candidates as $filePath) {
                if (isset($processedPaths[$filePath])) { continue; }
                $processedPaths[$filePath] = true;
                if (@unlink($filePath)) { $filesDeleted++; } else { $filesFailed++; }
            }
        }
    }
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    http_response_code(500);
    die('Delete failed: ' . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$_SESSION['photo_delete_result'] = [
    'references' => $referencesCleared,
    'deleted' => $filesDeleted,
    'missing' => $filesMissing,
    'failed' => $filesFailed,
    'scope' => $scope,
];

$redirectParams = ['user_id' => $userId];
if ($fromDate !== '') { $redirectParams['from_date'] = $fromDate; }
if ($toDate !== '') { $redirectParams['to_date'] = $toDate; }
if ($trainNo !== '') { $redirectParams['train_no'] = $trainNo; }
if ($grade !== '') { $redirectParams['grade'] = $grade; }
header('Location: station-data-export.php?' . http_build_query($redirectParams));
exit;
