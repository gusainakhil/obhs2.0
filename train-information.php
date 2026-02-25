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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Train Information - Jodhpur</title>
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

        .btn-reset {
            background-color: #64748b;
            color: white;
            padding: 10px 32px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-reset:hover {
            background-color: #475569;
        }

        .info-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-item {
            padding: 12px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 3px solid #10b981;
        }

        .info-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #1e293b;
            font-weight: 600;
            margin-top: 4px;
        }

        .train-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .train-table thead {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        }

        .train-table thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .train-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .train-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .train-table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: #334155;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr !important;
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
                <h2 class="text-2xl font-bold text-slate-800">Train Information</h2>
                <p class="text-sm text-slate-600 mt-1">Search and filter train details</p>
            </div>

            <!-- Filter Form -->
            <div class="filter-card">
                <form id="trainFilterForm" onsubmit="filterTrains(event)">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 filter-grid">
                        
                        <!-- Grade Selection -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-tag mr-1"></i>Select Grade
                            </label>
                            <select name="grade" id="grade" class="filter-select">
                                <option value="">All Grades</option>
                                <option value="A">A - Monday</option>
                                <option value="B">B - Tuesday</option>
                                <option value="C">C - Wednesday</option>
                                <option value="D">D - Thursday</option>
                                <option value="E">E - Friday</option>
                                <option value="F">F - Saturday</option>
                                <option value="G">G - Sunday</option>
                            </select>
                        </div>

                        <!-- Train Selection -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-train mr-1"></i>Select Train
                            </label>
                            <select name="train" id="train" class="filter-select">
                                <option value="">All Trains</option>
                                <option value="18236">18236 - BSP BPL Express</option>
                                <option value="18237">18237 - Chattisgarh Express</option>
                                <option value="12465">12465 - Ranthambore Express</option>
                                <option value="12466">12466 - Intercity Express</option>
                                <option value="14802">14802 - Jodhpur Express</option>
                                <option value="12479">12479 - Suryanagari Express</option>
                                <option value="12480">12480 - Ajmer Express</option>
                                <option value="14801">14801 - Mandore Express</option>
                            </select>
                        </div>

                        <!-- Date Filter -->
                        <div>
                            <label class="filter-label">
                                <i class="fas fa-calendar mr-1"></i>Date
                            </label>
                            <input type="date" name="date" id="date" class="filter-input" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Search Button -->
                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn-submit flex-1">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                            <button type="button" class="btn-reset" onclick="resetFilters()">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Train Information Display -->
            <div id="trainInfoSection" style="display: none;">
                
                <!-- Summary Info Cards -->
                <div class="info-card">
                    <h3 class="text-lg font-bold text-slate-800 mb-2">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>Train Details
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Train Number</div>
                            <div class="info-value" id="displayTrainNumber">18237</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Train Name</div>
                            <div class="info-value" id="displayTrainName">Chattisgarh Express</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Grade</div>
                            <div class="info-value" id="displayGrade">F - Saturday</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date</div>
                            <div class="info-value" id="displayDate"><?php echo date('d-m-Y'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Coaches</div>
                            <div class="info-value" id="displayCoaches">16</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge status-active">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coach Details Table -->
                <div class="info-card">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">
                        <i class="fas fa-list text-emerald-500 mr-2"></i>Coach Details
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="train-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Coach Number</th>
                                    <th>Coach Type</th>
                                    <th>Capacity</th>
                                    <th>Cleaning Target</th>
                                    <th>Feedback Target</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="coachTableBody">
                                <tr>
                                    <td>1</td>
                                    <td><strong>A1</strong></td>
                                    <td>AC 1st Class</td>
                                    <td>24</td>
                                    <td>2 times/day</td>
                                    <td>12 feedbacks</td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><strong>B1</strong></td>
                                    <td>AC 2-Tier</td>
                                    <td>48</td>
                                    <td>2 times/day</td>
                                    <td>24 feedbacks</td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><strong>B2</strong></td>
                                    <td>AC 2-Tier</td>
                                    <td>48</td>
                                    <td>2 times/day</td>
                                    <td>24 feedbacks</td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td><strong>S1</strong></td>
                                    <td>Sleeper</td>
                                    <td>72</td>
                                    <td>2 times/day</td>
                                    <td>36 feedbacks</td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td><strong>S2</strong></td>
                                    <td>Sleeper</td>
                                    <td>72</td>
                                    <td>2 times/day</td>
                                    <td>36 feedbacks</td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- No Data Message (Initially shown) -->
            <div id="noDataMessage" class="info-card">
                <div class="no-data">
                    <i class="fas fa-search text-5xl text-slate-300 mb-4"></i>
                    <p class="text-lg font-semibold text-slate-600 mb-2">No Train Selected</p>
                    <p>Please select filters above and click Search to view train information</p>
                </div>
            </div>

            <!-- Footer -->
            <?php
            require_once 'includes/footer.php'
            ?>

        </main>

    </div>

    <script>
        // Filter trains based on form submission
        function filterTrains(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const filters = {
                grade: formData.get('grade'),
                train: formData.get('train'),
                date: formData.get('date')
            };

            console.log('Filter Data:', filters);

            // Show train info section and hide no data message
            document.getElementById('trainInfoSection').style.display = 'block';
            document.getElementById('noDataMessage').style.display = 'none';

            // Update display values
            if (filters.train) {
                const trainSelect = document.getElementById('train');
                const selectedOption = trainSelect.options[trainSelect.selectedIndex];
                document.getElementById('displayTrainNumber').textContent = filters.train;
                document.getElementById('displayTrainName').textContent = selectedOption.text.split(' - ')[1] || 'N/A';
            }

            if (filters.grade) {
                const gradeSelect = document.getElementById('grade');
                const selectedGrade = gradeSelect.options[gradeSelect.selectedIndex];
                document.getElementById('displayGrade').textContent = selectedGrade.text;
            }

            if (filters.date) {
                const dateObj = new Date(filters.date);
                const formattedDate = dateObj.toLocaleDateString('en-GB').replace(/\//g, '-');
                document.getElementById('displayDate').textContent = formattedDate;
            }

            // In production, fetch data from API
            // fetch('api/train-info.php', {
            //     method: 'POST',
            //     body: JSON.stringify(filters)
            // }).then(response => response.json())
            //   .then(data => updateTrainInfo(data));
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('trainFilterForm').reset();
            document.getElementById('trainInfoSection').style.display = 'none';
            document.getElementById('noDataMessage').style.display = 'block';
            document.getElementById('date').value = '<?php echo date('Y-m-d'); ?>';
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
    </script>

</body>

</html>
