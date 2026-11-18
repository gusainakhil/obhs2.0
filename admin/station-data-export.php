<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) { http_response_code(400); die('Invalid station user.'); }

$stationStmt = $conn->prepare('SELECT u.user_id, u.station_id, s.station_name, u.organisation_name FROM OBHS_users u INNER JOIN OBHS_station s ON s.station_id = u.station_id WHERE u.user_id = ? AND u.type = 2 LIMIT 1');
$stationStmt->bind_param('i', $userId);
$stationStmt->execute();
$station = $stationStmt->get_result()->fetch_assoc();
$stationStmt->close();
if (!$station) { http_response_code(404); die('Station not found.'); }

if (empty($_SESSION['photo_delete_csrf'])) { $_SESSION['photo_delete_csrf'] = bin2hex(random_bytes(32)); }
$photoDeleteCsrf = $_SESSION['photo_delete_csrf'];
$deleteResult = $_SESSION['photo_delete_result'] ?? null;
unset($_SESSION['photo_delete_result']);

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$trainNo = substr(trim((string) ($_GET['train_no'] ?? '')), 0, 100);
$grade = substr(trim((string) ($_GET['grade'] ?? '')), 0, 100);
$fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) ? $fromDate : '';
$toDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate) ? $toDate : '';
$stationId = (string) $station['station_id'];
$escapedStationId = $conn->real_escape_string($stationId);

function reportWhere(mysqli $conn, string $stationId, string $dateColumn, string $fromDate, string $toDate, string $trainNo, string $grade): string
{
    $where = "station_id = '" . $conn->real_escape_string($stationId) . "'";
    if ($fromDate !== '') { $where .= " AND `{$dateColumn}` >= '" . $conn->real_escape_string($fromDate) . " 00:00:00'"; }
    if ($toDate !== '') { $where .= " AND `{$dateColumn}` < DATE_ADD('" . $conn->real_escape_string($toDate) . "', INTERVAL 1 DAY)"; }
    if ($trainNo !== '') { $where .= " AND train_no = '" . $conn->real_escape_string($trainNo) . "'"; }
    if ($grade !== '') { $where .= " AND grade = '" . $conn->real_escape_string($grade) . "'"; }
    return $where;
}

function fetchStats(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    return $result ? ($result->fetch_assoc() ?: []) : [];
}

function photoStats(mysqli $conn, string $table, string $where, string $folder): array
{
    $stats = ['references' => 0, 'local_files' => 0, 'missing_local' => 0, 'bytes' => 0];
    $directory = realpath(__DIR__ . '/../uploads/' . $folder);
    $result = $conn->query("SELECT photo FROM `{$table}` WHERE {$where} AND photo IS NOT NULL AND TRIM(photo) <> ''");
    if (!$result) { return $stats; }
    while ($row = $result->fetch_assoc()) {
        $stats['references']++;
        $fileName = basename(str_replace('\\', '/', trim((string) $row['photo'])));
        $candidate = ($directory && $fileName !== '') ? realpath($directory . DIRECTORY_SEPARATOR . $fileName) : false;
        $prefix = $directory ? rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
        if ($candidate !== false && is_file($candidate) && strncmp($candidate, $prefix, strlen($prefix)) === 0) {
            $stats['local_files']++;
            $stats['bytes'] += (int) filesize($candidate);
        } else { $stats['missing_local']++; }
    }
    return $stats;
}

function formatFileSize(int $bytes): string
{
    if ($bytes >= 1073741824) { return number_format($bytes / 1073741824, 2) . ' GB'; }
    if ($bytes >= 1048576) { return number_format($bytes / 1048576, 2) . ' MB'; }
    if ($bytes >= 1024) { return number_format($bytes / 1024, 2) . ' KB'; }
    return $bytes . ' B';
}

function exportLink(int $userId, string $type, string $fromDate, string $toDate, string $download = ''): string
{
    global $trainNo, $grade;
    $params = ['user_id' => $userId, 'type' => $type];
    if ($fromDate !== '') { $params['from_date'] = $fromDate; }
    if ($toDate !== '') { $params['to_date'] = $toDate; }
    if ($trainNo !== '') { $params['train_no'] = $trainNo; }
    if ($grade !== '') { $params['grade'] = $grade; }
    if ($download !== '') { $params['download'] = $download; }
    return 'export-station-data.php?' . http_build_query($params);
}

