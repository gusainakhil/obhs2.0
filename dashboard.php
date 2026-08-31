<?php
session_start();

require_once __DIR__ . '/includes/connection.php';

$debug = false;
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function uiEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function uiFormatNumber($value)
{
    return number_format((int) $value);
}

function uiCompactNumber($value)
{
    $value = (int) $value;

    if ($value >= 1000000) {
        return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
    }

    if ($value >= 1000) {
        return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
    }

    return (string) $value;
}

function uiFormatShortDate($value)
{
    $timestamp = strtotime((string) $value);

    return $timestamp ? date('m/d/Y', $timestamp) : '--';
}

function uiPercentWidth($part, $whole)
{
    $part = (int) $part;
    $whole = (int) $whole;

    if ($whole <= 0) {
        return 0;
    }

    return max(0, min(100, (int) round(($part / $whole) * 100)));
}

function uiScaleWidth($value, $maxValue, $minVisible = 12)
{
    $value = (int) $value;
    $maxValue = (int) $maxValue;
    $minVisible = (int) $minVisible;

    if ($value <= 0 || $maxValue <= 0) {
        return 0;
    }

    return max($minVisible, min(100, (int) round(($value / $maxValue) * 100)));
}

function uiCheckLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }

    if (isset($_SESSION['status']) && (int) $_SESSION['status'] === 1) {
        echo <<<HTML
<div style="background:#ffe4e4;padding:20px;margin:40px auto;width:50%;border:2px solid #ff0000;color:#b30000;font-size:18px;text-align:center;border-radius:8px;">
    Your account is currently disabled.<br>
    Please contact your administrator for assistance.<br>
    Note: If your subscription payment is overdue, please clear the payment to restore full access to your account.
    <br><br>
    <a href="index.php" style="display:inline-block;padding:10px 20px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;">
        Go to Home Page
    </a>
</div>
HTML;
        exit;
    }
}

function uiRenderSubscriptionNotice($type, $icon, $title, $endDate, $message, $buttonLabel, $buttonHref, $dismissible)
{
    $isWarning = $type === 'warning';
    $modalId = $isWarning ? 'subscription-warning-modal' : 'subscription-modal';
    $overlay = $isWarning ? 'rgba(0,0,0,0.5)' : 'rgba(0,0,0,0.6)';
    $cardBackground = $isWarning
        ? 'linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%)'
        : 'linear-gradient(135deg, #ffe4e4 0%, #ffcccc 100%)';
    $cardBorder = $isWarning ? '#ffc107' : '#dc3545';
    $textColor = $isWarning ? '#856404' : '#721c24';
    $buttonBackground = $isWarning ? '#ffc107' : '#dc3545';
    $buttonColor = $isWarning ? '#856404' : '#ffffff';
    $dateSafe = uiEscape($endDate);
    $messageSafe = uiEscape($message);
    $titleSafe = uiEscape($title);
    $buttonLabelSafe = uiEscape($buttonLabel);
    $buttonHrefSafe = uiEscape($buttonHref);

    $closeButton = '';
    if ($dismissible) {
        $closeButton = <<<HTML
<button onclick="document.getElementById('{$modalId}').style.display='none'" style="position:absolute;top:10px;right:15px;background:none;border:none;font-size:28px;color:{$textColor};cursor:pointer;font-weight:bold;">&times;</button>
HTML;
    }

    echo <<<HTML
<div id="{$modalId}" style="position:fixed;top:0;left:0;width:100%;height:100%;background:{$overlay};z-index:9999;display:flex;justify-content:center;align-items:center;">
    <div style="background:{$cardBackground};padding:40px;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.35);text-align:center;max-width:450px;width:90%;border:3px solid {$cardBorder};position:relative;">
        {$closeButton}
        <div style="font-size:60px;margin-bottom:15px;">{$icon}</div>
        <h2 style="color:{$textColor};margin-bottom:15px;font-weight:bold;">{$titleSafe}</h2>
        <p style="color:{$textColor};font-size:16px;margin-bottom:10px;">
            Your subscription expires on<br>
            <strong style="font-size:18px;">{$dateSafe}</strong>
        </p>
        <p style="color:{$textColor};font-size:14px;margin-bottom:20px;">{$messageSafe}</p>
        <a href="{$buttonHrefSafe}" style="display:inline-block;padding:12px 30px;background:{$buttonBackground};color:{$buttonColor};text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;">
            {$buttonLabelSafe}
        </a>
    </div>
</div>
HTML;
}

function uiCheckSubscription($mysqli, $stationId)
{
    $sql = 'SELECT end_date FROM OBHS_users WHERE station_id = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('i', $stationId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $subscriptionEndDate = (string) ($row['end_date'] ?? '');
        $currentTime = time();
        $endTime = strtotime($subscriptionEndDate);

        if ($endTime) {
            $daysDiff = ($endTime - $currentTime) / (60 * 60 * 24);

            if ($daysDiff > 0 && $daysDiff <= 7) {
                uiRenderSubscriptionNotice(
                    'warning',
                    '⚠️',
                    'Subscription Expiring Soon!',
                    $subscriptionEndDate,
                    'Please renew your subscription soon to avoid service interruption. Access will be blocked after 3 days of expiry.',
                    'Continue to Dashboard',
                    'dashboard.php',
                    true
                );
            }

            if ($currentTime > $endTime) {
                $daysExpired = abs($daysDiff);

                if ($daysExpired > 3) {
                    uiRenderSubscriptionNotice(
                        'danger',
                        '🚫',
                        'Access Denied!',
                        $subscriptionEndDate,
                        'Please contact administrator to restore access.',
                        'Go to Home Page',
                        'index.php',
                        false
                    );
                    exit;
                }

                uiRenderSubscriptionNotice(
                    'danger',
                    '⏰',
                    'Subscription Expired!',
                    $subscriptionEndDate,
                    'Please renew your subscription immediately to continue using the service. Otherwise, access will be blocked after 3 days of expiry.',
                    'Go to Home Page',
                    'index.php',
                    true
                );
            }
        }
    }

    $stmt->close();
}

function uiGetStationName($mysqli, $stationId)
{
    $sql = 'SELECT station_name FROM OBHS_station WHERE station_id = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        return 'DB Prepare Error';
    }

    $stmt->bind_param('i', $stationId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stmt->close();

        return (string) $row['station_name'];
    }

    $stmt->close();

    return 'Station Not Found';
}

