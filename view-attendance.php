<?php
session_start();
require_once './includes/connection.php';
require_once './includes/helpers.php';

// Optional: enable detailed error output in development only
$debug = false; // set to true only in local development
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Call reusable login check
checkLogin();

function viewAttendanceEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function viewAttendanceFetchTrains($mysqli, $station_id)
{
    $trains = [];
    $sql = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        error_log('View attendance train list prepare failed: ' . $mysqli->error);
        return $trains;
    }

    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trains[] = $row['train_no'];
    }

    $stmt->close();

    return $trains;
}

function viewAttendancePercentage($actual, $total)
{
    return $actual > 0 ? round(($actual / $total) * 100, 2) : 0;
}

function viewAttendanceFetchData($mysqli, $station_id, $selected_grade, $selected_train_from, $selected_train_to, $date_from, $date_to)
{
    if ($selected_grade === '') {
        return [];
    }

    $date_from_start = $date_from . ' 00:00:00';
    $date_to_next_day = strtotime($date_to . ' +1 day');
    $date_to_exclusive = $date_to_next_day
        ? date('Y-m-d', $date_to_next_day) . ' 00:00:00'
        : $date_to . ' 23:59:59';

    $query = "SELECT
            employee_id,
            employee_name,
            MAX(CASE WHEN train_no = ? AND type_of_attendance = 'Start of journey' THEN 1 ELSE 0 END) AS trip1_start,
            MAX(CASE WHEN train_no = ? AND type_of_attendance = 'Mid of journey' THEN 1 ELSE 0 END) AS trip1_mid,
            MAX(CASE WHEN train_no = ? AND type_of_attendance = 'End of journey' THEN 1 ELSE 0 END) AS trip1_end,
            MAX(CASE WHEN train_no = ? AND type_of_attendance = 'Start of journey' THEN 1 ELSE 0 END) AS trip2_start,
            MAX(CASE WHEN train_no = ? AND type_of_attendance = 'Mid of journey' THEN 1 ELSE 0 END) AS trip2_mid,
            MAX(CASE WHEN train_no = ? AND type_of_attendance = 'End of journey' THEN 1 ELSE 0 END) AS trip2_end
        FROM base_attendance
        WHERE station_id = ?
          AND grade = ?
          AND created_at >= ?
          AND created_at < ?";

    $bind_params = [
        $selected_train_from,
        $selected_train_from,
        $selected_train_from,
        $selected_train_to,
        $selected_train_to,
        $selected_train_to,
        $station_id,
        $selected_grade,
        $date_from_start,
        $date_to_exclusive,
    ];
    $type_string = "ssssssisss";

    if ($selected_train_from !== '' && $selected_train_to !== '') {
        $query .= " AND train_no IN (?, ?)";
        $bind_params[] = $selected_train_from;
        $bind_params[] = $selected_train_to;
        $type_string .= "ss";
    } elseif ($selected_train_from !== '') {
        $query .= " AND train_no = ?";
        $bind_params[] = $selected_train_from;
        $type_string .= "s";
    } elseif ($selected_train_to !== '') {
        $query .= " AND train_no = ?";
        $bind_params[] = $selected_train_to;
        $type_string .= "s";
    }

    $query .= " GROUP BY employee_id, employee_name ORDER BY employee_name";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        error_log('View attendance data prepare failed: ' . $mysqli->error);
        return [];
    }

    $stmt->bind_param($type_string, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_data = [];

    while ($row = $result->fetch_assoc()) {
        $trip1_actual = (int) $row['trip1_start'] + (int) $row['trip1_mid'] + (int) $row['trip1_end'];
        $trip2_actual = (int) $row['trip2_start'] + (int) $row['trip2_mid'] + (int) $row['trip2_end'];
        $round_actual = $trip1_actual + $trip2_actual;

        $attendance_data[] = [
            'employee_id' => $row['employee_id'],
            'employee_name' => $row['employee_name'],
            'trip1_percentage' => viewAttendancePercentage($trip1_actual, 3),
            'trip2_percentage' => viewAttendancePercentage($trip2_actual, 3),
            'round_percentage' => viewAttendancePercentage($round_actual, 6),
        ];
    }

    $stmt->close();

    return $attendance_data;
}

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_name = viewAttendanceEscape(getStationName($station_id));
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';

// Initialize variables for filters
$selected_grade = (string) ($_POST['grade'] ?? '');
$selected_train_from = (string) ($_POST['trainFrom'] ?? '');
$selected_train_to = (string) ($_POST['trainTo'] ?? '');
$date_from = (string) ($_POST['dateFrom'] ?? date('Y-m-01'));
$date_to = (string) ($_POST['dateTo'] ?? date('Y-m-d'));
$trains = viewAttendanceFetchTrains($mysqli, $station_id);

