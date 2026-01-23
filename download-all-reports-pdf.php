<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

$station_name = getStationName($_SESSION['station_id']);
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;
$grade = isset($_GET['grade']) ? $_GET['grade'] : null;
$up = isset($_GET['up']) ? $_GET['up'] : null;
$down = isset($_GET['down']) ? $_GET['down'] : null;

// Helper functions
function getAllFeedbacksForPassenger($passenger_id) {
    global $mysqli;
    $data = [];
    $pid = mysqli_real_escape_string($mysqli, $passenger_id);
    $sql = "SELECT feed_param, value FROM OBHS_feedback WHERE passenger_id = '" . $pid . "'";
    $result = mysqli_query($mysqli, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

function getAllFeedbackDetails($train_no, $grade, $from_date, $to_date, $coach_type) {
    global $mysqli;
    $where = [];
    if ($train_no) $where[] = "train_no = '" . mysqli_real_escape_string($mysqli, $train_no) . "'";
    if ($grade) $where[] = "grade = '" . mysqli_real_escape_string($mysqli, $grade) . "'";
    if ($from_date) $where[] = "DATE(created) >= '" . mysqli_real_escape_string($mysqli, $from_date) . "'";
    if ($to_date) $where[] = "DATE(created) <= '" . mysqli_real_escape_string($mysqli, $to_date) . "'";
    if ($coach_type) $where[] = "coach_type = '" . mysqli_real_escape_string($mysqli, $coach_type) . "'";
    $sql = "SELECT * FROM OBHS_passenger";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created DESC LIMIT 100"; // Limit for performance
    $result = mysqli_query($mysqli, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Report - All in One</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 15px;
            font-size: 9px;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        
        .print-btn {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
        }
        
        .print-btn:hover {
            background-color: #059669;
        }
        
        .close-btn {
            background-color: #6b7280;
        }
        
        h1 {
            text-align: center;
            background-color: #4472C4;
            color: white;
            padding: 12px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .header-info {
            text-align: center;
            margin: 8px 0;
            font-weight: bold;
            font-size: 10px;
            padding: 8px;
            background-color: #e0e0e0;
        }
        
        h3 {
            background-color: #059669;
            color: white;
            padding: 8px;
            margin-top: 15px;
            margin-bottom: 8px;
            font-size: 12px;
            text-align: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }
        
        th {
            background-color: #4472C4;
            color: white;
            padding: 6px 4px;
            border: 1px solid #000;
            font-size: 8px;
            text-align: center;
            font-weight: bold;
        }
        
        td {
            padding: 4px 3px;
            border: 1px solid #000;
            text-align: center;
            font-size: 8px;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tfoot td {
            font-weight: bold;
            background-color: #d0d0d0;
            font-size: 8px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .section-divider {
            margin: 30px 0;
            border-top: 3px solid #4472C4;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 8px;
            }
            
            h1, h3 {
                background-color: #4472C4 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            h3 {
                background-color: #059669 !important;
            }
            
            th {
                background-color: #4472C4 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            tfoot td {
                background-color: #d0d0d0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .header-info {
                background-color: #e0e0e0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h2 style="margin-bottom: 10px;">📄 Complete Report - Ready to Print</h2>
        <p style="margin-bottom: 15px;">Click the Print button below, then select "Save as PDF" in the print dialog</p>
        <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <button class="print-btn close-btn" onclick="window.history.back()">✖ Close</button>
    </div>

<?php
    
    // ============================================
    // 1. ROUND WISE SUMMARY REPORT
    // ============================================
    
    $upCoach = get_coach_count($up);
    $upAchieve = acheived_feedback($up, $from_date, $to_date, $grade);
    
    $up_ac_total = $upCoach['ac'];
    $up_non_ac_total = $upCoach['non_ac'];
    $up_ac_feed_total = $upCoach['ac'] * $upCoach['feed_ac'];
    $up_non_ac_feed_total = $upCoach['non_ac'] * $upCoach['feed_non_ac'];
    $up_tte_total = $upCoach['tte'];
    
    $up_total_target = $upCoach['total_feed'] + $upCoach['tte'];
    $up_total_achieved = $upAchieve['tte'] + $upAchieve['ac_non_ac'];
    
    $downCoach = get_coach_count($down);
    $downAchieve = acheived_feedback($down, $from_date, $to_date, $grade);
    
    $down_ac_total = $downCoach['ac'];
    $down_non_ac_total = $downCoach['non_ac'];
    $down_ac_feed_total = $downCoach['ac'] * $downCoach['feed_ac'];
    $down_non_ac_feed_total = $downCoach['non_ac'] * $downCoach['feed_non_ac'];
    $down_tte_total = $downCoach['tte'];
    
    $down_total_target = $downCoach['total_feed'] + $downCoach['tte'];
    $down_total_achieved = $downAchieve['tte'] + $downAchieve['ac_non_ac'];
    
    $up_ac = calculateCoachWisePercentage($up, $from_date, $to_date, 'AC', $grade);
    $up_non = calculateCoachWisePercentage($up, $from_date, $to_date, 'NON-AC', $grade);
    $up_tte = calculateCoachWisePercentage($up, $from_date, $to_date, 'TTE', $grade);
    
    $down_ac = calculateCoachWisePercentage($down, $from_date, $to_date, 'AC', $grade);
    $down_non = calculateCoachWisePercentage($down, $from_date, $to_date, 'NON-AC', $grade);
    $down_tte = calculateCoachWisePercentage($down, $from_date, $to_date, 'TTE', $grade);
    
    $upFinalPSI = calculateFinalPSI([
        ['total' => $up_ac_total, 'percent' => $up_ac['avg_percentage']],
        ['total' => $up_non_ac_total, 'percent' => $up_non['avg_percentage']],
        ['total' => $upCoach['tte'], 'percent' => $up_tte['avg_percentage']]
    ]);
    
    $downFinalPSI = calculateFinalPSI([
        ['total' => $down_ac_total, 'percent' => $down_ac['avg_percentage']],
        ['total' => $down_non_ac_total, 'percent' => $down_non['avg_percentage']],
        ['total' => $downCoach['tte'], 'percent' => $down_tte['avg_percentage']]
    ]);
    
    $up_down_PSI = number_format(($upFinalPSI + $downFinalPSI) / 2, 2);
    ?>

    <h1>Round-Wise Summary Report</h1>
    <div class="header-info">
        Station: <?php echo htmlspecialchars($station_name); ?> | 
        UP: <?php echo htmlspecialchars($up); ?> | 
        DOWN: <?php echo htmlspecialchars($down); ?> | 
        From: <?php echo htmlspecialchars($from_date); ?> | 
        To: <?php echo htmlspecialchars($to_date); ?> | 
        Grade: <?php echo htmlspecialchars($grade); ?>
    </div>
    
    <table>
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
                <th>Total</th><th>Achieved</th>
                <th>Total</th><th>Achieved</th>
                <th>Total</th><th>Achieved</th>
                <th>Total</th><th>Achieved</th>
                <th>Total</th><th>Achieved</th>
                <th>Total</th><th>Achieved</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><?php echo htmlspecialchars($up); ?></td>
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
                <td><?php echo htmlspecialchars($down); ?></td>
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
            <tr>
                <td colspan="2">Total</td>
                <td><?php echo ($up_ac_total + $down_ac_total); ?></td>
                <td><?php echo ($upAchieve['ac_achived_coaches'] + $downAchieve['ac_achived_coaches']); ?></td>
                <td><?php echo ($up_non_ac_total + $down_non_ac_total); ?></td>
                <td><?php echo ($upAchieve['non_ac_achived_coaches'] + $downAchieve['non_ac_achived_coaches']); ?></td>
                <td><?php echo ($up_ac_feed_total + $down_ac_feed_total); ?></td>
                <td><?php echo ($upAchieve['ac'] + $downAchieve['ac']); ?></td>
                <td><?php echo ($up_non_ac_feed_total + $down_non_ac_feed_total); ?></td>
                <td><?php echo ($upAchieve['non_ac'] + $downAchieve['non_ac']); ?></td>
                <td><?php echo ($up_tte_total + $down_tte_total); ?></td>
                <td><?php echo ($upAchieve['tte'] + $downAchieve['tte']); ?></td>
                <td><?php echo ($up_total_target + $down_total_target); ?></td>
                <td><?php echo ($up_total_achieved + $down_total_achieved); ?></td>
                <td><?php echo $up_down_PSI; ?>%</td>
            </tr>
        </tfoot>
    </table>

    <div class="page-break"></div>

    // ============================================
    // 2. TRAIN REPORTS (UP and DOWN)
    // ============================================
    <?php
    $trains = [$up, $down];
    $train_index = 0;
    foreach ($trains as $current_train) {
        // Skip if train number is empty
        if (empty($current_train)) {
            continue;
        }
        
        // Add page break for second train onwards
        if ($train_index > 0) {
            echo '<div class="page-break"></div>';
        }
        $train_index++;
        
        echo '<div class="section-divider"></div>';
        echo '<h1>Train Report - ' . htmlspecialchars($current_train) . '</h1>';
        echo '<div class="header-info">';
        echo 'Station: ' . htmlspecialchars($station_name) . ' | ';
        echo 'Train: ' . htmlspecialchars($current_train) . ' | ';
        echo 'From: ' . htmlspecialchars($from_date ?? '') . ' | ';
        echo 'To: ' . htmlspecialchars($to_date ?? '') . ' | ';
        echo 'Grade: ' . htmlspecialchars($grade ?? '');
        echo '</div>';
        
        $coach_types = ['AC', 'NON-AC', 'TTE'];
        foreach ($coach_types as $coach_type) {
            $feedbackData = feedback_calculation_coach_wise($current_train, $from_date, $to_date, $coach_type, $grade);
            $coachList = $feedbackData['coach_wise'] ?? [];
            $targets = $feedbackData['targets'] ?? [];
            $highest_marking = $feedbackData['highest_marking'] ?? 0;
            $total_questions = $feedbackData['total_questions'] ?? 0;
            
            if ($coach_type == 'AC') {
                $target_per_coach = $targets['ac_coach_target'] ?? 0;
            } elseif ($coach_type == 'NON-AC') {
                $target_per_coach = $targets['non_ac_coach_target'] ?? 0;
            } else {
                $target_per_coach = $targets['tte_target'] ?? 0;
            }
            
            echo '<h3>' . strtoupper($coach_type) . ' Feedback Report</h3>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>SR. No.</th>';
            echo '<th>Coach No.</th>';
            echo '<th>Target</th>';
            echo '<th>Achieved</th>';
            echo '<th>Avg P.S.I</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            $row_no = 1;
            $total_passenger_sum = 0;
            $total_percentage_sum = 0;
            $total_target_sum = 0;
            $total_coaches = count($coachList);
            
            if (!empty($coachList)) {
                foreach ($coachList as $coach_no => $data) {
                    $feedback_sum = $data['feedback_sum'] ?? 0;
                    $passenger_count = $data['total_passenger_count'] ?? 0;
                    
                    $total_passenger_sum += $passenger_count;
                    $total_target_sum += $target_per_coach;
                    
                    $percentage = 0.0;
                    if ($total_questions > 0 && $highest_marking > 0) {
                        if ($passenger_count <= $target_per_coach && $target_per_coach > 0) {
                            $denom = $total_questions * $highest_marking * $target_per_coach;
                        } else {
                            $denom = $total_questions * $highest_marking * $passenger_count;
                        }
                        if ($denom > 0) {
                            $percentage = ($feedback_sum / $denom) * 100;
                        }
                    }
                    
                    $total_percentage_sum += $percentage;
                    $percentage_display = number_format($percentage, 2) . '%';
                    
                    echo '<tr>';
                    echo '<td>' . $row_no . '</td>';
                    echo '<td>' . htmlspecialchars($coach_no) . '</td>';
                    echo '<td>' . $target_per_coach . '</td>';
                    echo '<td>' . $passenger_count . '</td>';
                    echo '<td>' . $percentage_display . '</td>';
                    echo '</tr>';
                    
                    $row_no++;
                }
            } else {
                echo '<tr><td colspan="5">No data available</td></tr>';
            }
            
            $avg_percentage = $total_coaches > 0 ? number_format($total_percentage_sum / $total_coaches, 2) . '%' : '0.00%';
                    
                    echo '</tbody>';
                    echo '<tfoot>';
                    echo '<tr>';
                    echo '<td colspan="2"><strong>Total</strong></td>';
                    echo '<td>' . $total_target_sum . '</td>';
                    echo '<td>' . $total_passenger_sum . '</td>';
                    echo '<td>' . $avg_percentage . '</td>';
                    echo '</tr>';
                    echo '</tfoot>';
                    echo '</table>';
        }
    }
    
    echo '<div class="page-break"></div>';
    
    // ============================================
    // 3. DETAILED FEEDBACK REPORTS (All Types for each Train)
    // ============================================
    
    $trains_for_detail = [$up, $down];
    $detail_train_index = 0;
    foreach ($trains_for_detail as $detail_train) {
        if (empty($detail_train)) {
            continue;
        }
        
        // Add page break for second train onwards
        if ($detail_train_index > 0) {
            echo '<div class="page-break"></div>';
        }
        $detail_train_index++;
        
        echo '<div class="section-divider"></div>';
        echo '<h1>Detailed Feedback Report - Train ' . htmlspecialchars($detail_train) . '</h1>';
        echo '<div class="header-info">';
        echo 'Station: ' . htmlspecialchars($station_name) . ' | ';
        echo 'Train: ' . htmlspecialchars($detail_train) . ' | ';
        echo 'From: ' . htmlspecialchars($from_date ?? '') . ' | ';
        echo 'To: ' . htmlspecialchars($to_date ?? '') . ' | ';
        echo 'Grade: ' . htmlspecialchars($grade ?? '');
        echo '</div>';
        
        $coach_types_detail = ['AC', 'NON-AC', 'TTE'];
        foreach ($coach_types_detail as $coach_type) {
            echo '<h3>' . strtoupper($coach_type) . ' Feedback Details</h3>';
            
            // Get questions for this coach type
            $questions = get_questions_data($_SESSION['station_id'], $coach_type);
            $totalQuestions = count($questions);
            $highest_marking = check_highest_marking($_SESSION['station_id']);

            // Find max number of feedbacks
            $feedbackData = getAllFeedbackDetails($detail_train, $grade, $from_date, $to_date, $coach_type);
            $max_feedbacks = 0;
            foreach ($feedbackData as $pd) {
                $feedbacks = getAllFeedbacksForPassenger($pd['id']);
                $count = count($feedbacks);
                if ($count > $max_feedbacks) $max_feedbacks = $count;
            }

            // Build headers
            $headers = ['SR.', 'Date', 'Seat', 'Coach', 'Name', 'PNR'];
            if ($_SESSION['station_id'] != 16) {
                $headers[] = 'Phone';
            }
            $headers = array_merge($headers, ['Train', 'Grade']);

            // Add question headers (shortened)
            foreach ($questions as $q) {
                $question_text = isset($q['eng_question']) ? $q['eng_question'] : (isset($q['hin_question']) ? $q['hin_question'] : 'Q');
                if (strlen($question_text) > 15) {
                    $question_text = substr($question_text, 0, 15) . '...';
                }
                $headers[] = $question_text;
            }
            // Pad if needed
            for ($i = count($questions); $i < $max_feedbacks; $i++) {
                $headers[] = '';
            }
            $headers[] = 'PSI';
            
            echo '<table>';
            echo '<thead><tr>';
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr></thead>';
            echo '<tbody>';
            
            $sr = 1;
            $psi_values = [];

            if (empty($feedbackData)) {
                echo '<tr><td colspan="' . count($headers) . '">No data available</td></tr>';
            } else {
                foreach ($feedbackData as $pd) {
                    $feedbacks = getAllFeedbacksForPassenger($pd['id']);
                    
                    echo '<tr>';
                    echo '<td>' . $sr . '</td>';
                    echo '<td>' . (($_SESSION['station_id'] == 16 || $_SESSION['station_id'] == 23) ? date('d/m/Y', strtotime($pd['created'])) : date('d/m/Y H:i', strtotime($pd['created']))) . '</td>';
                    echo '<td>' . htmlspecialchars($pd['seat_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($pd['coach_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($pd['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($pd['pnr_number']) . '</td>';
                    
                    if ($_SESSION['station_id'] != 16) {
                        echo '<td>' . htmlspecialchars($pd['ph_number']) . '</td>';
                    }
                    
                    echo '<td>' . htmlspecialchars($pd['train_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($pd['grade']) . '</td>';
                    
                    $feedback_sum = 0;
                    foreach ($feedbacks as $fb) {
                        echo '<td>' . htmlspecialchars($fb['value']) . '</td>';
                        $feedback_sum += floatval($fb['value']);
                    }
                    
                    // Pad with empty columns
                    $feedback_count = count($feedbacks);
                    while ($feedback_count < $max_feedbacks) {
                        echo '<td></td>';
                        $feedback_count++;
                    }
                    
                    // Calculate PSI Score
                    $max_total = $totalQuestions * $highest_marking;
                    $psi = ($max_total > 0) ? ($feedback_sum / $max_total) * 100 : 0;
                    $psi_display = number_format($psi, 2);
                    echo '<td><strong>' . $psi_display . '</strong></td>';
                    
                    echo '</tr>';
                    
                    $psi_values[] = $psi;
                    $sr++;
                }
            }
            
            echo '</tbody>';
            
            if (!empty($psi_values)) {
                echo '<tfoot>';
                echo '<tr>';
                echo '<td colspan="' . (count($headers) - 1) . '"><strong>Average PSI</strong></td>';
                echo '<td><strong>' . number_format(array_sum($psi_values) / count($psi_values), 2) . '</strong></td>';
                echo '</tr>';
                echo '</tfoot>';
            }
            
            echo '</table>';
        }
    }
    
    echo '<div style="text-align: center; margin-top: 30px; font-size: 9px; color: #666; padding: 15px; border-top: 2px solid #333;">';
    echo '<strong>Complete Report Generated on:</strong> ' . date('d/m/Y H:i:s') . '<br>';
    echo '<strong>Station:</strong> ' . htmlspecialchars($station_name) . ' | ';
    echo '<strong>Period:</strong> ' . htmlspecialchars($from_date ?? '') . ' to ' . htmlspecialchars($to_date ?? '');
    echo '</div>';
    ?>

</body>
</html>
