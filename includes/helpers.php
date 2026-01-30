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

//check if user subscription is active or not 
function checkSubscription($station_id) {
    global $mysqli;

    $sql = "SELECT end_date FROM OBHS_users WHERE station_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $subscription_end_date = $row['end_date'];
        $current_time = time();
        $end_time = strtotime($subscription_end_date);
        $days_diff = ($end_time - $current_time) / (60 * 60 * 24);

        // Show warning 7 days before expiry
        if ($days_diff > 0 && $days_diff <= 7) {
            echo "
            <div id='subscription-warning-modal' style='
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
            '>
                <div style='
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 450px;
                    width: 90%;
                    border: 3px solid #ffc107;
                    position: relative;
                '>
                    <button onclick=\"document.getElementById('subscription-warning-modal').style.display='none'\" style='
                        position: absolute;
                        top: 10px;
                        right: 15px;
                        background: none;
                        border: none;
                        font-size: 28px;
                        color: #856404;
                        cursor: pointer;
                        font-weight: bold;
                    '>&times;</button>
                    <div style='font-size: 60px; margin-bottom: 15px;'>⚠️</div>
                    <h2 style='color: #856404; margin-bottom: 15px; font-weight: bold;'>Subscription Expiring Soon!</h2>
                    <p style='color: #856404; font-size: 16px; margin-bottom: 10px;'>
                        Your subscription expires on<br>
                        <strong style='font-size: 18px;'>" . htmlspecialchars($subscription_end_date) . "</strong>
                    </p>
                    <p style='color: #856404; font-size: 14px; margin-bottom: 20px;'>
                        Please renew your subscription soon to avoid service interruption. <br>
                        Otherwise, access will be blocked after 3 days of expiry.
                    </p>
                    <a href='index.php' style='
                        display: inline-block;
                        padding: 12px 30px;
                        background: #ffc107;
                        color: #856404;
                        text-decoration: none;
                        border-radius: 8px;
                        font-weight: bold;
                        font-size: 16px;
                        transition: all 0.3s;
                    ' onmouseover=\"this.style.background='#e0a800'\" onmouseout=\"this.style.background='#ffc107'\">
                        Continue to Dashboard
                    </a>
                </div>
            </div>";
        }
        
        // Block access 3 days after expiry
        if ($current_time > $end_time) {
            $days_expired = abs($days_diff);
            if ($days_expired > 3) {
                echo "
                <div style='
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.7);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                '>
                    <div style='
                        background: linear-gradient(135deg, #ffe4e4 0%, #ffcccc 100%);
                        padding: 40px;
                        border-radius: 15px;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.4);
                        text-align: center;
                        max-width: 450px;
                        width: 90%;
                        border: 3px solid #dc3545;
                    '>
                        <div style='font-size: 60px; margin-bottom: 15px;'>🚫</div>
                        <h2 style='color: #721c24; margin-bottom: 15px; font-weight: bold;'>Access Denied!</h2>
                        <p style='color: #721c24; font-size: 16px; margin-bottom: 10px;'>
                            Your subscription expired on<br>
                            <strong style='font-size: 18px;'>" . htmlspecialchars($subscription_end_date) . "</strong>
                        </p>
                        <p style='color: #721c24; font-size: 14px; margin-bottom: 20px;'>
                            Please contact administrator to restore access.
                        </p>
                        <a href='index.php' style='
                            display: inline-block;
                            padding: 12px 30px;
                            background: #dc3545;
                            color: #fff;
                            text-decoration: none;
                            border-radius: 8px;
                            font-weight: bold;
                            font-size: 16px;
                            transition: all 0.3s;
                        ' onmouseover=\"this.style.background='#c82333'\" onmouseout=\"this.style.background='#dc3545'\">
                            Go to Home Page
                        </a>
                    </div>
                </div>";
                exit;
            } else {
                echo "
                <div id='subscription-modal' style='
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.6);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                '>
                    <div style='
                        background: linear-gradient(135deg, #ffe4e4 0%, #ffcccc 100%);
                        padding: 40px;
                        border-radius: 15px;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.4);
                        text-align: center;
                        max-width: 450px;
                        width: 90%;
                        border: 3px solid #dc3545;
                        position: relative;
                    '>
                        <button onclick=\"document.getElementById('subscription-modal').style.display='none'\" style='
                            position: absolute;
                            top: 10px;
                            right: 15px;
                            background: none;
                            border: none;
                            font-size: 28px;
                            color: #721c24;
                            cursor: pointer;
                            font-weight: bold;
                        '>&times;</button>
                        <div style='font-size: 60px; margin-bottom: 15px;'>⏰</div>
                        <h2 style='color: #721c24; margin-bottom: 15px; font-weight: bold;'>Subscription Expired!</h2>
                        <p style='color: #721c24; font-size: 16px; margin-bottom: 10px;'>
                            Your subscription expired on<br>
                            <strong style='font-size: 18px;'>" . htmlspecialchars($subscription_end_date) . "</strong>
                        </p>
                        <p style='color: #721c24; font-size: 14px; margin-bottom: 20px;'>
                            Please renew your subscription immediately to continue using the service. otherwise, access will be blocked after 3 days of expiry.
                        </p>
                        <a href='index.php' style='
                            display: inline-block;
                            padding: 12px 30px;
                            background: #dc3545;
                            color: #fff;
                            text-decoration: none;
                            border-radius: 8px;
                            font-weight: bold;
                            font-size: 16px;
                            transition: all 0.3s;
                        ' onmouseover=\"this.style.background='#c82333'\" onmouseout=\"this.style.background='#dc3545'\">
                            Go to Home Page
                        </a>
                    </div>
                </div>";
            }
        }
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

