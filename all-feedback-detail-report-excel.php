<?php
    // DEBUG: Output all unique feed_param values (must be first!)
    if (isset($_GET['debug_params'])) {
        include './includes/connection.php';
        global $mysqli;
        $result = mysqli_query($mysqli, "SELECT DISTINCT feed_param FROM OBHS_feedback");
        $params = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $params[] = $row['feed_param'];
        }
        header('Content-Type: text/plain');
        echo "Unique feed_param values in OBHS_feedback:\n";
        print_r($params);
        exit;
    }

    session_start();
    include './includes/connection.php';
    include './includes/helpers.php';

    require 'vendor/autoload.php'; // For PhpSpreadsheet
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    checkLogin();

    $station_name = getStationName($_SESSION['station_id']);
    $train_no = isset($_GET['train']) ? $_GET['train'] : null;
    // $coach is not used as per user request
    $grade = isset($_GET['grade']) ? $_GET['grade'] : null;
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;
    $coach_type = isset($_GET['coach_type']) ? $_GET['coach_type'] : null;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $row = 1;

    // Header
    $sheet->setCellValue('A'.$row, 'All Feedback Detail Report - ' . $station_name);
    $row++;
    $sheet->setCellValue('A'.$row, 'Train No:');
    $sheet->setCellValue('B'.$row, $train_no);
    $sheet->setCellValue('C'.$row, '');
    $sheet->setCellValue('D'.$row, '');
    $sheet->setCellValue('E'.$row, 'From:');
    $sheet->setCellValue('F'.$row, $from_date);
    $sheet->setCellValue('G'.$row, 'To:');
    $sheet->setCellValue('H'.$row, $to_date);
    $sheet->setCellValue('I'.$row, 'Grade:');
    $sheet->setCellValue('J'.$row, $grade);
    $sheet->setCellValue('K'.$row, 'Coach Type:');
    $sheet->setCellValue('L'.$row, $coach_type);
    $row += 2;

    // Table header (adjust columns as per your table)
    // Find the max number of feedbacks for any passenger to set column count
    $feedbackData = getAllFeedbackDetails($train_no, $grade, $from_date, $to_date, $coach_type);
    $max_feedbacks = 0;
    foreach ($feedbackData as $pd) {
        $feedbacks = getAllFeedbacksForPassenger($pd['id']);
        $count = count($feedbacks);
        if ($count > $max_feedbacks) $max_feedbacks = $count;
    }
    $headers = [
        'SR.', 'Date', 'Seat No', 'Coach No', 'Customer Name', 'Phone', 'PNR No', 'Train No', 'Grade'
    ];
    for ($i = 1; $i <= $max_feedbacks; $i++) {
        $headers[] = 'Feedback ' . $i;
    }
    $headers[] = 'PSI Score';
    $sheet->fromArray($headers, null, 'A'.$row);
    $row++;

    // Fetch feedback data (adjust query as per your DB structure)
    $feedbackData = getAllFeedbackDetails($train_no, $grade, $from_date, $to_date, $coach_type);
    $sr = 1;
    // Get number of questions and highest_marking for PSI calculation
    $questions = get_questions_data($_SESSION['station_id'], $coach_type);
    $totalQuestions = count($questions);
    $highest_marking = check_highest_marking($_SESSION['station_id']);

    foreach ($feedbackData as $pd) {
        $feedbacks = getAllFeedbacksForPassenger($pd['id']);
        $rowData = [
            $sr,
            date('d/m/Y H:i:s', strtotime($pd['created'])),
            $pd['seat_no'],
            $pd['coach_no'],
            $pd['name'],
            $pd['ph_number'],
            $pd['pnr_number'],
            $pd['train_no'],
            $pd['grade']
        ];
        $feedback_sum = 0;
        foreach ($feedbacks as $fb) {
            $rowData[] = $fb['value'];
            $feedback_sum += floatval($fb['value']);
        }
        // Pad with empty columns if this passenger has fewer feedbacks than max
        while (count($rowData) < count($headers) - 1) {
            $rowData[] = '';
        }
        // Calculate PSI Score
        $max_total = $totalQuestions * $highest_marking;
        $psi = ($max_total > 0) ? ($feedback_sum / $max_total) * 100 : 0;
        $psi_display = number_format($psi, 2);
        $rowData[] = $psi_display;
        $sheet->fromArray($rowData, null, 'A'.$row);
        $row++;
        $sr++;
    }
    // Helper function to fetch all feedbacks for a passenger (grouped by feed_param)
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

    // Clean output buffer to prevent corrupt Excel file
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="all-feedback-detail-report.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

    // Helper function to fetch feedback data (implement as per your DB)
    function getAllFeedbackDetails($train_no, $grade, $from_date, $to_date, $coach_type) {
        global $mysqli;
        $where = [];
        if ($train_no) $where[] = "train_no = '" . mysqli_real_escape_string($mysqli, $train_no) . "'";
        // Coach is not considered as per user request
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
