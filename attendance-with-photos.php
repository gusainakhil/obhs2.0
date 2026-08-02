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

function attendancePhotosEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function attendancePhotosRequestValue($key, $default = '')
{
    if (isset($_POST[$key])) {
        return (string) $_POST[$key];
    }

    if (isset($_GET[$key])) {
        return (string) $_GET[$key];
    }

    return (string) $default;
}

function attendancePhotosFetchTrains($mysqli, $station_id)
{
    $trains = [];
    $sql = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        error_log('Attendance photos train list prepare failed: ' . $mysqli->error);
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

function attendancePhotosDisplayFullAddress($fullLocationRaw)
{
    $fullLocationRaw = trim((string)$fullLocationRaw);
    if ($fullLocationRaw === '') {
        return '';
    }

    $address = '';
    $decoded = json_decode($fullLocationRaw, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $address = trim((string)($decoded['formattedAddress'] ?? ''));
    } else {
        if (preg_match('/formattedAddress:\s*(.*?)(?:,\s*subregion\s*:|,\s*timezone\s*:|$)/i', $fullLocationRaw, $matches)) {
            $address = trim($matches[1]);
        } else {
            $address = $fullLocationRaw;
        }
    }

    $address = preg_replace('/,\s*India\s*$/i', '', $address);
    return trim($address);
}

function attendancePhotosParseLocation($rawLocation)
{
    $location = html_entity_decode((string)$rawLocation, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $location = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $location);
    $location = trim(preg_replace('/\s+/', ' ', $location));

    $latitude = '';
    $longitude = '';
    $location_name = $location;

    if (preg_match('/lati\s*:\s*(-?[\d.]+)\s*longi\s*:\s*(-?[\d.]+)\s*(.+)?/iu', $location, $matches)) {
        $latitude = trim($matches[1]);
        $longitude = trim($matches[2]);
        $location_name = trim($matches[3] ?? '');
    } elseif (preg_match('/^(-?[\d.]+),\s*(-?[\d.]+),\s*(.+)$/u', $location, $matches)) {
        $latitude = trim($matches[1]);
        $longitude = trim($matches[2]);
        $location_name = trim($matches[3]);
    }

    return [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'location_name' => $location_name,
    ];
}

function attendancePhotosResolveImagePath($directory, $filename, $fallback_url, array &$cache)
{
    $filename = ltrim(str_replace('\\', '/', trim((string) $filename)), '/');

    if ($filename === '' || strpos($filename, '..') !== false) {
        return $fallback_url;
    }

    $path = rtrim($directory, '/') . '/' . $filename;

    if (!array_key_exists($path, $cache)) {
        $cache[$path] = is_file($path);
    }

    return $cache[$path] ? $path : $fallback_url;
}

function attendancePhotosFormatDate($created_at, $show_time)
{
    $timestamp = strtotime((string) $created_at);

    if (!$timestamp) {
        return '';
    }

    return date($show_time ? 'd/m/Y H:i:s' : 'd/m/Y', $timestamp);
}

function attendancePhotosBuildCheckpointData($row, $station_id, $show_time, array &$attendance_photo_cache)
{
    $parsedLocation = attendancePhotosParseLocation($row['location'] ?? '');

    return [
        'photo_path' => attendancePhotosResolveImagePath(
            'uploads/attendence',
            $row['photo'] ?? '',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/No_image_available.svg/1024px-No_image_available.svg.png',
            $attendance_photo_cache
        ),
        'latitude' => $parsedLocation['latitude'],
        'longitude' => $parsedLocation['longitude'],
        'location_name' => $parsedLocation['location_name'],
        'display_full_address' => attendancePhotosDisplayFullAddress($row['fullLocation'] ?? ''),
        'display_date' => attendancePhotosFormatDate($row['created_at'] ?? '', $show_time),
    ];
}

function attendancePhotosFetchData($mysqli, $station_id, $selected_grade, $selected_train_from, $selected_train_to, $date_from, $date_to, $debug = false)
{
    if ($selected_grade === '' || $selected_train_from === '' || $selected_train_to === '') {
        return [];
    }

    $date_from_start = $date_from . ' 00:00:00';
    $date_to_next_day = strtotime($date_to . ' +1 day');
    $date_to_exclusive = $date_to_next_day
        ? date('Y-m-d', $date_to_next_day) . ' 00:00:00'
        : $date_to . ' 23:59:59';

    $query = "SELECT
                ba.employee_id,
                ba.employee_name,
                ba.train_no,
                ba.type_of_attendance,
                ba.location,
                ba.photo,
                ba.created_at,
                ba.fullLocation,
                be.photo as employee_photo
              FROM base_attendance ba
              LEFT JOIN base_employees be ON ba.employee_id = be.employee_id AND be.station = ?
              WHERE ba.station_id = ?
              AND ba.grade = ?
              AND ba.train_no IN (?, ?)
              AND ba.created_at >= ?
              AND ba.created_at < ?
              ORDER BY ba.employee_name, ba.train_no, FIELD(ba.type_of_attendance, 'Start of journey', 'Mid of journey', 'End of journey')";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        error_log('Attendance photos data prepare failed: ' . $mysqli->error);
        return [];
    }

    $stmt->bind_param("iisssss", $station_id, $station_id, $selected_grade, $selected_train_from, $selected_train_to, $date_from_start, $date_to_exclusive);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance_data = [];
    $employee_photo_cache = [];
    $attendance_photo_cache = [];
    $show_time = (int) $station_id !== 23;

    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['employee_id'];

        if (!isset($attendance_data[$emp_id])) {
            $employee_photo = attendancePhotosResolveImagePath(
                'uploads/employee',
                $row['employee_photo'] ?? '',
                'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png',
                $employee_photo_cache
            );

            if ($debug && empty($row['employee_photo'])) {
                error_log("No photo found for employee: $emp_id, station: $station_id");
            }

            $attendance_data[$emp_id] = [
                'employee_name' => $row['employee_name'],
                'employee_id' => $row['employee_id'],
                'employee_photo_path' => $employee_photo,
                'train_from' => [],
                'train_to' => []
            ];
        }

        $checkpoint_data = attendancePhotosBuildCheckpointData($row, $station_id, $show_time, $attendance_photo_cache);

        if ($row['train_no'] == $selected_train_from) {
            $attendance_data[$emp_id]['train_from'][$row['type_of_attendance']] = $checkpoint_data;
        } elseif ($row['train_no'] == $selected_train_to) {
            $attendance_data[$emp_id]['train_to'][$row['type_of_attendance']] = $checkpoint_data;
        }
    }

    $stmt->close();

    return $attendance_data;
}

