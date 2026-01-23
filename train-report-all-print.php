<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

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
    <title>All Feedback Report - Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            font-size: 12px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            border: 2px solid #000;
            padding: 15px;
            background-color: #f0f0f0;
        }
        
        .report-header h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .report-info {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .report-info div {
            margin: 5px;
        }
        
        .section-header {
            background-color: #059669;
            color: white;
            padding: 10px;
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            border-radius: 5px;
        }
        
        .first-section {
            margin-top: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            border: 1px solid #000;
            font-size: 11px;
            text-align: center;
        }
        
        table td {
            padding: 6px 8px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tfoot td {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-btn {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-btn:hover {
            background-color: #059669;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 10px;
            }
            
            .section-header {
                page-break-before: auto;
                background-color: #059669 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            table th {
                background-color: #4472C4 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-header {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
        <button class="print-btn" onclick="window.close()" style="background-color: #6b7280;">✖ Close</button>
    </div>

    <div class="report-header">
        <h1>All Feedback Detail Report - <?php echo htmlspecialchars($station_name); ?></h1>
        <div class="report-info">
            <div><strong>Station:</strong> <?php echo htmlspecialchars($station_name); ?></div>
            <div><strong>Train No:</strong> <?php echo htmlspecialchars($train_no); ?></div>
            <div><strong>From:</strong> <?php echo htmlspecialchars($from_date); ?></div>
            <div><strong>To:</strong> <?php echo htmlspecialchars($to_date); ?></div>
            <div><strong>Grade:</strong> <?php echo htmlspecialchars($grade); ?></div>
        </div>
    </div>

    <?php
    // Define coach types
    $coach_types = ['AC', 'NON-AC', 'TTE'];
    $first_section = true;
    
    foreach ($coach_types as $coach_type) {
        ?>
        <div class="section-header <?php echo $first_section ? 'first-section' : ''; ?>">
            <?php echo strtoupper($coach_type); ?> Feedback Report
        </div>
        <?php
        $first_section = false;
        
        // Fetch feedback data for this coach type
        $feedbackData = feedback_calculation_coach_wise($train_no, $from_date, $to_date, $coach_type, $grade);
        
        $coachList = $feedbackData['coach_wise'] ?? [];
        $targets = $feedbackData['targets'] ?? [];
        $highest_marking = $feedbackData['highest_marking'] ?? 0;
        $total_questions = $feedbackData['total_questions'] ?? 0;
        
        // Determine target based on coach type
        if ($coach_type == 'AC') {
            $target_per_coach = $targets['ac_coach_target'] ?? 0;
        } elseif ($coach_type == 'NON-AC') {
            $target_per_coach = $targets['non_ac_coach_target'] ?? 0;
        } else {
            $target_per_coach = $targets['tte_target'] ?? 0;
        }
        
        $row_no = 1;
        $total_passenger_sum = 0;
        $total_percentage_sum = 0;
        $total_target_sum = 0;
        $total_coaches = count($coachList);
        ?>
        
        <table>
            <thead>
                <tr>
                    <th>SR. No.</th>
                    <th>Coach No.</th>
                    <th>Target Per Coach</th>
                    <th>Achieved Feedbacks</th>
                    <th>Avg P.S.I</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($coachList)) {
                    echo '<tr><td colspan="5">No data available</td></tr>';
                } else {
                    foreach ($coachList as $coach_no => $data) {
                        $feedback_sum = $data['feedback_sum'] ?? 0;
                        $passenger_count = $data['total_passenger_count'] ?? 0;
                        
                        $total_passenger_sum += $passenger_count;
                        $total_target_sum += $target_per_coach;
                        
                        // Percentage calculation
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
                        echo '<td>' . htmlspecialchars($target_per_coach) . '</td>';
                        echo '<td>' . htmlspecialchars($passenger_count) . '</td>';
                        echo '<td>' . $percentage_display . '</td>';
                        echo '</tr>';
                        
                        $row_no++;
                    }
                }
                
                // Calculate footer values
                $avg_percentage = $total_coaches > 0 ? number_format($total_percentage_sum / $total_coaches, 2) . '%' : '0.00%';
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td><?php echo $total_target_sum; ?></td>
                    <td><?php echo $total_passenger_sum; ?></td>
                    <td><?php echo $avg_percentage; ?></td>
                </tr>
            </tfoot>
        </table>
        <?php
    }
    ?>
    
    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #666;">
        Report Generated on: <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>
