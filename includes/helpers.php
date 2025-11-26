<?php
// Make sure mysqli connection exists
require_once "connection.php";

/**
 * Get station name from station_id
 * 
 */
 
 // --------------------------
// Check Login Function
// --------------------------
function checkLogin() {

    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // If user is not logged in → redirect to login
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }

    // NEW CONDITION → If user status = 1 (Disabled)
    if (isset($_SESSION['status']) && $_SESSION['status'] == 1) {
        echo "<div style='
                background:#ffe4e4;
                padding:20px;
                margin:40px auto;
                width:50%;
                border:2px solid #ff0000;
                color:#b30000;
                font-size:18px;
                text-align:center;
                border-radius:8px;
              '>
                Your account is currently disabled.<br>
Please contact your administrator for assistance.<br>
Note: If your subscription payment is overdue, please clear the payment to restore full access to your account. 
<br>

<a href='index.php' 
   style='display:inline-block; padding:10px 20px; background:#007bff; color:#fff;''
          text-decoration:none; border-radius:5px; font-weight:bold;'>
    Go to Home Page
</a>

              </div>";
        exit; // stop execution
    }
}




function getStationName($station_id)
{
    global $mysqli;

    $sql = "SELECT station_name FROM OBHS_station WHERE station_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        return "DB Prepare Error";
    }

    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['station_name'];
    } else {
        return "Station Not Found";
    }
}



function get_coach_count($train_no)
{
    global $mysqli;

$sql = "SELECT 
            no_ac_coach,
            feed_per_ac_coach,
            no_non_ac_coach,
            feed_per_non_ac_coach,
            feedback_tte,
            (no_ac_coach * feed_per_ac_coach + no_non_ac_coach * feed_per_non_ac_coach) AS total_feed
        FROM base_fb_target 
        WHERE train_no = ? AND station = ?";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $train_no, $_SESSION['station_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $total_coach = $row['no_ac_coach'] + $row['no_non_ac_coach'];

        return [
            'ac'        => $row['no_ac_coach'],
            'non_ac'    => $row['no_non_ac_coach'],
            'total'     => $total_coach,
            'feed_ac'   => $row['feed_per_ac_coach'],
            'feed_non_ac' => $row['feed_per_non_ac_coach'],
            'tte'       => $row['feedback_tte'] , 
            'total_feed' => $row['total_feed']
        ];
    } else {
        return false;
    }
}



function acheived_feedback($train_no, $date_from, $date_to , $grade)
{
    global $mysqli;

    $station = $_SESSION['station_id'];

    // Append full-day time
    $date_from = $date_from . " 00:00:00";
    $date_to   = $date_to . " 23:59:59";

    $sql = "SELECT 
        COUNT(DISTINCT coach_no) AS distinct_coaches,
        COUNT(*) AS total_count,
        COUNT(CASE WHEN coach_type = 'AC' THEN 1 END) AS ac_count,
        COUNT(CASE WHEN coach_type = 'NON-AC' THEN 1 END) AS non_ac_count,
        COUNT(CASE WHEN coach_type = 'TTE' THEN 1 END) AS tte_count
    FROM OBHS_passenger
    WHERE train_no = ?
      AND grade = ?
      AND station_id = ?
      AND created BETWEEN ? AND ?";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        return false;
    }

    // Correct Types: i = int, s = string
    $stmt->bind_param("isiss", $train_no, $grade, $station, $date_from, $date_to);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        $total_all = $row['ac_count'] + $row['non_ac_count'] + $row['tte_count'];
        $acCnonacacheivedata = $row['ac_count'] + $row['non_ac_count'];

        return [
            'distinct_coach'   => $row['distinct_coaches'],
            'ac'               => $row['ac_count'],
            'non_ac'           => $row['non_ac_count'],
            'tte'              => $row['tte_count'],
            'ac_non_ac'        => $acCnonacacheivedata,
            'total'            => $total_all
        ];
    }

    return false;
}
///first page psi calculation function

