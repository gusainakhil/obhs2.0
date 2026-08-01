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
$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_id_text = (string) $station_id;
$user_id = (int) ($_SESSION['user_id'] ?? 0);

checkSubscription($station_id);

// Dashboard only reads session data; release the lock before heavier DB work.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

function dashboard_empty_day_counts($ordered_days)
{
    $data = [];
    foreach ($ordered_days as $day) {
        $data[$day] = 0;
    }

    return $data;
}

function dashboard_activity_summary($mysqli, $source, $station_id, $station_id_text, $ordered_days, $week_date_map)
{
    $sources = [
        'attendance' => [
            'table' => 'base_attendance',
            'date_column' => 'created_at',
            'station_type' => 'i',
        ],
        'photo' => [
            'table' => 'base_photo_report',
            'date_column' => 'created_at',
            'station_type' => 's',
        ],
        'feedback' => [
            'table' => 'OBHS_passenger',
            'date_column' => 'created',
            'station_type' => 's',
        ],
    ];

    $summary = [
        'today' => 0,
        'month' => 0,
        'weekly' => dashboard_empty_day_counts($ordered_days),
    ];

    if (!isset($sources[$source])) {
        return $summary;
    }

    $table = $sources[$source]['table'];
    $date_column = $sources[$source]['date_column'];

    $sql = "
        SELECT
            DATE({$date_column}) AS report_date,
            COUNT(*) AS day_count,
            SUM(CASE
                WHEN {$date_column} >= CURDATE()
                 AND {$date_column} < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN 1 ELSE 0
            END) AS today_count,
            SUM(CASE
                WHEN {$date_column} >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                 AND {$date_column} < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN 1 ELSE 0
            END) AS month_count
        FROM {$table}
        WHERE station_id = ?
          AND {$date_column} >= DATE_SUB(CURDATE(), INTERVAL 31 DAY)
          AND {$date_column} < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        GROUP BY report_date
        ORDER BY report_date ASC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard activity prepare failed: ' . $mysqli->error);
        return $summary;
    }

    if ($sources[$source]['station_type'] === 'i') {
        $station_param = $station_id;
        $stmt->bind_param('i', $station_param);
    } else {
        $station_param = $station_id_text;
        $stmt->bind_param('s', $station_param);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_date = $row['report_date'] ?? '';
        if (isset($week_date_map[$report_date])) {
            $summary['weekly'][$week_date_map[$report_date]] = (int) $row['day_count'];
        }

        $summary['today'] += (int) ($row['today_count'] ?? 0);
        $summary['month'] += (int) ($row['month_count'] ?? 0);
    }

    $stmt->close();

    return $summary;
}

function dashboard_feedback_counts($mysqli, $station_id_text)
{
    $counts = ['today' => 0, 'month' => 0];

    $sql = "
        SELECT
            COUNT(DISTINCT CASE
                WHEN p.created >= CURDATE()
                 AND p.created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN p.id
            END) AS today_count,
            COUNT(DISTINCT CASE
                WHEN p.created >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                 AND p.created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN p.id
            END) AS month_count
        FROM OBHS_feedback f
        INNER JOIN OBHS_passenger p ON p.id = f.passenger_id
        WHERE p.station_id = ?
          AND p.created >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND p.created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard feedback count prepare failed: ' . $mysqli->error);
        return $counts;
    }

    $stmt->bind_param('s', $station_id_text);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $counts['today'] = (int) ($row['today_count'] ?? 0);
        $counts['month'] = (int) ($row['month_count'] ?? 0);
    }

    $stmt->close();

    return $counts;
}

function dashboard_train_activity_counts($mysqli, $station_id, $station_id_text)
{
    $counts = ['today' => 0, 'month' => 0];

    $sql = "
        SELECT
            COUNT(DISTINCT CASE
                WHEN event_date >= CURDATE()
                 AND event_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN train_no
            END) AS today_count,
            COUNT(DISTINCT train_no) AS month_count
        FROM (
            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no, created_at AS event_date
            FROM base_attendance
            WHERE station_id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

            UNION ALL

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no, created AS event_date
            FROM OBHS_passenger
            WHERE station_id = ?
              AND created >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

            UNION ALL

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no, created_at AS event_date
            FROM base_photo_report
            WHERE station_id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ) AS all_trains";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard train count prepare failed: ' . $mysqli->error);
        return $counts;
    }

    $station_param = $station_id;
    $passenger_station_param = $station_id_text;
    $photo_station_param = $station_id_text;
    $stmt->bind_param('iss', $station_param, $passenger_station_param, $photo_station_param);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $counts['today'] = (int) ($row['today_count'] ?? 0);
        $counts['month'] = (int) ($row['month_count'] ?? 0);
    }

    $stmt->close();

    return $counts;
}