// Fetch attendance data if form is submitted
$attendance_data = [];
if ($is_post && !empty($selected_grade)) {
    $attendance_data = viewAttendanceFetchData(
        $mysqli,
        $station_id,
        $selected_grade,
        $selected_train_from,
        $selected_train_to,
        $date_from,
        $date_to
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
    <title>View Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .filter-card {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .filter-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .filter-select,
        .filter-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .btn-submit {
            background-color: #10b981;
            color: white;
            padding: 10px 32px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #059669;
        }

        .btn-all-attendance {
            background-color: #0ea5e9;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-all-attendance:hover {
            background-color: #0284c7;
        }

        .selected-grade-info {
            background-color: #f1f5f9;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            color: #475569;
            margin-top: 8px;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .entries-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #475569;
        }

        .entries-select {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .search-input {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
            width: 250px;
        }

        .search-input:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        .data-table thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table thead th i {
            margin-left: 4px;
            opacity: 0.7;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .data-table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: #334155;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
        }

        .pagination-info {
            font-size: 14px;
            color: #475569;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
        }

        .pagination-btn {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            background: white;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #475569;
        }

        .pagination-btn:hover:not(:disabled) {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background-color: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
        }

        .attendance-percentage {
            font-weight: 600;
            color: #0891b2;
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr !important;
            }

            .search-input {
                width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body class="bg-slate-50">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <!-- sidebar  -->
   <?php 
   require_once 'includes/sidebar.php'
   ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">

        <!-- Top Navigation Bar -->
       <?php 
       require_once 'includes/header.php'
       ?>

        <!-- Main Content Area -->
        <main class="p-4 lg:p-6">

            <!-- Filter Card -->
            <div class="filter-card">
                <form id="attendanceForm" method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 filter-grid">

                        <!-- Grade Selection -->
                        <div>
                            <label class="filter-label">Grade</label>
                            <select name="grade" id="grade" class="filter-select" required>
                                <option value="">Select Grade</option>
                                <option value="A" <?php echo $selected_grade === 'A' ? 'selected' : ''; ?>>A - Monday</option>
                                <option value="B" <?php echo $selected_grade === 'B' ? 'selected' : ''; ?>>B - Tuesday</option>
                                <option value="C" <?php echo $selected_grade === 'C' ? 'selected' : ''; ?>>C - Wednesday</option>
                                <option value="D" <?php echo $selected_grade === 'D' ? 'selected' : ''; ?>>D - Thursday</option>
                                <option value="E" <?php echo $selected_grade === 'E' ? 'selected' : ''; ?>>E - Friday</option>
                                <option value="F" <?php echo $selected_grade === 'F' ? 'selected' : ''; ?>>F - Saturday</option>
                                <option value="G" <?php echo $selected_grade === 'G' ? 'selected' : ''; ?>>G - Sunday</option>
                            </select>
                        </div>

                        <!-- Train Number From -->
                        <div>
                            <label class="filter-label">Train Number (From)</label>
                            <select name="trainFrom" id="trainFrom" class="filter-select">
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo viewAttendanceEscape($train); ?>" <?php echo $selected_train_from === $train ? 'selected' : ''; ?>>
                                        <?php echo viewAttendanceEscape($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Train Number To -->
                        <div>
                            <label class="filter-label">Train Number (To)</label>
                            <select name="trainTo" id="trainTo" class="filter-select">
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo viewAttendanceEscape($train); ?>" <?php echo $selected_train_to === $train ? 'selected' : ''; ?>>
                                        <?php echo viewAttendanceEscape($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="filter-label">From Date</label>
                            <input type="date" name="dateFrom" id="dateFrom" class="filter-input" value="<?php echo viewAttendanceEscape($date_from); ?>" required>
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="filter-label">To Date</label>
                            <input type="date" name="dateTo" id="dateTo" class="filter-input" value="<?php echo viewAttendanceEscape($date_to); ?>" required>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 mt-6">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-search mr-2"></i>Submit
                        </button>
                        <button type="button" class="btn-all-attendance" onclick="viewAllAttendance()">
                            <i class="fas fa-list mr-2"></i>All Attendance
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Container -->
            <div class="table-container">

                <!-- Data Table -->
                <table class="data-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Emp. Name</th>
                            <th>Emp. ID</th>
                            <th>Trip 1 Attendance</th>
                            <th>Trip 2 Attendance</th>
                            <th>Round Trip Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_data)): ?>
                            <?php foreach ($attendance_data as $attendance): ?>
                                <tr>
                                    <td><?php echo viewAttendanceEscape($attendance['employee_name']); ?></td>
                                    <td><?php echo viewAttendanceEscape($attendance['employee_id']); ?></td>
                                    <td><span class="attendance-percentage"><?php echo viewAttendanceEscape($attendance['trip1_percentage']); ?>%</span></td>
                                    <td><span class="attendance-percentage"><?php echo viewAttendanceEscape($attendance['trip2_percentage']); ?>%</span></td>
                                    <td><span class="attendance-percentage"><?php echo viewAttendanceEscape($attendance['round_percentage']); ?>%</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">
                                    <?php echo $is_post ? 'No attendance records found for the selected filters.' : 'Please select filters and click Submit to view attendance.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>

        
            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
           ?>

        </main>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        // Initialize DataTable
        // $(document).ready(function() {
        //     $('#attendanceTable').DataTable({
        //         "pageLength": 10,
        //         "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        //         "language": {
        //             "search": "Search:",
        //             "lengthMenu": "Show _MENU_ entries",
        //             "info": "Showing _START_ to _END_ of _TOTAL_ entries",
        //             "infoEmpty": "Showing 0 to 0 of 0 entries",
        //             "infoFiltered": "(filtered from _MAX_ total entries)",
        //             "paginate": {
        //                 "first": "First",
        //                 "last": "Last",
        //                 "next": "Next",
        //                 "previous": "Previous"
        //             }
        //         },
        //         "order": [[0, 'asc']] // Default sort by employee name
        //     });
        // });

        // Mobile Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const closeSidebar = document.getElementById('closeSidebar');

        if (menuToggle && sidebar && sidebarOverlay && closeSidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
            });

            closeSidebar.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            });

            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            });
        }
        
        // View All Attendance with filters
        function viewAllAttendance() {
            const grade = document.getElementById('grade').value;
            const trainFrom = document.getElementById('trainFrom').value;
            const trainTo = document.getElementById('trainTo').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            const params = new URLSearchParams({
                grade: grade,
                trainFrom: trainFrom,
                trainTo: trainTo,
                dateFrom: dateFrom,
                dateTo: dateTo
            });
            
           window.open('attendance-with-photos.php?' + params.toString(), '_blank');
        }
    </script>

</body>

</html>
