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