function dashboard_empty_day_counts($ordered_days)
{
    $data = [];
    foreach ($ordered_days as $day) {
        $data[$day] = 0;
    }

    return $data;
}

function dashboard_activity_summary($mysqli, $source, $station_id, $station_id_text, $ordered_days, $week_date_map)
{
    $sources = [
        'attendance' => [
            'table' => 'base_attendance',
            'date_column' => 'created_at',
            'station_type' => 'i',
        ],
        'photo' => [
            'table' => 'base_photo_report',
            'date_column' => 'created_at',
            'station_type' => 's',
        ],
        'feedback' => [
            'table' => 'OBHS_passenger',
            'date_column' => 'created',
            'station_type' => 's',
        ],
    ];

    $summary = [
        'today' => 0,
        'month' => 0,
        'weekly' => dashboard_empty_day_counts($ordered_days),
    ];

    if (!isset($sources[$source])) {
        return $summary;
    }

    $table = $sources[$source]['table'];
    $date_column = $sources[$source]['date_column'];

    $sql = "
        SELECT
            DATE({$date_column}) AS report_date,
            COUNT(*) AS day_count,
            SUM(CASE
                WHEN {$date_column} >= CURDATE()
                 AND {$date_column} < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN 1 ELSE 0
            END) AS today_count,
            SUM(CASE
                WHEN {$date_column} >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                 AND {$date_column} < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN 1 ELSE 0
            END) AS month_count
        FROM {$table}
        WHERE station_id = ?
          AND {$date_column} >= DATE_SUB(CURDATE(), INTERVAL 31 DAY)
          AND {$date_column} < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        GROUP BY report_date
        ORDER BY report_date ASC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard activity prepare failed: ' . $mysqli->error);
        return $summary;
    }

    if ($sources[$source]['station_type'] === 'i') {
        $station_param = $station_id;
        $stmt->bind_param('i', $station_param);
    } else {
        $station_param = $station_id_text;
        $stmt->bind_param('s', $station_param);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_date = $row['report_date'] ?? '';
        if (isset($week_date_map[$report_date])) {
            $summary['weekly'][$week_date_map[$report_date]] = (int) $row['day_count'];
        }

        $summary['today'] += (int) ($row['today_count'] ?? 0);
        $summary['month'] += (int) ($row['month_count'] ?? 0);
    }

    $stmt->close();

    return $summary;
}

function dashboard_feedback_counts($mysqli, $station_id_text)
{
    $counts = ['today' => 0, 'month' => 0];

    $sql = "
        SELECT
            COUNT(DISTINCT CASE
                WHEN p.created >= CURDATE()
                 AND p.created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN p.id
            END) AS today_count,
            COUNT(DISTINCT CASE
                WHEN p.created >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                 AND p.created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN p.id
            END) AS month_count
        FROM OBHS_feedback f
        INNER JOIN OBHS_passenger p ON p.id = f.passenger_id
        WHERE p.station_id = ?
          AND p.created >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND p.created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard feedback count prepare failed: ' . $mysqli->error);
        return $counts;
    }

    $stmt->bind_param('s', $station_id_text);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $counts['today'] = (int) ($row['today_count'] ?? 0);
        $counts['month'] = (int) ($row['month_count'] ?? 0);
    }

    $stmt->close();

    return $counts;
}

function dashboard_train_activity_counts($mysqli, $station_id, $station_id_text)
{
    $counts = ['today' => 0, 'month' => 0];

    $sql = "
        SELECT
            COUNT(DISTINCT CASE
                WHEN event_date >= CURDATE()
                 AND event_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                THEN train_no
            END) AS today_count,
            COUNT(DISTINCT train_no) AS month_count
        FROM (
            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no, created_at AS event_date
            FROM base_attendance
            WHERE station_id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

            UNION ALL

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no, created AS event_date
            FROM OBHS_passenger
            WHERE station_id = ?
              AND created >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

            UNION ALL

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no, created_at AS event_date
            FROM base_photo_report
            WHERE station_id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ) AS all_trains";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard train count prepare failed: ' . $mysqli->error);
        return $counts;
    }

    $station_param = $station_id;
    $passenger_station_param = $station_id_text;
    $photo_station_param = $station_id_text;
    $stmt->bind_param('iss', $station_param, $passenger_station_param, $photo_station_param);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $counts['today'] = (int) ($row['today_count'] ?? 0);
        $counts['month'] = (int) ($row['month_count'] ?? 0);
    }

    $stmt->close();

    return $counts;
}

function dashboard_train_daily_trend($mysqli, $station_id, $station_id_text, $ordered_days, $week_date_map)
{
    $trend = dashboard_empty_day_counts($ordered_days);

    $sql = "
        SELECT report_date, COUNT(DISTINCT train_no) AS day_count
        FROM (
            SELECT DATE(created_at) AS report_date, train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM base_attendance
            WHERE station_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

            UNION ALL

            SELECT DATE(created) AS report_date, train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM OBHS_passenger
            WHERE station_id = ?
              AND created >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND created < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

            UNION ALL

            SELECT DATE(created_at) AS report_date, train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM base_photo_report
            WHERE station_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ) AS train_events
        GROUP BY report_date
        ORDER BY report_date ASC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard train trend prepare failed: ' . $mysqli->error);
        return $trend;
    }

    $station_param = $station_id;
    $passenger_station_param = $station_id_text;
    $photo_station_param = $station_id_text;
    $stmt->bind_param('iss', $station_param, $passenger_station_param, $photo_station_param);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_date = $row['report_date'] ?? '';
        if (isset($week_date_map[$report_date])) {
            $trend[$week_date_map[$report_date]] = (int) ($row['day_count'] ?? 0);
        }
    }

    $stmt->close();

    return $trend;
}

function dashboard_train_list($mysqli, $station_id_text)
{
    $trains = [];
    $sql = 'SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard train list prepare failed: ' . $mysqli->error);
        return $trains;
    }

    $stmt->bind_param('s', $station_id_text);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trains[] = $row['train_no'];
    }

    $stmt->close();

    return $trains;
}

