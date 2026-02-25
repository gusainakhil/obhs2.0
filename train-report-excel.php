<?php
// train-report-excel.php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

require 'vendor/autoload.php'; // For PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

checkLogin();

$station_name = getStationName($_SESSION['station_id']);
$grade = isset($_GET['grade']) ? $_GET['grade'] : null;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;
$train_no = isset($_GET['train_no']) ? $_GET['train_no'] : null;

// Prepare spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$row = 1;

// Header
$sheet->setCellValue('A'.$row, 'Station: ' . $station_name);
$sheet->setCellValue('B'.$row, 'Train No: ' . $train_no);
$sheet->setCellValue('C'.$row, 'From: ' . $from_date);
$sheet->setCellValue('D'.$row, 'To: ' . $to_date);
$sheet->setCellValue('E'.$row, 'Grade: ' . $grade);
$row += 2;

// AC Feedback Report
$sheet->setCellValue('A'.$row, 'AC Feedback Report');
$row++;
$sheet->fromArray(['SR. No.', 'Coach No.', 'Target Per Coach', 'Achieved No. of Feedbacks', 'Avg P.S.I'], null, 'A'.$row);
$row++;
$acFeedbackData = feedback_calculation_coach_wise($train_no, $from_date, $to_date, 'AC', $grade);
// ...existing code...
$coachList = $acFeedbackData['coach_wise'] ?? [];
$targets = $acFeedbackData['targets'] ?? [];
$ac_coach_target = $targets['ac_coach_target'] ?? 0;
$highest_marking = $acFeedbackData['highest_marking'] ?? 0;
$total_questions = $acFeedbackData['total_questions'] ?? 0;
$row_no = 1;
$total_passenger_sum = 0;
$total_percentage_sum = 0;
$total_coaches = count($coachList);
foreach ($coachList as $coach_no => $data) {
    $feedback_sum = $data['feedback_sum'] ?? 0;
    $passenger_count = $data['total_passenger_count'] ?? 0;
    $total_passenger_sum += $passenger_count;
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
    $sheet->fromArray([
        $row_no,
        $coach_no,
        $ac_coach_target,
        $passenger_count,
        $percentage_display
    ], null, 'A'.$row);
    $row++;
    $row_no++;
}
// Footer
$total_ac_target = $ac_coach_target * $total_coaches;
$final_total_passenger = $total_passenger_sum;
$final_total_percentage = number_format(($total_percentage_sum / max($total_coaches, 1)), 2) . '%';
$sheet->fromArray(['Total', '', $total_ac_target, $final_total_passenger, $final_total_percentage], null, 'A'.$row);
$row += 2;

// NON AC Feedback Report
$sheet->setCellValue('A'.$row, 'NON AC Feedback Report');
$row++;
$sheet->fromArray(['SR. No.', 'Coach No.', 'Feedback Target', 'Achieved No. of Feedbacks', 'Avg P.S.I'], null, 'A'.$row);
$row++;
$nonAcFeedbackData = feedback_calculation_coach_wise($train_no, $from_date, $to_date, 'NON-AC', $grade);
$nonAc_coach_target = $nonAcFeedbackData['targets']['non_ac_coach_target'] ?? 0;
$total_questions = $nonAcFeedbackData['total_questions'] ?? 0;
$highest_marking = $nonAcFeedbackData['highest_marking'] ?? 0;
$coachList = $nonAcFeedbackData['coach_wise'] ?? [];
// ...existing code...
$row_no = 1;

$total_passenger_sum = 0;
$total_percentage_sum = 0;
$total_target_sum = 0;
$total_coaches = count($coachList);
foreach ($coachList as $coach_no => $data) {
    $feedback_sum = $data['feedback_sum'] ?? 0;
    $passenger_count = $data['total_passenger_count'] ?? 0;
    $total_target_sum += $nonAc_coach_target;
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
    $sheet->fromArray([
        $row_no,
        $coach_no,
        $nonAc_coach_target,
        $passenger_count,
        $percentage_display
    ], null, 'A'.$row);
    $row++;
    $row_no++;
}
$avg_percentage = number_format($total_percentage_sum / max($total_coaches, 1), 2) . '%';
$sheet->fromArray(['Total', '', $total_target_sum, $total_passenger_sum, $avg_percentage], null, 'A'.$row);
$row += 2;

// TTE Feedback Report
$sheet->setCellValue('A'.$row, 'TTe Feedback Report');
$row++;
$sheet->fromArray(['SR. No.', 'Coach No.', 'Feedback Target', 'Achieved No. of Feedbacks', 'Avg P.S.I'], null, 'A'.$row);
$row++;
$tteFeedbackData = feedback_calculation_coach_wise($train_no, $from_date, $to_date, 'TTE', $grade);
$tte_target = $tteFeedbackData['targets']['tte_target'] ?? 0;
$total_questions = $tteFeedbackData['total_questions'] ?? 0;
$highest_marking = $tteFeedbackData['highest_marking'] ?? 0;
$coachList = $tteFeedbackData['coach_wise'] ?? [];
$row_no = 1;
$total_passenger_sum = 0;
$total_percentage_sum = 0;
$total_target_sum = 0;
$total_coaches = count($coachList);
foreach ($coachList as $coach_no => $data) {
    $feedback_sum = $data['feedback_sum'] ?? 0;
    $passenger_count = $data['total_passenger_count'] ?? 0;
    $total_target_sum += $tte_target;
    $total_passenger_sum += $passenger_count;
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
    $sheet->fromArray([
        $row_no,
        $coach_no,
        $tte_target,
        $passenger_count,
        $percentage_display
    ], null, 'A'.$row);
    $row++;
    $row_no++;
}
$avg_percentage = number_format($total_percentage_sum / max($total_coaches, 1), 2) . '%';
$sheet->fromArray(['Total', '', $total_target_sum, $total_passenger_sum, $avg_percentage], null, 'A'.$row);

// Output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="train-report.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
