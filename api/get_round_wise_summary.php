<?php
header("Content-Type: application/json");
require "../includes/connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$grade       = $data["grade"];
$train_up    = $data["train_up"];   
$date_from   = $data["from"];
$date_to     = $data["to"];
$station_id  = $data["station_id"];




if (!is_array($train_up)) {
    $train_up = [$train_up];
}

// Build placeholders  
$ph = implode(",", array_fill(0, count($train_up), "?"));


$sqlTrain = "
SELECT 
    train_no,
    no_ac_coach,
    feed_per_ac_coach,
    no_non_ac_coach,
    feed_per_non_ac_coach,
    feedback_tte,
    (no_ac_coach * feed_per_ac_coach + no_non_ac_coach * feed_per_non_ac_coach) AS total_feed
FROM base_fb_target
WHERE train_no IN ($ph)
AND station = ?";

$params = $train_up;
$params[] = $station_id;

$typeStr = str_repeat("s", count($train_up)) . "i";

$stmt = $mysqli->prepare($sqlTrain);
$stmt->bind_param($typeStr, ...$params);
$stmt->execute();
$res = $stmt->get_result();

/* ---------------------------------------------------------
   5️⃣ LOOP — For Each Train, Get Achieved Feedback
--------------------------------------------------------- */

while ($t = $res->fetch_assoc()) {

    $train_no = $t["train_no"];
    $from = $date_from . " 00:00:00";
    $to   = $date_to   . " 23:59:59";

    $sqlAch = "
    SELECT 
        COUNT(DISTINCT CASE WHEN coach_type != 'TTE' THEN coach_no END) AS distinct_coaches,
        COUNT(CASE WHEN coach_type = 'AC' THEN 1 END) AS ac_count,
        COUNT(CASE WHEN coach_type = 'NON-AC' THEN 1 END) AS non_ac_count,
        COUNT(CASE WHEN coach_type = 'TTE' THEN 1 END) AS tte_count
    FROM OBHS_passenger
    WHERE train_no = ?
      AND grade = ?
      AND station_id = ?
      AND created BETWEEN ? AND ?";

    $stmt2 = $mysqli->prepare($sqlAch);
    $stmt2->bind_param("ssiss", $train_no, $grade, $station_id, $from, $to);
    $stmt2->execute();
    $ach = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    $total_achieved = $ach["ac_count"] + $ach["non_ac_count"] + $ach["tte_count"];

    // Add final combined data
 $Target_coach_feedback_ac     = $t["no_ac_coach"] * $t["feed_per_ac_coach"];
$Target_coach_feedback_non_ac = $t["no_non_ac_coach"] * $t["feed_per_non_ac_coach"];


$response["train_data"][] = [
    "train_no" => $train_no,
    "target" => [
        "ac"                     => $t["no_ac_coach"],
        "non_ac"                 => $t["no_non_ac_coach"],
        "feed_ac"                => $t["feed_per_ac_coach"],
        "feed_non_ac"            => $t["feed_per_non_ac_coach"],
        "tte_target"                    => $t["feedback_tte"],
        "total_feed"             => $t["total_feed"],
        "Target_coaches"         => $t["no_ac_coach"] + $t["no_non_ac_coach"] ,
        "Target_coach_feedback_ac"     => $Target_coach_feedback_ac,
        "Target_coach_feedback_non_ac" => $Target_coach_feedback_non_ac,
        "total_feedback_Target"        => $Target_coach_feedback_ac + $Target_coach_feedback_non_ac + $t["feedback_tte"] , 
        
    ],

        "achieved" => [
            "distinct_coach" => $ach["distinct_coaches"],
            "ac"             => $ach["ac_count"],
            "non_ac"         => $ach["non_ac_count"],
            "Achived_TTE"            => $ach["tte_count"],
            "acheived_feedback"          => $total_achieved ,
            "acheived_coach" => $ach["distinct_coaches"] ,
            "acheived_coach_feedback" => $ach["distinct_coaches"] +$ach["tte_count"] 
            
        ]
    ];
}

echo json_encode($response);
exit;