function safeHttpUrl(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) { return ''; }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

$feedbackWhere = reportWhere($conn, $stationId, 'created_at', $fromDate, $toDate, $trainNo, $grade);
$attendanceWhere = reportWhere($conn, $stationId, 'created_at', $fromDate, $toDate, $trainNo, $grade);
$photoReportWhere = reportWhere($conn, $stationId, 'created_at', $fromDate, $toDate, $trainNo, $grade);

$feedback = fetchStats($conn, "SELECT COUNT(*) total, SUM(verified = 1) verified, SUM(verified <> 1) pending, COUNT(DISTINCT train_no) trains, COUNT(DISTINCT coach_no) coaches FROM OBHS_passenger WHERE {$feedbackWhere}");
$attendance = fetchStats($conn, "SELECT COUNT(*) total, COUNT(DISTINCT employee_id) employees, COUNT(DISTINCT train_no) trains, COUNT(DISTINCT type_of_attendance) attendance_types FROM base_attendance WHERE {$attendanceWhere}");
$photoReport = fetchStats($conn, "SELECT COUNT(*) total, COUNT(DISTINCT train_no) trains, COUNT(DISTINCT coach_no) coaches, COUNT(DISTINCT cleaning_area) cleaning_areas FROM base_photo_report WHERE {$photoReportWhere}");
$attendancePhotos = photoStats($conn, 'base_attendance', $attendanceWhere, 'attendence');
$reportPhotos = photoStats($conn, 'base_photo_report', $photoReportWhere, 'photos');
$totalRecords = (int) ($feedback['total'] ?? 0) + (int) ($attendance['total'] ?? 0) + (int) ($photoReport['total'] ?? 0);

$latestPhotos = [];
$photoResult = $conn->query("SELECT id, train_no, grade, coach_no, coach_type, photo, cleaning_area, time_of_cleaning, janitor, location, location_link, created_at FROM base_photo_report WHERE {$photoReportWhere} AND photo IS NOT NULL AND TRIM(photo) <> '' ORDER BY created_at DESC LIMIT 12");
if ($photoResult) { while ($row = $photoResult->fetch_assoc()) { $latestPhotos[] = $row; } }