function dashboard_subscription($mysqli, $user_id)
{
    $subscription = [
        'exists' => false,
        'is_active' => false,
        'days_left' => 0,
        'status_color' => 'red',
        'status_text' => 'EXPIRED',
        'end_date_display' => '--',
        'progress_width' => 0,
    ];

    $sql = 'SELECT start_date, end_date FROM OBHS_users WHERE user_id = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard subscription prepare failed: ' . $mysqli->error);
        return $subscription;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        try {
            $end_date = new DateTimeImmutable($row['end_date']);
            $now = new DateTimeImmutable();
            $days_left = $end_date > $now ? $now->diff($end_date)->days : 0;
            $is_active = $end_date > $now;
            $status_color = $days_left > 30 ? 'green' : ($days_left > 0 ? 'amber' : 'red');

            $subscription = [
                'exists' => true,
                'is_active' => $is_active,
                'days_left' => $days_left,
                'status_color' => $status_color,
                'status_text' => $is_active ? 'ACTIVE' : 'EXPIRED',
                'end_date_display' => $end_date->format('d M Y'),
                'progress_width' => min(100, ($days_left / 365) * 100),
            ];
        } catch (Exception $e) {
            error_log('Dashboard subscription date parse failed: ' . $e->getMessage());
        }
    }

    $stmt->close();

    return $subscription;
}

function dashboard_latest_feedback($mysqli, $station_id_text)
{
    $feedback = [];
    $sql = "
        SELECT train_no, name, pnr_number, ph_number, created AS created_at
        FROM OBHS_passenger
        WHERE station_id = ?
        ORDER BY created DESC
        LIMIT 5";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('Dashboard latest feedback prepare failed: ' . $mysqli->error);
        return $feedback;
    }

    $stmt->bind_param('s', $station_id_text);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }

    $stmt->close();

    return $feedback;
}

function dashboard_chart_rows($ordered_days, $data)
{
    $rows = [];
    foreach ($ordered_days as $day) {
        $rows[] = [$day, isset($data[$day]) ? (int) $data[$day] : 0];
    }

    return $rows;
}

function uiNiceScale($maxValue)
{
    $maxValue = (int) $maxValue;

    if ($maxValue <= 0) {
        return 4;
    }

    $power = pow(10, (int) floor(log10((float) $maxValue)));
    foreach ([1, 2, 5, 10] as $multiplier) {
        $candidate = (int) ($power * $multiplier);
        if ($maxValue <= $candidate) {
            return $candidate;
        }
    }

    return (int) ($power * 10);
}

function uiBuildSparkline($values)
{
    if (empty($values)) {
        $values = [0];
    }

    $scale = uiNiceScale(max($values));
    $baseline = 36;
    $top = 8;
    $drawHeight = $baseline - $top;
    $count = count($values);
    $points = [];
    $circles = [];

    foreach ($values as $index => $value) {
        $x = $count === 1 ? 75 : round(($index * 150) / ($count - 1), 2);
        $y = round($baseline - (((int) $value / $scale) * $drawHeight), 2);

        $points[] = $x . ',' . $y;
        $circles[] = ['x' => $x, 'y' => $y];
    }

    return [
        'points' => implode(' ', $points),
        'circles' => $circles,
    ];
}

function uiBuildChart($rows)
{
    if (empty($rows)) {
        $rows = [['-', 0]];
    }

    $days = [];
    $values = [];
    foreach ($rows as $row) {
        $days[] = (string) ($row[0] ?? '');
        $values[] = (int) ($row[1] ?? 0);
    }

    $count = count($values);
    $scale = uiNiceScale(max($values));
    $startX = 18;
    $endX = 378;
    $baseline = 112;
    $top = 28;
    $drawHeight = $baseline - $top;
    $step = $count > 1 ? (($endX - $startX) / ($count - 1)) : 0;
    $barWidth = $count > 1 ? min(22, max(12, (int) floor($step * 0.27))) : 16;
    $points = [];
    $dots = [];
    $bars = [];

    foreach ($values as $index => $value) {
        $x = $count === 1 ? (($startX + $endX) / 2) : round($startX + ($step * $index), 2);
        $height = round((((int) $value / $scale) * $drawHeight), 2);
        $y = round($baseline - $height, 2);

        $points[] = $x . ',' . $y;
        $dots[] = ['x' => $x, 'y' => $y];
        $bars[] = [
            'x' => round($x - ($barWidth / 2), 2),
            'y' => $y,
            'width' => $barWidth,
            'height' => max(0, round($height, 2)),
        ];
    }

    $weekTotal = array_sum($values);
    $peakValue = max($values);
    $peakDay = '-';
    if ($peakValue > 0) {
        $peakIndex = array_search($peakValue, $values, true);
        $peakDay = isset($days[$peakIndex]) ? $days[$peakIndex] : '-';
    }

    $axisLabels = [];
    foreach ([1, 0.75, 0.5, 0.25, 0] as $ratio) {
        $axisLabels[] = uiCompactNumber((int) round($scale * $ratio));
    }

    return [
        'days' => $days,
        'bars' => $bars,
        'polyline_points' => implode(' ', $points),
        'fill_points' => implode(' ', $points) . ' ' . $endX . ',' . $baseline . ' ' . $startX . ',' . $baseline,
        'dots' => $dots,
        'axis_labels' => $axisLabels,
        'week_total' => $weekTotal,
        'peak_day' => $peakDay,
        'average' => $count > 0 ? (int) round($weekTotal / $count) : 0,
    ];
}

function uiBuildSeriesState($rows)
{
    $values = [];
    foreach ($rows as $row) {
        $values[] = (int) ($row[1] ?? 0);
    }

    return [
        'sparkline' => uiBuildSparkline($values),
        'chart' => uiBuildChart($rows),
    ];
}

function uiBuildConicGradient($segments, $emptyColor = '#173142')
{
    $total = 0.0;
    foreach ($segments as $segment) {
        $total += max(0, (float) ($segment['value'] ?? 0));
    }

    if ($total <= 0) {
        return 'background: conic-gradient(' . $emptyColor . ' 0 100%)';
    }

    $parts = [];
    $current = 0.0;

    foreach ($segments as $segment) {
        $value = max(0, (float) ($segment['value'] ?? 0));
        $start = $current;
        $current += ($value / $total) * 100;
        $parts[] = $segment['color'] . ' ' . round($start, 2) . '% ' . round($current, 2) . '%';
    }

    if ($current < 100) {
        $parts[] = $emptyColor . ' ' . round($current, 2) . '% 100%';
    }

    return 'background: conic-gradient(' . implode(', ', $parts) . ')';
}

