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

$grade = isset($_GET['grade']) ? $_GET['grade'] : null;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;
$train_no = isset($_GET['train_no']) ? $_GET['train_no'] : null;


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Train Report - Jodhpur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .report-header {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px;
            border-radius: 8px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            align-items: center;
        }

        .report-cell {
            padding: 10px;
            color: white;
            font-weight: 600;
        }

        .report-cell.right {
            text-align: right;
        }

        .table-report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            table-layout: auto;
        }

        .table-report th {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 16px;
            text-align: left;
            font-size: 14px;
            white-space: nowrap;
        }

        .table-report td {
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-cell {
                font-size: 12px;
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

            <div class="max-w-full mx-auto">

                <!-- Export Buttons -->
                <div class="flex justify-end gap-2 mb-4">
                    <button class="btn-export" onclick="exportPDF()">
                        <i class="fas fa-file-pdf mr-2"></i>PDF
                    </button>
                    <button class="btn-export" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                        onclick="exportExcel()">
                        <i class="fas fa-file-excel mr-2"></i>Excel
                    </button>
                </div>

                <!-- Report Header -->
                <div class="report-header rounded-lg">
                    <div class="report-grid">
                        <div class="report-cell">Station: Dadn</div>
                        <div class="report-cell">Train No: <?php echo htmlspecialchars($train_no); ?></div>
                        <div class="report-cell">From: <?php echo htmlspecialchars($from_date); ?></div>
                        <div class="report-cell">To: <?php echo htmlspecialchars($to_date); ?></div>
                        <div class="report-cell right">Grade: <?php echo htmlspecialchars($grade); ?></div>
                    </div>
                </div>

                <div class="mt-4 text-sm text-slate-700">AC Feedback Report</div>
                <table class="table-report">
                    <thead>
                        <tr>
                            <th>SR. No.</th>
                            <th>Coach No.</th>
                            <th>Target Per Coach</th>
                            <th>Achieved No. of Feedbacks</th>
                            <th>Avg P.S.I</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>A</td>
                            <td>1</td>
                            <td style="text-align:center;"><a href="feedback-details.php?train=18237&coach=A"
                                    style="color:#2563eb;font-weight:600;text-decoration:none;">1</a></td>
                            <td style="text-align:right">19.60%</td>
                        </tr>
                        
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="font-weight:700;">Total</td>
                            <td>6</td>
                            <td style="text-align:center;">8</td>
                            <td style="text-align:right;">19.91 %</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="mt-6 text-sm text-slate-700">NON AC Feedback Report</div>
                <table class="table-report">
                    <thead>
                        <tr>
                            <th>SR. No.</th>
                            <th>Coach No.</th>
                            <th>Feedback Target</th>
                            <th>Achieved No. of Feedbacks</th>
                            <th>Avg P.S.I</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>S4</td>
                            <td>1</td>
                            <td style="text-align:center;"><a href="feedback-details.php?train=18237&coach=S4"
                                    style="color:#2563eb;font-weight:600;text-decoration:none;">1</a></td>
                            <td style="text-align:right">26.67%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="font-weight:700">Total</td>
                            <td>1</td>
                            <td style="text-align:center;">1</td>
                            <td style="text-align:right;">26.67 %</td>
                        </tr>
                    </tfoot>
                </table>

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