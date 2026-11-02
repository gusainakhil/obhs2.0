<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$selectedYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
$selectedMonth = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?: 0;
$selectedStation = filter_input(INPUT_GET, 'station_id', FILTER_VALIDATE_INT) ?: 0;
if ($selectedYear < 2000 || $selectedYear > 2100) { $selectedYear = (int) date('Y'); }
if ($selectedMonth < 1 || $selectedMonth > 12) { $selectedMonth = 0; }

$stations = [];
$stationUsers = [];
$stationResult = $conn->query('SELECT s.station_id, s.station_name, MIN(u.user_id) AS user_id FROM OBHS_station s LEFT JOIN OBHS_users u ON u.station_id = s.station_id AND u.type = 2 GROUP BY s.station_id, s.station_name ORDER BY s.station_name ASC');
while ($row = $stationResult->fetch_assoc()) {
    $stationId = (int) $row['station_id'];
    $stations[$stationId] = $row['station_name'];
    $stationUsers[$stationId] = $row['user_id'] !== null ? (int) $row['user_id'] : 0;
}
if ($selectedStation && !isset($stations[$selectedStation])) { $selectedStation = 0; }

$reports = [
    'feedback' => ['label' => 'Feedback', 'table' => 'OBHS_passenger', 'date' => 'created_at', 'color' => '#0d6efd'],
    'attendance' => ['label' => 'Attendance', 'table' => 'base_attendance', 'date' => 'created_at', 'color' => '#198754'],
    'photo_report' => ['label' => 'Photo Report', 'table' => 'base_photo_report', 'date' => 'created_at', 'color' => '#ffc107'],
];
$months = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$monthly = [];
$stationTotals = [];
$reportTotals = array_fill_keys(array_keys($reports), 0);
$detail = [];
foreach ($reports as $key => $report) {
    $sql = "SELECT station_id, MONTH(`{$report['date']}`) AS report_month, COUNT(*) AS total FROM `{$report['table']}` WHERE YEAR(`{$report['date']}`) = ?";
    if ($selectedStation) { $sql .= ' AND station_id = ?'; }
    $sql .= ' GROUP BY station_id, MONTH(`' . $report['date'] . '`)';
    $stmt = $conn->prepare($sql);
    if ($selectedStation) { $stationParam = (string) $selectedStation; $stmt->bind_param('is', $selectedYear, $stationParam); }
    else { $stmt->bind_param('i', $selectedYear); }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stationId = (int) $row['station_id'];
        $month = (int) $row['report_month'];
        $count = (int) $row['total'];
        $monthly[$key][$month] = ($monthly[$key][$month] ?? 0) + $count;
        if (!$selectedMonth || $month === $selectedMonth) {
            $reportTotals[$key] += $count;
            $stationTotals[$stationId] = ($stationTotals[$stationId] ?? 0) + $count;
            $detail[$stationId][$key] = ($detail[$stationId][$key] ?? 0) + $count;
        }
    }
    $stmt->close();
}
arsort($stationTotals);
$grandTotal = array_sum($reportTotals);
$activeStations = count(array_filter($stationTotals, fn($count) => $count > 0));
$topStationId = $stationTotals ? array_key_first($stationTotals) : 0;
$topStationName = $topStationId ? ($stations[$topStationId] ?? 'Station #' . $topStationId) : 'No data';
$topStationCount = $topStationId ? $stationTotals[$topStationId] : 0;
$periodLabel = ($selectedMonth ? $months[$selectedMonth] . ' ' : '') . $selectedYear;

if (($_GET['format'] ?? '') === 'csv') {
    $filename = 'station-overview-' . $selectedYear . ($selectedMonth ? '-' . str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT) : '') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Station ID', 'Station Name', 'Period', 'Feedback', 'Attendance', 'Photo Report', 'Total']);
    foreach ($stationTotals as $stationId => $total) {
        fputcsv($output, [$stationId, $stations[$stationId] ?? 'Unknown Station', $periodLabel, $detail[$stationId]['feedback'] ?? 0, $detail[$stationId]['attendance'] ?? 0, $detail[$stationId]['photo_report'] ?? 0, $total]);
    }
    fclose($output); exit;
}

