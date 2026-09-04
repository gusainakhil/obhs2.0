<?php
header("Content-Type: application/json");
require "../includes/connection.php";

function sendRoundWiseSummary(array $payload): void
{
    echo json_encode($payload);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    sendRoundWiseSummary([
        "train_data" => [],
        "message" => "Invalid request payload"
    ]);
}

$grade = trim((string) ($data["grade"] ?? ""));
// Mobile clients may send labels such as "D - Thursday"; the database stores only "D".
$grade = trim(explode("-", $grade, 2)[0]);
$train_up = $data["train_up"] ?? [];
$date_from = trim((string) ($data["from"] ?? ""));
$date_to = trim((string) ($data["to"] ?? ""));
$station_id = trim((string) ($data["station_id"] ?? ""));

$response = [
    "train_data" => []
];

if ($grade === "" || $date_from === "" || $date_to === "" || $station_id === "") {
    $response["message"] = "Missing required parameters";
    sendRoundWiseSummary($response);
}

if (!is_array($train_up)) {
    $train_up = [$train_up];
}

$train_up = array_values(array_filter(array_map(
    static function ($train_no) {
        return trim((string) $train_no);
    },
    $train_up
), static function ($train_no) {
    return $train_no !== "";
}));

if ($train_up === []) {
    sendRoundWiseSummary($response);
}

$placeholders = implode(",", array_fill(0, count($train_up), "?"));

$sqlTrain = "
SELECT
    train_no,
    COALESCE(no_ac_coach, 0) AS no_ac_coach,
    COALESCE(feed_per_ac_coach, 0) AS feed_per_ac_coach,
    COALESCE(no_non_ac_coach, 0) AS no_non_ac_coach,
    COALESCE(feed_per_non_ac_coach, 0) AS feed_per_non_ac_coach,
    COALESCE(feedback_tte, 0) AS feedback_tte,
    (
        COALESCE(no_ac_coach, 0) * COALESCE(feed_per_ac_coach, 0) +
        COALESCE(no_non_ac_coach, 0) * COALESCE(feed_per_non_ac_coach, 0)
    ) AS total_feed
FROM base_fb_target
WHERE train_no IN ($placeholders)
  AND station = ?";

$params = $train_up;
$params[] = $station_id;
// Match the web report, which treats train and station IDs as integers.
$type_str = str_repeat("i", count($params));

$stmt = $mysqli->prepare($sqlTrain);
if (!$stmt) {
    $response["message"] = "Failed to prepare target query";
    sendRoundWiseSummary($response);
}

$stmt->bind_param($type_str, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$sqlAch = "
SELECT
    COUNT(DISTINCT CASE WHEN coach_type != 'TTE' THEN coach_no END) AS distinct_coaches,
    COUNT(DISTINCT CASE WHEN coach_type = 'AC' THEN coach_no END) AS ac_achived_coaches,
    COUNT(DISTINCT CASE WHEN coach_type = 'NON-AC' THEN coach_no END) AS non_ac_achived_coaches,
    COUNT(CASE WHEN coach_type = 'AC' THEN 1 END) AS ac_count,
    COUNT(CASE WHEN coach_type = 'NON-AC' THEN 1 END) AS non_ac_count,
    COUNT(CASE WHEN coach_type = 'TTE' THEN 1 END) AS tte_count
FROM OBHS_passenger
WHERE train_no = ?
  AND grade = ?
  AND station_id = ?
  AND created BETWEEN ? AND ?";

$stmt2 = $mysqli->prepare($sqlAch);
if (!$stmt2) {
    $stmt->close();
    $response["message"] = "Failed to prepare achieved query";
    sendRoundWiseSummary($response);
}

$from = $date_from . " 00:00:00";
$to = $date_to . " 23:59:59";

while ($t = $res->fetch_assoc()) {
    $train_no = (string) ($t["train_no"] ?? "");
    $train_no_id = (int) $train_no;
    $station_no_id = (int) $station_id;

    $target_ac = (int) ($t["no_ac_coach"] ?? 0);
    $target_feed_ac = (int) ($t["feed_per_ac_coach"] ?? 0);
    $target_non_ac = (int) ($t["no_non_ac_coach"] ?? 0);
    $target_feed_non_ac = (int) ($t["feed_per_non_ac_coach"] ?? 0);
    $target_tte = (int) ($t["feedback_tte"] ?? 0);
    $target_total_feed = (int) ($t["total_feed"] ?? 0);

    // This binding and query match acheived_feedback() in round_wise_summary.php.
    $stmt2->bind_param("isiss", $train_no_id, $grade, $station_no_id, $from, $to);
    $stmt2->execute();
    $ach = $stmt2->get_result()->fetch_assoc() ?? [];

    $achieved_distinct_coaches = (int) ($ach["distinct_coaches"] ?? 0);
    $achieved_ac_coaches = (int) ($ach["ac_achived_coaches"] ?? 0);
    $achieved_non_ac_coaches = (int) ($ach["non_ac_achived_coaches"] ?? 0);
    $achieved_ac = (int) ($ach["ac_count"] ?? 0);
    $achieved_non_ac = (int) ($ach["non_ac_count"] ?? 0);
    $achieved_tte = (int) ($ach["tte_count"] ?? 0);

    $total_achieved = $achieved_ac + $achieved_non_ac + $achieved_tte;
    $target_coach_feedback_ac = $target_ac * $target_feed_ac;
    $target_coach_feedback_non_ac = $target_non_ac * $target_feed_non_ac;

    $response["train_data"][] = [
        "train_no" => $train_no,
        "target" => [
            "ac" => $target_ac,
            "non_ac" => $target_non_ac,
            "feed_ac" => $target_feed_ac,
            "feed_non_ac" => $target_feed_non_ac,
            "tte_target" => $target_tte,
            "total_feed" => $target_total_feed,
            "Target_coaches" => $target_ac + $target_non_ac,
            "Target_coach_feedback_ac" => $target_coach_feedback_ac,
            "Target_coach_feedback_non_ac" => $target_coach_feedback_non_ac,
            "total_feedback_Target" => $target_coach_feedback_ac + $target_coach_feedback_non_ac + $target_tte,
        ],
        "achieved" => [
            "distinct_coach" => $achieved_distinct_coaches,
            "ac_achived_coaches" => $achieved_ac_coaches,
            "non_ac_achived_coaches" => $achieved_non_ac_coaches,
            "ac" => $achieved_ac,
            "non_ac" => $achieved_non_ac,
            "Achived_TTE" => $achieved_tte,
            "acheived_feedback" => $total_achieved,
            "acheived_coach" => $achieved_ac_coaches + $achieved_non_ac_coaches,
            "acheived_coach_feedback" => $achieved_ac_coaches + $achieved_non_ac_coaches + $achieved_tte
        ]
    ];
}

$stmt2->close();
$stmt->close();

sendRoundWiseSummary($response);
