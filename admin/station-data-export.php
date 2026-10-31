<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) { http_response_code(400); die('Invalid station user.'); }

$stationStmt = $conn->prepare('SELECT u.user_id, u.station_id, s.station_name FROM OBHS_users u INNER JOIN OBHS_station s ON s.station_id = u.station_id WHERE u.user_id = ? AND u.type = 2 LIMIT 1');
$stationStmt->bind_param('i', $userId);
$stationStmt->execute();
$station = $stationStmt->get_result()->fetch_assoc();
$stationStmt->close();
if (!$station) { http_response_code(404); die('Station not found.'); }

$exportTypes = [
    'feedback' => ['label' => 'Passenger Feedback', 'table' => 'OBHS_passenger', 'icon' => 'bi-chat-square-text', 'color' => 'primary', 'description' => 'Passenger details, PNR, coach, rating/grade, remarks and photos.'],
    'attendance' => ['label' => 'Attendance', 'table' => 'base_attendance', 'icon' => 'bi-person-check', 'color' => 'success', 'description' => 'Complete employee attendance, train, designation, location and photo data.'],
    'photo_report' => ['label' => 'Photo Report', 'table' => 'base_photo_report', 'icon' => 'bi-images', 'color' => 'warning', 'description' => 'Complete coach cleaning report, photo, janitor, time and location data.'],
];

$recordCounts = [];
foreach ($exportTypes as $key => $type) {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `{$type['table']}` WHERE station_id = ?");
    $stationId = (string) $station['station_id'];
    $countStmt->bind_param('s', $stationId);
    $countStmt->execute();
    $recordCounts[$key] = (int) $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
}
$totalRecords = array_sum($recordCounts);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo htmlspecialchars($station['station_name']); ?> Data Export | OBHS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" />
    <link rel="preload" href="css/adminlte.css" as="style" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="css/adminlte.css" />
    <style>
      .export-hero { background: linear-gradient(135deg, #0d6efd 0%, #084298 100%); color: #fff; border: 0; overflow: hidden; }
      .export-hero::after { content: ''; position: absolute; width: 230px; height: 230px; right: -55px; top: -100px; border-radius: 50%; background: rgba(255,255,255,.09); }
      .export-option { cursor: pointer; border: 2px solid transparent; transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease; }
      .export-option:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
      .export-option:has(input:checked) { border-color: #0d6efd; background: rgba(13,110,253,.04); box-shadow: 0 0 0 .2rem rgba(13,110,253,.1); }
      .export-icon { width: 52px; height: 52px; display: inline-grid; place-items: center; border-radius: 14px; flex: 0 0 auto; }
      .record-count { font-variant-numeric: tabular-nums; }
    </style>
  </head>
  <body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
      <?php include 'header.php'; ?>
      <main class="app-main">
        <div class="app-content-header"><div class="container-fluid"><div class="row align-items-center">
          <div class="col-sm-7"><a href="user-list.php" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to stations</a><h3 class="mb-0 mt-1">Station Data Export</h3></div>
          <div class="col-sm-5"><ol class="breadcrumb float-sm-end mb-0"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item"><a href="user-list.php">Stations</a></li><li class="breadcrumb-item active">Export</li></ol></div>
        </div></div></div>

        <div class="app-content"><div class="container-fluid">
          <div class="card export-hero position-relative mb-4 shadow-sm"><div class="card-body position-relative p-4"><div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div><div class="text-white-50 text-uppercase small fw-semibold mb-1">Selected station</div><h2 class="mb-1"><i class="bi bi-broadcast-pin me-2"></i><?php echo htmlspecialchars($station['station_name']); ?></h2><p class="mb-0 text-white-50">Station ID: <?php echo (int) $station['station_id']; ?> &middot; <?php echo number_format($totalRecords); ?> total records available</p></div>
            <div class="text-md-end"><div class="display-6 fw-bold"><?php echo number_format($totalRecords); ?></div><div class="text-white-50">Records in 3 tables</div></div>
          </div></div></div>

          <form action="export-station-data.php" method="get" id="exportForm">
            <input type="hidden" name="user_id" value="<?php echo (int) $station['user_id']; ?>" />
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-body py-3"><h5 class="card-title mb-1"><i class="bi bi-table me-2"></i>Select data to export</h5><div class="text-body-secondary small">The selected table's complete station-wise data and all columns will be downloaded.</div></div>
              <div class="card-body"><div class="row g-3">
                <?php foreach ($exportTypes as $key => $type): ?>
                  <div class="col-lg-4"><label class="card export-option h-100 mb-0" for="type-<?php echo $key; ?>"><div class="card-body d-flex gap-3">
                    <span class="export-icon bg-<?php echo $type['color']; ?>-subtle text-<?php echo $type['color']; ?>"><i class="bi <?php echo $type['icon']; ?> fs-4"></i></span>
                    <span class="flex-grow-1"><span class="d-flex align-items-start justify-content-between gap-2 mb-1"><span class="fw-bold fs-5"><?php echo htmlspecialchars($type['label']); ?></span><input class="form-check-input mt-1" type="radio" name="type" id="type-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $key === 'feedback' ? 'checked' : ''; ?> /></span>
                      <span class="d-block text-body-secondary small mb-3"><?php echo htmlspecialchars($type['description']); ?></span>
                      <span class="badge text-bg-light border record-count"><i class="bi bi-database me-1"></i><?php echo number_format($recordCounts[$key]); ?> records</span>
                      <span class="d-block text-body-secondary mt-2 small">Source: <?php echo htmlspecialchars($type['table']); ?></span>
                    </span>
                  </div></label></div>
                <?php endforeach; ?>
              </div></div>
              <div class="card-footer bg-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 py-3">
                <div class="small text-body-secondary"><i class="bi bi-info-circle me-1"></i>File will download as UTF-8 CSV and opens directly in Microsoft Excel.</div>
                <button type="submit" class="btn btn-success btn-lg px-4" id="exportBtn"><i class="bi bi-file-earmark-excel me-2"></i>Download Selected Data</button>
              </div>
            </div>
          </form>
          <div class="alert alert-light border d-flex gap-3 align-items-start" role="note"><i class="bi bi-shield-check text-success fs-4"></i><div><strong>Station-wise secure export</strong><div class="text-body-secondary small">The station is verified from the selected user account. Changing the station name in the URL cannot export another station's data.</div></div></div>
        </div></div>
      </main>
      <?php include 'footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="js/adminlte.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector('.sidebar-wrapper');
        if (sidebarWrapper && window.OverlayScrollbarsGlobal?.OverlayScrollbars) OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, { scrollbars: { theme: 'os-theme-light', autoHide: 'leave', clickScroll: true } });
        document.getElementById('exportForm').addEventListener('submit', function () {
          const button = document.getElementById('exportBtn');
          button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Preparing download...';
          setTimeout(function () { button.innerHTML = '<i class="bi bi-file-earmark-excel me-2"></i>Download Selected Data'; }, 2500);
        });
      });
    </script>
  </body>
</html>
