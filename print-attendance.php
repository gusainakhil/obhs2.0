<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];

$selected_grade = $_REQUEST['grade'] ?? '';
$selected_train_from = $_REQUEST['trainFrom'] ?? '';
$selected_train_to = $_REQUEST['trainTo'] ?? '';
$date_from = $_REQUEST['dateFrom'] ?? date('Y-m-01');
$date_to = $_REQUEST['dateTo'] ?? date('Y-m-d');

$grade_mapping = [
    'A' => 'Monday',
    'B' => 'Tuesday',
    'C' => 'Wednesday',
    'D' => 'Thursday',
    'E' => 'Friday',
    'F' => 'Saturday',
    'G' => 'Sunday'
];
$grade_day = $grade_mapping[$selected_grade] ?? '';

$attendance_data = [];

if ($selected_grade && $selected_train_from && $selected_train_to) {

    $query = "SELECT 
        ba.employee_id,
        ba.employee_name,
        ba.train_no,
        ba.type_of_attendance,
        ba.photo,
        ba.location,
        ba.created_at,
        be.photo AS employee_photo
      FROM base_attendance ba
      LEFT JOIN base_employees be 
        ON ba.employee_id = be.employee_id AND be.station = ?
      WHERE ba.station_id = ?
        AND ba.grade = ?
        AND ba.train_no IN (?, ?)
        AND DATE(ba.created_at) BETWEEN ? AND ?
      ORDER BY ba.employee_name, ba.train_no,
      FIELD(ba.type_of_attendance,
      'Start of journey','Mid of journey','End of journey')";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(
        "sssssss",
        $station_id,
        $station_id,
        $selected_grade,
        $selected_train_from,
        $selected_train_to,
        $date_from,
        $date_to
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $id = $row['employee_id'];

        if (!isset($attendance_data[$id])) {
            $attendance_data[$id] = [
                'employee_name' => $row['employee_name'],
                'employee_id' => $id,
                'employee_photo' => $row['employee_photo'],
                'train_from' => [],
                'train_to' => []
            ];
        }

        if ($row['train_no'] == $selected_train_from) {
            $attendance_data[$id]['train_from'][$row['type_of_attendance']] = $row;
        } else {
            $attendance_data[$id]['train_to'][$row['type_of_attendance']] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Attendance Report</title>

<style>
body { font-family: Arial, sans-serif; background:#fff; margin:0; }
.print-container { max-width:8.5in; margin:auto; padding:8px; }

.header-section {
    text-align:center;
    border-bottom:1px solid #333;
    margin-bottom:8px;
    padding-bottom:6px;
}

.station-title { font-size:14px; font-weight:bold; }
.report-title { font-size:11px; color:#555; }

.filter-info {
    font-size:10px;
    margin-top:6px;
    line-height:1.5;
}

.print-table { width:100%; border-collapse:collapse; font-size:10px; }
.print-table th, .print-table td {
    border:1px solid #ccc;
    padding:5px;
    vertical-align:top;
    text-align:center;
}
.print-table thead { background:#0ea5e9; color:#fff; }

.photo-thumbnail {
    width:100px;
    height:100px;
    object-fit:contain;
    border:1px solid #ccc;
    display:block;
    margin:3px auto;
    background:#f9fafb;
}

.location-info {
    font-size:9px;
    font-weight:600;
    line-height:1.4;
}

.date-info {
    font-size:9px;
    font-weight:700;
    margin-top:4px;
}

.no-data { color:#999; font-style:italic; }

@media print {
    thead { display: table-header-group; }
    @page { size:A4 portrait; margin:0 10mm 10mm 10mm; }
    body { margin:10mm; }
}
</style>
</head>

<body>
<div class="print-container">

<!-- HEADER -->
<div class="header-section">
    <div class="station-title"><?= htmlspecialchars($station_name) ?></div>
    <div class="report-title">Attendance Report with Photos</div>

    <div class="filter-info">
        <strong>Grade:</strong> <?= htmlspecialchars($selected_grade) ?>
        <?= $grade_day ? '(' . htmlspecialchars($grade_day) . ')' : '' ?>
        &nbsp; | &nbsp;

        <strong>Train Up:</strong> <?= htmlspecialchars($selected_train_from) ?>
        &nbsp; | &nbsp;

        <strong>Train Down:</strong> <?= htmlspecialchars($selected_train_to) ?><br>

        <strong>Date From:</strong> <?= date('d-m-Y', strtotime($date_from)) ?>
        &nbsp; | &nbsp;

        <strong>Date To:</strong> <?= date('d-m-Y', strtotime($date_to)) ?>
    </div>
</div>

<!-- TABLE -->
<table class="print-table">
<thead>
<tr>
    <th rowspan="2">Employee</th>
    <th colspan="3">Train Up : <?= htmlspecialchars($selected_train_from) ?> </th>
    <th colspan="3">Train Down : <?= htmlspecialchars($selected_train_to) ?> </th>
</tr>
<tr>
    <th>Start</th><th>Mid</th><th>End</th>
    <th>Start</th><th>Mid</th><th>End</th>
</tr>
</thead>

<tbody>
    <?php $sr_no = 1; ?>
<?php foreach ($attendance_data as $emp): ?>
<tr>
<td>
<?php
$emp_photo = 'uploads/employee/'.$emp['employee_photo'];
if (!$emp['employee_photo'] || !file_exists($emp_photo)) {
    $emp_photo = 'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png';
}
?>
<strong>Sr No: <?= $sr_no++ ?></strong>
<img src="<?= $emp_photo ?>" class="photo-thumbnail">
<?= htmlspecialchars($emp['employee_name']) ?><br>
<small>ID: <?= $emp['employee_id'] ?></small>

</td>

<?php
$points = ['Start of journey','Mid of journey','End of journey'];
foreach (['train_from','train_to'] as $direction):
foreach ($points as $p):
$data = $emp[$direction][$p] ?? null;
?>
<td>
<?php if ($data):

$img = 'uploads/attendence/'.$data['photo'];
if (!file_exists($img)) {
    $img = 'https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';
}

$location = trim(preg_replace('/\s+/', ' ',
    preg_replace('/[^\x20-\x7E]/u', ' ',
    html_entity_decode($data['location'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')
)));

$latitude = '';
$longitude = '';
$location_name = '';

// Clean location string first (important)
$location = trim(preg_replace('/\s+/', ' ',
    preg_replace('/[^\x20-\x7E]/u', ' ', $location)
));

// Format 1: lati: xx longi: yy Place
if (preg_match('/lati:\s*([\d.]+)\s*longi:\s*([\d.]+)\s*(.+)?/i', $location, $matches)) {
    $latitude = $matches[1];
    $longitude = $matches[2];
    $location_name = trim($matches[3] ?? '');
}
// Format 2: lat,long,place
else if (preg_match('/^([\d.]+),([\d.]+),(.+)$/', $location, $matches)) {
    $latitude = $matches[1];
    $longitude = $matches[2];
    $location_name = trim($matches[3]);
}
else {
    $location_name = $location;
}

?>
<img src="<?= $img ?>" class="photo-thumbnail">
<div class="location-info">
<?php if (!empty($latitude)): ?>
    Lati: <?= htmlspecialchars($latitude) ?><br>
    Longi: <?= htmlspecialchars($longitude) ?><br>
<?php endif; ?>
<?= htmlspecialchars($location_name ?: 'NA') ?>
</div>
<div class="date-info"><?= date('d-m-Y H:i:s', strtotime($data['created_at'])) ?></div>
<?php else: ?><div class="no-data">No Attendance</div><?php endif; ?>
</td>
<?php endforeach; endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

<script>
window.onload = () => window.print();
</script>
</body>
</html>
