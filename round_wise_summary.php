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
    <title>Round-Wise Summary - <?php echo $station_name ?></title>
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

                    <?php
                    // Fetch train numbers for UP select 
                    $train_query = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
                    $stmt = $mysqli->prepare($train_query);
                    $stmt->bind_param("i", $_SESSION['station_id']);
                    $stmt->execute();
                    $train_result = $stmt->get_result();
                    ?>
                    <div class=""> 
                        <!-- filter-group -->
                        <label for="upFilter">UP</label>
                        <select id="upFilter" name="up" class="filter-select">
                            <option value="">-- All --</option>
                            <?php
                            $first = true;
                            while ($train = $train_result->fetch_assoc()) {
                                $tn = $train['train_no'];
                                if (isset($_GET['up'])) {
                                    $selected = ($_GET['up'] == $tn) ? 'selected' : '';
                                } else {
                                    $selected = $first ? 'selected' : '';
                                }
                                echo '<option value="' . htmlspecialchars($tn) . '" ' . $selected . '>' . htmlspecialchars($tn) . '</option>';
                                $first = false;
                            }
                            $stmt->close();
                            ?>
                        </select>
                    </div>

                    <?php
                    // Fetch train numbers for DOWN select (same query; keep separate to reset result pointer)
                    $stmt_down = $mysqli->prepare($train_query);
                    $stmt_down->bind_param("i", $_SESSION['station_id']);
                    $stmt_down->execute();
                    $train_result_down = $stmt_down->get_result();
                    ?>
                    <div class="">
                        <label for="downFilter">Down</label>
                        <select id="downFilter" name="down" class="filter-select">
                            <option value="">-- All --</option>
                            <?php
                            $first_down = true;
                            while ($train_down = $train_result_down->fetch_assoc()) {
                                $tn = $train_down['train_no'];
                                if (isset($_GET['down'])) {
                                    $selected = ($_GET['down'] == $tn) ? 'selected' : '';
                                } else {
                                    $selected = $first_down ? 'selected' : '';
                                }
                                echo '<option value="' . htmlspecialchars($tn) . '" ' . $selected . '>' . htmlspecialchars($tn) . '</option>';
                                $first_down = false;
                            }
                            $stmt_down->close();
                            ?>
                        </select>
                    </div>

                    <div class="">
                        <label for="fromDate">From</label>
                        <input type="date" id="fromDate" name="from_date" class="filter-input"
                            value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="">
                        <label for="toDate">To</label>
                        <input type="date" id="toDate" name="to_date" class="filter-input"
                            value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="filter-group" style="flex-shrink: 0;">
                        <input type="submit" class="btn-submit" value="Submit">
                    </div>
                    <!-- add print button -->
                    <div class="export-buttons" style="flex-shrink: 0; display: flex; gap: 2px;">
                        <button type="button" class="btn-submit" id="printButton">Print</button>
                        <button type="button" class="btn-submit" id="excelButton">Export to Excel</button>
                        <!-- <button type="button" class="btn-submit" id="downloadAllButton">📥  All Reports PDF</button>
                        <button type="button" class="btn-submit" id="downloadAllExcelButton">📊  All Reports Excel</button> -->
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

                        document.getElementById('printButton').addEventListener('click', function() {
                            window.print();
                        });

                        document.getElementById('excelButton').addEventListener('click', function() {
                            exportToExcel();
                        });

                        document.getElementById('downloadAllButton').addEventListener('click', function() {
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
                        
                        document.getElementById('downloadAllExcelButton').addEventListener('click', function() {
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
                    </script>

                    <div class="export-buttons"
                        style="align-self: flex-end; display: flex; gap: 6px; margin-left: auto;">

                    </div>
                </div>
            </form>


            <?php
            if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
                $from_date = htmlspecialchars($_GET['from_date']);
                $to_date = htmlspecialchars($_GET['to_date']);
                $grade = $_GET['grade'];
                $station_id = $_SESSION['station_id'];
                $up = $_GET['up'];
                $down = $_GET['down'];

                // normalize datetimes to include time portion
                // $from_datetime = $from_date . ' 00:00:00';
                // $to_datetime   = $to_date   . ' 23:59:59';
            
                // ensure variables are defined
                $grade = isset($grade) ? $grade : '';
                $up = isset($up) ? $up : '';
                $down = isset($down) ? $down : '';


            } else {
                echo '<p> </p>';
                exit();

            }
            ?>
            <!-- Summary Information -->
            <div class="summary-header" style="text-align: center;">
                Station: <?php echo $station_name ?> &nbsp;&nbsp;|&nbsp;&nbsp; UP: <?php echo $up ?>
                &nbsp;&nbsp;|&nbsp;&nbsp; Down: <?php echo $down ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                From: <span id="displayFrom"><?php echo $from_date ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
                To: <span id="displayTo"><?php echo $to_date ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
                Grade: <span class="grade-badge"><?php echo $grade ?> </span>
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
                    
                    <?php
                    
                    $upCoach   = get_coach_count($up);
                    $upAchieve = acheived_feedback($up, $from_date, $to_date, $grade);
                    
                    $up_ac_total        = $upCoach['ac'];
                    $up_non_ac_total    = $upCoach['non_ac'];
                    $up_ac_feed_total   = $upCoach['ac'] * $upCoach['feed_ac'];
                    $up_non_ac_feed_total = $upCoach['non_ac'] * $upCoach['feed_non_ac'];
                    $up_tte_total       = $upCoach['tte'];
                    
                    $up_total_target    = $upCoach['total_feed'] + $upCoach['tte'];
                    $up_total_achieved  = $upAchieve['tte'] + $upAchieve['ac_non_ac'];
                    
                    
                    $downCoach   = get_coach_count($down);
                    $downAchieve = acheived_feedback($down, $from_date, $to_date, $grade);
                    
                    $down_ac_total        = $downCoach['ac'];
                    $down_non_ac_total    = $downCoach['non_ac'];
                    $down_ac_feed_total   = $downCoach['ac'] * $downCoach['feed_ac'];
                    $down_non_ac_feed_total = $downCoach['non_ac'] * $downCoach['feed_non_ac'];
                    $down_tte_total       = $downCoach['tte'];
                    
                    $down_total_target    = $downCoach['total_feed'] + $downCoach['tte'];
                    $down_total_achieved  = $downAchieve['tte'] + $downAchieve['ac_non_ac'];
                    
                    $up_ac  = calculateCoachWisePercentage($up, $from_date, $to_date, 'AC', $grade);
                    $up_non = calculateCoachWisePercentage($up, $from_date, $to_date, 'NON-AC', $grade);
                    $up_tte = calculateCoachWisePercentage($up, $from_date, $to_date, 'TTE', $grade);    
                    
                    
                    $down_ac  = calculateCoachWisePercentage($down, $from_date, $to_date, 'AC', $grade);
                    $down_non = calculateCoachWisePercentage($down, $from_date, $to_date, 'NON-AC', $grade);
                    $down_tte = calculateCoachWisePercentage($down, $from_date, $to_date, 'TTE', $grade);
                    
                    $upFinalPSI = calculateFinalPSI([
                        [
                            'total'   => $up_ac_total,
                            'percent' => $up_ac['avg_percentage']
                        ],
                        [
                            'total'   => $up_non_ac_total,
                            'percent' => $up_non['avg_percentage']
                        ],
                        [
                            'total'   => $upCoach['tte'],
                            'percent' => $up_tte['avg_percentage']
                        ]
                    ]);
                    
                    
                    $downFinalPSI = calculateFinalPSI([
                        [
                            'total'   => $down_ac_total,
                            'percent' => $down_ac['avg_percentage']
                        ],
                        [
                            'total'   => $down_non_ac_total,
                            'percent' => $down_non['avg_percentage']
                        ],
                        [
                            'total'   => $downCoach['tte'],
                            'percent' => $down_tte['avg_percentage']
                        ]
                    ]);

             
                    $up_down_PSI = number_format(($upFinalPSI + $downFinalPSI) / 2, 2);

                    
                    ?>
                    
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><a href="<?php echo 'train-report.php?' . http_build_query(['train_no' => $up, 'grade' => $grade, 'from_date' => $from_date, 'to_date' => $to_date]); ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    class="train-link"><?php echo htmlspecialchars($up); ?></a></td>
                                    
                            <td><?php $trainUpData = get_coach_count($up);
                            echo $trainUpData['ac']; ?> </td>
                            <td><?php $uptrainachivedata = acheived_feedback($up, $from_date, $to_date, $grade);
                            echo $uptrainachivedata['ac_achived_coaches']; ?>
                            </td>
                            <td><?php $trainUpData = get_coach_count($up);
                            echo $trainUpData['non_ac']; ?> </td>
                            <td><?php $uptrainachivedata = acheived_feedback($up, $from_date, $to_date, $grade);
                            echo $uptrainachivedata['non_ac_achived_coaches']; ?>
                            </td>
                            

                            <td>
                                <?php
                                    $trainUpData = get_coach_count($up);
                                    $total_ac = $trainUpData['ac'] * $trainUpData['feed_ac'];
                                    echo $total_ac;
                                ?>
                            </td>
                            <td><?php echo $uptrainachivedata['ac']; ?></td>

                            <td>
                                <?php
                                    $trainUpData = get_coach_count($up);
                                    $total_non_ac = $trainUpData['non_ac'] * $trainUpData['feed_non_ac'];
                                    echo $total_non_ac;
                                ?>
                            </td>
                            <td><?php echo $uptrainachivedata['non_ac']; ?></td>

                            <td><?php echo $trainUpData['tte']; ?></td>
                            <td><?php echo $uptrainachivedata['tte']; ?></td>
                            
                            
                            <td><?php echo $trainUpData['total_feed'] + $trainUpData['tte']; ?></td>
                            <td><?php echo $uptrainachivedata['tte'] + $uptrainachivedata['ac_non_ac']; ?></td>
                            <td><?php echo $up_train_psi = $upFinalPSI ?>%</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><a href="<?php echo 'train-report.php?' . http_build_query(['train_no' => $down, 'grade' => $grade, 'from_date' => $from_date, 'to_date' => $to_date]); ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    class="train-link"><?php echo htmlspecialchars($down); ?></a></td>
                            <td><?php $trainDownData = get_coach_count($down);
                            echo $trainDownData['ac']; ?> </td>
                            <td><?php $downtrainachivedata = acheived_feedback($down, $from_date, $to_date, $grade);
                            echo $downtrainachivedata['ac_achived_coaches']; ?>
                            </td>
                            <td><?php $trainDownData = get_coach_count($down);
                            echo $trainDownData['non_ac']; ?> </td>
                            <td><?php $downtrainachivedata = acheived_feedback($down, $from_date, $to_date, $grade);
                            echo $downtrainachivedata['non_ac_achived_coaches']; ?>
                            </td>
                            

                            <td>
                                <?php
                                    $trainDownData = get_coach_count($down);
                                    $total_ac = $trainDownData['ac'] * $trainDownData['feed_ac'];
                                    echo $total_ac;
                                ?>
                            </td>
                            <td><?php echo $downtrainachivedata['ac']; ?></td>

                            <td>
                                <?php
                                    $trainDownData = get_coach_count($down);
                                    $total_non_ac = $trainDownData['non_ac'] * $trainDownData['feed_non_ac'];
                                    echo $total_non_ac;
                                ?>
                            </td>
                            <td><?php echo $downtrainachivedata['non_ac']; ?></td>

                            <td><?php echo $trainDownData['tte']; ?></td>
                            <td><?php echo $downtrainachivedata['tte']; ?></td>
                            
                            
                            <td><?php echo $trainDownData['total_feed'] + $trainDownData['tte']; ?></td>
                            <td><?php echo $downtrainachivedata['tte'] + $downtrainachivedata['ac_non_ac']; ?></td>
                            <td><?php echo $down_train_psi = $downFinalPSI ?> %</td>
            <!-- Print Footer (only visible when printing) -->
            <div class="print-footer" style=" margin-top: 20px; margin-bottom: 20px;">
               Station: <?php echo $station_name; ?> | UP Train: <?php echo $up; ?> | DOWN Train: <?php echo $down; ?> | Grade: <?php echo $grade; ?> | Report Date: From <?php echo $from_date; ?> To <?php echo $to_date; ?>
            </div>
        
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