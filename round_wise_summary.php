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
$station_name = getStationName($station_id);

function roundWiseEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function roundWiseFetchTrainOptions($mysqli, $station_id)
{
    $trains = [];
    $sql = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        error_log('Round-wise train list prepare failed: ' . $mysqli->error);
        return $trains;
    }

    $stmt->bind_param("s", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trains[] = $row['train_no'];
    }

    $stmt->close();

    return $trains;
}

function roundWiseEmptyCoachData()
{
    return [
        'ac' => 0,
        'non_ac' => 0,
        'total' => 0,
        'feed_ac' => 0,
        'feed_non_ac' => 0,
        'tte' => 0,
        'total_feed' => 0,
    ];
}

function roundWiseEmptyAchievedData()
{
    return [
        'ac_achived_coaches' => 0,
        'non_ac_achived_coaches' => 0,
        'distinct_coach' => 0,
        'ac' => 0,
        'non_ac' => 0,
        'tte' => 0,
        'ac_non_ac' => 0,
        'total' => 0,
    ];
}

function roundWisePercentage($train_no, $from_date, $to_date, $coach_type, $grade, $target_units)
{
    if ($train_no === '') {
        return [
            'avg_percentage' => '0.00',
            'percentage_sum' => 0.0,
            'divisor' => max(0, (int) $target_units),
            'target_units' => max(0, (int) $target_units),
            'achieved_units' => 0,
        ];
    }

    return calculateRoundWiseSummaryPercentage(
        $train_no,
        $from_date,
        $to_date,
        $coach_type,
        $grade,
        (int) $target_units
    );
}

function roundWiseBuildTrainSummary($train_no, $from_date, $to_date, $grade)
{
    $coach = $train_no !== '' ? get_coach_count($train_no) : false;
    $achieve = $train_no !== '' ? acheived_feedback($train_no, $from_date, $to_date, $grade) : false;

    $coach = array_merge(roundWiseEmptyCoachData(), is_array($coach) ? $coach : []);
    $achieve = array_merge(roundWiseEmptyAchievedData(), is_array($achieve) ? $achieve : []);

    $ac_total = (int) $coach['ac'];
    $non_ac_total = (int) $coach['non_ac'];
    $ac_feed_total = $ac_total * (int) $coach['feed_ac'];
    $non_ac_feed_total = $non_ac_total * (int) $coach['feed_non_ac'];
    $tte_total = (int) $coach['tte'];

    $ac = roundWisePercentage($train_no, $from_date, $to_date, 'AC', $grade, $ac_total);
    $non_ac = roundWisePercentage($train_no, $from_date, $to_date, 'NON-AC', $grade, $non_ac_total);
    $tte = roundWisePercentage($train_no, $from_date, $to_date, 'TTE', $grade, $tte_total);

    $final_psi = calculateFinalPSI([
        ['total' => $ac_total, 'percent' => $ac['avg_percentage']],
        ['total' => $non_ac_total, 'percent' => $non_ac['avg_percentage']],
        ['total' => $tte_total, 'percent' => $tte['avg_percentage']],
    ]);

    return [
        'coach' => $coach,
        'achieve' => $achieve,
        'ac_total' => $ac_total,
        'non_ac_total' => $non_ac_total,
        'ac_feed_total' => $ac_feed_total,
        'non_ac_feed_total' => $non_ac_feed_total,
        'tte_total' => $tte_total,
        'total_target' => (int) $coach['total_feed'] + $tte_total,
        'total_achieved' => (int) $achieve['tte'] + (int) $achieve['ac_non_ac'],
        'final_psi' => $final_psi,
        'sections' => [
            'ac' => $ac,
            'non_ac' => $non_ac,
            'tte' => $tte,
        ],
    ];
}

