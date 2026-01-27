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
    <title>Feedback Details - <?php echo htmlspecialchars($station_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
      <style>
        @media print {
            @page {
                size: A4 portrait!important;
                margin: 0.3cm;
            }
            body {
                background: #fff !important;
                color: #000 !important;
                font-weight: bold !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 14px !important;
            }
            main, .max-w-full {
                margin: 0 !important;
                padding: 0 !important;
            }
            /* Hide navigation and buttons */
            .badge, button, .btn-export, .flex.justify-end, #sidebar, #sidebarOverlay, nav, .fa-print, .fa-file-excel, header, .header, footer, .footer, script {
                display: none !important;
            }
            /* Reset main content positioning */
            .lg\:ml-64 {
                margin-left: 0 !important;
            }
            /* Table styling for print */
            .feedback-table {
                width: 100% !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
                font-size: 13px !important;
            }
            .feedback-table thead {
                display: table-header-group !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            .feedback-table thead th {
                padding: 6px 4px !important;
                font-size: 13px !important;
                border: 1.5px solid #000000 !important;
                font-weight: bold !important;
                background: #ffffff !important;
                color: #000000 !important;
                text-align: center !important;
                width: auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .feedback-table thead th[colspan] {
                padding: 4px 3px !important;
                font-size: 12px !important;
                width: 1% !important;
                white-space: nowrap !important;
                font-weight: bold !important;
            }
            .feedback-table tbody tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }
            .feedback-table tbody td {
                padding: 4px 5px !important;
                font-size: 13px !important;
                border: 1px solid #000 !important;
                font-weight: bold !important;
                text-align: center !important;
            }
            .status-circle {
                width: auto !important;
                height: auto !important;
                display: inline !important;
                padding: 2px 4px !important;
                font-size: 13px !important;
                font-weight: bold !important;
                background: transparent !important;
                color: #000 !important;
            }
            .table-wrapper {
                overflow: visible !important;
            }
            a {
                color: #000 !important;
                text-decoration: none !important;
                font-weight: bold !important;
            }
        }
        </style>
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
            width: 34px;
            height: 24px;
            border-radius: 20%;
            display: inline-block;
            text-align: center;
            line-height: 22px;
            font-size: 12px;
            /* font-weight: 700; */
            color: black;
        }
        .badge-satisfactory {
            background-color: #fbbf24;
        }
        .badge-notattended {
            background-color: #eb8022ff;
        }
        .badge-unsatisfactory
        {
            background-color: #ef4444;
        }
             .badge-notsatisfied {
            background-color: #440909;
        }
        /* .status-excellent {
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
        } */

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

        @media print {

            /* A4 Landscape with no margin */
            @page {
                size: A4 landscape;
                margin: 0;
            }

            /* Hide everything */
            body * {
                visibility: hidden;
            }

            /* Only print this DIV */
            .table-wrapper,
            .table-wrapper * {
                visibility: visible;
            }

            /* Position cleanly for print */
            .table-wrapper {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 10px;
                font-size: 11px;
                line-height: 1.2;
            }

            /* Hide print button */
            button {
                display: none !important;
            }
        }
    </style>

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
                <button type="button" class="badge badge-excellent" style="background: #22c55e;" onclick="exportExcel()">
                    <i class="fas fa-file-excel mr-1"></i>Export to Excel
                </button>
                <button type="button" class="badge" style="background: #0ea5e9;" onclick="window.print()"
                    aria-label="Print">Print</button>
                <br>
                <br>
<script>
    function exportExcel() {
        // Build query string from current filters
        const params = new URLSearchParams({
            train: '<?php echo $train_no; ?>',
            coach: '<?php echo $coach; ?>',
            grade: '<?php echo $grade; ?>',
            from_date: '<?php echo $from_date; ?>',
            to_date: '<?php echo $to_date; ?>',
            coach_type: '<?php echo $coach_type; ?>'
        });
        window.location.href = 'all-feedback-detail-report-excel.php?' + params.toString();
    }
