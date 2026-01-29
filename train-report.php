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
        <style>
        @media print {
            @page {
                /* size: landscape; */
                margin: 1cm;
            }
            body {
                background: #fff !important;
                color: #000 !important;
                font-weight: bold !important;
                margin-top: 0 !important;
                padding-top: 0 !important;
            }
            main, .max-w-full {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }
            /* Hide navigation and buttons */
            .btn-export, .flex.justify-end, #sidebar, #sidebarOverlay, nav, .fa-print, .fa-file-excel, header, .header, footer, .footer {
                display: none !important;
            }
            /* Reset main content positioning */
            .lg\:ml-64 {
                margin-left: 0 !important;
            }
            /* Table styling for print */
            .report-header, .report-grid, .table-report, .table-report th, .table-report td {
                color: #000 !important;
                background: #fff !important;
                box-shadow: none !important;
            }
            .table-report th, .table-report td {
                border: 1px solid #222 !important;
                font-size: 12px !important;
                padding: 6px 8px !important;
                font-weight: bold !important;
            }
            .report-cell {
                font-weight: bold !important;
                color: #000 !important;
            }
            .report-cell * {
                color: #000 !important;
            }
            .report-header {
                border: 2px solid #222 !important;
                margin-bottom: 10px !important;
                padding: 8px !important;
                border-radius: 0 !important;
                background: #fff !important;
            }
            .report-header * {
                color: #000 !important;
            }
            .report-grid {
                display: flex !important;
                flex-direction: row !important;
                gap: 15px !important;
                flex-wrap: nowrap !important;
            }
            .report-cell {
                padding: 5px !important;
                white-space: nowrap !important;
            }
            .table-report tfoot td {
                font-weight: bold !important;
                background: #f0f0f0 !important;
            }
            a {
                color: #000 !important;
                text-decoration: none !important;
            }
        }
        </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Train Report - <?php echo htmlspecialchars($station_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }
        td {
            white-space: nowrap;
            text-align: center;
        }
        th {
            white-space: nowrap;
            text-align: center;
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
                    <button type="button" class="btn-export" onclick="window.print()">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <!-- <button type="button" class="btn-export" onclick="printAllInOne()"> -->
                        <!-- <i class="fas fa-print mr-2"></i>Print All in One
                    </button> -->
                    <button class="btn-export"  onclick="exportExcel()">
                        <i class="fas fa-file-excel mr-2"></i>Export To Excel
                    </button>
                     <button class="btn-export"  onclick="exportAllInOne()">
                        <i class="fas fa-file-excel mr-2"></i>Export All in One Report
                    </button> 
                </div>

                <!-- Report Header -->
                <div class="report-header rounded-lg">
                    <div class="report-grid">
                        <div class="report-cell" >Station:</strong> <?php echo htmlspecialchars($station_name); ?></div>
                        <div class="report-cell" >Train No:</strong> <?php echo htmlspecialchars($train_no); ?></div>
                        <div class="report-cell" >From:</strong> <?php echo htmlspecialchars($from_date); ?></div>
                        <div class="report-cell" >To:</strong> <?php echo htmlspecialchars($to_date); ?></div>
                        <div class="report-cell" >Grade:</strong> <?php echo htmlspecialchars($grade); ?></div>
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
                        <?php
                        $acFeedbackData = feedback_calculation_coach_wise($train_no, $from_date, $to_date, 'AC', $grade);

                        // Ensure arrays exist
                        $coachList = $acFeedbackData['coach_wise'] ?? [];
                        $targets = $acFeedbackData['targets'] ?? [];
                        $ac_coach_target = $targets['ac_coach_target'] ?? 0;

                        $highest_marking = $acFeedbackData['highest_marking'] ?? 0;
                        $total_questions = $acFeedbackData['total_questions'] ?? 0;

                        $row_no = 1;

                        // Totals for footer
                        $total_passenger_sum = 0;
                        $total_percentage_sum = 0;
                        $total_coaches = count($coachList);
                        $query = '';

                        if (empty($coachList)) {
                            echo '<tr><td colspan="5">No data available</td></tr>';
                        } else {
                            foreach ($coachList as $coach_no => $data) {

                                $feedback_sum = $data['feedback_sum'] ?? 0;
                                $passenger_count = $data['total_passenger_count'] ?? 0;

                                // Add to totals for footer
                                $total_passenger_sum += $passenger_count;

                                // Percentage calculation
                                $percentage = 0.0;
                                if ($total_questions > 0 && $highest_marking > 0) {

                                    if ($passenger_count <= $ac_coach_target && $ac_coach_target > 0) {
                                        $denom = $total_questions * $highest_marking * $ac_coach_target;
                                        if ($denom > 0) {
                                            $percentage = ($feedback_sum / $denom) * 100;
                                        }
                                    } elseif ($passenger_count > $ac_coach_target) {
                                        $denom = $total_questions * $highest_marking * $passenger_count;
                                        if ($denom > 0) {
                                            $percentage = ($feedback_sum / $denom) * 100;
                                        }
                                    }
                                }

                                $total_percentage_sum += $percentage;

                                $percentage_display = number_format((float) $percentage, 2) . '%';

                                $coach_qs = urlencode($coach_no);
                                $train_qs = urlencode($train_no);

                                echo '<tr>';
                                echo '<td>' . $row_no . '</td>';
                                echo '<td>' . htmlspecialchars($coach_no) . '</td>';
                                echo '<td >' . htmlspecialchars($ac_coach_target) . '</td>';
                                echo '<td>';
                                $query = http_build_query([
                                    'train' => $train_no,
                                    'coach' => $coach_no,
                                    'grade' => $grade,
                                    'from_date' => $from_date,
                                    'to_date' => $to_date,
                                    'coach_type' => 'AC'
                                ]);
                                echo '<a href="feedback-details.php?' . $query . '"style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank"> ';
                                echo htmlspecialchars($passenger_count);
                                echo '</a>';
                                echo '</td>';
                                echo '<td>' . $percentage_display . '</td>';
                                echo '</tr>';

                                $row_no++;
                            }
                        }

                        // FINAL FOOTER VALUES
                        $total_ac_target = $ac_coach_target * $total_coaches;               // ex: 6
                        $final_total_passenger = $total_passenger_sum;       // total passenger
                        $final_total_percentage = number_format(
                            ($total_percentage_sum / max($total_coaches, 1)),
                            2
                        ) . '%';
                        ?>

                    <tfoot>
                        <tr>
                            <td colspan="2" style="font-weight:700;">Total</td>
                            <td><?php echo $total_ac_target ?></td>
                            <td><a href="all-feedback-detail-report.php?<?php echo $query; ?>" target="_blank" style="color:blue"><?php echo $final_total_passenger; ?></a>
                            </td>
                            <td><?php echo $final_total_percentage; ?></td>
                        </tr>
                    </tfoot>

                    </tbody>

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
                        <?php
                        // Fetch NON-AC feedback data
                        $nonAcFeedbackData = feedback_calculation_coach_wise(
                            $train_no,
                            $from_date,
                            $to_date,
                            'NON-AC',
                            $grade
                        );

                        // Extract data from function
                        $nonAc_coach_target = $nonAcFeedbackData['targets']['non_ac_coach_target'] ?? 0;
                        $total_questions = $nonAcFeedbackData['total_questions'] ?? 0;
                        $highest_marking = $nonAcFeedbackData['highest_marking'] ?? 0;
                        $coachList = $nonAcFeedbackData['coach_wise'] ?? [];

                        $row_no = 1;
                        $total_passenger_sum = 0; // We won't use it in footer
                        $total_percentage_sum = 0;
                        $total_target_sum = 0;
                        $total_coaches = count($coachList);
                        $query2 = '';

                        if (empty($coachList)) {
                            echo '<tr><td colspan="5">No data found</td></tr>';
                        } else {
                            foreach ($coachList as $coach_no => $data) {

                                $feedback_sum = $data['feedback_sum'] ?? 0;
                                $passenger_count = $data['total_passenger_count'] ?? 0;

                                // Add to footer totals
                                $total_target_sum += $nonAc_coach_target;

                                // Percentage calculation
                                $percentage = 0.0;
                                if ($total_questions > 0 && $highest_marking > 0) {

                                    if ($passenger_count <= $nonAc_coach_target && $nonAc_coach_target > 0) {
                                        $denom = $total_questions * $highest_marking * $nonAc_coach_target;
                                    } else {
                                        $denom = $total_questions * $highest_marking * $passenger_count;
                                    }

                                    if ($denom > 0) {
                                        $percentage = ($feedback_sum / $denom) * 100;
                                    }
                                }
                                $total_passenger_sum += $passenger_count;

                                $total_percentage_sum += $percentage;
                                $percentage_display = number_format($percentage, 2) . '%';

                                $coach_qs = urlencode($coach_no);
                                $train_qs = urlencode($train_no);

                                // Output table row
                                echo "<tr>";
                                echo "<td>{$row_no}</td>";
                                echo "<td>{$coach_no}</td>";
                                echo "<td>{$nonAc_coach_target}</td>";
                                echo "<td>";
                                $query2 = http_build_query([
                                    'train' => $train_no,
                                    'coach' => $coach_no,
                                    'grade' => $grade,
                                    'from_date' => $from_date,
                                    'to_date' => $to_date,
                                    'coach_type' => 'Non-AC'
                                ]);
                                echo '<a href="feedback-details.php?' . $query2 . '" style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank"> ';
                                echo $passenger_count;
                                echo "</a>";
                                echo "</td>";
                                echo "<td>{$percentage_display}</td>";
                                echo "</tr>";

                                $row_no++;
                            }
                        }

                        // Footer average percentage
                        $avg_percentage = number_format($total_percentage_sum / max($total_coaches, 1), 2) . '%';
                        ?>

                    <tfoot>
                        <tr>
                            <td colspan="2" style="font-weight:700;">Total</td>

                            <!-- 3rd column: total target sum -->
                            <td><?php echo $total_target_sum; ?></td>

                            <!-- 4th column: total target sum again -->
                            <td><a
                                    href="all-feedback-detail-report.php?<?php echo $query2; ?>"
                                    style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank"><?php echo $total_passenger_sum; ?></a>
                            </td>


                            <!-- 5th column: average percentage -->
                            <td><?php echo $avg_percentage; ?></td>
                        </tr>
                    </tfoot>


                </table>

            </div>

            <div class="mt-6 text-sm text-slate-700">TTe Feedback Report</div>
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
                    <?php
                    // Fetch TTE feedback data
                    $tteFeedbackData = feedback_calculation_coach_wise(
                        $train_no,
                        $from_date,
                        $to_date,
                        'TTE',
                        $grade
                    );

                    $tte_target = $tteFeedbackData['targets']['tte_target'] ?? 0;
                    $total_questions = $tteFeedbackData['total_questions'] ?? 0;
                    $highest_marking = $tteFeedbackData['highest_marking'] ?? 0;
                    $coachList = $tteFeedbackData['coach_wise'] ?? [];

                    $row_no = 1;
                    $total_passenger_sum = 0;
                    $total_percentage_sum = 0;
                    $total_target_sum = 0;
                    $total_coaches = count($coachList);
                    $query3 = '';

                    if (empty($coachList)) {
                        echo '<tr><td colspan="5">No data found</td></tr>';
                    } else {
                        foreach ($coachList as $coach_no => $data) {

                            $feedback_sum = $data['feedback_sum'] ?? 0;
                            $passenger_count = $data['total_passenger_count'] ?? 0;

                            // Add to footer totals
                            $total_target_sum += $tte_target;
                            $total_passenger_sum += $passenger_count;

                            // Percentage calculation
                            $percentage = 0.0;
                            if ($total_questions > 0 && $highest_marking > 0) {

                                if ($passenger_count <= $tte_target && $tte_target > 0) {
                                    $denom = $total_questions * $highest_marking * $tte_target;
                                } else {
                                    $denom = $total_questions * $highest_marking * $passenger_count;
                                }

                                if ($denom > 0) {
                                    $percentage = ($feedback_sum / $denom) * 100;
                                }
                            }

                            $total_percentage_sum += $percentage;
                            $percentage_display = number_format($percentage, 2) . '%';

                            $coach_qs = urlencode($coach_no);
                            $train_qs = urlencode($train_no);

                            // Output table row
                            echo "<tr>";
                            echo "<td>{$row_no}</td>";
                            echo "<td>{$coach_no}</td>";
                            echo "<td>{$tte_target}</td>";
                            echo "<td>";
                            $query3 = http_build_query([
                                'train' => $train_no,
                                'coach' => $coach_no,
                                'grade' => $grade,
                                'from_date' => $from_date,
                                'to_date' => $to_date,
                                'coach_type' => 'TTE'
                            ]);
                            echo '<a href="feedback-details.php?' . $query3 . '" style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank"> ';
                            echo $passenger_count;
                            echo "</a>";
                            echo "</td>";
                            echo "<td>{$percentage_display}</td>";
                            echo "</tr>";

                            $row_no++;
                        }
                    }

                    // Footer average percentage
                    $avg_percentage = number_format($total_percentage_sum / max($total_coaches, 1), 2) . '%';
                    ?>

                <tfoot>
                    <tr>
                        <td colspan="2" style="font-weight:700;">Total</td>

                        <!-- 3rd column: total TTE target sum -->
                        <td><?php echo $total_target_sum; ?></td>

                        <!-- 4th column: total passenger feedback count -->
                        <td><a href="all-feedback-detail-report.php? <?php echo $query3; ?>"
                                style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank"><?php echo $total_passenger_sum; ?></a>
                        </td>

                        <!-- 5th column: average percentage -->
                        <td><?php echo $avg_percentage; ?></td>
                    </tr>
                </tfoot>
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
        function exportPDF() {
            alert('PDF export is a placeholder in this demo.');
        }
        function exportExcel() {
            // Build query string from current filters
            const params = new URLSearchParams({
                grade: '<?php echo $grade; ?>',
                from_date: '<?php echo $from_date; ?>',
                to_date: '<?php echo $to_date; ?>',
                train_no: '<?php echo $train_no; ?>'
            });
            window.location.href = 'train-report-excel.php?' + params.toString();
        }

        function exportAllInOne() {
            // Build query string from current filters for all-in-one export
            const params = new URLSearchParams({
                grade: '<?php echo $grade; ?>',
                from_date: '<?php echo $from_date; ?>',
                to_date: '<?php echo $to_date; ?>',
                train_no: '<?php echo $train_no; ?>'
            });
            window.location.href = 'train-report-all-excel.php?' + params.toString();
        }

        function printAllInOne() {
            // Build query string from current filters for all-in-one print
            const params = new URLSearchParams({
                grade: '<?php echo $grade; ?>',
                from_date: '<?php echo $from_date; ?>',
                to_date: '<?php echo $to_date; ?>',
                train_no: '<?php echo $train_no; ?>'
            });
            window.open('train-report-all-detail-print.php?' + params.toString(), '_blank');
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