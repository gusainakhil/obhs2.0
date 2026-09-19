<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/includes/connection.php';
require_once __DIR__ . '/includes/helpers.php';

checkLogin();

function trainAttendanceEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function trainAttendanceRequestValue($key, $default = '')
{
    $value = $_GET[$key] ?? $default;

    return is_scalar($value) && !is_bool($value) ? trim((string) $value) : (string) $default;
}

function trainAttendanceFetchTrains($mysqli, $stationId)
{
    $trains = [];
    $stmt = $mysqli->prepare(
        'SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no'
    );

    if (!$stmt) {
        error_log('Train-wise attendance train list prepare failed: ' . $mysqli->error);
        return $trains;
    }

    $stmt->bind_param('i', $stationId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trainNumber = trim((string) ($row['train_no'] ?? ''));

        if ($trainNumber !== '') {
            $trains[] = $trainNumber;
        }
    }

    $stmt->close();

    return $trains;
}

function trainAttendanceFetchRows($mysqli, $stationId, $trainNumber, $dateFrom, $dateTo, $grade = '')
{
    $dateStart = $dateFrom . ' 00:00:00';
    $nextDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateTo);
    $dateEnd = $nextDate
        ? $nextDate->modify('+1 day')->format('Y-m-d') . ' 00:00:00'
        : $dateTo . ' 23:59:59';

    $sql = "SELECT
                    employee_id,
                    employee_name,
                    train_no,
                    grade,
                    type_of_attendance,
                    created_at
                  FROM base_attendance
                  WHERE station_id = ?";
    $types = 'i';
    $params = [$stationId];

    if ($trainNumber !== 'all') {
        $sql .= ' AND train_no = ?';
        $types .= 's';
        $params[] = $trainNumber;
    }

    if ($grade !== '') {
        $sql .= ' AND grade = ?';
        $types .= 's';
        $params[] = $grade;
    }

    $sql .= " AND created_at >= ?
              AND created_at < ?
              ORDER BY train_no, grade, employee_name, employee_id, created_at";
    $types .= 'ss';
    $params[] = $dateStart;
    $params[] = $dateEnd;

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        error_log('Train-wise attendance data prepare failed: ' . $mysqli->error);
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $employeeId = (string) ($row['employee_id'] ?? '');
        $rowTrainNumber = (string) ($row['train_no'] ?? '');
        $rowGrade = strtoupper(trim((string) ($row['grade'] ?? '')));
        $rowKey = $employeeId . '|' . $rowTrainNumber . '|' . $rowGrade;

        if (!isset($rows[$rowKey])) {
            $rows[$rowKey] = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_name' => (string) ($row['employee_name'] ?? ''),
                'employee_id' => $employeeId,
                'train_no' => $rowTrainNumber,
                'grade' => $rowGrade,
                'checkpoints' => [
                    'Start of journey' => false,
                    'Mid of journey' => false,
                    'End of journey' => false,
                ],
            ];
        }

        $checkpoint = (string) ($row['type_of_attendance'] ?? '');

        if (array_key_exists($checkpoint, $rows[$rowKey]['checkpoints'])) {
            // Any matching punch inside the selected date range marks this checkpoint present.
            $rows[$rowKey]['checkpoints'][$checkpoint] = true;
        }
    }

    $stmt->close();

    return array_values($rows);
}

function trainAttendanceRenderCheckpoint($checkpoints, $checkpoint)
{
    $statusText = 'Pending';

    if (!empty($checkpoints[$checkpoint])) {
        $statusText = 'Present';
    } elseif ($checkpoint === 'Start of journey') {
        $statusText = 'Absent';
    } elseif ($checkpoint === 'Mid of journey'
        && !empty($checkpoints['End of journey'])) {
        // An end punch closes the journey even if the start punch is missing.
        $statusText = 'Absent';
    }

    $statusClass = 'status-' . strtolower($statusText);

    echo '<span class="attendance-status ' . $statusClass . '">' . $statusText . '</span>';
}

$stationId = (int) ($_SESSION['station_id'] ?? 0);
$station_name = getStationName($stationId);
$today = date('Y-m-d');
$selectedDateFrom = trainAttendanceRequestValue('date_from', $today);
$selectedDateTo = trainAttendanceRequestValue('date_to', $today);
$selectedTrain = trainAttendanceRequestValue('train_no');
$selectedGrade = strtoupper(trainAttendanceRequestValue('grade'));
$grades = [
    'A' => 'A - Monday',
    'B' => 'B - Tuesday',
    'C' => 'C - Wednesday',
    'D' => 'D - Thursday',
    'E' => 'E - Friday',
    'F' => 'F - Saturday',
    'G' => 'G - Sunday',
];
$filterError = '';

