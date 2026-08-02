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

function trainReportEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function trainReportJson($value)
{
    return json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}

function trainReportDefaultTargets()
{
    return [
        'ac_coach_target' => 0,
        'non_ac_coach_target' => 0,
        'tte_target' => 0,
    ];
}

function trainReportCalculatePercentage($feedback_sum, $passenger_count, $target_per_coach, $total_questions, $highest_marking)
{
    $passenger_count = (int) $passenger_count;
    $target_per_coach = (int) $target_per_coach;
    $total_questions = (int) $total_questions;
    $highest_marking = (float) $highest_marking;

    if ($total_questions <= 0 || $highest_marking <= 0) {
        return 0.0;
    }

    $effective_target = ($passenger_count <= $target_per_coach && $target_per_coach > 0)
        ? $target_per_coach
        : $passenger_count;
    $denom = $total_questions * $highest_marking * $effective_target;

    return $denom > 0 ? ((float) $feedback_sum / $denom) * 100 : 0.0;
}

function trainReportFetchFeedbackData($mysqli, $station_id, $train_no, $from_date, $to_date, $grade)
{
    $coach_data = [
        'AC' => [],
        'NON-AC' => [],
        'TTE' => [],
    ];
    $targets = trainReportDefaultTargets();
    $highest_marking = 0;
    $question_counts = [
        'AC' => 0,
        'NON-AC' => 0,
        'TTE' => 0,
    ];

    $date_from = $from_date . ' 00:00:00';
    $date_to = $to_date . ' 23:59:59';

    if ($train_no !== '' && $from_date !== '' && $to_date !== '' && $grade !== '') {
        $sql = "SELECT
                    p.coach_type,
                    p.coach_no,
                    SUM(f.value) AS feedback_sum,
                    COUNT(DISTINCT p.id) AS total_passenger_count
                FROM OBHS_feedback f
                JOIN OBHS_passenger p ON p.id = f.passenger_id
                WHERE p.train_no = ?
                  AND p.coach_type IN ('AC', 'NON-AC', 'TTE')
                  AND p.grade = ?
                  AND p.station_id = ?
                  AND p.created BETWEEN ? AND ?
                GROUP BY p.coach_type, p.coach_no
                ORDER BY p.coach_type, p.coach_no";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("isiss", $train_no, $grade, $station_id, $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $coach_type = $row['coach_type'];
                if (!isset($coach_data[$coach_type])) {
                    continue;
                }

                $coach_data[$coach_type][$row['coach_no']] = [
                    'coach_no' => $row['coach_no'],
                    'feedback_sum' => $row['feedback_sum'] ?? 0,
                    'total_passenger_count' => $row['total_passenger_count'] ?? 0,
                ];
            }

            $stmt->close();
        } else {
            error_log('Train report feedback query prepare failed: ' . $mysqli->error);
        }
    }

    $marking_sql = "SELECT MAX(value) AS highest_marking FROM OBHS_marking WHERE station_id = ?";
    $marking_stmt = $mysqli->prepare($marking_sql);
    if ($marking_stmt) {
        $marking_stmt->bind_param("i", $station_id);
        $marking_stmt->execute();
        $marking_row = $marking_stmt->get_result()->fetch_assoc();
        $highest_marking = $marking_row['highest_marking'] ?? 0;
        $marking_stmt->close();
    } else {
        error_log('Train report marking query prepare failed: ' . $mysqli->error);
    }

    if ($train_no !== '') {
        $target_sql = "SELECT
                    feed_per_ac_coach AS ac_coach_target,
                    feed_per_non_ac_coach AS non_ac_coach_target,
                    feedback_tte AS tte_target
                FROM base_fb_target
                WHERE station = ?
                  AND train_no = ?
                LIMIT 1";
        $target_stmt = $mysqli->prepare($target_sql);
        if ($target_stmt) {
            $target_stmt->bind_param("ii", $station_id, $train_no);
            $target_stmt->execute();
            $targets = array_merge($targets, $target_stmt->get_result()->fetch_assoc() ?? []);
            $target_stmt->close();
        } else {
            error_log('Train report target query prepare failed: ' . $mysqli->error);
        }
    }

    $question_sql = "SELECT type, COUNT(*) AS total_questions
        FROM OBHS_questions
        WHERE station_id = ?
          AND type IN ('AC', 'NON-AC')
        GROUP BY type";
    $question_stmt = $mysqli->prepare($question_sql);
    if ($question_stmt) {
        $question_stmt->bind_param("i", $station_id);
        $question_stmt->execute();
        $question_result = $question_stmt->get_result();
        while ($row = $question_result->fetch_assoc()) {
            $question_counts[$row['type']] = (int) $row['total_questions'];
        }
        $question_stmt->close();
    } else {
        error_log('Train report question query prepare failed: ' . $mysqli->error);
    }
    $question_counts['TTE'] = $question_counts['AC'];

    return [
        'AC' => [
            'coach_wise' => $coach_data['AC'],
            'highest_marking' => $highest_marking,
            'targets' => $targets,
            'total_questions' => $question_counts['AC'],
        ],
        'NON-AC' => [
            'coach_wise' => $coach_data['NON-AC'],
            'highest_marking' => $highest_marking,
            'targets' => $targets,
            'total_questions' => $question_counts['NON-AC'],
        ],
        'TTE' => [
            'coach_wise' => $coach_data['TTE'],
            'highest_marking' => $highest_marking,
            'targets' => $targets,
            'total_questions' => $question_counts['TTE'],
        ],
    ];
}