$filterParts = [($fromDate || $toDate) ? (($fromDate ?: 'Beginning') . ' to ' . ($toDate ?: 'Latest')) : 'All time'];
if ($trainNo !== '') { $filterParts[] = 'Train ' . $trainNo; }
if ($grade !== '') { $filterParts[] = 'Grade ' . $grade; }
$filterLabel = implode(' · ', $filterParts);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?php echo htmlspecialchars($station['station_name']); ?> Reports | OBHS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="color-scheme" content="light dark" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="css/adminlte.css" />
  <style>
    .export-hero{background:linear-gradient(135deg,#101828,#0d6efd);color:#fff;border:0;overflow:hidden}.export-hero:after{content:'';position:absolute;width:260px;height:260px;border-radius:50%;right:-70px;top:-120px;background:rgba(255,255,255,.09);pointer-events:none}.export-hero .card-body{position:relative;z-index:1}
    .filter-card{border-top:3px solid #0d6efd}.report-card{overflow:hidden}.report-number{width:46px;height:46px;display:grid;place-items:center;border-radius:14px;font-weight:800;font-size:1.1rem}.metric{border:1px solid var(--bs-border-color);border-radius:12px;padding:1rem;height:100%;background:var(--bs-tertiary-bg)}.metric-value{font-size:1.65rem;font-weight:700;line-height:1.1;font-variant-numeric:tabular-nums}
    .photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(245px,1fr));gap:1rem}.photo-card{border:1px solid var(--bs-border-color);border-radius:14px;overflow:hidden;background:var(--bs-body-bg)}.photo-preview{height:180px;background:var(--bs-tertiary-bg);position:relative;display:grid;place-items:center}.photo-preview img{width:100%;height:100%;object-fit:cover}.photo-preview:after{content:'Image unavailable';display:none;color:var(--bs-secondary-color)}.photo-preview.image-error:after{display:block}.photo-details{padding:1rem}.photo-details dl{display:grid;grid-template-columns:80px 1fr;gap:.3rem .65rem;margin:0;font-size:.875rem}.photo-details dt{color:var(--bs-secondary-color);font-weight:600}.photo-details dd{margin:0;word-break:break-word}.section-anchor{scroll-margin-top:80px}
    .delete-fab{position:fixed;right:24px;bottom:24px;z-index:1050;box-shadow:0 .5rem 1.25rem rgba(220,53,69,.3)}
    .delete-data-fab{position:fixed;right:24px;bottom:82px;z-index:1050;box-shadow:0 .5rem 1.25rem rgba(33,37,41,.25)}
  </style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
  <?php if ((int) ($_SESSION['type'] ?? 0) === 1 || !empty($_SESSION['admin_logged_in'])): ?>
  <a href="delete-station-data.php?user_id=<?php echo (int)$station['user_id']; ?>" class="btn btn-dark btn-lg delete-data-fab"><i class="bi bi-database-x me-2"></i>Delete Data</a>
  <button type="button" class="btn btn-danger btn-lg delete-fab" data-bs-toggle="modal" data-bs-target="#deletePhotosModal"><i class="bi bi-trash3 me-2"></i>Delete Photos</button>
  <?php if (is_array($deleteResult)): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080"><div class="toast show" role="status"><div class="toast-header"><i class="bi bi-check-circle-fill text-success me-2"></i><strong class="me-auto">Photo deletion completed</strong><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body"><?php echo number_format((int)$deleteResult['references']); ?> references cleared, <?php echo number_format((int)$deleteResult['deleted']); ?> physical files deleted, <?php echo number_format((int)$deleteResult['missing']); ?> files not found<?php if ((int)$deleteResult['failed'] > 0): ?>, <?php echo number_format((int)$deleteResult['failed']); ?> failed<?php endif; ?>.</div></div></div>
  <?php endif; ?>
  <div class="modal fade" id="deletePhotosModal" tabindex="-1" aria-labelledby="deletePhotosTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-danger">
      <form method="post" action="delete-station-photos.php" id="deletePhotosForm">
        <div class="modal-header bg-danger-subtle"><div><h5 class="modal-title text-danger-emphasis" id="deletePhotosTitle"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete station photos</h5><small class="text-body-secondary">Records remain saved; image files and photo references are removed.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($photoDeleteCsrf); ?>" /><input type="hidden" name="user_id" value="<?php echo (int)$station['user_id']; ?>" /><input type="hidden" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" /><input type="hidden" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" /><input type="hidden" name="train_no" value="<?php echo htmlspecialchars($trainNo); ?>" /><input type="hidden" name="grade" value="<?php echo htmlspecialchars($grade); ?>" />
          <div class="alert alert-warning"><strong id="deleteCount"><?php echo number_format($attendancePhotos['references'] + $reportPhotos['references']); ?></strong> filtered photo references will be cleared. Physical files found on this server will be permanently deleted.</div>
          <label class="form-label fw-semibold">Choose photos to delete</label>
          <div class="list-group mb-3">
            <label class="list-group-item d-flex gap-3"><input class="form-check-input flex-shrink-0 delete-scope" type="radio" name="scope" value="attendance" data-count="<?php echo $attendancePhotos['references']; ?>" /><span><strong>Attendance Photos</strong><small class="d-block text-body-secondary"><?php echo number_format($attendancePhotos['references']); ?> filtered references</small></span></label>
            <label class="list-group-item d-flex gap-3"><input class="form-check-input flex-shrink-0 delete-scope" type="radio" name="scope" value="photo_report" data-count="<?php echo $reportPhotos['references']; ?>" /><span><strong>Photo Report Images</strong><small class="d-block text-body-secondary"><?php echo number_format($reportPhotos['references']); ?> filtered references</small></span></label>
            <label class="list-group-item d-flex gap-3"><input class="form-check-input flex-shrink-0 delete-scope" type="radio" name="scope" value="both" data-count="<?php echo $attendancePhotos['references'] + $reportPhotos['references']; ?>" checked /><span><strong>Both</strong><small class="d-block text-body-secondary"><?php echo number_format($attendancePhotos['references'] + $reportPhotos['references']); ?> total filtered references</small></span></label>
          </div>
          <div class="mb-3"><label class="form-label" for="confirmationText">Type <strong>DELETE</strong> to confirm</label><input class="form-control" id="confirmationText" name="confirmation_text" autocomplete="off" required /></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="confirm_delete" value="1" id="confirmDelete" /><label class="form-check-label" for="confirmDelete">I understand that deleted image files cannot be recovered.</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger" id="finalDeleteButton" disabled><i class="bi bi-trash3 me-1"></i>Delete <span id="deleteButtonCount"><?php echo number_format($attendancePhotos['references'] + $reportPhotos['references']); ?></span> Photos</button></div>
      </form>
    </div></div>
  </div>
  <?php endif; ?>
  <?php include 'header.php'; ?>
  <main class="app-main">
    <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-sm-7"><a href="user-list.php" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to stations</a><h3 class="mb-0 mt-1">Station Reports & Downloads</h3></div><div class="col-sm-5"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item"><a href="user-list.php">Stations</a></li><li class="breadcrumb-item active">Reports</li></ol></div></div></div></div>
    <div class="app-content"><div class="container-fluid">
      <div class="card export-hero position-relative shadow-sm mb-4"><div class="card-body p-4"><div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3"><div><div class="text-white-50 text-uppercase small fw-semibold">Selected station</div><h2 class="mb-1"><i class="bi bi-broadcast-pin me-2"></i><?php echo htmlspecialchars($station['station_name']); ?></h2><div class="text-white-50"><?php echo htmlspecialchars($station['organisation_name']); ?> &middot; Station ID <?php echo (int) $station['station_id']; ?> &middot; <?php echo htmlspecialchars($filterLabel); ?></div></div><div class="d-flex align-items-center gap-3"><a href="station-overview.php" class="btn btn-light"><i class="bi bi-bar-chart-line me-1"></i>Overview</a><div class="text-end"><div class="display-6 fw-bold"><?php echo number_format($totalRecords); ?></div><div class="text-white-50">Filtered records</div></div></div></div></div></div>

      <form method="get" class="card filter-card shadow-sm mb-4"><input type="hidden" name="user_id" value="<?php echo (int) $station['user_id']; ?>" /><div class="card-header bg-body"><h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Common Filters</h5></div><div class="card-body"><div class="row g-3 align-items-end"><div class="col-md-6 col-xl-2"><label class="form-label" for="fromDate">From Date</label><input class="form-control" type="date" id="fromDate" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" /></div><div class="col-md-6 col-xl-2"><label class="form-label" for="toDate">To Date</label><input class="form-control" type="date" id="toDate" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" /></div><div class="col-md-6 col-xl-3"><label class="form-label" for="trainNo">Train Number</label><input class="form-control" type="text" id="trainNo" name="train_no" maxlength="100" value="<?php echo htmlspecialchars($trainNo); ?>" placeholder="All trains" /></div><div class="col-md-6 col-xl-3"><label class="form-label" for="grade">Grade</label><input class="form-control" type="text" id="grade" name="grade" maxlength="100" value="<?php echo htmlspecialchars($grade); ?>" placeholder="All grades" /></div><div class="col-xl-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-check2 me-1"></i>Apply</button><a href="station-data-export.php?user_id=<?php echo (int) $station['user_id']; ?>" class="btn btn-outline-secondary" title="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a></div></div></div><div class="card-footer bg-body d-flex flex-wrap gap-2"><a href="#feedback" class="btn btn-sm btn-outline-primary">1. Feedback</a><a href="#attendance" class="btn btn-sm btn-outline-success">2. Attendance</a><a href="#photo-report" class="btn btn-sm btn-outline-warning">3. Photo Report</a></div></form>

      <section class="card report-card shadow-sm mb-4 section-anchor" id="feedback"><div class="card-header bg-primary-subtle py-3"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div class="d-flex align-items-center gap-3"><span class="report-number bg-primary text-white">01</span><div><h4 class="mb-0">Passenger Feedback</h4><small class="text-body-secondary">Passenger, PNR, coach, train, grade, verification and remarks</small></div></div><a href="<?php echo htmlspecialchars(exportLink((int)$station['user_id'],'feedback',$fromDate,$toDate)); ?>" class="btn btn-primary"><i class="bi bi-file-earmark-excel me-2"></i>Download Feedback CSV</a></div></div><div class="card-body"><div class="row g-3"><div class="col-6 col-lg-3"><div class="metric"><div class="text-body-secondary small">Total Feedback</div><div class="metric-value text-primary"><?php echo number_format((int)($feedback['total']??0)); ?></div></div></div><div class="col-6 col-lg-3"><div class="metric"><div class="text-body-secondary small">Verified</div><div class="metric-value text-success"><?php echo number_format((int)($feedback['verified']??0)); ?></div></div></div><div class="col-6 col-lg-3"><div class="metric"><div class="text-body-secondary small">Pending</div><div class="metric-value text-warning"><?php echo number_format((int)($feedback['pending']??0)); ?></div></div></div><div class="col-6 col-lg-3"><div class="metric"><div class="text-body-secondary small">Trains / Coaches</div><div class="metric-value"><?php echo number_format((int)($feedback['trains']??0)); ?> / <?php echo number_format((int)($feedback['coaches']??0)); ?></div></div></div></div></div></section>

      <section class="card report-card shadow-sm mb-4 section-anchor" id="attendance"><div class="card-header bg-success-subtle py-3"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div class="d-flex align-items-center gap-3"><span class="report-number bg-success text-white">02</span><div><h4 class="mb-0">Attendance</h4><small class="text-body-secondary">Employee, attendance type, train, designation, location and attendance photos</small></div></div><div class="d-flex flex-wrap gap-2"><a href="<?php echo htmlspecialchars(exportLink((int)$station['user_id'],'attendance',$fromDate,$toDate)); ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Detailed CSV</a><a href="<?php echo htmlspecialchars(exportLink((int)$station['user_id'],'attendance',$fromDate,$toDate,'photos')); ?>" class="btn btn-success"><i class="bi bi-file-earmark-zip me-1"></i>Attendance Photos ZIP</a></div></div></div><div class="card-body"><div class="row g-3"><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Records</div><div class="metric-value text-success"><?php echo number_format((int)($attendance['total']??0)); ?></div></div></div><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Employees</div><div class="metric-value"><?php echo number_format((int)($attendance['employees']??0)); ?></div></div></div><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Photo References</div><div class="metric-value"><?php echo number_format($attendancePhotos['references']); ?></div></div></div><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Local / Remote</div><div class="metric-value"><?php echo number_format($attendancePhotos['local_files']); ?> / <?php echo number_format($attendancePhotos['missing_local']); ?></div><small class="text-body-secondary">Remote URL fallback enabled</small></div></div><div class="col-12 col-xl"><div class="metric"><div class="text-body-secondary small">Known Local Size</div><div class="metric-value text-primary"><?php echo formatFileSize($attendancePhotos['bytes']); ?></div></div></div></div></div></section>

      <section class="card report-card shadow-sm mb-4 section-anchor" id="photo-report"><div class="card-header bg-warning-subtle py-3"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div class="d-flex align-items-center gap-3"><span class="report-number bg-warning text-dark">03</span><div><h4 class="mb-0">Photo Report</h4><small class="text-body-secondary">Train, coach, cleaning area, janitor, time, location and complete image details</small></div></div><div class="d-flex flex-wrap gap-2"><a href="<?php echo htmlspecialchars(exportLink((int)$station['user_id'],'photo_report',$fromDate,$toDate)); ?>" class="btn btn-outline-warning"><i class="bi bi-file-earmark-excel me-1"></i>Detailed CSV</a><a href="<?php echo htmlspecialchars(exportLink((int)$station['user_id'],'photo_report',$fromDate,$toDate,'photos')); ?>" class="btn btn-warning"><i class="bi bi-file-earmark-zip me-1"></i>Download All Images ZIP</a></div></div></div><div class="card-body"><div class="row g-3"><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Photo Reports</div><div class="metric-value text-warning-emphasis"><?php echo number_format((int)($photoReport['total']??0)); ?></div></div></div><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Image References</div><div class="metric-value"><?php echo number_format($reportPhotos['references']); ?></div></div></div><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Trains / Coaches</div><div class="metric-value"><?php echo number_format((int)($photoReport['trains']??0)); ?> / <?php echo number_format((int)($photoReport['coaches']??0)); ?></div></div></div><div class="col-6 col-xl"><div class="metric"><div class="text-body-secondary small">Cleaning Areas</div><div class="metric-value"><?php echo number_format((int)($photoReport['cleaning_areas']??0)); ?></div></div></div><div class="col-12 col-xl"><div class="metric"><div class="text-body-secondary small">Known Local Size</div><div class="metric-value text-primary"><?php echo formatFileSize($reportPhotos['bytes']); ?></div><small class="text-body-secondary"><?php echo number_format($reportPhotos['missing_local']); ?> via public fallback</small></div></div></div></div>
        <hr class="my-4" /><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-0">Latest Image Details</h5><small class="text-body-secondary">Latest 12 images matching common filters</small></div><span class="badge text-bg-warning"><?php echo count($latestPhotos); ?> shown</span></div>
        <?php if ($latestPhotos): ?><div class="photo-grid"><?php foreach ($latestPhotos as $photo): $fileName=basename(str_replace('\\','/',trim((string)$photo['photo']))); $url='https://obhs.beatleanalytics.in/uploads/photos/'.rawurlencode($fileName); $mapUrl=safeHttpUrl($photo['location_link']); ?><article class="photo-card"><a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener" class="photo-preview"><img src="<?php echo htmlspecialchars($url); ?>" alt="Coach <?php echo htmlspecialchars($photo['coach_no']); ?> cleaning photo" loading="lazy" onerror="this.style.display='none';this.parentElement.classList.add('image-error')" /></a><div class="photo-details"><div class="d-flex justify-content-between gap-2 mb-2"><strong>Report #<?php echo (int)$photo['id']; ?></strong><span class="badge text-bg-light border"><?php echo htmlspecialchars($photo['grade']); ?></span></div><dl><dt>Train</dt><dd><?php echo htmlspecialchars($photo['train_no']); ?></dd><dt>Coach</dt><dd><?php echo htmlspecialchars($photo['coach_no'].' · '.$photo['coach_type']); ?></dd><dt>Area</dt><dd><?php echo htmlspecialchars($photo['cleaning_area'] ?: '—'); ?></dd><dt>Janitor</dt><dd><?php echo htmlspecialchars($photo['janitor'] ?: '—'); ?></dd><dt>Time</dt><dd><?php echo htmlspecialchars($photo['time_of_cleaning'] ?: date('d M Y, h:i A',strtotime($photo['created_at']))); ?></dd><dt>Location</dt><dd><?php if ($mapUrl): ?><a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" rel="noopener">View map</a><?php else: echo htmlspecialchars($photo['location'] ?: '—'); endif; ?></dd><dt>File</dt><dd><a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($fileName); ?></a></dd></dl></div></article><?php endforeach; ?></div><?php else: ?><div class="text-center py-5 text-body-secondary"><i class="bi bi-images fs-1 d-block mb-2"></i>No photo reports found for selected filters.</div><?php endif; ?>
      </div></section>
      <div class="alert alert-light border d-flex gap-3"><i class="bi bi-shield-check text-success fs-4"></i><div><strong>Secure station-wise exports</strong><div class="small text-body-secondary">Every CSV and ZIP is restricted to this station and uses the common date filters above. ZIP files include a manifest with image status, source URL and size.</div></div></div>
    </div></div>
  </main>
  <?php include 'footer.php'; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="js/adminlte.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const s=document.querySelector('.sidebar-wrapper');
  if(s&&window.OverlayScrollbarsGlobal?.OverlayScrollbars) OverlayScrollbarsGlobal.OverlayScrollbars(s,{scrollbars:{theme:'os-theme-light',autoHide:'leave',clickScroll:true}});
  const form=document.getElementById('deletePhotosForm');
  if(!form) return;
  const text=document.getElementById('confirmationText');
  const check=document.getElementById('confirmDelete');
  const button=document.getElementById('finalDeleteButton');
  const countText=document.getElementById('deleteCount');
  const buttonCount=document.getElementById('deleteButtonCount');
  const updateDeleteState=function(){
    const selected=document.querySelector('.delete-scope:checked');
    const count=selected?Number(selected.dataset.count||0):0;
    countText.textContent=count.toLocaleString();
    buttonCount.textContent=count.toLocaleString();
    button.disabled=!(selected&&count>0&&text.value==='DELETE'&&check.checked);
  };
  document.querySelectorAll('.delete-scope').forEach(input=>input.addEventListener('change',updateDeleteState));
  text.addEventListener('input',updateDeleteState);
  check.addEventListener('change',updateDeleteState);
  form.addEventListener('submit',function(event){
    const selected=document.querySelector('.delete-scope:checked');
    const count=selected?Number(selected.dataset.count||0):0;
    if(!window.confirm('Permanently delete '+count.toLocaleString()+' filtered photos? This cannot be undone.')) event.preventDefault();
  });
  updateDeleteState();
});
</script>
</body>
</html>