function dashboard_train_list($mysqli, $station_id_text)
{
    $trains = [];
    $sql = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard train list prepare failed: ' . $mysqli->error);
        return $trains;
    }

    $stmt->bind_param('s', $station_id_text);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trains[] = $row['train_no'];
    }

    $stmt->close();

    return $trains;
}

function dashboard_subscription($mysqli, $user_id)
{
    $subscription = [
        'exists' => false,
        'is_active' => false,
        'days_left' => 0,
        'status_color' => 'red',
        'status_text' => 'EXPIRED',
        'end_date_display' => '',
        'progress_width' => 0,
    ];

    $sql = "SELECT start_date, end_date FROM OBHS_users WHERE user_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard subscription prepare failed: ' . $mysqli->error);
        return $subscription;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        try {
            $end_date = new DateTimeImmutable($row['end_date']);
            $now = new DateTimeImmutable();
            $days_left = $end_date > $now ? $now->diff($end_date)->days : 0;
            $is_active = $end_date > $now;
            $status_color = $days_left > 30 ? 'green' : ($days_left > 0 ? 'amber' : 'red');

            $subscription = [
                'exists' => true,
                'is_active' => $is_active,
                'days_left' => $days_left,
                'status_color' => $status_color,
                'status_text' => $is_active ? 'ACTIVE' : 'EXPIRED',
                'end_date_display' => $end_date->format('d M Y'),
                'progress_width' => min(100, ($days_left / 365) * 100),
            ];
        } catch (Exception $e) {
            error_log('Dashboard subscription date parse failed: ' . $e->getMessage());
        }
    }

    $stmt->close();

    return $subscription;
}

function dashboard_latest_feedback($mysqli, $station_id_text)
{
    $feedback = [];
    $sql = "
        SELECT train_no, name, pnr_number, ph_number, created AS created_at
        FROM OBHS_passenger
        WHERE station_id = ?
        ORDER BY created DESC
        LIMIT 5";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard latest feedback prepare failed: ' . $mysqli->error);
        return $feedback;
    }

    $stmt->bind_param('s', $station_id_text);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }

    $stmt->close();

    return $feedback;
}

function dashboard_chart_rows($ordered_days, $data)
{
    $rows = [];
    foreach ($ordered_days as $day) {
        $rows[] = [$day, isset($data[$day]) ? (int) $data[$day] : 0];
    }

    return $rows;
}

$station_name = getStationName($station_id);
$station_name_safe = htmlspecialchars($station_name, ENT_QUOTES, 'UTF-8');
$station_name = $station_name_safe;

$ordered_days = [];
$week_date_map = [];
$today = new DateTimeImmutable('today');
for ($i = 6; $i >= 0; $i--) {
    $date = $today->modify("-{$i} days");
    $day_name = $date->format('D');
    $ordered_days[] = $day_name;
    $week_date_map[$date->format('Y-m-d')] = $day_name;
}

$trains = dashboard_train_list($mysqli, $station_id_text);
$total_trains = count($trains);

$attendance_summary = dashboard_activity_summary($mysqli, 'attendance', $station_id, $station_id_text, $ordered_days, $week_date_map);
$photo_summary = dashboard_activity_summary($mysqli, 'photo', $station_id, $station_id_text, $ordered_days, $week_date_map);
$feedback_summary = dashboard_activity_summary($mysqli, 'feedback', $station_id, $station_id_text, $ordered_days, $week_date_map);

$attendance_data = $attendance_summary['weekly'];
$photo_data = $photo_summary['weekly'];
$feedback_data = $feedback_summary['weekly'];

$today_attendance = $attendance_summary['today'];
$month_attendance = $attendance_summary['month'];
$today_photos = $photo_summary['today'];
$month_photos = $photo_summary['month'];
$counts = dashboard_feedback_counts($mysqli, $station_id_text);
$train_counts = dashboard_train_activity_counts($mysqli, $station_id, $station_id_text);
$subscription = dashboard_subscription($mysqli, $user_id);
$latest_feedback = dashboard_latest_feedback($mysqli, $station_id_text);

