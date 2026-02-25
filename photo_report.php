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

// Get filter parameters from GET or POST
if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_grade = $_REQUEST['grade'] ?? '';
    $selected_train = $_REQUEST['train'] ?? '';
    $date_from = $_REQUEST['dateFrom'] ?? date('Y-m-d');
    $date_to = $_REQUEST['dateTo'] ?? date('Y-m-d');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Report - Jodhpur</title>
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
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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

        .btn-export {
            background-color: #3b82f6;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-export:hover {
            background-color: #2563eb;
        }

        .coach-container {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .coach-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 12px 20px;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
        }

        .time-sections {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border-bottom: 1px solid #e2e8f0;
        }

        .time-section {
            border-right: 1px solid #e2e8f0;
            padding: 16px;
        }

        .time-section:last-child {
            border-right: none;
        }

        .time-header {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            color: #334155;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }

        .photo-item {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            background: #f8fafc;
        }

        .photo-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .photo-img:hover {
            transform: scale(1.05);
        }

        .photo-info {
            padding: 8px;
            font-size: 11px;
            line-height: 1.4;
            color: #475569;
            background: white;
        }

        .photo-info strong {
            color: #1e293b;
            font-weight: 600;
        }

        .no-photos {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            .time-sections {
                grid-template-columns: 1fr;
            }

            .time-section {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .time-section:last-child {
                border-bottom: none;
            }
        }

        @media (max-width: 640px) {
            .photo-grid {
                grid-template-columns: 1fr;
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

            <!-- Page Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Photo Report</h2>
                <p class="text-sm text-slate-600 mt-1">View coach-wise cleaning photos by time slots</p>
            </div>

            <!-- Filter Form -->
            <div class="filter-card">
                <form id="photoReportForm" method="GET" action="">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        
                        <!-- Grade Selection -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-tag mr-1"></i>Grade
                            </label>
                            <?php
$gradeDays = [
    'A' => 'Monday',
    'B' => 'Tuesday',
    'C' => 'Wednesday',
    'D' => 'Thursday',
    'E' => 'Friday',
    'F' => 'Saturday',
    'G' => 'Sunday'
];
?>
<select name="grade" id="grade" class="filter-select" required>
    <option value="">Select Grade</option>
    <?php
    foreach ($gradeDays as $value => $day) {
        $sel = ($value == $selected_grade) ? 'selected' : '';
        echo "<option value='$value' $sel>$value - $day</option>";
    }
    ?>
</select>

                        </div>

                        <!-- Train Number -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-train mr-1"></i>Train Number
                            </label>
                            <select name="train" id="train" class="filter-select" required>
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo htmlspecialchars($train); ?>" <?php echo $selected_train === $train ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-calendar mr-1"></i>From Date
                            </label>
                            <input type="date" name="dateFrom" id="dateFrom" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>" required>
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-calendar mr-1"></i>To Date
                            </label>
                            <input type="date" name="dateTo" id="dateTo" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>" required>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 mt-4">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-search mr-2"></i>Submit
                        </button>
                        <button type="button" class="btn-print" onclick="window.print()">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </form>
            </div>

            <!-- Photo Report Content -->
            <div id="photoReportContent">
                <?php
                if (!empty($selected_grade) && !empty($selected_train)):
                    // Fetch coaches grouped by date
                    $coach_query = "SELECT *, DATE(created_at) as report_date
                                    FROM base_photo_report
                                    WHERE grade = ?
                                    AND train_no = ?
                                    AND station_id = ?
                                    AND DATE(created_at) BETWEEN ? AND ?
                                    GROUP BY coach_no, DATE(created_at)
                                    ORDER BY coach_no, report_date";
                    
                    $stmt = $mysqli->prepare($coach_query);
                    $stmt->bind_param("sssss", $selected_grade, $selected_train, $station_id, $date_from, $date_to);
                    $stmt->execute();
                    $coach_result = $stmt->get_result();
                    
                    if ($coach_result->num_rows > 0):
                        while ($coach_row = $coach_result->fetch_assoc()):
                            $coach_no = $coach_row['coach_no'];
                            $date_only = date('Y-m-d', strtotime($coach_row['created_at']));
                ?>

                <!-- Dynamic Coach -->
                <div class="coach-container">
                    <div class="coach-header">COACH: <?php echo htmlspecialchars($coach_no); ?></div>
                    <div class="time-sections">
                        
                        <!-- 6:00 AM - 10:00 AM Section -->
                        <div class="time-section">
                            <div class="time-header">6:00:00 - 10:00:00</div>
                            <div class="photo-grid">
                                <?php
                                $time1_query = "SELECT * FROM base_photo_report
                                               WHERE grade = ?
                                               AND coach_no = ?
                                               AND train_no = ?
                                               AND station_id = ?
                                               AND DATE(created_at) = ?
                                               AND TIME(created_at) BETWEEN '06:00:00' AND '10:00:00'
                                               LIMIT 6";
                                $stmt1 = $mysqli->prepare($time1_query);
                                $stmt1->bind_param("sssss", $selected_grade, $coach_no, $selected_train, $station_id, $date_only);
                                $stmt1->execute();
                                $time1_result = $stmt1->get_result();
                                
                                if ($time1_result->num_rows > 0):
                                    while ($photo = $time1_result->fetch_assoc()):
                                        // Extract latitude and longitude
                                        $location_str = $photo['location'];
                                        preg_match('/lati:\s*([-\d\.]+)\s*longi:\s*([-\d\.]+)/', $location_str, $matches);
                                        $latitude = isset($matches[1]) ? $matches[1] : 'N/A';
                                        $longitude = isset($matches[2]) ? $matches[2] : 'N/A';
                                        $photo_path = 'uploads/photos/' . $photo['photo'];
                                        if (!file_exists($photo_path)) {
                                            $photo_path = 'https://via.placeholder.com/150x120/94a3b8/ffffff?text=No+Image';
                                        }
                                ?>
                                <div class="photo-item">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Photo" class="photo-img" onclick="viewPhoto(this.src)">
                                    <div class="photo-info">
                                        <strong>Lati:</strong> <?php echo $latitude; ?><br>
                                        <strong>Longi:</strong> <?php echo $longitude; ?><br>
                                        <strong>Date:</strong> <?php echo date('d-M-Y', strtotime($photo['created_at'])); ?><br>
                                        <strong>Time:</strong> <?php echo date('H:i:s', strtotime($photo['created_at'])); ?>
                                    </div>
                                </div>
                                <?php
                                    endwhile;
                                    $stmt1->close();
                                else:
                                ?>
                                <div class="no-photos">
                                    <i class="fas fa-image text-4xl text-slate-300 mb-2"></i>
                                    <p>No photos available</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 12:00 PM - 14:00 PM Section -->
                        <div class="time-section">
                            <div class="time-header">12:00:00 - 14:00:00</div>
                            <div class="photo-grid">
                                <?php
                                $time2_query = "SELECT * FROM base_photo_report
                                               WHERE grade = ?
                                               AND coach_no = ?
                                               AND train_no = ?
                                               AND station_id = ?
                                               AND DATE(created_at) = ?
                                               AND TIME(created_at) BETWEEN '12:00:00' AND '14:00:00'
                                               LIMIT 6";
                                $stmt2 = $mysqli->prepare($time2_query);
                                $stmt2->bind_param("sssss", $selected_grade, $coach_no, $selected_train, $station_id, $date_only);
                                $stmt2->execute();
                                $time2_result = $stmt2->get_result();
                                
                                if ($time2_result->num_rows > 0):
                                    while ($photo = $time2_result->fetch_assoc()):
                                        $location_str = $photo['location'];
                                        preg_match('/lati:\s*([-\d\.]+)\s*longi:\s*([-\d\.]+)/', $location_str, $matches);
                                        $latitude = isset($matches[1]) ? $matches[1] : 'N/A';
                                        $longitude = isset($matches[2]) ? $matches[2] : 'N/A';
                                        $photo_path = 'uploads/photos/' . $photo['photo'];
                                        if (!file_exists($photo_path)) {
                                            $photo_path = 'https://via.placeholder.com/150x120/94a3b8/ffffff?text=No+Image';
                                        }
                                ?>
                                <div class="photo-item">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Photo" class="photo-img" onclick="viewPhoto(this.src)">
                                    <div class="photo-info">
                                        <strong>Lati:</strong> <?php echo $latitude; ?><br>
                                        <strong>Longi:</strong> <?php echo $longitude; ?><br>
                                        <strong>Date:</strong> <?php echo date('d-M-Y', strtotime($photo['created_at'])); ?><br>
                                        <strong>Time:</strong> <?php echo date('H:i:s', strtotime($photo['created_at'])); ?>
                                    </div>
                                </div>
                                <?php
                                    endwhile;
                                    $stmt2->close();
                                else:
                                ?>
                                <div class="no-photos">
                                    <i class="fas fa-image text-4xl text-slate-300 mb-2"></i>
                                    <p>No photos available</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 16:00 PM - 21:00 PM Section -->
                        <div class="time-section">
                            <div class="time-header">16:00:00 - 21:00:00</div>
                            <div class="photo-grid">
                                <?php
                                $time3_query = "SELECT * FROM base_photo_report
                                               WHERE grade = ?
                                               AND coach_no = ?
                                               AND train_no = ?
                                               AND station_id = ?
                                               AND DATE(created_at) = ?
                                               AND TIME(created_at) BETWEEN '16:00:00' AND '21:00:00'
                                               LIMIT 6";
                                $stmt3 = $mysqli->prepare($time3_query);
                                $stmt3->bind_param("sssss", $selected_grade, $coach_no, $selected_train, $station_id, $date_only);
                                $stmt3->execute();
                                $time3_result = $stmt3->get_result();
                                
                                if ($time3_result->num_rows > 0):
                                    while ($photo = $time3_result->fetch_assoc()):
                                        $location_str = $photo['location'];
                                        preg_match('/lati:\s*([-\d\.]+)\s*longi:\s*([-\d\.]+)/', $location_str, $matches);
                                        $latitude = isset($matches[1]) ? $matches[1] : 'N/A';
                                        $longitude = isset($matches[2]) ? $matches[2] : 'N/A';
                                        $photo_path = 'uploads/photos/' . $photo['photo'];
                                        if (!file_exists($photo_path)) {
                                            $photo_path = 'https://via.placeholder.com/150x120/94a3b8/ffffff?text=No+Image';
                                        }
                                ?>
                                <div class="photo-item">
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Photo" class="photo-img" onclick="viewPhoto(this.src)">
                                    <div class="photo-info">
                                        <strong>Lati:</strong> <?php echo $latitude; ?><br>
                                        <strong>Longi:</strong> <?php echo $longitude; ?><br>
                                        <strong>Date:</strong> <?php echo date('d-M-Y', strtotime($photo['created_at'])); ?><br>
                                        <strong>Time:</strong> <?php echo date('H:i:s', strtotime($photo['created_at'])); ?>
                                    </div>
                                </div>
                                <?php
                                    endwhile;
                                    $stmt3->close();
                                else:
                                ?>
                                <div class="no-photos">
                                    <i class="fas fa-image text-4xl text-slate-300 mb-2"></i>
                                    <p>No photos available</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php
                        endwhile;
                        $stmt->close();
                    else:
                ?>
                <div class="no-photos" style="padding: 60px; text-align: center; background: white; border-radius: 8px;">
                    <i class="fas fa-images text-6xl text-slate-300 mb-4"></i>
                    <p style="font-size: 18px; color: #64748b;">No photo reports found for the selected filters.</p>
                </div>
                <?php
                    endif;
                else:
                ?>
                <div class="no-photos" style="padding: 60px; text-align: center; background: white; border-radius: 8px;">
                    <i class="fas fa-filter text-6xl text-slate-300 mb-4"></i>
                    <p style="font-size: 18px; color: #64748b;">Please select grade and train number to view photo reports.</p>
                </div>
                <?php endif; ?>


            </div>

            <!-- Footer -->
            <?php
            require_once 'includes/footer.php'
            ?>

        </main>

    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeModal()">
        <div class="max-w-4xl w-full">
            <img id="modalImage" src="" alt="Full Photo" class="w-full h-auto rounded-lg">
        </div>
    </div>

    <script>
        // Export report
        function exportReport() {
            window.print();
        }

        // View photo in modal
        function viewPhoto(imageSrc) {
            const modal = document.getElementById('photoModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.remove('hidden');
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('photoModal');
            modal.classList.add('hidden');
        }

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

        // Close modal on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

</body>

</html>