function psi_calculation($train_no, $date_from, $date_to, $grade)
{
    global $mysqli;
    $station = $_SESSION['station_id'];

    $date_from = $date_from . " 00:00:00";
    $date_to   = $date_to . " 23:59:59";

    // First query to get SUM feedback
    $sql1 = "SELECT SUM(f.value) AS feedback_sum
             FROM OBHS_feedback f
             JOIN OBHS_passenger p ON p.id = f.passenger_id
             WHERE p.train_no = ?
               AND p.grade = ?
               AND p.station_id = ?
               AND p.created BETWEEN ? AND ?";

    $stmt1 = $mysqli->prepare($sql1);
    $stmt1->bind_param("isiss", $train_no, $grade, $station, $date_from, $date_to);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $row1 = $result1->fetch_assoc();
    $feedback_sum = $row1['feedback_sum'] ?? 0;

    // Second query to get MAX marking just for how highest marks 
    $sql2 = "SELECT MAX(value) AS highest_marking
             FROM OBHS_marking
             WHERE station_id = ?";

    $stmt2 = $mysqli->prepare($sql2);
    $stmt2->bind_param("i", $station);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $row2 = $result2->fetch_assoc();
    $highest_marking = $row2['highest_marking'] ?? 0;

    return [
        'feedback_sum' => $feedback_sum,
        'highest_marking' => $highest_marking
    ];
}


// ac or no ac and tte  coach feedback calculation function second page calculation  second page calculation se
function feedback_calculation_coach_wise($train_no, $date_from, $date_to, $coach_type, $grade)
{
    global $mysqli;
    $station = $_SESSION['station_id'];

    $date_from = $date_from . " 00:00:00";
    $date_to   = $date_to . " 23:59:59";

    // 1️⃣ Coach-wise feedback SUM + passenger count
    $sql = "SELECT 
                p.coach_no,
                SUM(f.value) AS feedback_sum,
                COUNT(DISTINCT p.id) AS total_passenger_count
            FROM OBHS_feedback f
            JOIN OBHS_passenger p ON p.id = f.passenger_id
            WHERE p.train_no = ?
              AND p.coach_type = ?
              AND p.grade = ?
              AND p.station_id = ?
              AND p.created BETWEEN ? AND ?
            GROUP BY p.coach_no";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ississ", $train_no, $coach_type, $grade, $station, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();

    $coachData = [];
    while ($row = $result->fetch_assoc()) {
        $coachData[$row['coach_no']] = [
            'coach_no'               => $row['coach_no'],
            'feedback_sum'           => $row['feedback_sum'] ?? 0,
            'total_passenger_count'  => $row['total_passenger_count'] ?? 0
        ];
    }

    // 2️⃣ Highest marking + count
    $sql2 = "SELECT 
                MAX(value) AS highest_marking,
                COUNT(*) AS marking_count
             FROM OBHS_marking 
             WHERE station_id = ?";

    $stmt2 = $mysqli->prepare($sql2);
    $stmt2->bind_param("i", $station);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $row2 = $res2->fetch_assoc();

    $highest_marking = $row2['highest_marking'] ?? 0;
    $marking_count   = $row2['marking_count'] ?? 0;

    // 3️⃣ NEW: Fetch targets from base_fb_target
    $sql3 = "SELECT 
                feed_per_ac_coach AS ac_coach_target,
                feed_per_non_ac_coach AS non_ac_coach_target,
                feedback_tte AS tte_target
             FROM base_fb_target
             WHERE station = ?
               AND train_no = ?
             LIMIT 1";

    $stmt3 = $mysqli->prepare($sql3);
    $stmt3->bind_param("ii", $station, $train_no);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    $targetData = $res3->fetch_assoc() ?? [
        'ac_coach_target'     => 0,
        'non_ac_coach_target' => 0,
        'tte_target'          => 0
    ];

    // FINAL RETURN
    return [
        'coach_wise'       => $coachData,
        'highest_marking'  => $highest_marking,
        'marking_count'    => $marking_count,
        'targets'          => $targetData
    ];
}