function attendancePhotosRenderCheckpointCell($data, $station_id)
{
    if (!$data) {
        echo '<div style="color: #94a3b8;">No Data</div>';
        return;
    }
    ?>
    <img src="<?php echo attendancePhotosEscape($data['photo_path']); ?>" alt="Report" class="report-icon">
    <div class="coordinates">
        <?php if (!empty($data['latitude'])): ?>
            Lati: <?php echo attendancePhotosEscape($data['latitude']); ?><br>
            Longi: <?php echo attendancePhotosEscape($data['longitude']); ?><br>
        <?php endif; ?>
        <?php if ((string) $station_id !== '25'): ?>
            location: <?php echo attendancePhotosEscape($data['location_name'] ?: 'NA'); ?>
        <?php endif; ?>

        <?php if (!empty($data['display_full_address']) && (string) $station_id === '25'): ?>
            location: <?php echo attendancePhotosEscape($data['display_full_address']); ?>
        <?php endif; ?>
    </div>
    <div class="date-time">
        Date:
        <?php echo attendancePhotosEscape($data['display_date']); ?>
    </div>
    <?php
}

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_name = attendancePhotosEscape(getStationName($station_id));

// Get filter parameters from URL or POST
$selected_grade = attendancePhotosRequestValue('grade');
$selected_train_from = attendancePhotosRequestValue('trainFrom');
$selected_train_to = attendancePhotosRequestValue('trainTo');
$date_from = attendancePhotosRequestValue('dateFrom', date('Y-m-01'));
$date_to = attendancePhotosRequestValue('dateTo', date('Y-m-d'));
$trains = attendancePhotosFetchTrains($mysqli, $station_id);
$checkpoints = ['Start of journey', 'Mid of journey', 'End of journey'];
$attendance_data = attendancePhotosFetchData(
    $mysqli,
    $station_id,
    $selected_grade,
    $selected_train_from,
    $selected_train_to,
    $date_from,
    $date_to,
    $debug
);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
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
            object-fit: contain;
            object-position: center;
            border: 2px solid #e2e8f0;
            display: block;
            margin: 0 auto;
            flex-shrink: 0;
            background-color: #f9fafb;
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
            object-fit: contain;
            object-position: center;
            border: 2px solid #e2e8f0;
            background-color: #f9fafb;
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
                                <option value="A" <?php echo $selected_grade === 'A' ? 'selected' : ''; ?>>A - Monday
                                </option>
                                <option value="B" <?php echo $selected_grade === 'B' ? 'selected' : ''; ?>>B - Tuesday
                                </option>
                                <option value="C" <?php echo $selected_grade === 'C' ? 'selected' : ''; ?>>C - Wednesday
                                </option>
                                <option value="D" <?php echo $selected_grade === 'D' ? 'selected' : ''; ?>>D - Thursday
                                </option>
                                <option value="E" <?php echo $selected_grade === 'E' ? 'selected' : ''; ?>>E - Friday
                                </option>
                                <option value="F" <?php echo $selected_grade === 'F' ? 'selected' : ''; ?>>F - Saturday
                                </option>
                                <option value="G" <?php echo $selected_grade === 'G' ? 'selected' : ''; ?>>G - Sunday
                                </option>
                            </select>
                        </div>

                        <!-- Train Number From -->
                        <div>
                            <label class="filter-label">Train Number (From)</label>
                            <select name="trainFrom" id="trainFrom" class="filter-select" required>
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo attendancePhotosEscape($train); ?>" <?php echo $selected_train_from === $train ? 'selected' : ''; ?>>
                                        <?php echo attendancePhotosEscape($train); ?>
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
                                    <option value="<?php echo attendancePhotosEscape($train); ?>" <?php echo $selected_train_to === $train ? 'selected' : ''; ?>>
                                        <?php echo attendancePhotosEscape($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="filter-label">From</label>
                            <input type="date" name="dateFrom" id="dateFrom" class="filter-input"
                                value="<?php echo attendancePhotosEscape($date_from); ?>" required>
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="filter-label">To</label>
                            <input type="date" name="dateTo" id="dateTo" class="filter-input"
                                value="<?php echo attendancePhotosEscape($date_to); ?>" required>
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
                            <th colspan="3" class="journey-header">Train Up No.
                                <?php echo attendancePhotosEscape($selected_train_from ?: 'N/A'); ?></th>
                            <th colspan="3" class="journey-header">Train Down No.
                                <?php echo attendancePhotosEscape($selected_train_to ?: 'N/A'); ?></th>
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
                                                <img src="<?php echo attendancePhotosEscape($employee['employee_photo_path']); ?>" alt="Photo"
                                                    class="employee-photo">

                                                <div class="employee-details">
                                                    <div class="employee-name">
                                                        <strong>Emp Name:</strong>
                                                        <?php echo attendancePhotosEscape($employee['employee_name']); ?>
                                                    </div>

                                                    <div class="employee-id">
                                                        <strong>Emp ID:</strong>
                                                        <strong><?php echo attendancePhotosEscape($employee['employee_id']); ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>


                                    <?php foreach ($checkpoints as $checkpoint):
                                        $data = $employee['train_from'][$checkpoint] ?? null;
                                        ?>
                                        <td>
                                            <?php attendancePhotosRenderCheckpointCell($data, $station_id); ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <?php foreach ($checkpoints as $checkpoint):
                                        $data = $employee['train_to'][$checkpoint] ?? null;
                                        ?>
                                        <td>
                                            <?php attendancePhotosRenderCheckpointCell($data, $station_id); ?>
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
