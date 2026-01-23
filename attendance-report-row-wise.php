<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

// Optional: enable detailed error output in development only
$debug = true; // set to false in production
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Call reusable login check
checkLogin();

// Now fetch station name
$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];

// Fetch train numbers from base_fb_target table for the station
$trains = [];
$train_query = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no";
$stmt = $mysqli->prepare($train_query);
$stmt->bind_param("s", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trains[] = $row['train_no'];
}
$stmt->close();

// Get filter parameters from URL or POST
$selected_grade = $_REQUEST['grade'] ?? '';
$selected_train_from = $_REQUEST['trainFrom'] ?? '';
$selected_train_to = $_REQUEST['trainTo'] ?? '';
$date_from = $_REQUEST['dateFrom'] ?? date('Y-m-d');
$date_to = $_REQUEST['dateTo'] ?? date('Y-m-d');

// Fetch attendance report data
$attendance_data = [];
if (!empty($selected_grade) && !empty($selected_train_from) && !empty($selected_train_to)) {
    $query = "SELECT 
                ba.employee_id,
                ba.employee_name,
                ba.train_no,
                ba.type_of_attendance,
                ba.location,
                ba.created_at,
                ba.photo as employee_photo
              FROM base_attendance ba
              LEFT JOIN base_employees be ON ba.employee_id = be.employee_id AND be.station = ?
              WHERE ba.station_id = ?
              AND ba.grade = ?
              AND ba.train_no IN (?, ?)
              AND DATE(ba.created_at) BETWEEN ? AND ?
              ORDER BY ba.created_at, ba.employee_name";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssssss", $station_id, $station_id, $selected_grade, $selected_train_from, $selected_train_to, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $attendance_data[] = $row;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

        .btn-print {
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

        .btn-print:hover {
            background-color: #0284c7;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .attendance-table thead {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        .attendance-table thead th {
            padding: 14px 12px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }

        .attendance-table thead th:last-child {
            border-right: none;
        }

        .attendance-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .attendance-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .attendance-table tbody td {
            padding: 12px;
            text-align: center;
            font-size: 13px;
            color: #334155;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .attendance-table tbody td:last-child {
            border-right: none;
        }

        .employee-photo {
            width: 100%;
            height: 100%;
            border-radius: 4px;
            object-fit: contain;
            object-position: center;
            display: block;
        }

        .photo-container {
            width: 80px;
            height: 80px;
            background-color: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0 auto;
        }

        .employee-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }

        .employee-id {
            color: #64748b;
            font-size: 12px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-start {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-interval {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-destination {
            background-color: #dbeafe;
            color: #1e40af;
        }

        @media (max-width: 768px) {
            .attendance-table {
                font-size: 11px;
            }

            .photo-container {
                width: 70px;
                height: 70px;
            }
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            #sidebarOverlay,
            .lg\:ml-64 > *:not(.overflow-x-auto):not(main),
            header,
            footer {
                display: none !important;
            }

            .lg\:ml-64 {
                margin-left: 0 !important;
            }

            main {
                padding: 10px !important;
            }

            .filter-card {
                page-break-after: avoid;
                box-shadow: none;
                border: 1px solid #e2e8f0;
                padding: 12px !important;
                margin-bottom: 12px !important;
            }

            .filter-label {
                font-size: 11px !important;
                margin-bottom: 3px !important;
            }

            .filter-select,
            .filter-input {
                padding: 6px 8px !important;
                font-size: 11px !important;
            }

            .btn-submit,
            .btn-print {
                padding: 6px 12px !important;
                font-size: 11px !important;
            }

            .filter-grid {
                gap: 8px !important;
            }

            .attendance-table {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }

            .attendance-table thead {
                background: #06b6d4 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .attendance-table thead th {
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .status-badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .status-start {
                background-color: #dcfce7 !important;
                color: #166534 !important;
            }

            .status-interval {
                background-color: #fef3c7 !important;
                color: #92400e !important;
            }

            .status-destination {
                background-color: #dbeafe !important;
                color: #1e40af !important;
            }

            .photo-container {
                page-break-inside: avoid;
                width: 100px !important;
                height: 100px !important;
            }

            .employee-photo {
                border-radius: 4px !important;
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
                            <select name="trainFrom" id="trainFrom" class="filter-select" required>
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo htmlspecialchars($train); ?>" <?php echo $selected_train_from === $train ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Train Number To -->
                        <div>
                            <label class="filter-label">Train Number (To)</label>
                            <select name="trainTo" id="trainTo" class="filter-select" required>
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo htmlspecialchars($train); ?>" <?php echo $selected_train_to === $train ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="filter-label">From</label>
                            <input type="date" name="dateFrom" id="dateFrom" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>" required>
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="filter-label">To</label>
                            <input type="date" name="dateTo" id="dateTo" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>" required>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 mt-6">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-search mr-2"></i>Submit
                        </button>
                        <button type="button" class="btn-print" onclick="window.print()">
                            <i class="fas fa-print mr-2"></i>Print Attendance
                        </button>
                    </div>
                </form>
            </div>

            <!-- Attendance Table -->
            <div class="overflow-x-auto">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">S.No</th>
                            <th style="width: 120px;">Employee Photo</th>
                            <th>Employee Name</th>
                            <th>Employee ID</th>
                            <th>Train NO.</th>
                            <th>IN-OUT</th>
                            <th>Punch Location & Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_data)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($attendance_data as $record): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <div class="photo-container">
                                            <?php 
                                            $employee_photo = 'uploads/attendence/' . $record['employee_photo'];
                                            if (empty($record['employee_photo']) || !file_exists($employee_photo)) {
                                                $employee_photo = 'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png';
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($employee_photo); ?>" alt="Photo" class="employee-photo">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="employee-name"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                                    </td>
                                    <td>
                                        <div class="employee-id"><?php echo htmlspecialchars($record['employee_id']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['train_no']); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = 'status-destination';
                                        $status_text = 'Destination';
                                        if ($record['type_of_attendance'] === 'Start of journey') {
                                            $status_class = 'status-start';
                                            $status_text = 'Start';
                                        } elseif ($record['type_of_attendance'] === 'Mid of journey') {
                                            $status_class = 'status-interval';
                                            $status_text = 'Interval';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td style="text-align: left; padding-left: 16px;">
                                        <?php echo htmlspecialchars($record['location']); ?>
                                        <br>
                                        <span style="color: #64748b; font-size: 12px;">
                                            <?php echo date('d-m-Y H:i:s', strtotime($record['created_at'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                    <?php if (empty($selected_grade)): ?>
                                        Please select filters and click Submit to view attendance report.
                                    <?php else: ?>
                                        No attendance records found for the selected filters.
                                    <?php endif; ?>
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
    
    <script>
    // Mobile Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const closeSidebar = document.getElementById('closeSidebar');

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
    </script>

</body>

</html>