/// feedback count function for today and month


function train_today_count($station_id) {
    global $mysqli;

    $sql = "
        SELECT COUNT(DISTINCT train_no) AS total_unique_trains
        FROM (
            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM base_attendance
            WHERE station_id = $station_id
              AND DATE(created_at) = CURDATE()

            UNION

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM OBHS_passenger
            WHERE station_id = $station_id
              AND DATE(created_at) = CURDATE()

            UNION

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM base_photo_report
            WHERE station_id = $station_id
              AND DATE(created_at) = CURDATE()
        ) AS all_trains
    ";

    $result = mysqli_query($mysqli, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total_unique_trains'];
}



function train_month_count($station_id) {
    global $mysqli;

    $sql = "
        SELECT COUNT(DISTINCT train_no) AS total_unique_trains
        FROM (
            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM base_attendance
            WHERE station_id = $station_id
              AND MONTH(created_at) = MONTH(CURDATE())
              AND YEAR(created_at) = YEAR(CURDATE())

            UNION

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM OBHS_passenger
            WHERE station_id = $station_id
              AND MONTH(created_at) = MONTH(CURDATE())
              AND YEAR(created_at) = YEAR(CURDATE())

            UNION

            SELECT train_no COLLATE utf8mb4_unicode_ci AS train_no
            FROM base_photo_report
            WHERE station_id = $station_id
              AND MONTH(created_at) = MONTH(CURDATE())
              AND YEAR(created_at) = YEAR(CURDATE())
        ) AS all_trains
    ";

    $result = mysqli_query($mysqli, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total_unique_trains'];
}






function feedback_count()
{
    global $mysqli;

    // Ensure session started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $station_id = $_SESSION['station_id'] ?? null;
    if (!$station_id) {
        return ['today' => 0, 'month' => 0];
    }

    $counts = ['today' => 0, 'month' => 0];

    // Count distinct passengers who gave feedback today
    $sqlToday = "SELECT COUNT(DISTINCT p.id) AS total FROM OBHS_feedback f
                 JOIN OBHS_passenger p ON p.id = f.passenger_id
                 WHERE DATE(p.created) = CURDATE() AND p.station_id = ?";
    $stmt = $mysqli->prepare($sqlToday);
    if ($stmt) {
        $stmt->bind_param("i", $station_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $counts['today'] = (int) ($row['total'] ?? 0);
        $stmt->close();
    }

    // Count distinct passengers who gave feedback in the current month
    $sqlMonth = "SELECT COUNT(DISTINCT p.id) AS total FROM OBHS_feedback f
                 JOIN OBHS_passenger p ON p.id = f.passenger_id
                 WHERE DATE_FORMAT(p.created, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
                   AND p.station_id = ?";
    $stmt2 = $mysqli->prepare($sqlMonth);
    if ($stmt2) {
        $stmt2->bind_param("i", $station_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $row2 = $res2->fetch_assoc();
        $counts['month'] = (int) ($row2['total'] ?? 0);
        $stmt2->close();
    }

    return $counts;
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
    COUNT(DISTINCT CASE WHEN coach_type != 'TTE' THEN coach_no END) AS distinct_coaches,
    COUNT(DISTINCT CASE WHEN coach_type = 'AC' THEN coach_no END) AS Ac_achived_coaches,
    COUNT(DISTINCT CASE WHEN coach_type = 'NON-AC' THEN coach_no END) AS Non_ac_achived_coaches,
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
            'ac_achived_coaches' => $row['Ac_achived_coaches'],
            'non_ac_achived_coaches' => $row['Non_ac_achived_coaches'],
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
//first page question count function
function get_question_count($station_id)
{
    global $mysqli; 

    $data = [
        'total_questions' => 0,
        'ac_questions'    => 0,
        'non_ac_questions'=> 0
    ];

    $sql = "SELECT 
                COUNT(*) AS total_questions,
                COUNT(CASE WHEN type = 'AC' THEN 1 END) AS ac_questions,
                COUNT(CASE WHEN type = 'NON-AC' THEN 1 END) AS non_ac_questions
            FROM OBHS_questions
            WHERE station_id = ?";

    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $station_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $row = $result->fetch_assoc();

            $data['total_questions'] = (int) ($row['total_questions'] ?? 0);
            $data['ac_questions']    = (int) ($row['ac_questions'] ?? 0);
            $data['non_ac_questions']= (int) ($row['non_ac_questions'] ?? 0);
        }

        $stmt->close();
    }

    return $data;
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
    $highest_marking = (int) ($row2['highest_marking'] ?? 0);

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
$question_type = $coach_type;
if ($coach_type === "TTE") {
    $question_type = "AC";
}
    // FINAL RETURN (include total questions)
    $sql4 = "SELECT COUNT(*) AS total_questions FROM `OBHS_questions` WHERE station_id = ? AND type = ?";
    $stmt4 = $mysqli->prepare($sql4);
    if ($stmt4) {
        $stmt4->bind_param("is", $station,  $question_type);
        $stmt4->execute();
        $result4 = $stmt4->get_result();
        $row4 = $result4->fetch_assoc();
        $total_questions = $row4['total_questions'] ?? 0;
    } else {
        $total_questions = 0;
    }

    return [
        'coach_wise'       => $coachData,
        'highest_marking'  => $highest_marking,
        'targets'          => $targetData,
        'total_questions'  => $total_questions
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
    $sql = "SELECT category , value FROM OBHS_marking WHERE station_id = ? ORDER BY `value` DESC";
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
    $question_type = $coach_type;
if ($coach_type === "TTE") {
    $question_type = "AC";
}
    $sql = "SELECT id , eng_question , hin_question FROM OBHS_questions WHERE station_id = ? AND type = ? ORDER BY `OBHS_questions`.`id` ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $station_id, $question_type);
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
    //added coach no condition
    

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
                f.feed_param,
                SUM(f.value) AS total_feedback_sum,
                GROUP_CONCAT(f.value ORDER BY f.feed_param ASC SEPARATOR ', ') AS feedback_values
            FROM OBHS_passenger p
            JOIN OBHS_feedback f ON p.id = f.passenger_id
            WHERE p.train_no = ?
              AND p.coach_no = ?
              AND p.coach_type = ?
              AND p.grade = ?
              AND p.station_id = ?
              AND p.created BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY f.feed_param ASC";

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
                f.feed_param,
                SUM(f.value) AS total_feedback_sum,
                GROUP_CONCAT(f.value ORDER BY f.feed_param ASC SEPARATOR ', ') AS feedback_values
            FROM OBHS_passenger p
            JOIN OBHS_feedback f ON p.id = f.passenger_id
            WHERE p.train_no = ?
              AND p.coach_type = ?
              AND p.grade = ?
              AND p.station_id = ?
              AND p.created BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY p.created asc";

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

function check_highest_marking($station_id)
{
    global $mysqli;

    $sql = "SELECT MAX(value) AS highest_marking FROM OBHS_marking WHERE station_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int) ($row['highest_marking'] ?? 0);
}


function calculateCoachWisePercentage(
    string $train,
    string $from_date,
    string $to_date,
    string $coach_type,
    string $grade
): array {

    $data = feedback_calculation_coach_wise(
        $train,
        $from_date,
        $to_date,
        $coach_type,
        $grade
    );

    $coachList = $data['coach_wise'] ?? [];
    $targets   = $data['targets'] ?? [];

    // Pick correct target key
    if ($coach_type === 'AC') {
        $target_per_coach = $targets['ac_coach_target'] ?? 0;
    } elseif ($coach_type === 'NON-AC') {
        $target_per_coach = $targets['non_ac_coach_target'] ?? 0;
    } else { // TTE
        $target_per_coach = $targets['tte_target'] ?? 0;
    }

    $total_questions = $data['total_questions'] ?? 0;
    $highest_marking = $data['highest_marking'] ?? 0;

    $total_percentage = 0.0;
    $total_coaches = count($coachList);

    foreach ($coachList as $coach_no => $row) {

        $feedback_sum = $row['feedback_sum'] ?? 0;
        $passenger_count = $row['total_passenger_count'] ?? 0;

        $percentage = 0.0;

        if ($total_questions > 0 && $highest_marking > 0) {

            $effective_target = ($passenger_count <= $target_per_coach && $target_per_coach > 0)
                ? $target_per_coach
                : $passenger_count;

            $denom = $total_questions * $highest_marking * $effective_target;

            if ($denom > 0) {
                $percentage = ($feedback_sum / $denom) * 100;
            }
        }

        $total_percentage += $percentage;
    }

    return [
        'avg_percentage' => number_format(
            $total_percentage / max($total_coaches, 1),
            2
        ),
        'total_coaches' => $total_coaches
    ];
}

function calculateFinalPSI(array $sections): float
{
    $sum = 0.0;
    $count = 0;

    foreach ($sections as $item) {

        // total must exist AND be > 0
        if (
            isset($item['total'], $item['percent']) &&
            $item['total'] > 0
        ) {
            $sum += (float) $item['percent'];
            $count++;
        }
    }

    // ALL ZERO → return 0
    if ($count === 0) {
        return 0.0;
    }

    // AUTO divide by 1 / 2 / 3
    return round($sum / $count, 2);
}