// 3 page calculation function with full details
function feedback_calculation_coach_wise_full($train_no, $date_from, $date_to, $coach_type, $grade)
{
    global $mysqli;
    $station = $_SESSION['station_id'];
    $date_from = $date_from . " 00:00:00";
    $date_to   = $date_to . " 23:59:59";

    // 1️⃣ Coach-wise feedback SUM + passenger count
    $sql = "SELECT 
                p.coach_no,
                SUM(f.value) AS feedback_sum,
                COUNT(DISTINCT p.id) AS total_passenger_count
            FROM OBHS_feedback f
            JOIN OBHS_passenger p ON p.id = f.passenger_id
            WHERE p.train_no = ?
              AND p.coach_type = ?
              AND p.grade = ?
              AND p.station_id = ?
              AND p.created BETWEEN ? AND ?
            GROUP BY p.coach_no";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ississ", $train_no, $coach_type, $grade, $station, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();

    $coachData = [];
    while ($row = $result->fetch_assoc()) {
        $coachData[$row['coach_no']] = [
            'coach_no'               => $row['coach_no'],
            'feedback_sum'           => $row['feedback_sum'] ?? 0,
            'total_passenger_count'  => $row['total_passenger_count'] ?? 0
        ];
    }

    // FINAL RETURN
    return $coachData;
}

// get OBHS_marking data
function get_marking_data($station_id)
{
    global $mysqli;
    $sql = "SELECT category , value FROM OBHS_marking WHERE station_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);    
}

// get OBHS_questions data 3 page english hindi 
function get_questions_data($station_id , $coach_type)
{
    global $mysqli;
    $sql = "SELECT id , eng_question , hin_question FROM OBHS_questions WHERE station_id = ? AND type = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $station_id, $coach_type);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);    
}

// get OBHS_feedback and passenger data for feedback details page 3 page
function get_passenger_details_data_coach_wise($coach_no, $train_no, $date_from, $date_to, $coach_type, $grade)
{
    global $mysqli;
    $station = $_SESSION['station_id'];

    $date_from = $date_from . " 00:00:00";
    $date_to   = $date_to . " 23:59:59";

    $sql = "SELECT 
                p.id AS passenger_id,
                p.created AS feedback_date,
                p.seat_no,
                p.coach_no,
                p.name,
                p.pnr_number,
                p.ph_number,
                p.train_no,
                p.grade,
                SUM(f.value) AS total_feedback_sum,
                GROUP_CONCAT(f.value ORDER BY f.id ASC SEPARATOR ', ') AS feedback_values
            FROM OBHS_passenger p
            JOIN OBHS_feedback f ON p.id = f.passenger_id
            WHERE p.train_no = ?
              AND p.coach_no = ?
              AND p.coach_type = ?
              AND p.grade = ?
              AND p.station_id = ?
              AND p.created BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY p.created ASC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("isssiss", $train_no, $coach_no, $coach_type, $grade, $station, $date_from, $date_to);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_passenger_details_data_coach_type_wise($train_no, $coach_type, $grade, $date_from, $date_to)
{
    global $mysqli;
    $station = $_SESSION['station_id'];

    $date_from = $date_from . " 00:00:00";
    $date_to   = $date_to . " 23:59:59";

    $sql = "SELECT 
                p.id AS passenger_id,
                p.created AS feedback_date,
                p.seat_no,
                p.coach_no,
                p.name,
                p.pnr_number,
                p.ph_number,
                p.train_no,
                p.grade,
                SUM(f.value) AS total_feedback_sum,
                GROUP_CONCAT(f.value ORDER BY f.id ASC SEPARATOR ', ') AS feedback_values
            FROM OBHS_passenger p
            JOIN OBHS_feedback f ON p.id = f.passenger_id
            WHERE p.train_no = ?
              AND p.coach_type = ?
              AND p.grade = ?
              AND p.station_id = ?
              AND p.created BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY p.created ASC";

    $stmt = $mysqli->prepare($sql);


    $stmt->bind_param(
        "ississ",
        $train_no,
        $coach_type,
        $grade,
        $station,
        $date_from,
        $date_to
    );

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