</script>



                <?php $highest_marking = check_highest_marking($_SESSION['station_id']); ?>
                <!-- Table -->
                <div class="table-wrapper">
                    <table class="feedback-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">SR.</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Date</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Seat<br>No</th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Coach<br>No</th>
                               
                                 <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Customer<br>Name</th>
                                 <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">PNR No</th>
                                
                             <?php if($_SESSION['station_id'] != 16): ?>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                                    Customer<br>Phone</th>
                                    <?php endif; ?>
                                

                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Train<br>No.
                                </th>
                                <th rowspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Grade</th>
                                <th colspan="2" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Passenger
                                    Details</th>
                                <th colspan="3" style="border-bottom: 1px solid rgba(255,255,255,0.2);">Feedback
                                    Parameters</th>
                                <th rowspan="2"
                                    style="border-bottom: 1px solid rgba(255,255,255,0.2); border-right: none;">
                                    Overall<br>Score</th>
                            </tr>
                            <tr>
                                <?php

                                $OBHS_question = get_questions_data($_SESSION['station_id'], $coach_type);

                                if (!empty($OBHS_question)) {
                                    $totalQuestions = count($OBHS_question);
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
                            $passenger_details_coach_type = get_passenger_details_data_coach_type_wise(
                                $train_no,
                                $coach_type,
                                $grade,
                                $from_date,
                                $to_date
                            );




                            // print_r($passenger_details_coach_type);
                            
                            if (!empty($passenger_details_coach_type)) {
                                $sr = 1;
                                $feedback_totals = [];      // To store total per feedback column
                                $grand_total_sum = 0;       // Total of total_feedback_sum
                            
                                foreach ($passenger_details_coach_type as $pd) {

                                    $feedback_array = explode(", ", $pd['feedback_values']);

                                    // Accumulate totals
                                    foreach ($feedback_array as $index => $value) {
                                        if (!isset($feedback_totals[$index])) {
                                            $feedback_totals[$index] = 0;
                                        }
                                        $feedback_totals[$index] += intval($value);
                                    }

                                    $grand_total_sum += intval($pd['total_feedback_sum']);

                                    echo "<tr>";
                                    echo "<td>{$sr}</td>";
                                    echo "<td>" . ($_SESSION['station_id'] == 16 || $_SESSION['station_id'] == 23 ? date('d/m/Y', strtotime($pd['feedback_date'])) : date('d/m/Y H:i:s', strtotime($pd['feedback_date']))) . "</td>";
                                    echo "<td>{$pd['seat_no']}</td>";
                                    echo "<td>{$pd['coach_no']}</td>";
                                    echo "<td><a href='employee-card.php?passenger_id={$pd['passenger_id']}"
                                        . "&station_id={$_SESSION['station_id']}"
                                        . "&train_no={$pd['train_no']}"
                                        . "&coach_no={$pd['coach_no']}"
                                        . "&phone={$pd['ph_number']}"
                                        . "&pnr_number={$pd['pnr_number']}"
                                        . "&name={$pd['name']}"
                                        . "&grade={$pd['grade']}"
                                        . "&seat_no={$pd['seat_no']}"
                                        . "&date_from={$from_date}"
                                        . "&date_to={$to_date}' class='customer-link' target='_blank'>"
                                        . "{$pd['name']}</a></td>";
                                       
                                    echo "<td>{$pd['pnr_number']}</td>";
                                     if ($_SESSION['station_id'] != 16) {
                                            echo "  <td>{$pd['ph_number']}</td>";
                                        }
                                    echo "<td>{$pd['train_no']}</td>";
                                    echo "<td>{$pd['grade']}</td>";

                                    foreach ($feedback_array as $fv) {
                                        echo "<td><span class='status-circle status-excellent'>{$fv}</span></td>";
                                    }


                                    $num_questions = count($feedback_array);
                                    $max_score = $highest_marking;
                                    $max_total = $totalQuestions * $max_score;
                                    $psi = 0;
                                    if ($max_total > 0) {
                                        $psi = (($pd['total_feedback_sum']) / $max_total) * 100;
                                    }
                                    $psi_display = number_format($psi);

                                    // Map PSI to status classes
                                    if ($psi >= 90) {
                                        $status_class = 'status-excellent';
                                    } elseif ($psi >= 75) {
                                        $status_class = 'status-verygood';
                                    } elseif ($psi >= 60) {
                                        $status_class = 'status-good';
                                    } elseif ($psi >= 40) {
                                        $status_class = 'status-average';
                                    } else {
                                        $status_class = 'status-poor';
                                    }

                                    echo "<td><span class='status-circle {$status_class}'>{$psi_display}</span></td>";
                                    echo "</tr>";

                                    $sr++;
                                }

                                // GRAND TOTAL ROW with same UI
                                echo "<tr>";

                                //exta code
                                // echo "<td colspan='9' style='text-align:center;font-weight:600;'>TOTAL</td>";
                            
                                // Calculate PSI per question and overall PSI for the total row
                                // Use the coach-type passenger list (`$passenger_details_coach_type`) that we iterated above
                                $responses = is_array($passenger_details_coach_type) ? count($passenger_details_coach_type) : 0;
                                $responses = max(0, intval($responses));
                                $max_score = intval($highest_marking);
                                $total_questions = isset($totalQuestions) ? intval($totalQuestions) : count($feedback_totals);

                                if ($responses > 0 && $max_score > 0) {
                                    foreach ($feedback_totals as $t) {
                                        $t = intval($t);
                                        $max_for_col = $responses * $max_score;
                                        $psi_col = $max_for_col > 0 ? ($t / $max_for_col) * 100 : 0;
                                        $psi_col_display = number_format($psi_col, 2) . '%';

                                        if ($psi_col >= 90) {
                                            $status_class = 'status-excellent';
                                        } elseif ($psi_col >= 75) {
                                            $status_class = 'status-verygood';
                                        } elseif ($psi_col >= 60) {
                                            $status_class = 'status-good';
                                        } elseif ($psi_col >= 40) {
                                            $status_class = 'status-average';
                                        } else {
                                            $status_class = 'status-poor';
                                        }

                                        // echo "<td><span class='status-circle {$status_class}'>{$psi_col_display}</span></td>";
                                    }

                                    // Overall PSI (use the accumulated grand total `$grand_total_sum`)
                                    $max_total_overall = $responses * $total_questions * $max_score;
                                    $overall_psi = $max_total_overall > 0 ? ($grand_total_sum / $max_total_overall) * 100 : 0;
                                    $overall_display = number_format($overall_psi, 2) . '%';

                                    if ($overall_psi >= 90) {
                                        $overall_class = 'status-excellent';
                                    } elseif ($overall_psi >= 75) {
                                        $overall_class = 'status-verygood';
                                    } elseif ($overall_psi >= 60) {
                                        $overall_class = 'status-good';
                                    } elseif ($overall_psi >= 40) {
                                        $overall_class = 'status-average';
                                    } else {
                                        $overall_class = 'status-poor';
                                    }

                                    // replace the raw total cell with overall PSI percentage
                                    // echo "<td><span class='status-circle {$overall_class}'>{$overall_display}</span></td>";
                                } else {
                                    // Fallback: no responses or invalid max score — show zeros
                                    foreach ($feedback_totals as $t) {
                                        // echo "<td><span class='status-circle status-poor'>0.00</span></td>";
                                    }
                                    // echo "<td><span class='status-circle status-poor'>0.00</span></td>";
                                }

                                // echo "<td><span class='status-circle status-excellent'>{$total_feedback_sum_all}</span></td>";
                                // echo "</tr>";
                            
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