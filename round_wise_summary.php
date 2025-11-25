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
    <title>Round-Wise Summary - Jodhpur</title>
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
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="gradeFilter">Grade</label>
                        <select id="gradeFilter" name="grade" class="filter-select">
                            <option value="">-- All --</option>
                            <option value="A" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'A') ? 'selected' : ''; ?>>A - Monday</option>
                            <option value="B" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'B') ? 'selected' : ''; ?>>B - Tuesday</option>
                            <option value="C" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'C') ? 'selected' : ''; ?>>C - Wednesday</option>
                            <option value="D" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'D') ? 'selected' : ''; ?>>D - Thursday</option>
                            <option value="E" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'E') ? 'selected' : ''; ?>>E - Friday</option>
                            <option value="F" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'F') ? 'selected' : ''; ?>>F - Friday</option>
                            <option value="G" <?php echo (isset($_GET['grade']) && $_GET['grade'] === 'G') ? 'selected' : ''; ?>>G - Saturday</option>
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
                    <div class="filter-group">
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
                    <div class="filter-group">
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

                    <div class="filter-group">
                        <label for="fromDate">From</label>
                        <input type="date" id="fromDate" name="from_date" class="filter-input"
                               value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="toDate">To</label>
                        <input type="date" id="toDate" name="to_date" class="filter-input"
                               value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : date('Y-m-d'); ?>">
                    </div>

                    <div class="filter-group" style="align-self: flex-end;">
                        <input type="submit" class="btn-submit" value="Submit">
                    </div>

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
                $up    = isset($up) ? $up : '';
                $down  = isset($down) ? $down : '';


             }  
             
                else {
                echo '<p> no data is found </p>';
                exit();
                
             }
            ?>
            <!-- Summary Information -->
            <div class="summary-header">
                Station: <?php echo  "no station" ?> &nbsp;&nbsp;|&nbsp;&nbsp; UP: <?php echo $up ?>  &nbsp;&nbsp;|&nbsp;&nbsp; Down: <?php echo $down ?>
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
                            <th rowspan="2">#</th>
                            <th rowspan="2">Train No.</th>
                            <th colspan="2">Target Coaches</th>
                            <th colspan="2">Target Coach Feedbacks</th>
                            <th rowspan="2">Target TTE</th>
                            <th rowspan="2">Achieved Target TTE</th>
                            <th rowspan="2">Total Feedback Target</th>
                            <th rowspan="2">Achieved Feedbacks</th>
                            <th rowspan="2">Avg. P.S.I</th>
                        </tr>
                        <tr>
                            
                            <th>Total</th>
                            <th>Achieved</th>
                            <th>Total</th>
                            <th>Achieved</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><a href="<?php echo 'train-report.php?' . http_build_query(['train_no' => $up, 'grade' => $grade, 'from_date' => $from_date, 'to_date' => $to_date]); ?>" target="_blank" rel="noopener noreferrer" class="train-link"><?php echo htmlspecialchars($up); ?></a></td>
                            <td><?php $trainUpData   = get_coach_count($up); echo $trainUpData['total']; ?> </td>
                            <td><?php $uptrainachivedata = acheived_feedback($up, $from_date, $to_date , $grade);echo $uptrainachivedata['distinct_coach'];   ?></td>
                            <td><?php if ($trainUpData['total_feed'] > 0) { echo $trainUpData['total_feed']; } ?></td>
                            <td><?php echo $uptrainachivedata['ac_non_ac'];  ?></td>
                            <td><?php echo $trainUpData['tte']; ?></td>
                            <td><?php echo $uptrainachivedata['tte']; ?></td>
                            <td><?php echo $trainUpData['total_feed']+$trainUpData['tte']; ?></td>
                            <td><?php echo $uptrainachivedata['tte']+$uptrainachivedata['ac_non_ac']; ?></td>
                            <td><?php $up_train_psi = psi_calculation($up, $from_date, $to_date, $grade);


                         echo  "Feedback SUM = " . ($up_train_psi['feedback_sum'] ?? 0) . "<br>";
                       echo     "Highest Marking Value = " . ($up_train_psi['highest_marking'] ?? 'N/A') . "<br>";
                    echo    "<br>".     "Total feedback: " . ($up_train_psi['feedback_count'] ?? ($up_train_psi['feedback_sum'] ?? 0));
                         
                          // Compare total target vs achieved for UP train safely (avoid using isset on expressions)
                         $up_total_target = (isset($trainUpData['total_feed']) ? $trainUpData['total_feed'] : 0) + (isset($trainUpData['tte']) ? $trainUpData['tte'] : 0);
                       $up_total_achieved = (isset($uptrainachivedata['tte']) ? $uptrainachivedata['tte'] : 0) + (isset($uptrainachivedata['ac_non_ac']) ? $uptrainachivedata['ac_non_ac'] : 0);
                          
                          if ($trainUpData['tte'] == 1 && $uptrainachivedata['tte'] == 0) {
                            $totalfeedbackup=$trainUpData['total_feed']+$trainUpData['tte']; 
                            $achievedfeedbackup=$uptrainachivedata['ac_non_ac']+$uptrainachivedata['tte'];

                            if ($totalfeedbackup >= $achievedfeedbackup) {
                                $psi_up = ($up_train_psi['feedback_sum'] / ($up_train_psi['highest_marking'] *  $totalfeedbackup* 2)) * 66.66;
                                echo  "<br>".  number_format($psi_up, 2) . "%";
                              // no TTE available or zero — handle as needed (placeholder)
                             } 
                             else if ($totalfeedbackup <= $achievedfeedbackup) {

                                $psi_up = ($up_train_psi['feedback_sum'] / $totalfeedbackup) * 66.66;
                                echo  "<br>".  number_format($psi_up, 2) . "%";
                                
                             } 
                          
                          }

                          elseif($trainUpData['tte'] == 1 && $uptrainachivedata['tte'] == 1) {
                            $totalfeedbackup=$trainUpData['total_feed']+$trainUpData['tte']; 
                            $achievedfeedbackup=$uptrainachivedata['ac_non_ac']+$uptrainachivedata['tte'];
                            $psi_up = ($up_train_psi['feedback_sum'] / ($up_train_psi['highest_marking'] *  $totalfeedbackup* 2)) * 100;
                            echo  "<br>".  number_format($psi_up, 2) . "%";
                            if ($totalfeedbackup < $achievedfeedbackup) {
                                $psi_up = ($up_train_psi['feedback_sum'] / $totalfeedbackup) * 100;
                                echo  "<br>".  number_format($psi_up, 2) . "%";
                            }
                            elseif($totalfeedbackup == $achievedfeedbackup){
                                $psi_up = ($up_train_psi['feedback_sum'] / ($up_train_psi['highest_marking'] *  $totalfeedbackup* 2)) * 100;
                                echo  "<br>".  number_format($psi_up, 2) . "%";
                            }


                          }
                          elseif($trainUpData['tte'] == 0 && $uptrainachivedata['tte'] == 0) {
                            $totalfeedbackup=$trainUpData['total_feed']; //
                            echo  $totalfeedbackup;
                            $achievedfeedbackup=$uptrainachivedata['ac_non_ac'];//
                            $psi_up = ($up_train_psi['feedback_sum'] / $totalfeedbackup) * 100;
                            echo  "<br>".  number_format($psi_up, 2) . "%1";
                            if ($totalfeedbackup < $achievedfeedbackup) {
                                $psi_up = ($up_train_psi['feedback_sum'] / $totalfeedbackup) * 100;
                                echo  "<br>".  number_format($psi_up, 2) . "%2";
                            }
                            elseif($totalfeedbackup >= $achievedfeedbackup){ // 
                               echo  $psi_up = ($up_train_psi['feedback_sum'] / $totalfeedbackup) * 100;
                                echo  "<br>".  number_format($psi_up, 2) . "%3";
                            }
                          }

                         
                         
                            ?>  
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><a href="<?php echo 'train-report.php?' . http_build_query(['train_no' => $down, 'grade' => $grade, 'from_date' => $from_date, 'to_date' => $to_date]); ?>" target="_blank" rel="noopener noreferrer" class="train-link"><?php echo htmlspecialchars($down); ?></a></td>
                            <td><?php $trainDownData = get_coach_count($down); echo $trainDownData['total']; ?></td>
                            <td><?php $downtrainachivedata = acheived_feedback($down, $from_date, $to_date , $grade);echo $downtrainachivedata['distinct_coach'];   ?></td>
                            <td><?php if ($trainDownData['total_feed'] > 0) { echo $trainDownData['total_feed']; } ?></td>
                            <td><?php echo $downtrainachivedata['ac_non_ac'];  ?></td>
                            <td><?php echo $trainDownData['tte']; ?></td>
                            <td><?php echo $downtrainachivedata['tte']; ?></td>
                            <td><?php echo $trainDownData['total_feed']+$trainDownData['tte']; ?></td>
                            <td><?php echo $downtrainachivedata['tte']+$downtrainachivedata['ac_non_ac']; ?></td>
                            <td>
                            <?php $down_train_psi = psi_calculation($down, $from_date, $to_date, $grade);
                            if($trainDownData['tte'] == 1 && $downtrainachivedata['tte'] == 1) {
                                $totalfeedbackdown=$trainDownData['total_feed']+$trainDownData['tte']; 
                                $achievedfeedbackdown=$downtrainachivedata['ac_non_ac']+$downtrainachivedata['tte'];
                                $psi_down = ($down_train_psi['feedback_sum'] / ($down_train_psi['highest_marking'] *  $totalfeedbackdown* 2)) * 100;
                                echo  "<br>".  number_format($psi_down, 2) . "%";
                                if ($totalfeedbackdown < $achievedfeedbackdown) {
                                    $psi_down = ($down_train_psi['feedback_sum'] / $totalfeedbackdown) * 100;
                                    echo  "<br>".  number_format($psi_down, 2) . "%";
                                }
                                elseif($totalfeedbackdown == $achievedfeedbackdown){
                                    $psi_down = ($down_train_psi['feedback_sum'] / ($down_train_psi['highest_marking'] *  $totalfeedbackdown* 2)) * 100;
                                    echo  "<br>".  number_format($psi_down, 2) . "%";
                                }
                            }
                            elseif($trainDownData['tte'] == 1 && $downtrainachivedata['tte'] == 0) {
                                $totalfeedbackdown=$trainDownData['total_feed']+$trainDownData['tte']; 
                                $achievedfeedbackdown=$downtrainachivedata['ac_non_ac']+$downtrainachivedata['tte'];

                                if ($totalfeedbackdown >= $achievedfeedbackdown) {
                                    $psi_down = ($down_train_psi['feedback_sum'] / ($down_train_psi['highest_marking'] *  $totalfeedbackdown* 2)) * 66.66;
                                    echo  "<br>".  number_format($psi_down, 2) . "%";
                                  // no TTE available or zero — handle as needed (placeholder)
                                 } 
                                 else if ($totalfeedbackdown <= $achievedfeedbackdown) {

                                    $psi_down = ($down_train_psi['feedback_sum'] / $totalfeedbackdown) * 66.66;
                                    echo  "<br>".  number_format($psi_down, 2) . "%";
                                  // no TTE available or zero — handle as needed (placeholder)
                                 }
                            }
                            elseif($trainDownData['tte'] == 0 && $downtrainachivedata['tte'] == 1) {
                                $totalfeedbackdown=$trainDownData['total_feed']; 
                                $achievedfeedbackdown=$downtrainachivedata['ac_non_ac']+$downtrainachivedata['tte'];

                                if ($totalfeedbackdown >= $achievedfeedbackdown) {
                                    $psi_down = ($down_train_psi['feedback_sum'] / ($down_train_psi['highest_marking'] *  $totalfeedbackdown* 2)) * 66.66;
                                    echo  "<br>".  number_format($psi_down, 2) . "%";
                                  // no TTE available or zero — handle as needed (placeholder)
                                 } 
                                 else if ($totalfeedbackdown <= $achievedfeedbackdown) {

                                    $psi_down = ($down_train_psi['feedback_sum'] / $totalfeedbackdown) * 66.66;
                                    echo  "<br>".  number_format($psi_down, 2) . "%";
                                  // no TTE available or zero — handle as needed (placeholder)
                            }
                            }
                         ?>

                            </td>
                        </tr>

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total</td>
                            <td><?php echo $trainUpData['total']+$trainDownData['total'];?></td>
                            <td><?php echo $uptrainachivedata['distinct_coach']+$downtrainachivedata['distinct_coach']; ?></td>
                            <td><?php echo $trainUpData['total_feed']+$trainDownData['total_feed']; ?></td>
                            <td><?php echo $uptrainachivedata['ac_non_ac']+$downtrainachivedata['ac_non_ac']; ?></td>
                            <td><?php echo $trainUpData['tte']+$trainDownData['tte']; ?></td>
                            <td><?php echo $uptrainachivedata['tte']+$downtrainachivedata['tte']; ?></td>
                            <td><?php echo $trainUpData['total_feed']+$trainDownData['total_feed']+$trainUpData['tte']+$trainDownData['tte']; ?></td>
                            <td><?php echo $uptrainachivedata['ac_non_ac']+$downtrainachivedata['ac_non_ac']+$uptrainachivedata['tte']+$downtrainachivedata['tte']; ?></td>
                            <!-- <td> <?php echo ($psi_up + $psi_down) / 2 * 100 ; ?> </td> -->
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