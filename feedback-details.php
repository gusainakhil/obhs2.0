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
    <title>Feedback Details - Jodhpur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .filter-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .badge {
            padding: 6px 16px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-excellent {
            background-color: #10b981;
        }

        .badge-verygood {
            background-color: #3b82f6;
        }

        .badge-good {
            background-color: #22c55e;
        }

        .badge-average {
            background-color: #f59e0b;
        }

        .badge-poor {
            background-color: #ef4444;
        }

        .badge-percent {
            background-color: #06b6d4;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .export-btn-group {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .header-info {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 12px 16px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 14px;
        }

        .feedback-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            table-layout: auto;
        }

        .feedback-table thead {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }

        .feedback-table thead th {
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            white-space: normal;
            line-height: 1.4;
            vertical-align: middle;
        }

        .feedback-table thead th:last-child {
            border-right: none;
        }

        .feedback-table thead tr:first-child th {
            padding: 10px 8px;
        }

        .feedback-table thead tr:last-child th {
            padding: 10px 8px;
            font-size: 10px;
            line-height: 1.3;
        }

        .feedback-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .feedback-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .feedback-table tbody td {
            padding: 12px 8px;
            text-align: center;
            color: #334155;
            font-size: 12px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .feedback-table tbody td:first-child {
            font-weight: 600;
        }

        .feedback-table tbody td:last-child {
            border-right: none;
            font-weight: 700;
        }

        .status-circle {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 18px;
            font-size: 10px;
            font-weight: 700;
            color: white;
        }

        .status-excellent {
            background-color: #10b981;
        }

        .status-verygood {
            background-color: #3b82f6;
        }

        .status-good {
            background-color: #22c55e;
        }

        .status-average {
            background-color: #f59e0b;
        }

        .status-poor {
            background-color: #ef4444;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
        }

        .customer-link {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
        }

        .customer-link:hover {
            text-decoration: underline;
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

            <div class="max-w-full mx-auto">

                <!-- Filter Badges -->
                <div class="filter-badges">
                    <div class="badge badge-excellent">Excellent = 5</div>
                    <div class="badge badge-verygood">Very Good = 4</div>
                    <div class="badge badge-good">Good = 3</div>
                    <div class="badge badge-average">Average = 2</div>
                    <div class="badge badge-poor">Poor = 1</div>
                    <div class="badge-percent"><i class="fas fa-percentage"></i></div>
                    <button class="badge badge-excellent" onclick="exportExcel()">Excel</button>
                </div>

                <!-- PDF Button -->
                <div class="export-btn-group">
                    <button class="badge" style="background: #0ea5e9;" onclick="exportPDF()">PDF</button>
                </div>

                <!-- Header Info -->
                <div class="header-info">
                    Station: Dadn
                </div>

                <!-- Table -->
                <div class="table-wrapper">
                    <table class="feedback-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">SR.</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Date</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Seat<br>No</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Coach<br>No</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Customer<br>Name
                                </th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">PNR No</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                                    Customer<br>Phone</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Train<br>No.
                                </th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Grade</th>
                                <th colspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Passenger
                                    Details</th>
                                <th colspan="5" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Feedback
                                    Parameters</th>
                                <th rowspan="2"
                                    style="border-bottom: 1px solid rgba(255,255,255,0.2); border-right: none;">
                                    Overall<br>Score</th>
                            </tr>
                            <tr>
                                <th>Cleaning of toilets (including toilet floor, commode pan wall panels, shelf, mirror,
                                    wash
                                    basin, disinfection and provision of deodorant etc.)/शौचालयों की सफाई ( शामिल है,
                                    शौचालय का
                                    फर्श, कमोड पैन, वाल पैनल, शेल्फ, आइना, वाशबेसिन, दीशांफेक्शेन और दुर्गंध नाशक का
                                    प्रयोग करना
                                    उपलब्ध कराना)</th>
                                <th>Cleaning of passenger compartment (including cleaning of passenger aisle, vestibule
                                    area,
                                    Doorway area and doorway wash basin, spraying of air freshener and cleaning of dust
                                    bin) /
                                    यैसेंजर कम्पार्टमेंट की सफाई ( शामिल है, यात्री गलियारे, वेस्टीब्युलक्षेत्र,
                                    द्वारक्षेत्र,
                                    और द्वारक्षे के वाश बेसिन की सफाई, एयर रेफ़ेरेशर का छिड़काव करना और डस्टबिन की सफाई
                                    कराना)
                                </th>
                                <th>Collection of garbage from the coach compartments and clearance of dustbins /
                                    डिब्बों के
                                    कम्रों का कचरा और डस्ट के डिब्बों की सफाई कराना</th>
                                <th>Spraying of Mosquito/Cockroach/ Fly repellent and Hanging Glue Board whenever
                                    required or on
                                    demand of passengers / जरूरत पड़ने पर मस्तर / मकडी द्वार डगा का छिड़काव / विलचुवे कर
                                    लू
                                    बोर्ड को लटकाना</th>
                                <th>Behavior / Response of janitors/supervisor (including hygiene & cleanliness of
                                    janitor/Supervision) / सफाई कर्मचारी का व्यवहार ( शामिल है, स्वच्छता और साफ सफाई
                                    इस्वार्ड )
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>11/02/2025<br>11:48:45</td>
                                <td>55</td>
                                <td>B1</td>
                                <td><a href="#" class="customer-link">Nilamkumar</a></td>
                                <td>6559946940</td>
                                <td>919871477796</td>
                                <td>18237</td>
                                <td>F</td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">25</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>11/02/2025<br>18:24:10</td>
                                <td>33</td>
                                <td>A2</td>
                                <td><a href="#" class="customer-link">Lek chand</a></td>
                                <td>6760492725</td>
                                <td>919302537984</td>
                                <td>18237</td>
                                <td>F</td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">25</span></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>11/02/2025<br>11:33:46</td>
                                <td>17</td>
                                <td>B2</td>
                                <td><a href="#" class="customer-link">Naresh singh</a></td>
                                <td>6659839692</td>
                                <td>918178781662</td>
                                <td>18237</td>
                                <td>F</td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">25</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>11/02/2025<br>18:17:21</td>
                                <td>2</td>
                                <td>A</td>
                                <td><a href="#" class="customer-link">Parikshit Khanduri</a></td>
                                <td>6822794349</td>
                                <td>919971112610</td>
                                <td>18237</td>
                                <td>F</td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent"
                                        style="background-color:#10b981;">115</span>
                                </td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">25</span></td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>11/02/2025<br>11:48:17</td>
                                <td>55</td>
                                <td>B1</td>
                                <td><a href="#" class="customer-link">Nilamkumar</a></td>
                                <td>6559946940</td>
                                <td>919871477796</td>
                                <td>18237</td>
                                <td>F</td>
                                <td><span class="status-circle status-excellent">5+3</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">5</span></td>
                                <td><span class="status-circle status-excellent">25</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        
            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
           ?>

        </main>

    </div>

    <script>
        function exportPDF() {
            alert('PDF export is a placeholder in this demo.');
        }

        function exportExcel() {
            alert('Excel export is a placeholder in this demo.');
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