$chartLabels = array_values($months);
$monthlyChart = [];
foreach ($reports as $key => $report) {
    $monthlyChart[$key] = [];
    for ($month = 1; $month <= 12; $month++) { $monthlyChart[$key][] = $monthly[$key][$month] ?? 0; }
}
$topStations = array_slice($stationTotals, 0, 10, true);
$selectedStationName = $selectedStation ? ($stations[$selectedStation] ?? 'Selected Station') : 'All Stations';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Station Overview | OBHS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="css/adminlte.css" />
  <style>
    .overview-hero { background: linear-gradient(135deg,#101828,#1d4ed8); color:#fff; border:0; }
    .metric-icon { width:48px; height:48px; display:grid; place-items:center; border-radius:14px; }
    .chart-wrap { position:relative; height:340px; }
    .chart-wrap-sm { position:relative; height:300px; }
    .rank-number { width:30px; height:30px; display:inline-grid; place-items:center; border-radius:50%; background:var(--bs-tertiary-bg); font-weight:700; }
    .table thead th { white-space:nowrap; }
    @media print { .app-sidebar,.app-header,.no-print,.app-footer { display:none!important; } .app-main { margin-left:0!important; } .card { break-inside:avoid; box-shadow:none!important; } }
  </style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
  <?php include 'header.php'; ?>
  <main class="app-main">
    <div class="app-content-header"><div class="container-fluid"><div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div><a href="user-list.php" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to stations</a><h3 class="mb-0 mt-1">Station Overview</h3></div>
      <div class="d-flex gap-2 no-print"><button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / PDF</button><a class="btn btn-success" href="?<?php echo http_build_query(['year'=>$selectedYear,'month'=>$selectedMonth,'station_id'=>$selectedStation,'format'=>'csv']); ?>"><i class="bi bi-file-earmark-excel me-1"></i>Download Summary</a></div>
    </div></div></div>
    <div class="app-content"><div class="container-fluid">
      <div class="card overview-hero shadow-sm mb-4"><div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div><div class="text-white-50 text-uppercase small fw-semibold">Analytics dashboard</div><h2 class="mb-1"><?php echo htmlspecialchars($selectedStationName); ?></h2><div class="text-white-50"><?php echo htmlspecialchars($periodLabel); ?> &middot; Feedback, Attendance and Photo Report</div></div>
        <div class="text-lg-end"><div class="display-5 fw-bold"><?php echo number_format($grandTotal); ?></div><div class="text-white-50">Total data records</div></div>
      </div></div>

      <form class="card shadow-sm mb-4 no-print" method="get"><div class="card-body"><div class="row g-3 align-items-end">
        <div class="col-lg-5"><label class="form-label" for="stationFilter">Station</label><select class="form-select" id="stationFilter" name="station_id"><option value="0">All Stations</option><?php foreach ($stations as $id => $name): ?><option value="<?php echo $id; ?>" <?php echo $id === $selectedStation ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-5 col-lg-2"><label class="form-label" for="yearFilter">Year</label><select class="form-select" id="yearFilter" name="year"><?php for ($year=(int)date('Y');$year>=2020;$year--): ?><option value="<?php echo $year; ?>" <?php echo $year===$selectedYear?'selected':''; ?>><?php echo $year; ?></option><?php endfor; ?></select></div>
        <div class="col-sm-5 col-lg-3"><label class="form-label" for="monthFilter">Month</label><select class="form-select" id="monthFilter" name="month"><option value="0">All Months</option><?php foreach ($months as $number=>$name): ?><option value="<?php echo $number; ?>" <?php echo $number===$selectedMonth?'selected':''; ?>><?php echo $name; ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-2 col-lg-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Apply</button></div>
      </div></div></form>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card h-100 shadow-sm"><div class="card-body d-flex gap-3 align-items-center"><span class="metric-icon bg-primary-subtle text-primary"><i class="bi bi-database fs-4"></i></span><div><div class="text-body-secondary small">Total Records</div><div class="fs-3 fw-bold"><?php echo number_format($grandTotal); ?></div></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 shadow-sm"><div class="card-body d-flex gap-3 align-items-center"><span class="metric-icon bg-success-subtle text-success"><i class="bi bi-broadcast-pin fs-4"></i></span><div><div class="text-body-secondary small">Active Stations</div><div class="fs-3 fw-bold"><?php echo number_format($activeStations); ?></div></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 shadow-sm"><div class="card-body d-flex gap-3 align-items-center"><span class="metric-icon bg-warning-subtle text-warning"><i class="bi bi-trophy fs-4"></i></span><div class="min-w-0"><div class="text-body-secondary small">Top Station</div><div class="fw-bold text-truncate" title="<?php echo htmlspecialchars($topStationName); ?>"><?php echo htmlspecialchars($topStationName); ?></div><small><?php echo number_format($topStationCount); ?> records</small></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 shadow-sm"><div class="card-body d-flex gap-3 align-items-center"><span class="metric-icon bg-info-subtle text-info"><i class="bi bi-calendar3 fs-4"></i></span><div><div class="text-body-secondary small">Selected Period</div><div class="fw-bold"><?php echo htmlspecialchars($periodLabel); ?></div><small><?php echo htmlspecialchars($selectedStationName); ?></small></div></div></div></div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-xl-8"><div class="card h-100 shadow-sm"><div class="card-header bg-body d-flex justify-content-between"><div><h5 class="card-title mb-0">Monthly Data Trend</h5><small class="text-body-secondary">All months in <?php echo $selectedYear; ?></small></div><button class="btn btn-sm btn-outline-secondary no-print" data-download-chart="monthlyChart"><i class="bi bi-download"></i></button></div><div class="card-body"><div class="chart-wrap"><canvas id="monthlyChart"></canvas></div></div></div></div>
        <div class="col-xl-4"><div class="card h-100 shadow-sm"><div class="card-header bg-body d-flex justify-content-between"><div><h5 class="card-title mb-0">Report Distribution</h5><small class="text-body-secondary"><?php echo htmlspecialchars($periodLabel); ?></small></div><button class="btn btn-sm btn-outline-secondary no-print" data-download-chart="reportChart"><i class="bi bi-download"></i></button></div><div class="card-body"><div class="chart-wrap-sm"><canvas id="reportChart"></canvas></div></div></div></div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-xl-7"><div class="card h-100 shadow-sm"><div class="card-header bg-body d-flex justify-content-between"><div><h5 class="card-title mb-0">Top Stations</h5><small class="text-body-secondary">Highest total records for selected period</small></div><button class="btn btn-sm btn-outline-secondary no-print" data-download-chart="stationChart"><i class="bi bi-download"></i></button></div><div class="card-body"><div class="chart-wrap"><canvas id="stationChart"></canvas></div></div></div></div>
        <div class="col-xl-5"><div class="card h-100 shadow-sm"><div class="card-header bg-body"><h5 class="card-title mb-0">Report Totals</h5></div><div class="card-body p-0"><div class="list-group list-group-flush">
          <?php foreach ($reports as $key=>$report): $percent=$grandTotal?round($reportTotals[$key]*100/$grandTotal,1):0; ?><div class="list-group-item py-3"><div class="d-flex justify-content-between mb-2"><span class="fw-semibold"><?php echo $report['label']; ?></span><span><strong><?php echo number_format($reportTotals[$key]); ?></strong> <small class="text-body-secondary">(<?php echo $percent; ?>%)</small></span></div><div class="progress" style="height:7px"><div class="progress-bar" style="width:<?php echo $percent; ?>%;background:<?php echo $report['color']; ?>"></div></div></div><?php endforeach; ?>
        </div></div></div></div>
      </div>

      <div class="card shadow-sm mb-4"><div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0">Station-wise Details</h5><small class="text-body-secondary">Exact report counts for <?php echo htmlspecialchars($periodLabel); ?></small></div><input type="search" id="stationSearch" class="form-control no-print" style="max-width:260px" placeholder="Search station..." /></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" id="stationTable"><thead class="table-light"><tr><th class="ps-4">Rank</th><th>Station</th><th class="text-end">Feedback</th><th class="text-end">Attendance</th><th class="text-end">Photo Report</th><th class="text-end">Total</th><th class="text-end pe-4 no-print">Action</th></tr></thead><tbody>
        <?php $rank=1; foreach ($stationTotals as $stationId=>$total): ?><tr><td class="ps-4"><span class="rank-number"><?php echo $rank++; ?></span></td><td><div class="fw-semibold"><?php echo htmlspecialchars($stations[$stationId] ?? 'Unknown Station'); ?></div><small class="text-body-secondary">ID: <?php echo $stationId; ?></small></td><td class="text-end"><?php echo number_format($detail[$stationId]['feedback'] ?? 0); ?></td><td class="text-end"><?php echo number_format($detail[$stationId]['attendance'] ?? 0); ?></td><td class="text-end"><?php echo number_format($detail[$stationId]['photo_report'] ?? 0); ?></td><td class="text-end fw-bold"><?php echo number_format($total); ?></td><td class="text-end pe-4 no-print"><?php if (!empty($stationUsers[$stationId])): ?><a href="station-data-export.php?user_id=<?php echo $stationUsers[$stationId]; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>View / Export</a><?php else: ?><span class="text-body-secondary small">No user linked</span><?php endif; ?></td></tr><?php endforeach; ?>
        <?php if (!$stationTotals): ?><tr><td colspan="7" class="text-center py-5 text-body-secondary"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No data found for selected filters.</td></tr><?php endif; ?>
      </tbody></table></div></div></div>
    </div></div>
  </main>
  <?php include 'footer.php'; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script src="js/adminlte.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const monthlyChart = new Chart(document.getElementById('monthlyChart'), {type:'line',data:{labels:<?php echo json_encode($chartLabels); ?>,datasets:[
    {label:'Feedback',data:<?php echo json_encode($monthlyChart['feedback']); ?>,borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,.12)',tension:.35,fill:true},
    {label:'Attendance',data:<?php echo json_encode($monthlyChart['attendance']); ?>,borderColor:'#198754',backgroundColor:'rgba(25,135,84,.08)',tension:.35,fill:true},
    {label:'Photo Report',data:<?php echo json_encode($monthlyChart['photo_report']); ?>,borderColor:'#f0ad00',backgroundColor:'rgba(255,193,7,.08)',tension:.35,fill:true}
  ]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
  const reportChart = new Chart(document.getElementById('reportChart'), {type:'doughnut',data:{labels:['Feedback','Attendance','Photo Report'],datasets:[{data:<?php echo json_encode(array_values($reportTotals)); ?>,backgroundColor:['#0d6efd','#198754','#ffc107'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom'}}}});
  const stationChart = new Chart(document.getElementById('stationChart'), {type:'bar',data:{labels:<?php echo json_encode(array_map(fn($id)=>$stations[$id]??('Station #'.$id), array_keys($topStations))); ?>,datasets:[{label:'Total records',data:<?php echo json_encode(array_values($topStations)); ?>,backgroundColor:'#4f46e5',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0}}}}});
  document.querySelectorAll('[data-download-chart]').forEach(function(button){button.addEventListener('click',function(){const chart=Chart.getChart(button.dataset.downloadChart);const link=document.createElement('a');link.download=button.dataset.downloadChart+'.png';link.href=chart.toBase64Image('image/png',1);link.click();});});
  document.getElementById('stationSearch').addEventListener('input',function(){const query=this.value.toLowerCase();document.querySelectorAll('#stationTable tbody tr').forEach(row=>row.hidden=!row.textContent.toLowerCase().includes(query));});
  const sidebarWrapper=document.querySelector('.sidebar-wrapper');if(sidebarWrapper&&window.OverlayScrollbarsGlobal?.OverlayScrollbars)OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper,{scrollbars:{theme:'os-theme-light',autoHide:'leave',clickScroll:true}});
});
</script>
</body>
</html>
