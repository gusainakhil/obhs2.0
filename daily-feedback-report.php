<?php
session_start();
require_once './includes/connection.php';
require_once './includes/helpers.php';

checkLogin();

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_name = getStationName($station_id);
$from_date = trim((string) ($_GET['from_date'] ?? ''));
$to_date = trim((string) ($_GET['to_date'] ?? ''));
$is_submitted = isset($_GET['from_date'], $_GET['to_date']);
$error_message = '';
$report_data = [];
$total_feedbacks = 0;

function dailyFeedbackEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dailyFeedbackValidDate($value)
{
    $date = DateTime::createFromFormat('Y-m-d', (string) $value);
    return $date && $date->format('Y-m-d') === $value;
}

function dailyFeedbackNormalizeCoachType($coach_type)
{
    $coach_type = strtoupper(trim((string) $coach_type));
    if ($coach_type === 'NON AC' || $coach_type === 'NONAC') {
        return 'NON-AC';
    }
    return $coach_type !== '' ? $coach_type : 'OTHER';
}

function dailyFeedbackStatusClass($percentage)
{
    if ($percentage >= 90) return 'status-excellent';
    if ($percentage >= 75) return 'status-verygood';
    if ($percentage >= 60) return 'status-good';
    if ($percentage >= 40) return 'status-average';
    return 'status-poor';
}

function dailyFeedbackQuestionColumns(array $questions, array $passengers)
{
    $columns = [];
    foreach ($questions as $question) {
        $columns[] = [
            'key' => (string) ($question['id'] ?? ''),
            'english' => (string) ($question['eng_question'] ?? ''),
            'hindi' => (string) ($question['hin_question'] ?? ''),
        ];
    }

    if ($columns) return $columns;

    $keys = [];
    foreach ($passengers as $passenger) {
        foreach (array_keys($passenger['feedback'] ?? []) as $key) {
            $keys[(string) $key] = true;
        }
    }
    $keys = array_keys($keys);
    natsort($keys);
    foreach ($keys as $key) {
        $columns[] = ['key' => (string) $key, 'english' => 'Feedback ' . $key, 'hindi' => ''];
    }
    return $columns;
}

function dailyFeedbackOrderedValues(array $feedback, array $columns)
{
    $matched = false;
    foreach ($columns as $column) {
        if (array_key_exists($column['key'], $feedback)) {
            $matched = true;
            break;
        }
    }

    $values = [];
    if ($matched) {
        foreach ($columns as $column) {
            $values[] = array_key_exists($column['key'], $feedback) ? (float) $feedback[$column['key']] : null;
        }
        return $values;
    }

    $sequential = array_values($feedback);
    foreach ($columns as $index => $column) {
        $values[] = isset($sequential[$index]) ? (float) $sequential[$index] : null;
    }
    return $values;
}

if ($is_submitted) {
    if (!dailyFeedbackValidDate($from_date) || !dailyFeedbackValidDate($to_date)) {
        $error_message = 'Please select valid From and To dates.';
    } elseif ($from_date > $to_date) {
        $error_message = 'From date cannot be later than To date.';
    } else {
        $date_from = $from_date . ' 00:00:00';
        $date_to = $to_date . ' 23:59:59';
        $sql = "SELECT p.id AS passenger_id, p.created AS feedback_date, p.seat_no,
                    p.coach_no, p.name, p.pnr_number, p.ph_number, p.train_no,
                    p.grade, p.coach_type, f.feed_param, f.value
                FROM OBHS_passenger p
                INNER JOIN OBHS_feedback f ON f.passenger_id = p.id
                WHERE p.station_id = ? AND p.created BETWEEN ? AND ?
                ORDER BY p.train_no, p.coach_type, p.created, p.id, f.feed_param";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('iss', $station_id, $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $train_no = trim((string) $row['train_no']);
                $train_no = $train_no !== '' ? $train_no : 'Unknown Train';
                $coach_type = dailyFeedbackNormalizeCoachType($row['coach_type']);
                $passenger_id = (string) $row['passenger_id'];

                if (!isset($report_data[$train_no][$coach_type][$passenger_id])) {
                    $report_data[$train_no][$coach_type][$passenger_id] = [
                        'passenger_id' => $passenger_id,
                        'feedback_date' => $row['feedback_date'],
                        'seat_no' => $row['seat_no'],
                        'coach_no' => $row['coach_no'],
                        'name' => $row['name'],
                        'pnr_number' => $row['pnr_number'],
                        'ph_number' => $row['ph_number'],
                        'train_no' => $train_no,
                        'grade' => $row['grade'],
                        'feedback' => [],
                    ];
                    $total_feedbacks++;
                }

                $key = (string) $row['feed_param'];
                $report_data[$train_no][$coach_type][$passenger_id]['feedback'][$key] = (float) $row['value'];
            }
            $stmt->close();
            uksort($report_data, 'strnatcasecmp');
        } else {
            $error_message = 'Unable to prepare the feedback report.';
            error_log('Daily feedback report prepare failed: ' . $mysqli->error);
        }
    }
}