function uiSubscriptionPalette($subscription)
{
    $palettes = [
        'green' => [
            'text' => '#13dd5b',
            'badge' => '#05b94d',
            'shadow' => '0 0 16px #0c5',
            'progress' => '#20d962',
        ],
        'amber' => [
            'text' => '#ffb020',
            'badge' => '#f59e0b',
            'shadow' => '0 0 16px rgba(245,158,11,.7)',
            'progress' => '#f59e0b',
        ],
        'red' => [
            'text' => '#ff5468',
            'badge' => '#d91b28',
            'shadow' => '0 0 16px rgba(217,27,40,.7)',
            'progress' => '#d91b28',
        ],
    ];

    $statusColor = isset($subscription['status_color']) ? $subscription['status_color'] : 'red';

    return isset($palettes[$statusColor]) ? $palettes[$statusColor] : $palettes['red'];
}

uiCheckLogin();

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_id_text = (string) $station_id;
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$username = trim((string) ($_SESSION['username'] ?? ''));
$organisation_name = trim((string) ($_SESSION['organisation_name'] ?? ''));

uiCheckSubscription($mysqli, $station_id);

$station_name = uiGetStationName($mysqli, $station_id);
$user_display_name = $username !== '' ? $username : ($organisation_name !== '' ? $organisation_name : 'User');
$profile_label = $organisation_name !== '' ? $organisation_name : $station_name;
$avatar_text = strtoupper(substr($user_display_name, 0, 1));

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$ordered_days = [];
$week_date_map = [];
$today = new DateTimeImmutable('today');
for ($i = 6; $i >= 0; $i--) {
    $date = $today->modify("-{$i} days");
    $day_name = $date->format('D');
    $ordered_days[] = $day_name;
    $week_date_map[$date->format('Y-m-d')] = $day_name;
}

$trains = dashboard_train_list($mysqli, $station_id_text);
$total_trains = count($trains);

$attendance_summary = dashboard_activity_summary($mysqli, 'attendance', $station_id, $station_id_text, $ordered_days, $week_date_map);
$photo_summary = dashboard_activity_summary($mysqli, 'photo', $station_id, $station_id_text, $ordered_days, $week_date_map);
$feedback_summary = dashboard_activity_summary($mysqli, 'feedback', $station_id, $station_id_text, $ordered_days, $week_date_map);
$train_trend = dashboard_train_daily_trend($mysqli, $station_id, $station_id_text, $ordered_days, $week_date_map);

$counts = dashboard_feedback_counts($mysqli, $station_id_text);
$train_counts = dashboard_train_activity_counts($mysqli, $station_id, $station_id_text);
$subscription = dashboard_subscription($mysqli, $user_id);
$latest_feedback = dashboard_latest_feedback($mysqli, $station_id_text);

$attendance_chart_rows = dashboard_chart_rows($ordered_days, $attendance_summary['weekly']);
$photo_chart_rows = dashboard_chart_rows($ordered_days, $photo_summary['weekly']);
$feedback_chart_rows = dashboard_chart_rows($ordered_days, $feedback_summary['weekly']);
$train_chart_rows = dashboard_chart_rows($ordered_days, $train_trend);

$feedback_series = uiBuildSeriesState($feedback_chart_rows);
$attendance_series = uiBuildSeriesState($attendance_chart_rows);
$photo_series = uiBuildSeriesState($photo_chart_rows);
$train_series = uiBuildSeriesState($train_chart_rows);

$today_feedback = (int) $counts['today'];
$month_feedback = (int) $counts['month'];
$today_attendance = (int) $attendance_summary['today'];
$month_attendance = (int) $attendance_summary['month'];
$today_photos = (int) $photo_summary['today'];
$month_photos = (int) $photo_summary['month'];
$subscription_palette = uiSubscriptionPalette($subscription);
$subscription_icon = (!empty($subscription['exists']) && !empty($subscription['is_active'])) ? '✓' : '!';
$subscription_remaining_label = (!empty($subscription['exists']) && !empty($subscription['is_active']))
    ? round((float) $subscription['progress_width']) . '% Remaining'
    : 'Renewal Required';
$latest_feedback_total = count($latest_feedback);
$display_date = date('F d, Y');
$display_year = date('Y');
$today_total_activity = $today_feedback + $today_attendance + $today_photos;
$ai_message = $today_total_activity > 0
    ? 'Today: ' . uiFormatNumber($today_feedback) . ' feedback entries, ' . uiFormatNumber($today_attendance) . ' attendance captures, and ' . uiFormatNumber($today_photos) . ' cleanliness photos at ' . $station_name . '.'
    : 'No dashboard activity has been captured yet today for ' . $station_name . '.';