$parsedDateFrom = DateTimeImmutable::createFromFormat('!Y-m-d', $selectedDateFrom);
$parsedDateTo = DateTimeImmutable::createFromFormat('!Y-m-d', $selectedDateTo);

if (!$parsedDateFrom || $parsedDateFrom->format('Y-m-d') !== $selectedDateFrom) {
    $selectedDateFrom = $today;
    $filterError = 'Please select a valid From Date.';
}

if (!$parsedDateTo || $parsedDateTo->format('Y-m-d') !== $selectedDateTo) {
    $selectedDateTo = $today;
    $filterError = 'Please select a valid To Date.';
}

if ($filterError === '' && $selectedDateFrom > $selectedDateTo) {
    $filterError = 'From Date must be on or before To Date.';
}

$trains = trainAttendanceFetchTrains($mysqli, $stationId);
$attendanceRows = [];

if ($selectedTrain !== '' && $selectedTrain !== 'all' && !in_array($selectedTrain, $trains, true)) {
    $filterError = 'Please select a train assigned to your station.';
    $selectedTrain = '';
}

if ($selectedGrade !== '' && !array_key_exists($selectedGrade, $grades)) {
    $filterError = 'Please select a valid grade.';
    $selectedGrade = '';
}

if ($selectedTrain !== '' && $filterError === '') {
    $attendanceRows = trainAttendanceFetchRows(
        $mysqli,
        $stationId,
        $selectedTrain,
        $selectedDateFrom,
        $selectedDateTo,
        $selectedGrade
    );
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Train and Grade-wise Attendance Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        body {
            background: #f8fafc;
            color: #172033;
            font-family: Inter, Arial, sans-serif;
        }

        .report-heading {
            margin-bottom: 10px;
        }

        .report-heading h2 {
            color: #172033;
            font-size: 18px;
            font-weight: 700;
        }

        .report-heading p {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .filter-card {
            align-items: end;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(140px, 1fr) minmax(140px, 1fr) minmax(180px, 1.25fr) minmax(140px, 1fr) auto auto;
            margin-bottom: 10px;
            padding: 10px;
        }

        .filter-label {
            color: #475569;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .filter-control {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #1e293b;
            font-size: 14px;
            min-height: 36px;
            padding: 6px 9px;
            width: 100%;
        }

        .filter-control:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
            outline: none;
        }

        .action-button {
            align-items: center;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 7px;
            justify-content: center;
            min-height: 36px;
            padding: 7px 13px;
            text-decoration: none;
            white-space: nowrap;
        }

        .view-button { background: #10b981; color: #fff; }
        .view-button:hover { background: #059669; }
        .print-button { background: #0ea5e9; color: #fff; }
        .print-button:hover { background: #0284c7; }

        .filter-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            color: #b91c1c;
            font-size: 13px;
            margin-bottom: 8px;
            padding: 7px 10px;
        }

        .report-summary {
            color: #475569;
            display: flex;
            flex-wrap: wrap;
            font-size: 13px;
            gap: 5px 16px;
            margin-bottom: 7px;
        }

        .employee-search {
            align-items: end;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            justify-content: flex-end;
            margin-bottom: 7px;
            padding: 7px 9px;
        }

        .employee-search-field {
            max-width: 270px;
            width: 100%;
        }

        .table-shell {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            overflow-x: auto;
        }

        .attendance-grid {
            border-collapse: collapse;
            min-width: 1050px;
            table-layout: fixed;
            width: 100%;
        }

        .attendance-grid th,
        .attendance-grid td {
            border: 1px solid #cbd5e1;
            padding: 6px 5px;
            text-align: center !important;
            vertical-align: middle;
        }

        .attendance-grid th {
            background: #f8fafc;
            color: #172033;
            font-size: 12px;
            font-weight: 700;
            vertical-align: middle;
        }

        .attendance-grid tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        .attendance-grid .serial-column { width: 62px; }
        .attendance-grid .date-column { width: 145px; }
        .attendance-grid .name-column { width: 170px; }
        .attendance-grid .id-column { width: 100px; }
        .attendance-grid .train-column { width: 95px; }
        .attendance-grid .grade-column { width: 85px; }
        .attendance-grid .journey-column { width: 145px; }

        .identity-value {
            color: #1e293b;
            font-size: 12px;
            font-weight: 600;
            padding-top: 0;
            text-align: center;
        }

        .date-value {
            white-space: nowrap;
        }

        .attendance-status {
            border-radius: 999px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            min-width: 66px;
            padding: 5px 9px;
            text-align: center;
        }

        .status-present {
            background: #dcfce7;
            color: #166534;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #475569;
            font-size: 13px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_length select {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
        }

        .empty-state {
            color: #64748b;
            font-size: 14px;
            padding: 22px 10px !important;
        }

        @media (max-width: 1280px) {
            .filter-card {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 560px) {
            .filter-card {
                grid-template-columns: 1fr;
            }

            .action-button {
                width: 100%;
            }
        }

        @media print {
            @page { size: A4 landscape; margin: 10mm; }

            #sidebarOverlay,
            #sidebar,
            header,
            footer,
            .filter-card,
            .employee-search,
            .dataTables_length,
            .dataTables_info,
            .dataTables_paginate,
            .print-hidden {
                display: none !important;
            }

            .lg\:ml-64 {
                margin-left: 0 !important;
            }

            main {
                padding: 0 !important;
            }

            .table-shell {
                border: 0;
                box-shadow: none;
                overflow: visible;
            }

            .attendance-grid {
                min-width: 0;
            }

            .attendance-grid th,
            .attendance-grid td {
                font-size: 8px;
                padding: 4px;
            }

            .attendance-status {
                font-size: 7px;
                min-width: 48px;
                padding: 4px 6px;
            }

            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <div class="lg:ml-64 min-h-screen">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <main class="p-3 lg:p-4">
            <div class="report-heading">
                <h2>Train and Grade-wise Attendance Status</h2>
                <p>Select a train and date range, then choose a grade or view all grades.</p>
            </div>

            <form method="GET" action="" class="filter-card">
                <div>
                    <label for="date_from" class="filter-label">From Date</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        class="filter-control"
                        value="<?php echo trainAttendanceEscape($selectedDateFrom); ?>"
                        required
                    >
                </div>

                <div>
                    <label for="date_to" class="filter-label">To Date</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        class="filter-control"
                        value="<?php echo trainAttendanceEscape($selectedDateTo); ?>"
                        required
                    >
                </div>

                <div>
                    <label for="train_no" class="filter-label">Train Number</label>
                    <select id="train_no" name="train_no" class="filter-control" required>
                        <option value="">Select Train</option>
                        <option value="all" <?php echo $selectedTrain === 'all' ? 'selected' : ''; ?>>All Trains</option>
                        <?php foreach ($trains as $train): ?>
                            <option
                                value="<?php echo trainAttendanceEscape($train); ?>"
                                <?php echo $selectedTrain === $train ? 'selected' : ''; ?>
                            ><?php echo trainAttendanceEscape($train); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="grade" class="filter-label">Grade</label>
                    <select id="grade" name="grade" class="filter-control">
                        <option value="" <?php echo $selectedGrade === '' ? 'selected' : ''; ?>>All Grades</option>
                        <?php foreach ($grades as $grade => $gradeLabel): ?>
                            <option
                                value="<?php echo trainAttendanceEscape($grade); ?>"
                                <?php echo $selectedGrade === $grade ? 'selected' : ''; ?>
                            ><?php echo trainAttendanceEscape($gradeLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="action-button view-button">
                    <i class="fas fa-search"></i> View Attendance
                </button>

                <button type="button" class="action-button print-button" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </form>

            <?php if ($filterError !== ''): ?>
                <div class="filter-error"><?php echo trainAttendanceEscape($filterError); ?></div>
            <?php endif; ?>

            <div class="report-summary">
                <span><strong>Station:</strong> <?php echo trainAttendanceEscape($station_name); ?></span>
                <span><strong>From:</strong> <?php echo date('d/m/Y', strtotime($selectedDateFrom)); ?></span>
                <span><strong>To:</strong> <?php echo date('d/m/Y', strtotime($selectedDateTo)); ?></span>
                <span>
                    <strong>Train:</strong>
                    <?php echo trainAttendanceEscape($selectedTrain === 'all' ? 'All Trains' : ($selectedTrain ?: 'Not selected')); ?>
                </span>
                <span><strong>Grade:</strong> <?php echo trainAttendanceEscape($grades[$selectedGrade] ?? 'All Grades'); ?></span>
                <?php if ($selectedTrain !== ''): ?>
                    <span><strong>Records:</strong> <?php echo count($attendanceRows); ?></span>
                <?php endif; ?>
            </div>

            <div class="employee-search">
                <div class="employee-search-field">
                    <label for="employeeSearch" class="filter-label">Search Employee</label>
                    <input
                        type="search"
                        id="employeeSearch"
                        class="filter-control"
                        placeholder="Search by employee name or ID"
                        autocomplete="off"
                        <?php echo empty($attendanceRows) ? 'disabled' : ''; ?>
                    >
                </div>
            </div>

            <div class="table-shell">
                <table class="attendance-grid" id="attendanceTable">
                    <thead>
                        <tr>
                            <th class="serial-column">S.no</th>
                            <th class="date-column">Date</th>
                            <th class="name-column">Employee Name</th>
                            <th class="id-column">Emp id</th>
                            <th class="train-column">Train No</th>
                            <th class="grade-column">Grade</th>
                            <th class="journey-column">Start Of Journey</th>
                            <th class="journey-column">Mid Of Journey</th>
                            <th class="journey-column">End Of Journey</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($selectedTrain === ''): ?>
                            <tr>
                                <td colspan="9" class="empty-state">Select a train to view attendance.</td>
                            </tr>
                        <?php elseif (empty($attendanceRows)): ?>
                            <tr>
                                <td colspan="9" class="empty-state">No attendance records found for the selected train, grade and date range.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceRows as $index => $attendanceRow): ?>
                                <tr>
                                    <td><div class="identity-value"><?php echo $index + 1; ?></div></td>
                                    <td data-order="<?php echo trainAttendanceEscape($attendanceRow['date_from']); ?>">
                                        <div class="identity-value date-value">
                                            <?php echo date('d/m/y', strtotime($attendanceRow['date_from'])); ?>
                                            <?php if ($attendanceRow['date_from'] !== $attendanceRow['date_to']): ?>
                                                - <?php echo date('d/m/y', strtotime($attendanceRow['date_to'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><div class="identity-value"><?php echo trainAttendanceEscape($attendanceRow['employee_name']); ?></div></td>
                                    <td><div class="identity-value"><?php echo trainAttendanceEscape($attendanceRow['employee_id']); ?></div></td>
                                    <td><div class="identity-value"><?php echo trainAttendanceEscape($attendanceRow['train_no']); ?></div></td>
                                    <td><div class="identity-value"><?php echo trainAttendanceEscape($attendanceRow['grade']); ?></div></td>
                                    <td><?php trainAttendanceRenderCheckpoint($attendanceRow['checkpoints'], 'Start of journey'); ?></td>
                                    <td><?php trainAttendanceRenderCheckpoint($attendanceRow['checkpoints'], 'Mid of journey'); ?></td>
                                    <td><?php trainAttendanceRenderCheckpoint($attendanceRow['checkpoints'], 'End of journey'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php require_once __DIR__ . '/includes/footer.php'; ?>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        let attendanceDataTable = null;
        let printPageLength = 25;

        <?php if (!empty($attendanceRows)): ?>
        if (window.jQuery && jQuery.fn.DataTable) {
            attendanceDataTable = jQuery('#attendanceTable').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[2, 'asc'], [1, 'asc']],
                dom: 'lrtip',
                columnDefs: [
                    { searchable: false, targets: [0, 1, 4, 5, 6, 7, 8] }
                ],
                language: {
                    emptyTable: 'No attendance records found.',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                    infoEmpty: 'Showing 0 records',
                    lengthMenu: 'Show _MENU_ records',
                    paginate: {
                        next: 'Next',
                        previous: 'Previous'
                    }
                }
            });

            jQuery('#employeeSearch').on('input', function () {
                attendanceDataTable.search(this.value).draw();
            });

            window.addEventListener('beforeprint', function () {
                printPageLength = attendanceDataTable.page.len();
                attendanceDataTable.page.len(-1).draw();
            });

            window.addEventListener('afterprint', function () {
                attendanceDataTable.page.len(printPageLength).draw();
            });
        }
        <?php endif; ?>

        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const closeSidebar = document.getElementById('closeSidebar');

        function setSidebarOpen(isOpen) {
            if (!sidebar || !sidebarOverlay) return;
            sidebar.classList.toggle('-translate-x-full', !isOpen);
            sidebarOverlay.classList.toggle('hidden', !isOpen);
        }

        if (menuToggle) menuToggle.addEventListener('click', () => setSidebarOpen(true));
        if (closeSidebar) closeSidebar.addEventListener('click', () => setSidebarOpen(false));
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', () => setSidebarOpen(false));
    </script>
</body>
</html>