function trainReportBuildDetailQuery($train_no, $coach_no, $grade, $from_date, $to_date, $coach_type)
{
    return http_build_query([
        'train' => $train_no,
        'coach' => $coach_no,
        'grade' => $grade,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'coach_type' => $coach_type,
    ]);
}

function trainReportRenderFeedbackSection($title, $target_heading, array $feedback_data, $target_key, $detail_coach_type, $empty_text, $train_no, $from_date, $to_date, $grade, $footer_link_style, $title_margin_class = 'mt-6')
{
    $coach_list = $feedback_data['coach_wise'] ?? [];
    $targets = array_merge(trainReportDefaultTargets(), $feedback_data['targets'] ?? []);
    $target_per_coach = $targets[$target_key] ?? 0;
    $highest_marking = $feedback_data['highest_marking'] ?? 0;
    $total_questions = $feedback_data['total_questions'] ?? 0;
    $row_no = 1;
    $total_passenger_sum = 0;
    $total_percentage_sum = 0;
    $total_target_sum = 0;
    $total_coaches = count($coach_list);
    $footer_query = '';

    echo '<div class="' . trainReportEscape($title_margin_class) . ' text-sm text-slate-700">' . trainReportEscape($title) . '</div>';
    echo '<table class="table-report">';
    echo '<thead><tr>';
    echo '<th>SR. No.</th>';
    echo '<th>Coach No.</th>';
    echo '<th>' . trainReportEscape($target_heading) . '</th>';
    echo '<th>Achieved No. of Feedbacks</th>';
    echo '<th>Avg P.S.I</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    if (empty($coach_list)) {
        echo '<tr><td colspan="5">' . trainReportEscape($empty_text) . '</td></tr>';
    } else {
        foreach ($coach_list as $coach_no => $data) {
            $feedback_sum = $data['feedback_sum'] ?? 0;
            $passenger_count = (int) ($data['total_passenger_count'] ?? 0);
            $percentage = trainReportCalculatePercentage(
                $feedback_sum,
                $passenger_count,
                $target_per_coach,
                $total_questions,
                $highest_marking
            );
            $detail_query = trainReportBuildDetailQuery(
                $train_no,
                $coach_no,
                $grade,
                $from_date,
                $to_date,
                $detail_coach_type
            );

            $total_target_sum += (int) $target_per_coach;
            $total_passenger_sum += $passenger_count;
            $total_percentage_sum += $percentage;
            $footer_query = $detail_query;

            echo '<tr>';
            echo '<td>' . $row_no . '</td>';
            echo '<td>' . trainReportEscape($coach_no) . '</td>';
            echo '<td>' . trainReportEscape($target_per_coach) . '</td>';
            echo '<td><a href="feedback-details.php?' . trainReportEscape($detail_query) . '" style="color:#2563eb;font-weight:600;text-decoration:none;" target="_blank"> ';
            echo trainReportEscape($passenger_count);
            echo '</a></td>';
            echo '<td>' . number_format($percentage, 2) . '%</td>';
            echo '</tr>';

            $row_no++;
        }
    }

    $avg_percentage = number_format($total_percentage_sum / max($total_coaches, 1), 2) . '%';

    echo '</tbody>';
    echo '<tfoot><tr>';
    echo '<td colspan="2" style="font-weight:700;">Total</td>';
    echo '<td>' . trainReportEscape($total_target_sum) . '</td>';
    echo '<td><a href="all-feedback-detail-report.php?' . trainReportEscape($footer_query) . '" style="' . trainReportEscape($footer_link_style) . '" target="_blank">';
    echo trainReportEscape($total_passenger_sum);
    echo '</a></td>';
    echo '<td>' . trainReportEscape($avg_percentage) . '</td>';
    echo '</tr></tfoot>';
    echo '</table>';
}

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_name = getStationName($station_id);
$grade = (string) ($_GET['grade'] ?? '');
$from_date = (string) ($_GET['from_date'] ?? '');
$to_date = (string) ($_GET['to_date'] ?? '');
$train_no = (string) ($_GET['train_no'] ?? '');
$feedback_sections = trainReportFetchFeedbackData($mysqli, $station_id, $train_no, $from_date, $to_date, $grade);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}


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
    <title>Train Report - <?php echo trainReportEscape($station_name); ?></title>
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

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .btn-export {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
        }

        .btn-export:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.18);
            opacity: 0.95;
        }

        .btn-export:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.14);
        }

        .btn-print {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        }

        .btn-print-all {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .btn-excel {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }

        .btn-excel-all {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }

        @media (max-width: 768px) {
            .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-cell {
                font-size: 12px;
            }

            .action-buttons {
                justify-content: stretch;
            }

            .btn-export {
                width: 100%;
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
                <div class="action-buttons">
                    <button type="button" class="btn-export btn-print" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                  
                    <button class="btn-export btn-excel"  onclick="exportExcel()">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                    <button type="button" class="btn-export btn-print-all" onclick="printAllInOne()"> 
                        <i class="fas fa-print mr-1"></i>Print All in One
                    </button> 
                     <button class="btn-export btn-excel-all"  onclick="exportAllInOne()">
                        <i class="fas fa-file-excel mr-1"></i>Excel All in One 
                    </button> 
                </div>

                <!-- Report Header -->
                <div class="report-header rounded-lg">
                    <div class="report-grid">
                        <div class="report-cell" >Station:</strong> <?php echo trainReportEscape($station_name); ?></div>
                        <div class="report-cell" >Train No:</strong> <?php echo trainReportEscape($train_no); ?></div>
                        <div class="report-cell" >From:</strong> <?php echo trainReportEscape($from_date); ?></div>
                        <div class="report-cell" >To:</strong> <?php echo trainReportEscape($to_date); ?></div>
                        <div class="report-cell" >Grade:</strong> <?php echo trainReportEscape($grade); ?></div>
                    </div>
                </div>



                <?php
                trainReportRenderFeedbackSection(
                    'AC Feedback Report',
                    'Target Per Coach',
                    $feedback_sections['AC'],
                    'ac_coach_target',
                    'AC',
                    'No data available',
                    $train_no,
                    $from_date,
                    $to_date,
                    $grade,
                    'color:blue',
                    'mt-4'
                );

                trainReportRenderFeedbackSection(
                    'NON AC Feedback Report',
                    'Feedback Target',
                    $feedback_sections['NON-AC'],
                    'non_ac_coach_target',
                    'NON-AC',
                    'No data found',
                    $train_no,
                    $from_date,
                    $to_date,
                    $grade,
                    'color:#2563eb;font-weight:600;text-decoration:none;'
                );

                trainReportRenderFeedbackSection(
                    'TTe Feedback Report',
                    'Feedback Target',
                    $feedback_sections['TTE'],
                    'tte_target',
                    'TTE',
                    'No data found',
                    $train_no,
                    $from_date,
                    $to_date,
                    $grade,
                    'color:#2563eb;font-weight:600;text-decoration:none;'
                );
                ?>

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
                grade: <?php echo trainReportJson($grade); ?>,
                from_date: <?php echo trainReportJson($from_date); ?>,
                to_date: <?php echo trainReportJson($to_date); ?>,
                train_no: <?php echo trainReportJson($train_no); ?>
            });
            window.location.href = 'train-report-excel.php?' + params.toString();
        }

        function exportAllInOne() {
            // Build query string from current filters for all-in-one export
            const params = new URLSearchParams({
                grade: <?php echo trainReportJson($grade); ?>,
                from_date: <?php echo trainReportJson($from_date); ?>,
                to_date: <?php echo trainReportJson($to_date); ?>,
                train_no: <?php echo trainReportJson($train_no); ?>
            });
            window.location.href = 'train-report-all-excel.php?' + params.toString();
        }

        function printAllInOne() {
            // Build query string from current filters for all-in-one print
            const params = new URLSearchParams({
                grade: <?php echo trainReportJson($grade); ?>,
                from_date: <?php echo trainReportJson($from_date); ?>,
                to_date: <?php echo trainReportJson($to_date); ?>,
                train_no: <?php echo trainReportJson($train_no); ?>
            });
            window.open('train-report-all-detail-print.php?' + params.toString(), '_blank');
        }

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
