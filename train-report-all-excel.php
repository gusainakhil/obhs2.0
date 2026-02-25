<?php
    session_start();
    include './includes/connection.php';
    include './includes/helpers.php';

    require 'vendor/autoload.php'; // For PhpSpreadsheet
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;

    checkLogin();

    $station_name = getStationName($_SESSION['station_id']);
    $train_no = isset($_GET['train_no']) ? $_GET['train_no'] : null;
    $grade = isset($_GET['grade']) ? $_GET['grade'] : null;
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $row = 1;

    // Main Header
    $sheet->setCellValue('A'.$row, 'All Feedback Detail Report (All Types) - ' . $station_name);
    $sheet->mergeCells('A'.$row.':L'.$row);
    $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
    $sheet->getStyle('A'.$row)->getFont()->getColor()->setARGB('FFFFFFFF');
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
    $row += 2;

    // Define coach types to process
    $coach_types = ['AC', 'NON-AC', 'TTE'];
    
    foreach ($coach_types as $coach_type) {
        // Section Header
        $sheet->setCellValue('A'.$row, strtoupper($coach_type) . ' Feedback Report');
        $sheet->mergeCells('A'.$row.':L'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF059669');
        $sheet->getStyle('A'.$row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row++;
        $row++; // Empty row for spacing

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
            $question_headers[] = isset($q['eng_question']) ? $q['eng_question'] : (isset($q['hin_question']) ? $q['hin_question'] : 'Feedback');
        }
        // Pad if needed
        for ($i = count($question_headers) + 1; $i <= $max_feedbacks; $i++) {
            $question_headers[] = '';
        }
        foreach ($question_headers as $qh) {
            $headers[] = $qh;
        }
        $headers[] = 'PSI Score';

        // Write headers
        $sheet->fromArray($headers, null, 'A'.$row);
        $headerRow = $row;
        $sheet->getStyle('A'.$headerRow.':'.chr(65+count($headers)-1).$headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$headerRow.':'.chr(65+count($headers)-1).$headerRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A'.$headerRow.':'.chr(65+count($headers)-1).$headerRow)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row++;

        // Write data
        $sr = 1;
        $psi_values = [];

        foreach ($feedbackData as $pd) {
            $feedbacks = getAllFeedbacksForPassenger($pd['id']);
            $rowData = [
                $sr,
                ($_SESSION['station_id'] == 16 || $_SESSION['station_id'] == 23) ? date('d/m/Y', strtotime($pd['created'])) : date('d/m/Y H:i:s', strtotime($pd['created'])),
                $pd['seat_no'],
                $pd['coach_no'],
                $pd['name'],
                $pd['pnr_number']
            ];
            if ($_SESSION['station_id'] != 16) {
                $rowData[] = $pd['ph_number'];
            }
            $rowData = array_merge($rowData, [
                $pd['train_no'],
                $pd['grade']
            ]);

            $feedback_sum = 0;
            foreach ($feedbacks as $fb) {
                $rowData[] = $fb['value'];
                $feedback_sum += floatval($fb['value']);
            }

            // Pad with empty columns
            while (count($rowData) < count($headers) - 1) {
                $rowData[] = '';
            }

            // Calculate PSI Score
            $max_total = $totalQuestions * $highest_marking;
            $psi = ($max_total > 0) ? ($feedback_sum / $max_total) * 100 : 0;
            $psi_display = number_format($psi, 2);
            $rowData[] = $psi_display;

            $sheet->fromArray($rowData, null, 'A'.$row);
            $psi_values[] = $psi;
            $row++;
            $sr++;
        }

        // Print average PSI
        if (!empty($psi_values)) {
            $avg_psi = array_sum($psi_values) / count($psi_values);
            $avg_psi_display = number_format($avg_psi, 2);
            $avgRow = array_fill(0, count($headers) - 1, '');
            $avgRow[0] = 'Average PSI:';
            $avgRow[] = $avg_psi_display;
            $sheet->fromArray($avgRow, null, 'A'.$row);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $row++;
        }

        $row += 2; // Space between sections
    }

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

    // Clean output buffer to prevent corrupt Excel file
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="train-report-all-types.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