$health_progress = max(0, min(100, (int) round((float) $subscription['progress_width'])));
$feedback_week_total = (int) $feedback_series['chart']['week_total'];
$attendance_week_total = (int) $attendance_series['chart']['week_total'];
$photo_week_total = (int) $photo_series['chart']['week_total'];
$week_total_activity = $feedback_week_total + $attendance_week_total + $photo_week_total;
$month_total_activity = $month_feedback + $month_attendance + $month_photos;
$days_elapsed_in_month = max(1, (int) $today->format('j'));
$feedback_month_avg = (int) round($month_feedback / $days_elapsed_in_month);
$attendance_month_avg = (int) round($month_attendance / $days_elapsed_in_month);
$photo_month_avg = (int) round($month_photos / $days_elapsed_in_month);
$today_feedback_share = uiPercentWidth($today_feedback, max(1, $today_total_activity));
$today_attendance_share = uiPercentWidth($today_attendance, max(1, $today_total_activity));
$today_photo_share = uiPercentWidth($today_photos, max(1, $today_total_activity));
$train_coverage_share = uiPercentWidth($train_counts['today'], max(1, $total_trains));
$today_activity_donut_style = uiBuildConicGradient([
    ['color' => '#19c553', 'value' => $today_feedback],
    ['color' => '#f3c11a', 'value' => $today_attendance],
    ['color' => '#ff8b11', 'value' => $today_photos],
]);
$snapshot_metrics = [
    [
        'name' => 'Feedback',
        'today' => $today_feedback,
        'month' => $month_feedback,
        'insight' => 'Peak ' . $feedback_series['chart']['peak_day'],
        'detail' => 'Avg ' . uiFormatNumber($feedback_month_avg) . '/day',
        'color' => '#19c553',
    ],
    [
        'name' => 'Attendance',
        'today' => $today_attendance,
        'month' => $month_attendance,
        'insight' => 'Peak ' . $attendance_series['chart']['peak_day'],
        'detail' => 'Avg ' . uiFormatNumber($attendance_month_avg) . '/day',
        'color' => '#b764ff',
    ],
    [
        'name' => 'Photos',
        'today' => $today_photos,
        'month' => $month_photos,
        'insight' => 'Peak ' . $photo_series['chart']['peak_day'],
        'detail' => 'Avg ' . uiFormatNumber($photo_month_avg) . '/day',
        'color' => '#ffab18',
    ],
    [
        'name' => 'Train Coverage',
        'today' => $train_counts['today'],
        'month' => $train_counts['month'],
        'insight' => $train_coverage_share . '% running today',
        'detail' => uiFormatNumber($total_trains) . ' configured trains',
        'color' => '#24b8ff',
    ],
];
$snapshot_max_today = 0;
$snapshot_max_month = 0;
foreach ($snapshot_metrics as $snapshot_metric) {
    $snapshot_max_today = max($snapshot_max_today, (int) $snapshot_metric['today']);
    $snapshot_max_month = max($snapshot_max_month, (int) $snapshot_metric['month']);
}

