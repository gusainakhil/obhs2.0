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
        be.photo as employee_photo
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
body {
    font-family: Arial, sans-serif;
    background: #fff;
    margin: 0;
}

.print-container {
    max-width: 8.5in;
    margin: auto;
    padding: 8px;
}

.header-section {
    text-align: center;
    border-bottom: 1px solid #333;
    margin-bottom: 6px;
}

.station-title {
    font-size: 14px;
    font-weight: bold;
}

.report-title {
    font-size: 11px;
    color: #555;
}

.filter-info {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 6px;
    border-radius: 4px;
    margin-bottom: 6px;
    font-size: 10px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 4px;
    margin-bottom: 2px;
}

.filter-item {
    display: flex;
    gap: 8px;
}

.filter-label {
    font-weight: 600;
    color: #1e293b;
    min-width: 110px;
}

.filter-value {
    color: #475569;
}

.employee-cell {
    text-align: center;
}

.data-cell {
    text-align: center;
}

.print-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}

.print-table th,
.print-table td {
    border: 1px solid #ccc;
    padding: 5px;
    vertical-align: top;
}

.print-table thead {
    background: #0ea5e9;
    color: #fff;
}

.photo-thumbnail {
    width: 55px;
    /*height: 55px;*/
    object-fit: cover;
    border: 1px solid #ccc;
    display: block;
    margin: 3px auto;
}

.location-info {
    font-size: 8px;
    color: #555;
    line-height: 1.3;
    margin-top: 2px;
}

.no-data {
    color: #999;
    font-style: italic;
}


.location-info {
    font-size: 9px;
    color: #1f2937; /* dark gray */
    line-height: 1.4;
    margin-top: 3px;
    font-weight: 600; /* makes it bold like datetime */
}

.location-info span {
    display: block;
}

.date-info {
    font-size: 9px;
    font-weight: 700;
    color: #0f172a; /* darker for emphasis */
    margin-top: 4px;
}