$train_options = roundWiseFetchTrainOptions($mysqli, $station_id_text);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
        <style>
        .print-footer {
            display: none;
        }
        
        @media print {
            @page {
                size: portrait;
                margin: 0;
            }
            
            body * {
                visibility: hidden;
            }
            
            .summary-header,
            .summary-info,
            .table-wrapper,
            .table-wrapper *,
            .print-footer {
                visibility: visible;
            }
            
            .table-wrapper {
                position: absolute;
                left: 0;
                top: 15px;
                width: 100%;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .summary-header,
            .summary-info {
                position: relative;
                padding: 5px !important;
                margin: 0 !important;
                font-size: 14px !important;
                font-weight: bold !important;
                color: #000 !important;
            }
            
            .report-table {
                width: 100% !important;
                font-size: 12px !important;
                margin: 0 !important;
                padding: 0 !important;
                border-collapse: collapse !important;
                border: 2px solid #000 !important;
            }
            
            .report-table th {
                padding: 4px 6px !important;
                margin: 0 !important;
                font-size: 12px !important;
                border: 1px solid #000 !important;
                background-color: #e0e0e0 !important;
                color: #000 !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .report-table td {
                padding: 4px 6px !important;
                margin: 0 !important;
                font-size: 12px !important;
                border: 1px solid #000 !important;
                color: #000 !important;
            }
            
            .report-table tfoot tr {
                background-color: #d0d0d0 !important;
                font-weight: bold !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .print-footer {
                display: block !important;
                position: relative;
                margin-top: 10px !important;
                padding: 5px !important;
                font-size: 13px !important;
                font-weight: bold !important;
                color: #000 !important;
                text-align: center;
                border-top: 2px solid #000;
            }
            
            .filter-section,
            #menuToggle,
            .export-buttons,
            button,
            nav,
            aside,
            footer {
                display: none !important;
            }
        }
        </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Round-Wise Summary - <?php echo roundWiseEscape($station_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="round_wiseSummary.css">

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

            <!-- Filter Section -->
            <form class="filter-section" method="get" action="">
                <div class="filter-row" style="display: flex; flex-wrap: nowrap; align-items: flex-end; gap: 10px; overflow-x: auto;">
                    <div class="">
                        <label for="gradeFilter">Grade</label>
                        <select id="gradeFilter" name="grade" class="filter-select">
                            <option value="">-- All --</option>
                            <option value="A" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'A') ? 'selected' : ''; ?>>A -
                                Monday</option>
                            <option value="B" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'B') ? 'selected' : ''; ?>>B -
                                Tuesday</option>
                            <option value="C" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'C') ? 'selected' : ''; ?>>C -
                                Wednesday</option>
                            <option value="D" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'D') ? 'selected' : ''; ?>>D -
                                Thursday</option>
                            <option value="E" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'E') ? 'selected' : ''; ?>>E -
                                Friday</option>
                            <option value="F" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'F') ? 'selected' : ''; ?>>F -
                                Saturday</option>
                            <option value="G" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'G') ? 'selected' : ''; ?>>G -
                                Sunday</option>
                        </select>
                    </div>

                    <div class="">
                        <!-- filter-group -->
                        <label for="upFilter">UP</label>
                        <select id="upFilter" name="up" class="filter-select">
                            <option value="">-- All --</option>
                            <?php
                            $selected_up = $_GET['up'] ?? ($train_options[0] ?? '');
                            foreach ($train_options as $tn) {
                                $selected = ((string) $selected_up === (string) $tn) ? 'selected' : '';
                                echo '<option value="' . roundWiseEscape($tn) . '" ' . $selected . '>' . roundWiseEscape($tn) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="">
                        <label for="downFilter">Down</label>
                        <select id="downFilter" name="down" class="filter-select">
                            <option value="">-- All --</option>
                            <?php
                            $selected_down = $_GET['down'] ?? ($train_options[0] ?? '');
                            foreach ($train_options as $tn) {
                                $selected = ((string) $selected_down === (string) $tn) ? 'selected' : '';
                                echo '<option value="' . roundWiseEscape($tn) . '" ' . $selected . '>' . roundWiseEscape($tn) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="">
                        <label for="fromDate">From</label>
                        <input type="date" id="fromDate" name="from_date" class="filter-input"
                            value="<?php echo isset($_GET['from_date']) ? roundWiseEscape($_GET['from_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="">
                        <label for="toDate">To</label>
                        <input type="date" id="toDate" name="to_date" class="filter-input"
                            value="<?php echo isset($_GET['to_date']) ? roundWiseEscape($_GET['to_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="filter-group" style="flex-shrink: 0;">
                        <input type="submit" class="btn-submit" value="Submit">
                    </div>
                    <!-- add print button -->
                    <div class="export-buttons" style="flex-shrink: 0; display: flex; gap: 2px;">
                        <button type="button" class="btn-submit" id="printButton">Print</button>
                        <button type="button" class="btn-submit" id="excelButton">Export to Excel</button>
                        <button type="button" class="btn-submit" id="downloadAllButton">📥  All Reports PDF</button>
                        <button type="button" class="btn-submit" id="downloadAllExcelButton">📊  All Reports Excel</button>
                    </div>
                    <script>
                        function exportToCSV() {
                            const table = document.querySelector('.report-table');
                            let csv = [];
                            const rows = table.querySelectorAll('tr');
                            
                            rows.forEach(row => {
                                const cols = row.querySelectorAll('td, th');
                                const csvrow = [];
                                cols.forEach(col => {
                                    csvrow.push(col.innerText);
                                });
                                csv.push(csvrow.join(','));
                            });
                            
                            const csvContent = csv.join('\n');
                            const blob = new Blob([csvContent], { type: 'text/csv' });
                            const url = window.URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = 'round_wise_summary.csv';
                            link.click();
                        }

                        function exportToExcel() {
                            // Get header information from the page
                            const summaryHeader = document.querySelector('.summary-header');
                            if (!summaryHeader) {
                                alert('Please submit the form first to generate the report.');
                                return;
                            }
                            
                            const headerText = summaryHeader.innerText;
                            const stationMatch = headerText.match(/Station:\s*([^\|]+)/);
                            const upMatch = headerText.match(/UP:\s*([^\|]+)/);
                            const downMatch = headerText.match(/Down:\s*([^\|]+)/);
                            const fromMatch = headerText.match(/From:\s*([^\|]+)/);
                            const toMatch = headerText.match(/To:\s*([^\|]+)/);
                            const gradeMatch = headerText.match(/Grade:\s*([^\s]+)/);
                            
                            const stationName = stationMatch ? stationMatch[1].trim() : '';
                            const upTrain = upMatch ? upMatch[1].trim() : '';
                            const downTrain = downMatch ? downMatch[1].trim() : '';
                            const fromDate = fromMatch ? fromMatch[1].trim() : '';
                            const toDate = toMatch ? toMatch[1].trim() : '';
                            const grade = gradeMatch ? gradeMatch[1].trim() : '';
                            
                            const table = document.querySelector('.report-table');
                            const rows = table.querySelectorAll('tr');
                            
                            // Start Excel data with header information
                            let excelData = '<table border="1">';
                            
                            // Add header rows
                            excelData += '<tr><td colspan="15" style="text-align:center; font-weight:bold; font-size:16px; background-color:#4472C4; color:white;">Round-Wise Summary Report</td></tr>';
                            excelData += '<tr><td colspan="15" style="text-align:center; background-color:#D9E1F2;"></td></tr>';
                            excelData += '<tr><td style="font-weight:bold;">Station:</td><td colspan="3">' + stationName + '</td><td style="font-weight:bold;">UP Train:</td><td colspan="3">' + upTrain + '</td><td style="font-weight:bold;">DOWN Train:</td><td colspan="6">' + downTrain + '</td></tr>';
                            excelData += '<tr><td style="font-weight:bold;">From Date:</td><td colspan="3">' + fromDate + '</td><td style="font-weight:bold;">To Date:</td><td colspan="3">' + toDate + '</td><td style="font-weight:bold;">Grade:</td><td colspan="6">' + grade + '</td></tr>';
                            excelData += '<tr><td colspan="15" style="background-color:#D9E1F2;"></td></tr>';
                            
                            // Add table data
                            rows.forEach(row => {
                                excelData += '<tr>';
                                const cols = row.querySelectorAll('td, th');
                                cols.forEach(col => {
                                    const tag = col.tagName === 'TH' ? 'th' : 'td';
                                    const rowspan = col.getAttribute('rowspan') || '';
                                    const colspan = col.getAttribute('colspan') || '';
                                    const style = col.tagName === 'TH' ? 'style="background-color:#4472C4; color:white; font-weight:bold;"' : '';
                                    excelData += `<${tag}${rowspan ? ' rowspan="'+rowspan+'"' : ''}${colspan ? ' colspan="'+colspan+'"' : ''} ${style}>${col.innerText}</${tag}>`;
                                });
                                excelData += '</tr>';
                            });
                            
                            excelData += '</table>';
                            
                            const blob = new Blob([excelData], { 
                                type: 'application/vnd.ms-excel' 
                            });
                            const url = window.URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = 'round_wise_summary.xls';
                            link.click();
                            window.URL.revokeObjectURL(url);
                        }

                        const printButton = document.getElementById('printButton');
                        if (printButton) {
                            printButton.addEventListener('click', function() {
                                window.print();
                            });
                        }

                        const excelButton = document.getElementById('excelButton');
                        if (excelButton) {
                            excelButton.addEventListener('click', function() {
                                exportToExcel();
                            });
                        }

                        const downloadAllButton = document.getElementById('downloadAllButton');
                        if (downloadAllButton) {
                            downloadAllButton.addEventListener('click', function() {
                            // Get current filter values
                            const urlParams = new URLSearchParams(window.location.search);
                            const params = new URLSearchParams({
                                from_date: urlParams.get('from_date') || '',
                                to_date: urlParams.get('to_date') || '',
                                grade: urlParams.get('grade') || '',
                                up: urlParams.get('up') || '',
                                down: urlParams.get('down') || ''
                            });
                            
                            // Check if required parameters exist
                            if (!params.get('from_date') || !params.get('to_date')) {
                                alert('Please submit the form first to generate reports!');
                                return;
                            }
                                window.open('download-all-reports-pdf.php?' + params.toString(), '_blank');
                            });
                        }

                        const downloadAllExcelButton = document.getElementById('downloadAllExcelButton');
                        if (downloadAllExcelButton) {
                            downloadAllExcelButton.addEventListener('click', function() {
                            // Get current filter values
                            const urlParams = new URLSearchParams(window.location.search);
                            const params = new URLSearchParams({
                                from_date: urlParams.get('from_date') || '',
                                to_date: urlParams.get('to_date') || '',
                                grade: urlParams.get('grade') || '',
                                up: urlParams.get('up') || '',
                                down: urlParams.get('down') || ''
                            });
                            
                            // Check if required parameters exist
                            if (!params.get('from_date') || !params.get('to_date')) {
                                alert('Please submit the form first to generate reports!');
                                return;
                            }
                                window.open('download-all-reports-excel.php?' + params.toString(), '_blank');
                            });
                        }
                    </script>

                    <div class="export-buttons"
                        style="align-self: flex-end; display: flex; gap: 6px; margin-left: auto;">

                    </div>
                </div>
            </form>


            <?php
            if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
                $from_date = (string) ($_GET['from_date'] ?? '');
                $to_date = (string) ($_GET['to_date'] ?? '');
                $grade = (string) ($_GET['grade'] ?? '');
                $up = (string) ($_GET['up'] ?? '');
                $down = (string) ($_GET['down'] ?? '');

                $upSummary = roundWiseBuildTrainSummary($up, $from_date, $to_date, $grade);
                $downSummary = roundWiseBuildTrainSummary($down, $from_date, $to_date, $grade);

                $upCoach = $upSummary['coach'];
                $upAchieve = $upSummary['achieve'];
                $downCoach = $downSummary['coach'];
                $downAchieve = $downSummary['achieve'];

                $up_ac_total = $upSummary['ac_total'];
                $up_non_ac_total = $upSummary['non_ac_total'];
                $up_ac_feed_total = $upSummary['ac_feed_total'];
                $up_non_ac_feed_total = $upSummary['non_ac_feed_total'];
                $up_tte_total = $upSummary['tte_total'];
                $up_total_target = $upSummary['total_target'];
                $up_total_achieved = $upSummary['total_achieved'];
                $upFinalPSI = $upSummary['final_psi'];
                $upSections = $upSummary['sections'];

                $down_ac_total = $downSummary['ac_total'];
                $down_non_ac_total = $downSummary['non_ac_total'];
                $down_ac_feed_total = $downSummary['ac_feed_total'];
                $down_non_ac_feed_total = $downSummary['non_ac_feed_total'];
                $down_tte_total = $downSummary['tte_total'];
                $down_total_target = $downSummary['total_target'];
                $down_total_achieved = $downSummary['total_achieved'];
                $downFinalPSI = $downSummary['final_psi'];
                $downSections = $downSummary['sections'];

                $up_down_PSI = number_format(calculateFinalPSI([
                    [
                        'total' => $up_ac_total + $down_ac_total,
                        'percent' => combineRoundWiseSummaryPercentages([$upSections['ac'], $downSections['ac']]),
                    ],
                    [
                        'total' => $up_non_ac_total + $down_non_ac_total,
                        'percent' => combineRoundWiseSummaryPercentages([$upSections['non_ac'], $downSections['non_ac']]),
                    ],
                    [
                        'total' => $up_tte_total + $down_tte_total,
                        'percent' => combineRoundWiseSummaryPercentages([$upSections['tte'], $downSections['tte']]),
                    ],
                ]), 2);
            } else {
                echo '<p> </p>';
                exit();

            }
            ?>
            <!-- Summary Information -->
            <div class="summary-header" style="text-align: center;">
                Station: <?php echo roundWiseEscape($station_name) ?> &nbsp;&nbsp;|&nbsp;&nbsp; UP: <?php echo roundWiseEscape($up) ?>
                &nbsp;&nbsp;|&nbsp;&nbsp; Down: <?php echo roundWiseEscape($down) ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                From: <span id="displayFrom"><?php echo roundWiseEscape($from_date) ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
                To: <span id="displayTo"><?php echo roundWiseEscape($to_date) ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
                Grade: <span class="grade-badge"><?php echo roundWiseEscape($grade) ?> </span>
            </div>

            <div class="summary-info" id="summaryInfo">
                <!-- Summary info will be populated by JavaScript -->
            </div>


            <!-- Report Table -->
            <div class="table-wrapper">
                <table class="report-table">
                    <thead>
   
                <tr>
                    <th rowspan="2">No.</th>
                    <th rowspan="2">Train No.</th>
                    <th colspan="2">AC Coaches</th>
                    <th colspan="2">Non-AC Coaches</th>
                    <th colspan="2">AC Feedbacks</th>
                    <th colspan="2">Non-AC Feedbacks</th>
                    <th colspan="2">TTE Feedbacks</th>
                    <th colspan="2">Total Feedbacks</th>
                    <th rowspan="2">Avg. PSI</th>
                </tr>
            
                
                <tr>
                    <th>Total</th>
                    <th>Achieved</th>
                    <th>Total</th>
                    <th>Achieved</th>
                    <th>Total</th>
                    <th>Achieved</th>
                    <th>Total</th>
                    <th>Achieved</th>
                    <th>Total</th>
                    <th>Achieved</th>
                    <th>Total</th>
                    <th>Achieved</th>
                </tr>
            </thead>
                    
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><a href="<?php echo roundWiseEscape('train-report.php?' . http_build_query(['train_no' => $up, 'grade' => $grade, 'from_date' => $from_date, 'to_date' => $to_date])); ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    class="train-link"><?php echo roundWiseEscape($up); ?></a></td>
                            <td><?php echo $up_ac_total; ?></td>
                            <td><?php echo $upAchieve['ac_achived_coaches']; ?></td>
                            <td><?php echo $up_non_ac_total; ?></td>
                            <td><?php echo $upAchieve['non_ac_achived_coaches']; ?></td>
                            <td><?php echo $up_ac_feed_total; ?></td>
                            <td><?php echo $upAchieve['ac']; ?></td>
                            <td><?php echo $up_non_ac_feed_total; ?></td>
                            <td><?php echo $upAchieve['non_ac']; ?></td>
                            <td><?php echo $up_tte_total; ?></td>
                            <td><?php echo $upAchieve['tte']; ?></td>
                            <td><?php echo $up_total_target; ?></td>
                            <td><?php echo $up_total_achieved; ?></td>
                            <td><?php echo $upFinalPSI; ?>%</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><a href="<?php echo roundWiseEscape('train-report.php?' . http_build_query(['train_no' => $down, 'grade' => $grade, 'from_date' => $from_date, 'to_date' => $to_date])); ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    class="train-link"><?php echo roundWiseEscape($down); ?></a></td>
                            <td><?php echo $down_ac_total; ?></td>
                            <td><?php echo $downAchieve['ac_achived_coaches']; ?></td>
                            <td><?php echo $down_non_ac_total; ?></td>
                            <td><?php echo $downAchieve['non_ac_achived_coaches']; ?></td>
                            <td><?php echo $down_ac_feed_total; ?></td>
                            <td><?php echo $downAchieve['ac']; ?></td>
                            <td><?php echo $down_non_ac_feed_total; ?></td>
                            <td><?php echo $downAchieve['non_ac']; ?></td>
                            <td><?php echo $down_tte_total; ?></td>
                            <td><?php echo $downAchieve['tte']; ?></td>
                            <td><?php echo $down_total_target; ?></td>
                            <td><?php echo $down_total_achieved; ?></td>
                            <td><?php echo $downFinalPSI; ?>%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                    <tr class="font-bold bg-slate-100">
                        <td colspan="2">Total</td>
                    
                        <!-- AC Coaches -->
                        <td><?= $up_ac_total + $down_ac_total ?></td>
                        <td><?= $upAchieve['ac_achived_coaches'] + $downAchieve['ac_achived_coaches'] ?></td>
                    
                        <!-- Non-AC Coaches -->
                        <td><?= $up_non_ac_total + $down_non_ac_total ?></td>
                        <td><?= $upAchieve['non_ac_achived_coaches'] + $downAchieve['non_ac_achived_coaches'] ?></td>
                    
                        <!-- AC Feedback -->
                        <td><?= $up_ac_feed_total + $down_ac_feed_total ?></td>
                        <td><?= $upAchieve['ac'] + $downAchieve['ac'] ?></td>
                    
                        <!-- Non-AC Feedback -->
                        <td><?= $up_non_ac_feed_total + $down_non_ac_feed_total ?></td>
                        <td><?= $upAchieve['non_ac'] + $downAchieve['non_ac'] ?></td>
                    
                        <!-- TTE Feedback -->
                        <td><?= $up_tte_total + $down_tte_total ?></td>
                        <td><?= $upAchieve['tte'] + $downAchieve['tte'] ?></td>
                    
                        <!-- Overall -->
                        <td><?= $up_total_target + $down_total_target ?></td>
                        <td><?= $up_total_achieved + $down_total_achieved ?></td>
                        
                        <td><?= $up_down_PSI ?>%</td>


                    
                        
                    </tr>
                    </tfoot>

                </table>
                <!-- Print Footer (only visible when printing) -->
                <div class="print-footer" style=" margin-top: 20px; margin-bottom: 20px;">
                    Station: <?php echo roundWiseEscape($station_name); ?> | UP Train: <?php echo roundWiseEscape($up); ?> | DOWN Train: <?php echo roundWiseEscape($down); ?> | Grade: <?php echo roundWiseEscape($grade); ?> | Report Date: From <?php echo roundWiseEscape($from_date); ?> To <?php echo roundWiseEscape($to_date); ?>
                </div>
            </div>


            <!-- Footer -->
            <?php
            require_once 'includes/footer.php'
                ?>

        </main>

    </div>

    <script>
        // Apply filters: handled by normal form submission — no JavaScript here.

        // Export functions
        function exportPDF() {
            alert('Exporting to PDF...\nIn production, this would generate a PDF report.');
        }

        function exportExcel() {
            alert('Exporting to Excel...\nIn production, this would generate an Excel file.');
        }

        // Mobile Sidebar Toggle (guarded)
        (function () {
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
        })();
    </script>

</body>

</html>
