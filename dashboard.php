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

// Fetch trains from base_fb_target
$trains = [];
$train_query = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
$stmt = $mysqli->prepare($train_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trains[] = $row['train_no'];
}
$stmt->close();
$total_trains = count($trains);

// Fetch weekly attendance count for the last 7 days
$attendance_data = [];
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Initialize array with 0 for each day
foreach ($days as $day) {
    $attendance_data[$day] = 0;
}

// Get attendance count grouped by day of week for the last 7 days
$attendance_query = "SELECT DATE(created_at) as date, DAYNAME(created_at) as day_name, COUNT(*) as count 
                     FROM base_attendance 
                     WHERE station_id = ? 
                     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                     GROUP BY DATE(created_at), DAYNAME(created_at)
                     ORDER BY date ASC";
$stmt = $mysqli->prepare($attendance_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $day_short = substr($row['day_name'], 0, 3); // Get first 3 characters (Sun, Mon, etc.)
    $attendance_data[$day_short] = (int)$row['count'];
}
$stmt->close();

// Fetch weekly photo report count for the last 7 days
$photo_data = [];

// Initialize array with 0 for each day
foreach ($days as $day) {
    $photo_data[$day] = 0;
}

// Get photo report count grouped by day of week for the last 7 days
$photo_query = "SELECT DATE(created_at) as date, DAYNAME(created_at) as day_name, COUNT(*) as count 
                FROM base_photo_report 
                WHERE station_id = ? 
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at), DAYNAME(created_at)
                ORDER BY date ASC";
$stmt = $mysqli->prepare($photo_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $day_short = substr($row['day_name'], 0, 3); // Get first 3 characters (Sun, Mon, etc.)
    $photo_data[$day_short] = (int)$row['count'];
}
$stmt->close();

// Fetch cleanliness pics count for TODAY and THIS MONTH
$today_photos = 0;
$month_photos = 0;

// Get today's count
$today_query = "SELECT COUNT(*) as count FROM base_photo_report WHERE station_id = ? AND DATE(created_at) = CURDATE()";
$stmt = $mysqli->prepare($today_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $today_photos = (int)$row['count'];
}
$stmt->close();

// Get this month's count
$month_query = "SELECT COUNT(*) as count FROM base_photo_report WHERE station_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$stmt = $mysqli->prepare($month_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $month_photos = (int)$row['count'];
}
$stmt->close();

// Fetch attendance count for TODAY and THIS MONTH
$today_attendance = 0;
$month_attendance = 0;

// Get today's attendance count
$today_att_query = "SELECT COUNT(*) as count FROM base_attendance WHERE station_id = ? AND DATE(created_at) = CURDATE()";
$stmt = $mysqli->prepare($today_att_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $today_attendance = (int)$row['count'];
}
$stmt->close();

// Get this month's attendance count
$month_att_query = "SELECT COUNT(*) as count FROM base_attendance WHERE station_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$stmt = $mysqli->prepare($month_att_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $month_attendance = (int)$row['count'];
}
$stmt->close();

?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - <?php echo $station_name; ?> </title>
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
                                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php $counts = feedback_count(); echo $counts['today']; ?></p>
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
                            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">TRAINS RUNNING</h3>
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">TODAY</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight">28</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] text-gray-500 font-semibold mb-0.5">THIS MONTH</p>
                                    <p class="text-xl font-bold text-gray-900 leading-tight">235</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Second Row: Happiness Index, Latest Ratings, Trains List -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- Gross Happiness Index -->
                <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-smile text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Gross Happiness Index</h2>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-4"><?php echo $station_name; ?></h3>
                    <div class="space-y-3">
                        <div class="bg-emerald-500 rounded-lg px-4 py-3 text-white">
                            <p class="text-sm font-semibold">HIGH RATINGS (4034) :: 100.00%</p>
                        </div>
                        <div class="bg-red-500 rounded-lg px-4 py-3 text-white flex items-center">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-frown text-red-500"></i>
                            </div>
                            <p class="text-sm font-semibold">LOW RATINGS (0) :: 0.00%</p>
                        </div>
                    </div>
                </div>

                <!-- Latest Ratings -->
                <div class="bg-white rounded-xl shadow-md p-5">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-star text-slate-400"></i>
                        <h2 class="text-sm font-semibold text-slate-700">Latest Ratings</h2>
                    </div>
                    <div class="flex items-center justify-between py-8">
                        <div>
                            <p class="text-blue-500 font-semibold text-lg mb-1">Suresh :-</p>
                            <p class="text-slate-600 text-sm">Score: <span
                                    class="font-bold text-slate-800">100.00%</span></p>
                        </div>
                        <div class="text-xs text-blue-400">06/09/2025</div>
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
                        <h2 class="text-sm font-semibold text-slate-700">Weekly Cleanliness Photos Count</h2>
                    </div>
                    <div id="cleanlinessChart" style="width: 100%; height: 20</div></div>0px;"></div>
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
                        <h2 class="text-sm font-semibold text-slate-700">Weekly Feedback Count</h2>
                    </div>
                    <div id="feedbackChart" style="width: 100%; height: 200px;"></div>
                </div>

            </div>

            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
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
            var data = google.visualization.arrayToDataTable([
                ['Day', 'Photos Count'],
                ['Sun', <?php echo $photo_data['Sun']; ?>],
                ['Mon', <?php echo $photo_data['Mon']; ?>],
                ['Tue', <?php echo $photo_data['Tue']; ?>],
                ['Wed', <?php echo $photo_data['Wed']; ?>],
                ['Thu', <?php echo $photo_data['Thu']; ?>],
                ['Fri', <?php echo $photo_data['Fri']; ?>],
                ['Sat', <?php echo $photo_data['Sat']; ?>]
            ]);

            var options = {
                title: '',
                chartArea: { width: '80%', height: '70%' },
                colors: ['#94a3b8'],
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
            var data = google.visualization.arrayToDataTable([
                ['Day', 'Attendance'],
                ['Sun', <?php echo $attendance_data['Sun']; ?>],
                ['Mon', <?php echo $attendance_data['Mon']; ?>],
                ['Tue', <?php echo $attendance_data['Tue']; ?>],
                ['Wed', <?php echo $attendance_data['Wed']; ?>],
                ['Thu', <?php echo $attendance_data['Thu']; ?>],
                ['Fri', <?php echo $attendance_data['Fri']; ?>],
                ['Sat', <?php echo $attendance_data['Sat']; ?>]
            ]);

            var options = {
                title: '',
                chartArea: { width: '80%', height: '70%' },
                colors: ['#3b82f6'],
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
            var data = google.visualization.arrayToDataTable([
                ['Day', 'Feedback Count'],
                ['Sun', 600],
                ['Mon', 705],
                ['Tue', 560],
                ['Wed', 760],
                ['Thu', 520],
                ['Fri', 655],
                ['Sat', 465]
            ]);

            var options = {
                title: '',
                chartArea: { width: '80%', height: '70%' },
                colors: ['#3b82f6'],
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
        window.addEventListener('resize', function () {
            drawCharts();
        });

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