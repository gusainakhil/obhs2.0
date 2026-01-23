<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

$station_name = getStationName($_SESSION['station_id']);
$train_no = isset($_GET['train_no']) ? $_GET['train_no'] : null;
$grade = isset($_GET['grade']) ? $_GET['grade'] : null;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// Helper function to fetch all feedbacks for a passenger
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

// Helper function to fetch feedback data
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
    $sql .= " ORDER BY created DESC";
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
    <title>All Feedback Detail Report - Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 15px;
            font-size: 10px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 15px;
            border: 2px solid #000;
            padding: 12px;
            background-color: #4472C4;
            color: white;
        }
        
        .report-header h1 {
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .report-info {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 8px;
            font-size: 11px;
        }
        
        .report-info div {
            margin: 3px 10px;
        }
        
        .section-header {
            background-color: #059669;
            color: white;
            padding: 8px;
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
        }
        
        .first-section {
            margin-top: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }
        
        table th {
            background-color: #4472C4;
            color: white;
            padding: 6px 4px;
            border: 1px solid #000;
            font-size: 9px;
            text-align: center;
            font-weight: bold;
        }
        
        table td {
            padding: 4px 3px;
            border: 1px solid #000;
            text-align: center;
            font-size: 8px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tfoot td {
            font-weight: bold;
            background-color: #d0d0d0;
            font-size: 9px;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .print-btn {
            background-color: #10b981;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            margin: 0 5px;
        }
        
        .print-btn:hover {
            background-color: #059669;
        }
        
        .close-btn {
            background-color: #6b7280;
        }
        
        .close-btn:hover {
            background-color: #4b5563;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 8px;
                font-size: 9px;
            }
            
            .section-header {
                background-color: #059669 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                page-break-after: avoid;
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
            
            table th {
                background-color: #4472C4 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            table tfoot td {
                background-color: #d0d0d0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-header {
                background-color: #4472C4 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
        <button class="print-btn close-btn" onclick="window.close()">✖ Close</button>
    </div>

    <div class="report-header">
        <h1>All Feedback Detail Report (All Types) - <?php echo htmlspecialchars($station_name); ?></h1>
        <div class="report-info">
            <div><strong>Train No:</strong> <?php echo htmlspecialchars($train_no); ?></div>
            <div><strong>From:</strong> <?php echo htmlspecialchars($from_date); ?></div>
            <div><strong>To:</strong> <?php echo htmlspecialchars($to_date); ?></div>
            <div><strong>Grade:</strong> <?php echo htmlspecialchars($grade); ?></div>
        </div>
    </div>

    <?php
    // Define coach types to process
    $coach_types = ['AC', 'NON-AC', 'TTE'];
    $first_section = true;
    
    foreach ($coach_types as $coach_type) {
        ?>
        <div class="section-header <?php echo $first_section ? 'first-section' : ''; ?>">
            <?php echo strtoupper($coach_type); ?> Feedback Report
        </div>
        <?php
        $first_section = false;
        
        // Get questions for this coach type
        $questions = get_questions_data($_SESSION['station_id'], $coach_type);
        $totalQuestions = count($questions);
        $highest_marking = check_highest_marking($_SESSION['station_id']);

        // Find max number of feedbacks
        $feedbackData = getAllFeedbackDetails($train_no, $grade, $from_date, $to_date, $coach_type);
        $max_feedbacks = 0;
        foreach ($feedbackData as $pd) {
            $feedbacks = getAllFeedbacksForPassenger($pd['id']);
            $count = count($feedbacks);
            if ($count > $max_feedbacks) $max_feedbacks = $count;
        }

        // Build headers
        $headers = ['SR.', 'Date', 'Seat No', 'Coach No', 'Customer Name', 'PNR No'];
        if ($_SESSION['station_id'] != 16) {
            $headers[] = 'Phone';
        }
        $headers = array_merge($headers, ['Train No', 'Grade']);

        // Add question headers
        $question_headers = [];
        foreach ($questions as $q) {
            $question_text = isset($q['eng_question']) ? $q['eng_question'] : (isset($q['hin_question']) ? $q['hin_question'] : 'Feedback');
            // Truncate long questions for print
            if (strlen($question_text) > 20) {
                $question_text = substr($question_text, 0, 20) . '...';
            }
            $question_headers[] = $question_text;
        }
        // Pad if needed
        for ($i = count($question_headers) + 1; $i <= $max_feedbacks; $i++) {
            $question_headers[] = '';
        }
        foreach ($question_headers as $qh) {
            $headers[] = $qh;
        }
        $headers[] = 'PSI';
        
        ?>
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $header) { ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php
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
                ?>
            </tbody>
            <?php if (!empty($psi_values)) { ?>
            <tfoot>
                <tr>
                    <td colspan="<?php echo count($headers) - 1; ?>"><strong>Average PSI</strong></td>
                    <td><strong><?php echo number_format(array_sum($psi_values) / count($psi_values), 2); ?></strong></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
        <?php
    }
    ?>
    
    <div style="text-align: center; margin-top: 20px; font-size: 9px; color: #666;">
        Report Generated on: <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>