$question_sets = [
    'AC' => get_questions_data($station_id, 'AC'),
    'NON-AC' => get_questions_data($station_id, 'NON-AC'),
    'TTE' => get_questions_data($station_id, 'TTE'),
];
$highest_marking = check_highest_marking($station_id);
$marking_data = get_marking_data($station_id);
$default_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Feedback Report - <?php echo dailyFeedbackEscape($station_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="round_wiseSummary.css">
    <style>
        .daily-filter{display:flex;flex-wrap:wrap;align-items:end;gap:14px;padding:18px;margin-bottom:18px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(15,23,42,.05)}
        .filter-field{min-width:190px}.filter-field label{display:block;margin-bottom:6px;color:#334155;font-size:13px;font-weight:700}.filter-field input{width:100%;padding:9px 11px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#0f172a}
        .report-action{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:9px 15px;border:0;border-radius:8px;background:#0f766e;color:#fff;font-size:13px;font-weight:700;cursor:pointer}.report-action.secondary{background:#0369a1}.report-action.excel{background:#15803d}
        .report-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.overview-card{padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#fff}.overview-card span{display:block;color:#64748b;font-size:12px}.overview-card strong{color:#0f172a;font-size:20px}
        .marking-badges{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:15px}.marking-badge{padding:6px 11px;border-radius:999px;background:#e0f2fe;color:#075985;font-size:12px;font-weight:700}
        .train-section{margin-bottom:26px;border:1px solid #dbe4ee;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 5px 16px rgba(15,23,42,.06)}.train-heading{display:flex;justify-content:space-between;gap:12px;padding:14px 18px;background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff}.train-heading h2{margin:0;font-size:18px;font-weight:800}
        .coach-section{padding:16px;border-top:1px solid #e2e8f0}.coach-section:first-of-type{border-top:0}.coach-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.coach-heading h3{margin:0;color:#1e293b;font-size:15px;font-weight:800}
        .table-wrapper{overflow-x:auto;border-radius:8px}.feedback-table{width:100%;min-width:1100px;border-collapse:collapse}.feedback-table th{padding:9px 8px;border:1px solid #0f766e;background:#0f766e;color:#fff;font-size:12px;line-height:1.25;text-align:center;white-space:nowrap}.feedback-table th small{display:block;margin-top:3px;font-size:10px;font-weight:500}.feedback-table th.feedback-parameter{width:115px;min-width:115px;max-width:115px;padding:6px 5px;vertical-align:middle;white-space:normal}.parameter-name,.parameter-hindi{display:-webkit-box;overflow:hidden;overflow-wrap:anywhere;-webkit-box-orient:vertical}.parameter-name{max-height:2.4em;font-size:10px;line-height:1.2;-webkit-line-clamp:2}.parameter-hindi{max-height:1.2em;margin-top:2px!important;font-size:9px!important;line-height:1.2;-webkit-line-clamp:1}.feedback-table td{padding:8px;border:1px solid #e2e8f0;color:#334155;font-size:12px;text-align:center;white-space:nowrap}.feedback-table tbody tr:nth-child(even){background:#f8fafc}.feedback-table tfoot td{background:#ecfdf5;font-weight:800}
        .customer-link{color:#0369a1;font-weight:700;text-decoration:none}.customer-link:hover{text-decoration:underline}.status-circle{display:inline-flex;align-items:center;justify-content:center;min-width:34px;min-height:28px;padding:3px 7px;border-radius:999px;color:#fff;font-size:11px;font-weight:800}.status-excellent{background:#16a34a}.status-verygood{background:#2563eb}.status-good{background:#65a30d}.status-average{background:#d97706}.status-poor{background:#dc2626}
        .empty-report,.report-error{padding:28px;border:1px dashed #cbd5e1;border-radius:10px;background:#fff;color:#64748b;text-align:center}.report-error{border-color:#fecaca;background:#fef2f2;color:#b91c1c}
        @media(max-width:760px){.filter-field,.daily-filter .report-action{width:100%}.report-overview{grid-template-columns:1fr}.train-heading{flex-direction:column}}
        @media print{@page{size:A4 landscape;margin:8mm}#sidebar,#sidebarOverlay,header,nav,footer,.no-print{display:none!important}.lg\:ml-64{margin-left:0!important}main{padding:0!important}.train-section{break-inside:avoid;box-shadow:none}.coach-section{break-inside:auto}.table-wrapper{overflow:visible}.feedback-table{min-width:0}.feedback-table th,.feedback-table td{padding:4px;font-size:9px}.train-heading,.feedback-table th{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
    </style>
</head>
<body class="bg-slate-50">
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <?php require_once 'includes/sidebar.php'; ?>
    <div class="lg:ml-64 min-h-screen">
        <?php require_once 'includes/header.php'; ?>
        <main class="p-4 lg:p-6">
            <div class="max-w-full mx-auto">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold text-slate-800">Daily Feedback Report</h1>
                    <p class="text-sm text-slate-500 mt-1">Select a date range to view detailed feedback for every train.</p>
                </div>

                <form class="daily-filter no-print" method="get">
                    <div class="filter-field">
                        <label for="fromDate">From Date</label>
                        <input type="date" id="fromDate" name="from_date" required value="<?php echo dailyFeedbackEscape($from_date ?: $default_date); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="toDate">To Date</label>
                        <input type="date" id="toDate" name="to_date" required value="<?php echo dailyFeedbackEscape($to_date ?: $default_date); ?>">
                    </div>
                    <button type="submit" class="report-action"><i class="fas fa-search"></i> View Report</button>
                    <?php if ($is_submitted && !$error_message && $report_data): ?>
                        <button type="button" class="report-action secondary" id="printButton"><i class="fas fa-print"></i> Print</button>
                        <button type="button" class="report-action excel" id="excelButton"><i class="fas fa-file-excel"></i> Export to Excel</button>
                    <?php endif; ?>
                </form>

                <?php if ($error_message): ?>
                    <div class="report-error"><?php echo dailyFeedbackEscape($error_message); ?></div>
                <?php elseif (!$is_submitted): ?>
                    <div class="empty-report">Please select From and To dates to generate the report.</div>
                <?php elseif (!$report_data): ?>
                    <div class="empty-report">No feedback was found for the selected date range.</div>
                <?php else: ?>
                    <div id="dailyFeedbackContent">
                        <div class="report-overview">
                            <div class="overview-card"><span>Station</span><strong><?php echo dailyFeedbackEscape($station_name); ?></strong></div>
                            <div class="overview-card"><span>Report Period</span><strong><?php echo date('d/m/Y', strtotime($from_date)); ?> - <?php echo date('d/m/Y', strtotime($to_date)); ?></strong></div>
                            <div class="overview-card"><span>Trains / Feedbacks</span><strong><?php echo count($report_data); ?> / <?php echo $total_feedbacks; ?></strong></div>
                        </div>

                        <?php if ($marking_data): ?>
                            <div class="marking-badges">
                                <?php foreach ($marking_data as $marking): ?>
                                    <span class="marking-badge"><?php echo dailyFeedbackEscape($marking['category']); ?> = <?php echo dailyFeedbackEscape($marking['value']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($report_data as $train_no => $coach_groups): ?>
                            <?php $train_count = array_sum(array_map('count', $coach_groups)); ?>
                            <section class="train-section">
                                <div class="train-heading">
                                    <h2>Train No: <?php echo dailyFeedbackEscape($train_no); ?></h2>
                                    <span><?php echo $train_count; ?> feedback(s)</span>
                                </div>

                                <?php foreach ($coach_groups as $coach_type => $passengers_by_id): ?>
                                    <?php
                                    $passengers = array_values($passengers_by_id);
                                    $columns = dailyFeedbackQuestionColumns($question_sets[$coach_type] ?? [], $passengers);
                                    $column_count = count($columns);
                                    $psi_total = 0.0;
                                    ?>
                                    <div class="coach-section">
                                        <div class="coach-heading">
                                            <h3><?php echo dailyFeedbackEscape($coach_type); ?> Feedback</h3>
                                            <span class="text-xs text-slate-500"><?php echo count($passengers); ?> response(s)</span>
                                        </div>
                                        <div class="table-wrapper">
                                            <table class="feedback-table">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2">SR.</th><th rowspan="2">Date</th><th rowspan="2">Seat<br>No.</th>
                                                        <th rowspan="2">Coach<br>No.</th><th rowspan="2">Customer Name</th><th rowspan="2">PNR No.</th>
                                                        <?php if ($station_id !== 16): ?><th rowspan="2">Customer Phone</th><?php endif; ?>
                                                        <th rowspan="2">Train<br>No.</th><th rowspan="2">Grade</th>
                                                        <th colspan="<?php echo max(1, $column_count); ?>">Feedback Parameters</th>
                                                        <th rowspan="2">Overall<br>Score</th>
                                                    </tr>
                                                    <tr>
                                                        <?php if ($columns): ?>
                                                            <?php foreach ($columns as $column): ?>
                                                                <th class="feedback-parameter" title="<?php echo dailyFeedbackEscape(trim($column['english'] . ' ' . $column['hindi'])); ?>">
                                                                    <span class="parameter-name"><?php echo dailyFeedbackEscape($column['english']); ?></span>
                                                                    <?php if ($column['hindi']): ?><small class="parameter-hindi"><?php echo dailyFeedbackEscape($column['hindi']); ?></small><?php endif; ?>
                                                                </th>
                                                            <?php endforeach; ?>
                                                        <?php else: ?><th>No Questions Found</th><?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($passengers as $index => $passenger): ?>
                                                        <?php
                                                        $values = dailyFeedbackOrderedValues($passenger['feedback'], $columns);
                                                        $answered = array_values(array_filter($values, static function ($value) { return $value !== null; }));
                                                        $question_total = $column_count ?: count($answered);
                                                        $maximum = $question_total * $highest_marking;
                                                        $psi = $maximum > 0 ? min(100, (array_sum($answered) / $maximum) * 100) : 0;
                                                        $psi_total += $psi;
                                                        $detail_query = http_build_query([
                                                            'passenger_id' => $passenger['passenger_id'], 'feedback_date' => $passenger['feedback_date'],
                                                            'station_id' => $station_id, 'train_no' => $passenger['train_no'], 'coach_no' => $passenger['coach_no'],
                                                            'phone' => $passenger['ph_number'], 'pnr_number' => $passenger['pnr_number'], 'name' => $passenger['name'],
                                                            'grade' => $passenger['grade'], 'seat_no' => $passenger['seat_no'], 'date_from' => $from_date, 'date_to' => $to_date,
                                                        ]);
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo dailyFeedbackEscape(date('d/m/Y H:i:s', strtotime($passenger['feedback_date']))); ?></td>
                                                            <td><?php echo dailyFeedbackEscape($passenger['seat_no']); ?></td>
                                                            <td><?php echo dailyFeedbackEscape($passenger['coach_no']); ?></td>
                                                            <td><a class="customer-link" target="_blank" href="employee-card.php?<?php echo dailyFeedbackEscape($detail_query); ?>"><?php echo dailyFeedbackEscape($passenger['name']); ?></a></td>
                                                            <td><?php echo dailyFeedbackEscape($passenger['pnr_number']); ?></td>
                                                            <?php if ($station_id !== 16): ?><td><?php echo dailyFeedbackEscape($passenger['ph_number']); ?></td><?php endif; ?>
                                                            <td><?php echo dailyFeedbackEscape($passenger['train_no']); ?></td>
                                                            <td><?php echo dailyFeedbackEscape($passenger['grade']); ?></td>
                                                            <?php if ($columns): ?>
                                                                <?php foreach ($values as $value): ?>
                                                                    <td><?php if ($value !== null): ?><span class="status-circle status-excellent"><?php echo dailyFeedbackEscape(rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.')); ?></span><?php else: ?>&mdash;<?php endif; ?></td>
                                                                <?php endforeach; ?>
                                                            <?php else: ?><td>&mdash;</td><?php endif; ?>
                                                            <td><span class="status-circle <?php echo dailyFeedbackStatusClass($psi); ?>"><?php echo number_format($psi, 1); ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="<?php echo ($station_id !== 16 ? 9 : 8) + max(1, $column_count); ?>" style="text-align:right">Total Responses: <?php echo count($passengers); ?></td>
                                                        <td>Avg. <?php echo number_format($passengers ? $psi_total / count($passengers) : 0, 0); ?></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php require_once 'includes/footer.php'; ?>
        </main>
    </div>

    <script>
        (function () {
            const printButton = document.getElementById('printButton');
            const excelButton = document.getElementById('excelButton');
            if (printButton) printButton.addEventListener('click', function () { window.print(); });
            if (excelButton) {
                excelButton.addEventListener('click', function () {
                    const report = document.getElementById('dailyFeedbackContent');
                    if (!report) return;
                    const html = '<html><head><meta charset="UTF-8"></head><body><h2>Daily Feedback Report</h2>' + report.innerHTML + '</body></html>';
                    const url = URL.createObjectURL(new Blob([html], {type: 'application/vnd.ms-excel;charset=utf-8'}));
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'daily_feedback_report_<?php echo dailyFeedbackEscape($from_date); ?>_to_<?php echo dailyFeedbackEscape($to_date); ?>.xls';
                    document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
                });
            }

            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const closeSidebar = document.getElementById('closeSidebar');
            if (menuToggle && sidebar && overlay && closeSidebar) {
                menuToggle.addEventListener('click', function () { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); });
                closeSidebar.addEventListener('click', function () { sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
                overlay.addEventListener('click', function () { sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
            }
        })();
    </script>
</body>
</html>