?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>OBHS Futuristic Dashboard - <?php echo uiEscape($station_name); ?></title>
<link rel="stylesheet" href="dashboard-v2-assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main>
    <header class="topbar">
      <div class="welcome">
        <button id="menuBtn" class="mobile-menu" type="button">☰</button>
        <div>
          <h1>Welcome back, <?php echo uiEscape($user_display_name); ?></h1>
          <p><?php echo uiFormatNumber($train_counts['today']); ?> trains active today at <?php echo uiEscape($station_name); ?>.</p>
        </div>
      </div>
      <div class="top-actions">
        <button type="button" class="date-btn">▣ &nbsp; <?php echo uiEscape($display_date); ?> &nbsp;</button>
        <!--<button type="button" class="bell">♢<span><?php echo uiEscape($latest_feedback_total); ?></span></button>-->
        <button type="button" class="signout" onclick="window.location.href='logout.php'">↪ &nbsp; Sign Out</button>
      </div>
    </header>

    <div class="dashboard">

      <section class="content">

        <div class="kpis">
          <article class="kpi green">
            <div class="icon-wrap">💬</div>
            <div class="kmain"><span>FEEDBACK COUNT</span><small>Today</small><strong><?php echo uiFormatNumber($today_feedback); ?></strong></div>
            <svg class="mini-chart" viewBox="0 0 150 44" preserveAspectRatio="none">
              <polyline points="<?php echo uiEscape($feedback_series['sparkline']['points']); ?>"/>
              <g>
                <?php foreach ($feedback_series['sparkline']['circles'] as $circle): ?>
                <circle cx="<?php echo uiEscape($circle['x']); ?>" cy="<?php echo uiEscape($circle['y']); ?>" r="2"/>
                <?php endforeach; ?>
              </g>
            </svg>
            <div class="month"><small>This Month</small><b><?php echo uiFormatNumber($month_feedback); ?></b></div>
          </article>

          <article class="kpi purple">
            <div class="icon-wrap">👥</div>
            <div class="kmain"><span>ATTENDANCE</span><small>Today</small><strong><?php echo uiFormatNumber($today_attendance); ?></strong></div>
            <svg class="mini-chart" viewBox="0 0 150 44" preserveAspectRatio="none">
              <polyline points="<?php echo uiEscape($attendance_series['sparkline']['points']); ?>"/>
              <g>
                <?php foreach ($attendance_series['sparkline']['circles'] as $circle): ?>
                <circle cx="<?php echo uiEscape($circle['x']); ?>" cy="<?php echo uiEscape($circle['y']); ?>" r="2"/>
                <?php endforeach; ?>
              </g>
            </svg>
            <div class="month"><small>This Month</small><b><?php echo uiFormatNumber($month_attendance); ?></b></div>
          </article>

          <article class="kpi orange">
            <div class="icon-wrap">📷</div>
            <div class="kmain"><span>CLEANLINESS PICS</span><small>Today</small><strong><?php echo uiFormatNumber($today_photos); ?></strong></div>
            <svg class="mini-chart" viewBox="0 0 150 44" preserveAspectRatio="none">
              <polyline points="<?php echo uiEscape($photo_series['sparkline']['points']); ?>"/>
              <g>
                <?php foreach ($photo_series['sparkline']['circles'] as $circle): ?>
                <circle cx="<?php echo uiEscape($circle['x']); ?>" cy="<?php echo uiEscape($circle['y']); ?>" r="2"/>
                <?php endforeach; ?>
              </g>
            </svg>
            <div class="month"><small>This Month</small><b><?php echo uiFormatNumber($month_photos); ?></b></div>
          </article>

          <article class="kpi blue">
            <div class="icon-wrap">🚆</div>
            <div class="kmain"><span>TRAINS RUNNING</span><small>Today</small><strong><?php echo uiFormatNumber($train_counts['today']); ?></strong></div>
            <svg class="mini-chart" viewBox="0 0 150 44" preserveAspectRatio="none">
              <polyline points="<?php echo uiEscape($train_series['sparkline']['points']); ?>"/>
              <g>
                <?php foreach ($train_series['sparkline']['circles'] as $circle): ?>
                <circle cx="<?php echo uiEscape($circle['x']); ?>" cy="<?php echo uiEscape($circle['y']); ?>" r="2"/>
                <?php endforeach; ?>
              </g>
            </svg>
            <div class="month"><small>This Month</small><b><?php echo uiFormatNumber($train_counts['month']); ?></b></div>
          </article>

          <article class="kpi cyan">
            <div class="icon-wrap">🚉</div>
            <div class="kmain"><span>TOTAL TRAINS</span><small>Configured</small><strong><?php echo uiFormatNumber($total_trains); ?></strong></div>
            <svg class="mini-chart" viewBox="0 0 150 44" preserveAspectRatio="none">
              <polyline points="<?php echo uiEscape($train_series['sparkline']['points']); ?>"/>
              <g>
                <?php foreach ($train_series['sparkline']['circles'] as $circle): ?>
                <circle cx="<?php echo uiEscape($circle['x']); ?>" cy="<?php echo uiEscape($circle['y']); ?>" r="2"/>
                <?php endforeach; ?>
              </g>
            </svg>
            <div class="month"><small>Station ID</small><b><?php echo uiEscape($station_id); ?></b></div>
          </article>
        </div>

        <div class="middle">
          <section class="panel subscription">
            <div class="panel-head">◫ &nbsp; SUBSCRIPTION STATUS</div>
            <div class="status-box">
              <div><small>Status</small><strong style="color: <?php echo uiEscape($subscription_palette['text']); ?>;"><?php echo uiEscape($subscription['status_text']); ?></strong></div>
              <div class="ok-badge" style="background: <?php echo uiEscape($subscription_palette['badge']); ?>; box-shadow: <?php echo uiEscape($subscription_palette['shadow']); ?>;"><?php echo uiEscape($subscription_icon); ?></div>
            </div>
            <div class="sub-meta">
              <div><small>Days Left</small><b><?php echo uiEscape($subscription['days_left']); ?></b></div>
              <div><small>Expires On</small><b><?php echo uiEscape($subscription['end_date_display']); ?></b></div>
            </div>
            <div class="progress"><span style="width: <?php echo uiEscape($subscription['progress_width']); ?>%; background: <?php echo uiEscape($subscription_palette['progress']); ?>;"></span></div>
            <div class="remaining"><?php echo uiEscape($subscription_remaining_label); ?></div>
          </section>

          <section class="panel latest">
            <div class="panel-head">☆ &nbsp; LATEST FEEDBACK </div>
            <div class="latest-scroll">
            <?php if ($latest_feedback_total > 0): ?>
              <?php foreach ($latest_feedback as $row): ?>
            <div class="feed">
              <div>
                <a><?php echo uiEscape(($row['name'] ?? 'NA') . ' - ' . ($row['ph_number'] ?? 'NA')); ?></a>
                <span>PNR: <?php echo uiEscape(($row['pnr_number'] ?? 'NA') . ' - ' . ($row['train_no'] ?? 'NA')); ?></span>
              </div>
              <time><?php echo uiEscape(uiFormatShortDate($row['created_at'] ?? '')); ?></time>
            </div>
              <?php endforeach; ?>
            <?php else: ?>
            <div class="feed">
              <div>
                <a>No feedback yet</a>
                <span>New passenger feedback will appear here.</span>
              </div>
              <time>--</time>
            </div>
            <?php endif; ?>
            </div>
          </section>

          <section class="panel trains">
            <div class="panel-head">☷ &nbsp; TRAINS LIST (TOTAL: <?php echo uiEscape($total_trains); ?>) <button type="button" onclick="window.location.href='view-feedback-target.php'">View All</button></div>
            <div class="trains-scroll">
            <div class="train-grid">
              <?php if ($total_trains > 0): ?>
                <?php foreach ($trains as $index => $train_no): ?>
              <span<?php echo $index === 0 ? ' class=""' : ''; ?>><?php echo uiEscape($train_no); ?></span>
                <?php endforeach; ?>
              <?php else: ?>
              <span style="grid-column:1/-1;height:auto;padding:8px 10px;text-align:center;">No trains found. Add feedback targets to see trains.</span>
              <?php endif; ?>
            </div>
            </div>
            <!--<div class="train-slider"><i></i></div>-->
          </section>
        </div>

        <div class="charts">
          <section class="panel chart green-chart">
            <div class="panel-head"><span>▧</span> WEEKLY FEEDBACK COUNT   </div>
            <div class="chart-area">
              <div class="yaxis">
                <?php foreach ($feedback_series['chart']['axis_labels'] as $label): ?>
                <span><?php echo uiEscape($label); ?></span>
                <?php endforeach; ?>
              </div>
              <svg viewBox="0 0 420 120" preserveAspectRatio="none">
                <defs><linearGradient id="fill1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#26ed75" stop-opacity=".45"/><stop offset="1" stop-color="#26ed75" stop-opacity=".03"/></linearGradient></defs>
                <g class="bars">
                  <?php foreach ($feedback_series['chart']['bars'] as $bar): ?>
                  <rect x="<?php echo uiEscape($bar['x']); ?>" y="<?php echo uiEscape($bar['y']); ?>" width="<?php echo uiEscape($bar['width']); ?>" height="<?php echo uiEscape($bar['height']); ?>"/>
                  <?php endforeach; ?>
                </g>
                <polygon fill="url(#fill1)" points="<?php echo uiEscape($feedback_series['chart']['fill_points']); ?>"/>
                <polyline points="<?php echo uiEscape($feedback_series['chart']['polyline_points']); ?>"/>
                <g class="dots">
                  <?php foreach ($feedback_series['chart']['dots'] as $dot): ?>
                  <circle cx="<?php echo uiEscape($dot['x']); ?>" cy="<?php echo uiEscape($dot['y']); ?>" r="5"/>
                  <?php endforeach; ?>
                </g>
              </svg>
            </div>
            <div class="days">
              <?php foreach ($feedback_series['chart']['days'] as $day): ?>
              <span><?php echo uiEscape($day); ?></span>
              <?php endforeach; ?>
            </div>
            <div class="chart-meta"><div><b><?php echo uiFormatNumber($feedback_series['chart']['week_total']); ?></b><span>This Week</span></div><div><b><?php echo uiEscape($feedback_series['chart']['peak_day']); ?></b><span>Peak Day</span></div><div><b><?php echo uiFormatNumber($feedback_series['chart']['average']); ?></b><span>Avg / Day</span></div></div>
          </section>

          <section class="panel chart purple-chart">
            <div class="panel-head"><span>▧</span> WEEKLY ATTENDANCE COUNT   </div>
            <div class="chart-area">
              <div class="yaxis">
                <?php foreach ($attendance_series['chart']['axis_labels'] as $label): ?>
                <span><?php echo uiEscape($label); ?></span>
                <?php endforeach; ?>
              </div>
              <svg viewBox="0 0 420 120" preserveAspectRatio="none">
                <defs><linearGradient id="fill2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b765ff" stop-opacity=".45"/><stop offset="1" stop-color="#b765ff" stop-opacity=".03"/></linearGradient></defs>
                <g class="bars">
                  <?php foreach ($attendance_series['chart']['bars'] as $bar): ?>
                  <rect x="<?php echo uiEscape($bar['x']); ?>" y="<?php echo uiEscape($bar['y']); ?>" width="<?php echo uiEscape($bar['width']); ?>" height="<?php echo uiEscape($bar['height']); ?>"/>
                  <?php endforeach; ?>
                </g>
                <polygon fill="url(#fill2)" points="<?php echo uiEscape($attendance_series['chart']['fill_points']); ?>"/>
                <polyline points="<?php echo uiEscape($attendance_series['chart']['polyline_points']); ?>"/>
                <g class="dots">
                  <?php foreach ($attendance_series['chart']['dots'] as $dot): ?>
                  <circle cx="<?php echo uiEscape($dot['x']); ?>" cy="<?php echo uiEscape($dot['y']); ?>" r="5"/>
                  <?php endforeach; ?>
                </g>
              </svg>
            </div>
            <div class="days">
              <?php foreach ($attendance_series['chart']['days'] as $day): ?>
              <span><?php echo uiEscape($day); ?></span>
              <?php endforeach; ?>
            </div>
            <div class="chart-meta"><div><b><?php echo uiFormatNumber($attendance_series['chart']['week_total']); ?></b><span>This Week</span></div><div><b><?php echo uiEscape($attendance_series['chart']['peak_day']); ?></b><span>Peak Day</span></div><div><b><?php echo uiFormatNumber($attendance_series['chart']['average']); ?></b><span>Avg / Day</span></div></div>
          </section>

          <section class="panel chart orange-chart">
            <div class="panel-head"><span>▧</span> WEEKLY CLEANLINESS PHOTOS COUNT   </div>
            <div class="chart-area">
              <div class="yaxis">
                <?php foreach ($photo_series['chart']['axis_labels'] as $label): ?>
                <span><?php echo uiEscape($label); ?></span>
                <?php endforeach; ?>
              </div>
              <svg viewBox="0 0 420 120" preserveAspectRatio="none">
                <defs><linearGradient id="fill3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffae1c" stop-opacity=".45"/><stop offset="1" stop-color="#ffae1c" stop-opacity=".03"/></linearGradient></defs>
                <g class="bars">
                  <?php foreach ($photo_series['chart']['bars'] as $bar): ?>
                  <rect x="<?php echo uiEscape($bar['x']); ?>" y="<?php echo uiEscape($bar['y']); ?>" width="<?php echo uiEscape($bar['width']); ?>" height="<?php echo uiEscape($bar['height']); ?>"/>
                  <?php endforeach; ?>
                </g>
                <polygon fill="url(#fill3)" points="<?php echo uiEscape($photo_series['chart']['fill_points']); ?>"/>
                <polyline points="<?php echo uiEscape($photo_series['chart']['polyline_points']); ?>"/>
                <g class="dots">
                  <?php foreach ($photo_series['chart']['dots'] as $dot): ?>
                  <circle cx="<?php echo uiEscape($dot['x']); ?>" cy="<?php echo uiEscape($dot['y']); ?>" r="5"/>
                  <?php endforeach; ?>
                </g>
              </svg>
            </div>
            <div class="days">
              <?php foreach ($photo_series['chart']['days'] as $day): ?>
              <span><?php echo uiEscape($day); ?></span>
              <?php endforeach; ?>
            </div>
            <div class="chart-meta"><div><b><?php echo uiFormatNumber($photo_series['chart']['week_total']); ?></b><span>This Week</span></div><div><b><?php echo uiEscape($photo_series['chart']['peak_day']); ?></b><span>Peak Day</span></div><div><b><?php echo uiFormatNumber($photo_series['chart']['average']); ?></b><span>Avg / Day</span></div></div>
          </section>
        </div>

        <div class="bottom">
          <section class="panel coach">
            <div class="panel-head"><span class="green-t">▧</span> TODAY'S OPERATIONS MIX</div>
            <div class="coach-content">
              <div class="donut-wrap">
                <div class="donut" style="<?php echo uiEscape($today_activity_donut_style); ?>"></div>
                <div class="donut-center"><strong><?php echo uiFormatNumber($today_total_activity); ?></strong><span>Records Today</span></div>
              </div>
              <div class="legend">
                <div><i class="ex"></i><span>Feedback Entries</span><b><?php echo uiFormatNumber($today_feedback); ?> (<?php echo uiEscape($today_feedback_share); ?>%)</b></div>
                <div><i class="gd"></i><span>Attendance Captures</span><b><?php echo uiFormatNumber($today_attendance); ?> (<?php echo uiEscape($today_attendance_share); ?>%)</b></div>
                <div><i class="av"></i><span>Cleanliness Photos</span><b><?php echo uiFormatNumber($today_photos); ?> (<?php echo uiEscape($today_photo_share); ?>%)</b></div>
                <div><i class="po"></i><span>Train Coverage</span><b><?php echo uiFormatNumber($train_counts['today']); ?> / <?php echo uiFormatNumber($total_trains); ?> (<?php echo uiEscape($train_coverage_share); ?>%)</b></div>
              </div>
            </div>
          </section>

          <section class="panel targets">
            <div class="panel-head"><span class="green-t">▦</span> ACTIONABLE OPS SNAPSHOT</div>
            <div class="snapshot-grid" id="snapshotGrid">
              <?php foreach ($snapshot_metrics as $metric): ?>
              <article class="snapshot-card">
                <div class="snapshot-head">
                  <div class="snapshot-title">
                    <i style="background: <?php echo uiEscape($metric['color']); ?>; color: <?php echo uiEscape($metric['color']); ?>;"></i>
                    <strong><?php echo uiEscape($metric['name']); ?></strong>
                  </div>
                  <span class="snapshot-pill"><?php echo uiEscape($metric['insight']); ?></span>
                </div>
                <div class="snapshot-values">
                  <div class="snapshot-stat">
                    <small>Today</small>
                    <b><?php echo uiFormatNumber($metric['today']); ?></b>
                  </div>
                  <div class="snapshot-stat">
                    <small>Month</small>
                    <b><?php echo uiFormatNumber($metric['month']); ?></b>
                  </div>
                </div>
                <div class="snapshot-bars">
                  <div class="snapshot-bar-row">
                    <span>Today</span>
                    <div class="snapshot-track">
                      <i style="width: <?php echo uiScaleWidth($metric['today'], $snapshot_max_today); ?>%; background: <?php echo uiEscape($metric['color']); ?>; color: <?php echo uiEscape($metric['color']); ?>;"></i>
                    </div>
                  </div>
                  <div class="snapshot-bar-row">
                    <span>Month</span>
                    <div class="snapshot-track">
                      <i style="width: <?php echo uiScaleWidth($metric['month'], $snapshot_max_month); ?>%; background: <?php echo uiEscape($metric['color']); ?>; color: <?php echo uiEscape($metric['color']); ?>;"></i>
                    </div>
                  </div>
                </div>
                <div class="snapshot-foot"><?php echo uiEscape($metric['detail']); ?></div>
              </article>
              <?php endforeach; ?>
              <article class="snapshot-summary">
                <div>
                  <small>Total Activity</small>
                  <strong><?php echo uiFormatNumber($today_total_activity); ?></strong>
                  <span>Today</span>
                </div>
                <div>
                  <small>Monthly Load</small>
                  <strong><?php echo uiFormatNumber($month_total_activity); ?></strong>
                  <span><?php echo uiFormatNumber($week_total_activity); ?> records in last 7 days</span>
                </div>
              </article>
            </div>
            <div class="snapshot-controls">
              <button type="button" class="snapshot-scroll-btn" data-scroll-target="snapshotGrid" data-scroll-dir="up">↑</button>
              <button type="button" class="snapshot-scroll-btn" data-scroll-target="snapshotGrid" data-scroll-dir="down">↓</button>
            </div>
          </section>
        </div>

      </section>

      <aside class="rightbar">
        <div class="station">
          <label>SELECT STATION</label>
          <button type="button">⌖ &nbsp; <?php echo uiEscape($station_name); ?> <span></span></button>
        </div>

        <section class="panel health">
          <div class="panel-head">SYSTEM HEALTH OVERVIEW <span></span></div>
          <div class="health-visual">
            <div class="hud-core">
              <svg viewBox="0 0 240 240" class="health-svg">
                <defs>
                  <linearGradient id="healthGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1afff2"/><stop offset="1" stop-color="#00a8c6"/></linearGradient>
                  <filter id="glow"><feGaussianBlur stdDeviation="3" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                </defs>
                <circle cx="120" cy="120" r="100" class="scan-ring"/>
                <circle cx="120" cy="120" r="92" class="outer-grid"/>
                <circle cx="120" cy="120" r="76" class="outer-grid dashed"/>
                <circle cx="120" cy="120" r="57" class="outer-grid inner"/>
                <g class="crosslines"><line x1="120" y1="14" x2="120" y2="226"/><line x1="14" y1="120" x2="226" y2="120"/><line x1="45" y1="45" x2="195" y2="195"/><line x1="195" y1="45" x2="45" y2="195"/></g>
                <circle cx="120" cy="120" r="84" class="progress-bg"/>
                <circle cx="120" cy="120" r="84" class="progress-ring" pathLength="100" style="stroke-dasharray: <?php echo uiEscape($health_progress); ?> 100;"/>
                <g class="health-nodes" filter="url(#glow)"><circle cx="120" cy="35" r="4"/><circle cx="181" cy="60" r="4"/><circle cx="205" cy="127" r="4"/><circle cx="165" cy="193" r="4"/><circle cx="88" cy="200" r="4"/><circle cx="39" cy="130" r="4"/><circle cx="65" cy="61" r="4"/></g>
                <g class="shield" filter="url(#glow)">
                  <path d="M120 82 152 94v25c0 26-17 42-32 51-15-9-32-25-32-51V94z"/>
                  <path d="m105 124 11 11 21-27"/>
                </g>
              </svg>
            </div>
            <div class="health-copy"><strong><?php echo uiEscape($health_progress); ?>%</strong><span>Subscription Health</span><em><?php echo uiEscape($subscription['status_text']); ?></em></div>
            <svg class="health-pulse" viewBox="0 0 220 28" preserveAspectRatio="none">
              <polyline points="0,18 42,18 50,12 57,23 64,4 72,18 94,18 104,13 112,19 138,18 145,8 153,21 160,18 220,18"/>
            </svg>
          </div>

          <div class="health-list">
            <div><span>◉ &nbsp; Feedback Today</span><b><?php echo uiFormatNumber($today_feedback); ?> <i class="ok"></i></b></div>
            <div><span>▣ &nbsp; Photo Upload</span><b><?php echo uiFormatNumber($today_photos); ?> <i class="ok"></i></b></div>
            <div><span>♧ &nbsp; Attendance Sync</span><b><?php echo uiFormatNumber($today_attendance); ?> <i class="<?php echo $today_attendance > 0 ? 'ok' : 'warn'; ?>"></i></b></div>
            <div><span>◫ &nbsp; Trains Running</span><b><?php echo uiFormatNumber($train_counts['today']); ?> <i class="<?php echo $train_counts['today'] > 0 ? 'ok' : 'warn'; ?>"></i></b></div>
            <div><span>▤ &nbsp; Latest Records</span><b><?php echo uiEscape($latest_feedback_total); ?> <i class="<?php echo $latest_feedback_total > 0 ? 'ok' : 'warn'; ?>"></i></b></div>
          </div>
          <!--<button type="button" class="health-btn">View System Health →</button>-->
        </section>
      </aside>
    </div>

    <footer>Copyright © 2016 - <?php echo uiEscape($display_year); ?> | Beatle Analytics OBHS | All Rights Reserved <b>Beta Version 2.0.0 BETA</b></footer>
  </main>
</div>
<div id="overlay" class="overlay"></div>
<script src="dashboard-v2-assets/js/main.js"></script>
</body>
</html>
