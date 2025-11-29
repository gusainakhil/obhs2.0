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
//?train=14807&coach=A1&grade=E&from_date=2025-11-18&to_date=2025-11-24&coach_type=AC
$station_name = getStationName($_SESSION['station_id']);
$train_no = isset($_GET['train']) ? $_GET['train'] : null;
$coach = isset($_GET['coach']) ? $_GET['coach'] : null;
$grade = isset($_GET['grade']) ? $_GET['grade'] : null;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;
$coach_type = isset($_GET['coach_type']) ? $_GET['coach_type'] : null;


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
                <?php $marking_data = get_marking_data($_SESSION['station_id']);
                if ($marking_data) {
                    //print _r($marking_data);
                    {
                        //print _r($marking_data);
                        foreach ($marking_data as $data) {
                            echo '<span class="badge badge-' . strtolower(preg_replace('/\s+/', '', trim($data['category']))) . ' mr-2">' . htmlspecialchars($data['category']) . ' = ' . htmlspecialchars($data['value']) . '</span>';
                        }
                    }
                }
                ?>
                <br>
                <br>

                <!-- PDF Button -->
                <div class="export-btn-group">
                    <div class="badge-percent"><i class="fas fa-percentage"></i></div>
                    <button class="badge badge-excellent" onclick="exportExcel()">Excel</button>
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
                                <?php

                                $OBHS_question = get_questions_data($_SESSION['station_id'], $coach_type);

                                if (!empty($OBHS_question)) {
                                    foreach ($OBHS_question as $q) {

                                        $eng = htmlspecialchars($q['eng_question']);
                                        $hin = htmlspecialchars($q['hin_question']);

                                        echo "<th>{$eng}<br><small>{$hin}</small></th>";
                                    }
                                } else {
                                    echo "<th>No Questions Found</th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $passenger_details = get_passenger_details_data_coach_wise(
                                $coach,
                                $train_no,
                                $from_date,
                                $to_date,
                                $coach_type,
                                $grade
                            );

                            $total_feedback_sum_all = 0;   // grand total
                            $feedback_totals = [];         // totals per feedback column
                            
                            if (!empty($passenger_details)) {
                                $sr = 1;

                                foreach ($passenger_details as $pd) {

                                    $feedback_array = explode(", ", $pd['feedback_values']);

                                    // Collect totals for last row
                                    foreach ($feedback_array as $index => $value) {
                                        if (!isset($feedback_totals[$index])) {
                                            $feedback_totals[$index] = 0;
                                        }
                                        $feedback_totals[$index] += intval($value);
                                    }

                                    $total_feedback_sum_all += intval($pd['total_feedback_sum']);

                                    echo "<tr>";
                                    echo "<td>{$sr}</td>";
                                    echo "<td>" . date('d/m/Y H:i:s', strtotime($pd['feedback_date'])) . "</td>";
                                    echo "<td>{$pd['seat_no']}</td>";
                                    echo "<td>{$pd['coach_no']}</td>";
                                    echo "<td><a href='employee-card.php?passenger_id={$pd['passenger_id']}' class='customer-link' target='_blank'> {$pd['name']}</a></td>";
                                    echo "<td>{$pd['ph_number']}</td>";
                                    echo "<td>{$pd['pnr_number']}</td>";
                                    echo "<td>{$pd['train_no']}</td>";
                                    echo "<td>{$pd['grade']}</td>";

                                    foreach ($feedback_array as $fv) {
                                        echo "<td><span class='status-circle status-excellent'>{$fv}</span></td>";
                                    }

                                    echo "<td><span class='status-circle status-excellent'>{$pd['total_feedback_sum']}</span></td>";
                                    echo "</tr>";

                                    $sr++;
                                }

                                // GRAND TOTAL ROW with same UI
                                echo "<tr>";
                                echo "<td colspan='9' style='text-align:center;font-weight:600;'>TOTAL</td>";

                                foreach ($feedback_totals as $t) {
                                    echo "<td><span class='status-circle status-excellent'>{$t}</span></td>";
                                }

                                echo "<td><span class='status-circle status-excellent'>{$total_feedback_sum_all}</span></td>";
                                echo "</tr>";

                            } else {
                                echo "<tr><td colspan='15' style='text-align:center;color:red;'>No Data Found</td></tr>";
                            }
                            ?>



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