$attendance_chart_rows = dashboard_chart_rows($ordered_days, $attendance_data);
$photo_chart_rows = dashboard_chart_rows($ordered_days, $photo_data);
$feedback_chart_rows = dashboard_chart_rows($ordered_days, $feedback_data);

?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - <?php echo $station_name_safe; ?> </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Blinking animation */
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .blink-text {
            animation: blink 1s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-slate-50">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <!-- sidebar  -->
   <?php
   require_once 'includes/sidebar.php';
   ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">

        <!-- Top Navigation Bar -->
       <?php
       require_once 'includes/header.php';
       ?>


        <!-- Daily Report Bar -->
        <div class="bg-slate-200 px-4 lg:px-6 py-3 border-b border-slate-300">
            <div class="flex items-center space-x-2 text-slate-700">
                <i class="fas fa-calendar-day text-slate-600"></i>
                <span class="text-sm font-medium">Daily report for <span class="font-semibold"><?php echo date('F j, Y'); ?></span></span>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <main class="p-4 lg:p-6">

            <!-- Metric Cards Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">

                <!-- PSI Score Card -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="flex items-center p-3">
                        <div
                            class="w-16 h-16 bg-green-500 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-comments text-white text-2xl"></i>
                        </div>
                                <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">Feedback Count </h3>
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">TODAY</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $counts['today']; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">THIS MONTH</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $counts['month']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Card -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="flex items-center p-3">
                        <div
                            class="w-16 h-16 bg-purple-500 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-users text-white text-2xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">ATTENDANCE</h3>
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">TODAY</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $today_attendance; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">THIS MONTH</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $month_attendance; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cleanliness Pics Card -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="flex items-center p-3">
                        <div
                            class="w-16 h-16 bg-orange-500 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-broom text-white text-2xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">CLEANLINESS PICS</h3>
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">TODAY</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $today_photos; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">THIS MONTH</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $month_photos; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trains Running Card -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="flex items-center p-3">
                        <div
                            class="w-16 h-16 bg-blue-500 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-train text-white text-2xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">TOTAL TRAINS RUNNING</h3>
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">TODAY</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $train_counts['today']; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">THIS MONTH</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo $train_counts['month']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Second Row: Happiness Index, Latest Ratings, Trains List -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- Gross Happiness Index -->
                <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-crown text-indigo-500"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Subscription Status </h2>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if (!$subscription['exists'] || !$subscription['is_active']): ?>
                        <!-- Expired Subscription View -->
                        <div class="flex flex-col items-center justify-center p-6 bg-red-50 rounded-lg border border-red-200">
                            <i class="fas fa-exclamation-circle text-5xl text-red-500 mb-3"></i>
                            <p class="text-2xl font-bold text-red-600 mb-2">EXPIRED</p>
                            <p class="text-sm text-red-500 font-medium blink-text text-center">Please renew your subscription; otherwise, your dashboard will be locked.</p>
                        </div>
                        <?php else: ?>
                        <!-- Active Subscription View -->
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <div>
                                <p class="text-xs text-slate-500 font-medium">Status</p>
                                <p class="text-lg font-bold text-<?php echo $subscription['status_color']; ?>-600"><?php echo $subscription['status_text']; ?></p>
                            </div>
                            <i class="fas fa-check-circle text-2xl text-<?php echo $subscription['status_color']; ?>-500"></i>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="p-2 bg-slate-50 rounded border border-slate-200">
                                <p class="text-xs text-slate-500">Days Left</p>
                                <p class="text-xl font-bold text-slate-800"><?php echo $subscription['days_left']; ?></p>
                            </div>
                            <div class="p-2 bg-slate-50 rounded border border-slate-200">
                                <p class="text-xs text-slate-500">Expires</p>
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($subscription['end_date_display'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>

                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="bg-<?php echo $subscription['status_color']; ?>-500 h-full rounded-full" style="width: <?php echo $subscription['progress_width']; ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Latest Ratings -->
                <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-star text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Latest Feedback </h2>
                    </div>
                    <div class="space-y-3 max-h-48 overflow-y-auto">
                        <?php if (count($latest_feedback) > 0): ?>
                            <?php foreach ($latest_feedback as $row): ?>
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div>
                                <p class="text-blue-500 font-semibold text-sm mb-1"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . " - " . htmlspecialchars($row['ph_number'], ENT_QUOTES, 'UTF-8'); ?> </p>
                                <p class="text-slate-600 text-xs">PNR: <span class="font-bold text-slate-800"><?php echo htmlspecialchars($row['pnr_number'], ENT_QUOTES, 'UTF-8') . " - " . htmlspecialchars($row['train_no'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                            </div>
                            <div class="text-xs text-blue-400"><?php echo date('m/d/Y', strtotime($row['created_at'])); ?></div>
                        </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-4 text-slate-500 text-sm">
                            No feedback yet
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Trains List -->
                <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-list text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Trains List (Total: <?php echo $total_trains; ?>)</h2>
                    </div>
                    <div class="grid grid-cols-6 gap-2 max-h-48 overflow-y-auto">
                        <?php if (count($trains) > 0): ?>
                            <?php foreach ($trains as $train_no): ?>
                                <div class="bg-slate-100 hover:bg-blue-100 text-center py-2 rounded text-xs font-semibold text-slate-700 cursor-pointer transition">
                                    <?php echo htmlspecialchars($train_no); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-6 text-center py-4 text-slate-500 text-sm">
                                No trains found. Add feedback targets to see trains.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- Weekly Cleanliness Photos Count -->
                  <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-chart-bar text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Weekly Feedback Count</h2>
                    </div>
                    <div id="feedbackChart" style="width: 100%; height: 200px;"></div>
                </div>
                

                <!-- Weekly Attendance Count -->
                <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-chart-bar text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Weekly Attendance Count</h2>
                    </div>
                    <div id="attendanceChart" style="width: 100%; height: 200px;"></div>
                </div>

                <!-- Weekly Feedback Count -->
                 <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-chart-bar text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Weekly Cleanliness Photos Count</h2>
                    </div>
                    <div id="cleanlinessChart" style="width: 100%; height: 200px;"></div>
                </div>
            </div>

            <!-- Footer -->
           <?php
            require_once 'includes/footer.php';
           ?>

        </main>

    </div>

    <script>
        // Load Google Charts
        google.charts.load('current', { 'packages': ['corechart', 'bar'] });
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            drawCleanlinessChart();
            drawAttendanceChart();
            drawFeedbackChart();
        }

        // Weekly Cleanliness Photos Count Chart
        function drawCleanlinessChart() {
            var rows = <?php echo json_encode($photo_chart_rows); ?>;

            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Photos Count');
            data.addRows(rows);

            var options = {
                title: '',
                chartArea: { width: '80%', height: '70%' },
                colors: ['#f59e0b'],
                legend: { position: 'none' },
                vAxis: {
                    minValue: 0,
                    gridlines: { color: '#e2e8f0', count: 3 }
                },
                hAxis: {
                    textStyle: { fontSize: 11, color: '#64748b' }
                },
                bar: { groupWidth: '70%' },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('cleanlinessChart'));
            chart.draw(data, options);
        }

        // Weekly Attendance Count Chart
        function drawAttendanceChart() {
            var rows = <?php echo json_encode($attendance_chart_rows); ?>;

            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Attendance');
            data.addRows(rows);

            var options = {
                title: '',
                chartArea: { width: '80%', height: '70%' },
                colors: ['#8b5cf6'],
                legend: { position: 'none' },
                vAxis: {
                    minValue: 0,
                    gridlines: { color: '#e2e8f0' }
                },
                hAxis: {
                    textStyle: { fontSize: 11, color: '#64748b' }
                },
                bar: { groupWidth: '70%' },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('attendanceChart'));
            chart.draw(data, options);
        }

        // Weekly Feedback Count Chart
        function drawFeedbackChart() {
            var rows = <?php echo json_encode($feedback_chart_rows); ?>;

            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Feedback Count');
            data.addRows(rows);

            var options = {
                title: '',
                chartArea: { width: '80%', height: '70%' },
                colors: ['#30bd61'],
                legend: { position: 'none' },
                vAxis: {
                    minValue: 0,
                    gridlines: { color: '#e2e8f0' }
                },
                hAxis: {
                    textStyle: { fontSize: 11, color: '#64748b' }
                },
                bar: { groupWidth: '70%' },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('feedbackChart'));
            chart.draw(data, options);
        }

        // Redraw charts on window resize for responsiveness
        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(drawCharts, 150);
        });

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
    </script>

</body>

</html>