/* ============ PRINT FIX ============ */
@media print {

    .print-container {
        max-width: 100%;
        padding: 5mm;
    }

    .print-table,
    .print-table tr {
        page-break-inside: auto;
    }

    thead {
        display: table-header-group;
    }

    .photo-thumbnail {
        width: 45px;
        /*height: 45px;*/
    }

    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>
</head>

<body>
<div class="print-container">

<!-- HEADER -->
<div class="header-section">
    <div class="station-title"><?= htmlspecialchars($station_name) ?></div>
    <div class="report-title">Attendance Report with Photos</div>
</div>

<!-- FILTER INFO -->
<div class="filter-info">
    <div class="filter-row">
        <div class="filter-item">
            <span class="filter-label">Grade:</span>
            <span class="filter-value"><?= htmlspecialchars($selected_grade . ' - ' . $grade_day) ?></span>
        </div>
        <div class="filter-item">
            <span class="filter-label">Period:</span>
            <span class="filter-value"><?= htmlspecialchars(date('d-m-Y', strtotime($date_from)) . ' to ' . date('d-m-Y', strtotime($date_to))) ?></span>
        </div>
    </div>
    <div class="filter-row">
        <div class="filter-item">
            <span class="filter-label">Train Up No.:</span>
            <span class="filter-value"><?= htmlspecialchars($selected_train_from) ?></span>
        </div>
        <div class="filter-item">
            <span class="filter-label">Train Down No.:</span>
            <span class="filter-value"><?= htmlspecialchars($selected_train_to) ?></span>
        </div>
    </div>
</div>

<!-- TABLE -->
<table class="print-table">
<thead>
<tr>
    <th rowspan="2">Employee</th>
    <th colspan="3">Train Up</th>
    <th colspan="3">Train Down</th>
</tr>
<tr>
    <th>Start</th><th>Mid</th><th>End</th>
    <th>Start</th><th>Mid</th><th>End</th>
</tr>
</thead>

<tbody>
<?php foreach ($attendance_data as $emp): ?>
<tr>
<td class="employee-cell">
<?php
$emp_photo = 'uploads/employee/'.$emp['employee_photo'];
if (!$emp['employee_photo'] || !file_exists($emp_photo)) {
    $emp_photo = 'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png';
}
?>
<img src="<?= $emp_photo ?>" class="photo-thumbnail">
<?= htmlspecialchars($emp['employee_name']) ?><br>
<small>ID: <?= $emp['employee_id'] ?></small>
</td>

<?php
$points = ['Start of journey','Mid of journey','End of journey'];
foreach ($points as $p):
$data = $emp['train_from'][$p] ?? null;
?>
<td class="data-cell">
<?php if ($data):
$img = 'uploads/attendence/'.$data['photo'];
if (!file_exists($img)) {
    $img = 'https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';
}

// Parse location
$location = $data['location'] ?? '';
$latitude = '';
$longitude = '';
$location_name = '';

if (preg_match('/lati:\s*([\d.]+)\s+longi:\s*([\d.]+)\s*(.+)/', $location, $matches)) {
    $latitude = $matches[1];
    $longitude = $matches[2];
    $location_name = trim($matches[3]);
}
elseif (preg_match('/^([\d.]+),([\d.]+),(.+)$/', $location, $matches)) {
    $latitude = $matches[1];
    $longitude = $matches[2];
    $location_name = trim($matches[3]);
}
else {
    $location_name = $location;
}
?>
<img src="<?= $img ?>" class="photo-thumbnail">
<?php if (!empty($latitude)): ?>
<div class="location-info">Lati: <?= htmlspecialchars($latitude) ?><br>Longi: <?= htmlspecialchars($longitude) ?><br><?= htmlspecialchars($location_name) ?></div>
<?php else: ?>
<div class="location-info">Lati: NA<br>Longi: NA<br>NA</div>
<?php endif; ?>
<div class="date-info"><?= date('d-m-Y H:i:s', strtotime($data['created_at'])) ?></div>
<?php else: ?>
<div class="no-data">No Attendance</div>
<?php endif; ?>
</td>
<?php endforeach; ?>

<?php foreach ($points as $p):
$data = $emp['train_to'][$p] ?? null;
?>
<td class="data-cell">
<?php if ($data):
$img = 'uploads/attendence/'.$data['photo'];
if (!file_exists($img)) {
    $img = 'https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';
}

// Parse location
$location = $data['location'] ?? '';
$latitude = '';
$longitude = '';
$location_name = '';

if (preg_match('/lati:\s*([\d.]+)\s+longi:\s*([\d.]+)\s*(.+)/', $location, $matches)) {
    $latitude = $matches[1];
    $longitude = $matches[2];
    $location_name = trim($matches[3]);
}
elseif (preg_match('/^([\d.]+),([\d.]+),(.+)$/', $location, $matches)) {
    $latitude = $matches[1];
    $longitude = $matches[2];
    $location_name = trim($matches[3]);
}
else {
    $location_name = $location;
}
?>
<img src="<?= $img ?>" class="photo-thumbnail">
<?php if (!empty($latitude)): ?>
<div class="location-info">
    <span>Lati: <?= htmlspecialchars($latitude) ?></span>
    <span>Longi: <?= htmlspecialchars($longitude) ?></span>
    <span><?= htmlspecialchars($location_name) ?></span>
</div>

<?php else: ?>
<div class="location-info">
    <span>Lati: NA</span>
    <span>Longi: NA</span>
    <span>NA</span>
</div>

<?php endif; ?>
<div class="date-info"><?= date('d-m-Y H:i:s', strtotime($data['created_at'])) ?></div>
<?php else: ?>
<div class="no-data">No Attendance</div>
<?php endif; ?>
</td>
<?php endforeach; ?>

</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

<script>
window.onload = function() {
    window.print();
};
</script>
</body>
</html>
