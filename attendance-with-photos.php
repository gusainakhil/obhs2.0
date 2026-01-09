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
$date_from = $_REQUEST['dateFrom'] ?? date('Y-m-01');
$date_to = $_REQUEST['dateTo'] ?? date('Y-m-d');

// Fetch attendance data with photos (optimized with LEFT JOIN to avoid N+1 query)
$attendance_data = [];
if (!empty($selected_grade) && !empty($selected_train_from) && !empty($selected_train_to)) {
    $query = "SELECT 
                ba.employee_id,
                ba.employee_name,
                ba.train_no,
                ba.type_of_attendance,
                ba.location,
                ba.photo,
                ba.created_at,
                be.photo as employee_photo
              FROM base_attendance ba
              LEFT JOIN base_employees be ON ba.employee_id = be.employee_id AND be.station = ?
              WHERE ba.station_id = ?
              AND ba.grade = ?
              AND ba.train_no IN (?, ?)
              AND DATE(ba.created_at) BETWEEN ? AND ?
              ORDER BY ba.employee_name, ba.train_no, FIELD(ba.type_of_attendance, 'Start of journey', 'Mid of journey', 'End of journey')";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssssss", $station_id, $station_id, $selected_grade, $selected_train_from, $selected_train_to, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Organize data by employee
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['employee_id'];
        
        if (!isset($attendance_data[$emp_id])) {
            $employee_photo = $row['employee_photo'] ?? '';
            
            // Debug: Log if no photo found
            if ($debug && empty($employee_photo)) {
                error_log("No photo found for employee: $emp_id, station: $station_id");
            }
            
            $attendance_data[$emp_id] = [
                'employee_name' => $row['employee_name'],
                'employee_id' => $row['employee_id'],
                'employee_photo' => $employee_photo,
                'train_from' => [],
                'train_to' => []
            ];
        }
        
        // Organize by train and checkpoint type
        if ($row['train_no'] == $selected_train_from) {
            $attendance_data[$emp_id]['train_from'][$row['type_of_attendance']] = [
                'location' => $row['location'],
                'photo' => $row['photo'],
                'created_at' => $row['created_at']
            ];
        } elseif ($row['train_no'] == $selected_train_to) {
            $attendance_data[$emp_id]['train_to'][$row['type_of_attendance']] = [
                'location' => $row['location'],
                'photo' => $row['photo'],
                'created_at' => $row['created_at']
            ];
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance with Photos</title>
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

        .selected-grade-info {
            background-color: #f1f5f9;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            color: #475569;
            margin-top: 8px;
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
            transition: box-shadow 0.3s ease, background-color 0.3s ease;
        }

        .attendance-table tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background-color: #f8fafc;
        }

        .attendance-table tbody td {
            padding: 16px;
            text-align: center;
            font-size: 13px;
            color: #334155;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .attendance-table tbody td:last-child {
            border-right: none;
        }

        .employee-cell {
            text-align: center !important;
            padding: 12px 16px !important;
            min-width: 200px;
        }

        .employee-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            display: block;
            margin: 0 auto;
            flex-shrink: 0;
        }

        .employee-info {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 12px;
            justify-content: center;
        }

        .employee-photo-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .employee-details {
            flex: 1;
            width: 100%;
            text-align: center;
        }

        .employee-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 15px;
            line-height: 1.5;
        }

        .employee-name .counter {
            color: #0ea5e9;
            margin-right: 4px;
        }

        .counter-badge {
            font-size: 24px;
            font-weight: 700;
            color: #0ea5e9;
            background: #f0f9ff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #0ea5e9;
            flex-shrink: 0;
        }

        .employee-id {
            font-weight: 600;
            color: #1e293b;
            font-size: 15px;
            margin-top: 6px;
            line-height: 1.5;
        }

        .employee-id strong {
            color: #1e293b;
        }

        .location-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }

        .coordinates {
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
            font-weight: 500;
            max-width: 140px;
            word-wrap: break-word;
            word-break: break-all;
            overflow-wrap: break-word;
            margin: 0 auto;
        }

        .date-time {
            font-size: 13px;
            color: #1e293b;
            line-height: 1.6;
            font-weight: 600;
            margin-top: 6px;
        }

        .report-icon {
            width: 110px;
            height: 110px;
            cursor: pointer;
            transition: transform 0.2s ease;
            display: block;
            margin: 0 auto 8px auto;
            border-radius: 4px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .report-icon:hover {
            transform: scale(1.4);
        }

        .journey-header {
            background: #0ea5e9 !important;
            font-weight: 700;
            color: white !important;
            font-size: 14px !important;
            letter-spacing: 0.5px;
        }

        @media (max-width: 1024px) {
            .attendance-table tbody td {
                padding: 12px 8px;
            }

            .report-icon {
                width: 100px;
                height: 100px;
            }
        }

        @media (max-width: 768px) {
            .attendance-table {
                font-size: 11px;
            }

            .attendance-table tbody td {
                padding: 10px 6px;
            }

            .employee-photo {
                width: 60px;
                height: 60px;
            }
            
            .report-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 6px auto;
            }

            .coordinates {
                font-size: 11px;
                max-width: 120px;
            }

            .date-time {
                font-size: 11px;
            }

            .employee-cell {
                min-width: 150px;
                padding: 10px 8px !important;
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
                        <!--<button type="button" class="btn-print" onclick="window.print()">-->
                        <button type="button" class="btn-print" onclick="printAttendance()">
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
                            <th rowspan="2" style="width: 250px;">Employee Name</th>
                            <th colspan="3" class="journey-header">Train Up No. <?php echo htmlspecialchars($selected_train_from ?: 'N/A'); ?></th>
                            <th colspan="3" class="journey-header">Train Down No. <?php echo htmlspecialchars($selected_train_to ?: 'N/A'); ?></th>
                        </tr>
                        <tr>
                            <th>Start of journey</th>
                            <th>Mid of journey</th>
                            <th>End of journey</th>
                            <th>Start of journey</th>
                            <th>Mid of journey</th>
                            <th>End of journey</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_data)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($attendance_data as $emp_id => $employee): ?>
                                <tr>
                                    <td class="employee-cell">
    <div class="employee-info">
        <div class="counter-badge"><?php echo $counter++; ?></div>
        
        <div class="employee-photo-wrapper">
            <?php 
            $employee_photo = 'uploads/employee/' . $employee['employee_photo'];
            if (empty($employee['employee_photo']) || !file_exists($employee_photo)) {
                $employee_photo = 'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png';
            }
            ?>
            
            <img src="<?php echo htmlspecialchars($employee_photo); ?>" alt="Photo" class="employee-photo">

            <div class="employee-details">
                <div class="employee-name">
                    <strong>Emp Name:</strong> <?php echo htmlspecialchars($employee['employee_name']); ?>
                </div>

                <div class="employee-id">
                    <strong>Emp ID:</strong> <strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong>
                </div>
            </div>
        </div>
    </div>
</td>

                                    
                                    <?php 
                                    $checkpoints = ['Start of journey', 'Mid of journey', 'End of journey'];
                                    foreach ($checkpoints as $checkpoint): 
                                        $data = $employee['train_from'][$checkpoint] ?? null;
                                    ?>
                                        <td>
                                            <?php if ($data): ?>
                                                <?php 
                                                $photo_path = 'uploads/attendence/' . $data['photo'];
                                                if (!file_exists($photo_path)) {
                                                    $photo_path = 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/No_image_available.svg/1024px-No_image_available.svg.png';
                                                }
                                                
                                                // Parse location data
                                                $location = $data['location'];
                                                $latitude = '';
                                                $longitude = '';
                                                $location_name = '';
                                                
                                                // Format 1: 'lati: 21.1312829 longi: 79.0843243 Nagpur'
                                                if (preg_match('/lati:\s*([\d.]+)\s+longi:\s*([\d.]+)\s*(.+)/', $location, $matches)) {
                                                    $latitude = $matches[1];
                                                    $longitude = $matches[2];
                                                    $location_name = trim($matches[3]);
                                                }
                                                // Format 2: '16.3016563,80.4446609,Guntur'
                                                else if (preg_match('/^([\d.]+),([\d.]+),(.+)$/', $location, $matches)) {
                                                    $latitude = $matches[1];
                                                    $longitude = $matches[2];
                                                    $location_name = trim($matches[3]);
                                                }
                                                else {
                                                    $location_name = $location;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Report" class="report-icon">
                                                <div class="coordinates">
                                                    <?php if (!empty($latitude)): ?>
                                                        Lati: <?php echo htmlspecialchars($latitude); ?><br>
                                                        Longi: <?php echo htmlspecialchars($longitude); ?><br>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($location_name); ?>
                                                </div>
                                                <div class="date-time">Date: <?php echo date('d-m-Y H:i:s', strtotime($data['created_at'])); ?></div>
                                            <?php else: ?>
                                                <div style="color: #94a3b8;">No Data</div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($checkpoints as $checkpoint): 
                                        $data = $employee['train_to'][$checkpoint] ?? null;
                                    ?>
                                        <td>
                                            <?php if ($data): ?>
                                                <?php 
                                                $photo_path = 'uploads/attendence/' . $data['photo'];
                                                if (!file_exists($photo_path)) {
                                                    $photo_path = 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/No_image_available.svg/1024px-No_image_available.svg.png';
                                                }
                                                
                                                // Parse location data
                                                $location = $data['location'];
                                                $latitude = '';
                                                $longitude = '';
                                                $location_name = '';
                                                
                                                // Format 1: 'lati: 21.1312829 longi: 79.0843243 Nagpur'
                                                if (preg_match('/lati:\s*([\d.]+)\s+longi:\s*([\d.]+)\s*(.+)/', $location, $matches)) {
                                                    $latitude = $matches[1];
                                                    $longitude = $matches[2];
                                                    $location_name = trim($matches[3]);
                                                }
                                                // Format 2: '16.3016563,80.4446609,Guntur'
                                                else if (preg_match('/^([\d.]+),([\d.]+),(.+)$/', $location, $matches)) {
                                                    $latitude = $matches[1];
                                                    $longitude = $matches[2];
                                                    $location_name = trim($matches[3]);
                                                }
                                                else {
                                                    $location_name = $location;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Report" class="report-icon">
                                                <div class="coordinates">
                                                    <?php if (!empty($latitude)): ?>
                                                        Lati: <?php echo htmlspecialchars($latitude); ?><br>
                                                        Longi: <?php echo htmlspecialchars($longitude); ?><br>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($location_name); ?>
                                                </div>
                                                <div class="date-time">Date: <?php echo date('d-m-Y H:i:s', strtotime($data['created_at'])); ?></div>
                                            <?php else: ?>
                                                <div style="color: #94a3b8;">No Data</div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                    <?php if (empty($selected_grade)): ?>
                                        Please select filters and click Submit to view attendance.
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
        
        
        
        // Print Attendance Function
        function printAttendance() {
            const grade = document.getElementById('grade').value;
            const trainFrom = document.getElementById('trainFrom').value;
            const trainTo = document.getElementById('trainTo').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            if (!grade || !trainFrom || !trainTo) {
                alert('Please select all required filters before printing.');
                return;
            }

            const params = new URLSearchParams({
                grade: grade,
                trainFrom: trainFrom,
                trainTo: trainTo,
                dateFrom: dateFrom,
                dateTo: dateTo
            });

             window.open('print-attendance.php?' + params.toString(), '_blank');
        }
</script>

</body>

</html>
