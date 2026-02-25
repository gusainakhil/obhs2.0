<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
    $sql .= " ORDER BY created DESC LIMIT 1000";
    $result = mysqli_query($mysqli, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

$spreadsheet = new Spreadsheet();
$sheetIndex = 0;

// ============================================
// SHEET 1: ROUND WISE SUMMARY REPORT
// ============================================

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Round-Wise Summary');

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

// Header Info
$sheet->setCellValue('A1', 'Round-Wise Summary Report');
$sheet->mergeCells('A1:O1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$headerInfo = "Station: $station_name | UP: $up | DOWN: $down | From: $from_date | To: $to_date | Grade: $grade";
$sheet->setCellValue('A2', $headerInfo);
$sheet->mergeCells('A2:O2');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Headers
$sheet->setCellValue('A3', 'No.');
$sheet->setCellValue('B3', 'Train No.');
$sheet->setCellValue('C3', 'AC Coaches');
$sheet->mergeCells('C3:D3');
$sheet->setCellValue('E3', 'Non-AC Coaches');
$sheet->mergeCells('E3:F3');
$sheet->setCellValue('G3', 'AC Feedbacks');
$sheet->mergeCells('G3:H3');
$sheet->setCellValue('I3', 'Non-AC Feedbacks');
$sheet->mergeCells('I3:J3');
$sheet->setCellValue('K3', 'TTE Feedbacks');
$sheet->mergeCells('K3:L3');
$sheet->setCellValue('M3', 'Total Feedbacks');
$sheet->mergeCells('M3:N3');
$sheet->setCellValue('O3', 'Avg. PSI');

$sheet->setCellValue('C4', 'Total');
$sheet->setCellValue('D4', 'Achieved');
$sheet->setCellValue('E4', 'Total');
$sheet->setCellValue('F4', 'Achieved');
$sheet->setCellValue('G4', 'Total');
$sheet->setCellValue('H4', 'Achieved');
$sheet->setCellValue('I4', 'Total');
$sheet->setCellValue('J4', 'Achieved');
$sheet->setCellValue('K4', 'Total');
$sheet->setCellValue('L4', 'Achieved');
$sheet->setCellValue('M4', 'Total');
$sheet->setCellValue('N4', 'Achieved');

$sheet->mergeCells('A3:A4');
$sheet->mergeCells('B3:B4');
$sheet->mergeCells('O3:O4');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A3:O4')->applyFromArray($headerStyle);

// Data
$sheet->setCellValue('A5', '1');
$sheet->setCellValue('B5', $up);
$sheet->setCellValue('C5', $up_ac_total);
$sheet->setCellValue('D5', $upAchieve['ac_achived_coaches']);
$sheet->setCellValue('E5', $up_non_ac_total);
$sheet->setCellValue('F5', $upAchieve['non_ac_achived_coaches']);
$sheet->setCellValue('G5', $up_ac_feed_total);
$sheet->setCellValue('H5', $upAchieve['ac']);
$sheet->setCellValue('I5', $up_non_ac_feed_total);
$sheet->setCellValue('J5', $upAchieve['non_ac']);
$sheet->setCellValue('K5', $up_tte_total);
$sheet->setCellValue('L5', $upAchieve['tte']);
$sheet->setCellValue('M5', $up_total_target);
$sheet->setCellValue('N5', $up_total_achieved);
$sheet->setCellValue('O5', $upFinalPSI . '%');

$sheet->setCellValue('A6', '2');
$sheet->setCellValue('B6', $down);
$sheet->setCellValue('C6', $down_ac_total);
$sheet->setCellValue('D6', $downAchieve['ac_achived_coaches']);
$sheet->setCellValue('E6', $down_non_ac_total);
$sheet->setCellValue('F6', $downAchieve['non_ac_achived_coaches']);
$sheet->setCellValue('G6', $down_ac_feed_total);
$sheet->setCellValue('H6', $downAchieve['ac']);
$sheet->setCellValue('I6', $down_non_ac_feed_total);
$sheet->setCellValue('J6', $downAchieve['non_ac']);
$sheet->setCellValue('K6', $down_tte_total);
$sheet->setCellValue('L6', $downAchieve['tte']);
$sheet->setCellValue('M6', $down_total_target);
$sheet->setCellValue('N6', $down_total_achieved);
$sheet->setCellValue('O6', $downFinalPSI . '%');

// Total
$sheet->setCellValue('A7', 'Total');
$sheet->mergeCells('A7:B7');
$sheet->setCellValue('C7', $up_ac_total + $down_ac_total);
$sheet->setCellValue('D7', $upAchieve['ac_achived_coaches'] + $downAchieve['ac_achived_coaches']);
$sheet->setCellValue('E7', $up_non_ac_total + $down_non_ac_total);
$sheet->setCellValue('F7', $upAchieve['non_ac_achived_coaches'] + $downAchieve['non_ac_achived_coaches']);
$sheet->setCellValue('G7', $up_ac_feed_total + $down_ac_feed_total);
$sheet->setCellValue('H7', $upAchieve['ac'] + $downAchieve['ac']);
$sheet->setCellValue('I7', $up_non_ac_feed_total + $down_non_ac_feed_total);
$sheet->setCellValue('J7', $upAchieve['non_ac'] + $downAchieve['non_ac']);
$sheet->setCellValue('K7', $up_tte_total + $down_tte_total);
$sheet->setCellValue('L7', $upAchieve['tte'] + $downAchieve['tte']);
$sheet->setCellValue('M7', $up_total_target + $down_total_target);
$sheet->setCellValue('N7', $up_total_achieved + $down_total_achieved);
$sheet->setCellValue('O7', $up_down_PSI . '%');

$dataStyle = [
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A5:O7')->applyFromArray($dataStyle);

$footerStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D0D0D0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A7:O7')->applyFromArray($footerStyle);

foreach (range('A', 'O') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ============================================
// SHEETS 2-7: TRAIN REPORTS (AC, NON-AC, TTE for each train)
// ============================================

$trains = [$up, $down];
foreach ($trains as $train) {
    if (empty($train)) continue;
    
    $coach_types = ['AC', 'NON-AC', 'TTE'];
    foreach ($coach_types as $coach_type) {
        $sheetIndex++;
        $newSheet = $spreadsheet->createSheet($sheetIndex);
        $newSheet->setTitle(substr($train . ' ' . $coach_type, 0, 31));
        
        $feedbackData = feedback_calculation_coach_wise($train, $from_date, $to_date, $coach_type, $grade);
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
        
        // Header
        $newSheet->setCellValue('A1', "Train Report - $train - $coach_type");
        $newSheet->mergeCells('A1:E1');
        $newSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $headerInfo = "Station: $station_name | Train: $train | From: $from_date | To: $to_date | Grade: $grade";
        $newSheet->setCellValue('A2', $headerInfo);
        $newSheet->mergeCells('A2:E2');
        $newSheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Column headers
        $newSheet->setCellValue('A3', 'SR. No.');
        $newSheet->setCellValue('B3', 'Coach No.');
        $newSheet->setCellValue('C3', 'Target');
        $newSheet->setCellValue('D3', 'Achieved');
        $newSheet->setCellValue('E3', 'Avg P.S.I');
        $newSheet->getStyle('A3:E3')->applyFromArray($headerStyle);
        
        // Data
        $row = 4;
        $sr = 1;
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
                
                $newSheet->setCellValue('A' . $row, $sr);
                $newSheet->setCellValue('B' . $row, $coach_no);
                $newSheet->setCellValue('C' . $row, $target_per_coach);
                $newSheet->setCellValue('D' . $row, $passenger_count);
                $newSheet->setCellValue('E' . $row, number_format($percentage, 2) . '%');
                
                $sr++;
                $row++;
            }
        } else {
            $newSheet->setCellValue('A' . $row, 'No data available');
            $newSheet->mergeCells('A' . $row . ':E' . $row);
            $row++;
        }
        
        // Total
        $avg_percentage = $total_coaches > 0 ? number_format($total_percentage_sum / $total_coaches, 2) . '%' : '0.00%';
        $newSheet->setCellValue('A' . $row, 'Total');
        $newSheet->mergeCells('A' . $row . ':B' . $row);
        $newSheet->setCellValue('C' . $row, $total_target_sum);
        $newSheet->setCellValue('D' . $row, $total_passenger_sum);
        $newSheet->setCellValue('E' . $row, $avg_percentage);
        
        $newSheet->getStyle('A4:E' . ($row - 1))->applyFromArray($dataStyle);
        $newSheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($footerStyle);
        
        foreach (range('A', 'E') as $col) {
            $newSheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

// ============================================
// SHEETS 8+: DETAILED FEEDBACK REPORTS
// ============================================

foreach ($trains as $train) {
    if (empty($train)) continue;
    
    $coach_types_detail = ['AC', 'NON-AC', 'TTE'];
    foreach ($coach_types_detail as $coach_type) {
        $sheetIndex++;
        $newSheet = $spreadsheet->createSheet($sheetIndex);
        $newSheet->setTitle(substr($train . ' ' . $coach_type . ' Detail', 0, 31));
        
        $questions = get_questions_data($_SESSION['station_id'], $coach_type);
        $totalQuestions = count($questions);
        $highest_marking = check_highest_marking($_SESSION['station_id']);
        
        $feedbackData = getAllFeedbackDetails($train, $grade, $from_date, $to_date, $coach_type);
        
        // Header
        $newSheet->setCellValue('A1', "Detailed Feedback Report - Train $train - $coach_type");
        $newSheet->mergeCells('A1:O1');
        $newSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $headerInfo = "Station: $station_name | Train: $train | From: $from_date | To: $to_date | Grade: $grade";
        $newSheet->setCellValue('A2', $headerInfo);
        $newSheet->mergeCells('A2:O2');
        $newSheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Headers
        $col = 'A';
        $headers = ['SR.', 'Date', 'Seat', 'Coach', 'Name', 'PNR'];
        if ($_SESSION['station_id'] != 16) {
            $headers[] = 'Phone';
        }
        $headers = array_merge($headers, ['Train', 'Grade']);
        
        foreach ($headers as $header) {
            $newSheet->setCellValue($col . '3', $header);
            $col++;
        }
        
        // Add question headers
        foreach ($questions as $q) {
            $question_text = isset($q['eng_question']) ? $q['eng_question'] : (isset($q['hin_question']) ? $q['hin_question'] : 'Q');
            if (strlen($question_text) > 20) {
                $question_text = substr($question_text, 0, 20) . '...';
            }
            $newSheet->setCellValue($col . '3', $question_text);
            $col++;
        }
        $newSheet->setCellValue($col . '3', 'PSI');
        
        $lastCol = $col;
        $headerStyle2 = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $newSheet->getStyle('A3:' . $lastCol . '3')->applyFromArray($headerStyle2);
        
        // Data
        $row = 4;
        $sr = 1;
        $psi_values = [];
        
        if (!empty($feedbackData)) {
            foreach ($feedbackData as $pd) {
                $feedbacks = getAllFeedbacksForPassenger($pd['id']);
                
                $col = 'A';
                $newSheet->setCellValue($col++ . $row, $sr);
                $newSheet->setCellValue($col++ . $row, ($_SESSION['station_id'] == 16 || $_SESSION['station_id'] == 23) ? date('d/m/Y', strtotime($pd['created'])) : date('d/m/Y H:i', strtotime($pd['created'])));
                $newSheet->setCellValue($col++ . $row, $pd['seat_no']);
                $newSheet->setCellValue($col++ . $row, $pd['coach_no']);
                $newSheet->setCellValue($col++ . $row, $pd['name']);
                $newSheet->setCellValue($col++ . $row, $pd['pnr_number']);
                
                if ($_SESSION['station_id'] != 16) {
                    $newSheet->setCellValue($col++ . $row, $pd['ph_number']);
                }
                
                $newSheet->setCellValue($col++ . $row, $pd['train_no']);
                $newSheet->setCellValue($col++ . $row, $pd['grade']);
                
                $feedback_sum = 0;
                foreach ($feedbacks as $fb) {
                    $newSheet->setCellValue($col++ . $row, $fb['value']);
                    $feedback_sum += floatval($fb['value']);
                }
                
                // Calculate PSI
                $max_total = $totalQuestions * $highest_marking;
                $psi = ($max_total > 0) ? ($feedback_sum / $max_total) * 100 : 0;
                $newSheet->setCellValue($col++ . $row, number_format($psi, 2));
                
                $psi_values[] = $psi;
                $sr++;
                $row++;
            }
        } else {
            $newSheet->setCellValue('A' . $row, 'No data available');
            $newSheet->mergeCells('A' . $row . ':' . $lastCol . $row);
            $row++;
        }
        
        // Average PSI
        if (!empty($psi_values)) {
            $newSheet->setCellValue('A' . $row, 'Average PSI');
            $newSheet->mergeCells('A' . $row . ':' . chr(ord($lastCol) - 1) . $row);
            $newSheet->setCellValue($lastCol . $row, number_format(array_sum($psi_values) / count($psi_values), 2));
            $newSheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray($footerStyle);
        }
        
        $newSheet->getStyle('A4:' . $lastCol . ($row - 1))->applyFromArray($dataStyle);
        
        foreach (range('A', $lastCol) as $c) {
            $newSheet->getColumnDimension($c)->setAutoSize(true);
        }
    }
}

// Download
$filename = 'Complete_Report_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
