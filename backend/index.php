<?php
session_start();
include '../includes/connection.php';
include '../includes/helpers.php';

// Enable error reporting for development
$debug = true; // set to false in production
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Check login
checkLogin();

// Get station information
$station_name = getStationName($_SESSION['station_id']);
$station_id = (int) ($_SESSION['station_id'] ?? 0);

$show_otp_skip_toggle = false;
$otp_skip_status = 0;
$current_user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($station_id === 0 && $current_user_id > 0) {
    $show_otp_skip_toggle = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_passenger_otp_skip'])) {
        $new_otp_skip_status = isset($_POST['passenger_otp_skip']) ? 1 : 0;

        $update_stmt = $mysqli->prepare("UPDATE OBHS_users SET passenger_otp_skip = ? WHERE user_id = ? LIMIT 1");
        if ($update_stmt) {
            $update_stmt->bind_param("ii", $new_otp_skip_status, $current_user_id);
            $update_stmt->execute();
            $update_stmt->close();
            $otp_skip_status = $new_otp_skip_status;
        }
    }

    $status_stmt = $mysqli->prepare("SELECT passenger_otp_skip FROM OBHS_users WHERE user_id = ? LIMIT 1");
    if ($status_stmt) {
        $status_stmt->bind_param("i", $current_user_id);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();

        if ($status_result && $status_result->num_rows > 0) {
            $status_row = $status_result->fetch_assoc();
            $otp_skip_status = (int) ($status_row['passenger_otp_skip'] ?? 0);
        }

        $status_stmt->close();
    }
}
?>
<?php $pageTitle = "Dashboard"; ?>
<?php include 'header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <?php if ($show_otp_skip_toggle) { ?>
                <div class="otp-skip-box">
                    <div class="otp-skip-box__header">
                        <h3>Passenger OTP Skip</h3>
                        <span class="otp-skip-box__badge <?php echo $otp_skip_status ? 'on' : 'off'; ?>"><?php echo $otp_skip_status ? 'ON' : 'OFF'; ?></span>
                    </div>
                    <form method="POST" class="otp-skip-form">
                        <input type="hidden" name="update_passenger_otp_skip" value="1">
                        <label class="otp-switch">
                            <input type="checkbox" name="passenger_otp_skip" value="1" <?php echo $otp_skip_status ? 'checked' : ''; ?>>
                            <span class="otp-slider"></span>
                        </label>
                        <button type="submit" class="otp-update-btn">Update</button>
                    </form>
                </div>
                <?php } ?>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3 style="margin-bottom: 20px; color: #20a779;">Quick Actions</h3>
                    <div class="action-cards">
                        <a href="create-ac-feedback.php" class="action-card">
                            <div class="action-icon">📝</div>
                            <h4>Create AC Feedback</h4>
                            <p>Add new passenger feedback for AC coaches</p>
                        </a>
                        <a href="create-non-ac-feedback.php" class="action-card">
                            <div class="action-icon">📋</div>
                            <h4>Create Non AC Feedback</h4>
                            <p>Add new passenger feedback for Non AC coaches</p>
                        </a>
                        <a href="edit-passenger-feedback.php" class="action-card">
                            <div class="action-icon">✏️</div>
                            <h4>Edit Passenger Feedback</h4>
                            <p>View and edit existing passenger feedback</p>
                        </a>
                        <a href="create-attendance.php" class="action-card">
                            <div class="action-icon">✅</div>
                            <h4>Create Attendance</h4>
                            <p>Record employee attendance details</p>
                        </a>
                        <a href="edit-attendance.php" class="action-card">
                            <div class="action-icon">📊</div>
                            <h4>Edit Attendance</h4>
                            <p>View and modify attendance records</p>
                        </a>
                        <?php if (isset($_SESSION['station_id']) && $_SESSION['station_id'] == 8) { ?>
                        <a href="create-pdf-attendence.php" class="action-card">
                            <div class="action-icon">📄</div>
                            <h4>Create PDF Attendence</h4>
                            <p>Upload attendance PDF with date and grade</p>
                        </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

    <style>
        .otp-skip-box {
            background: #f8fbff;
            border: 1px solid #dce7f1;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .otp-skip-box__header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .otp-skip-box__header h3 {
            margin: 0;
            color: #1f4e79;
            font-size: 16px;
        }

        .otp-skip-box__badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .otp-skip-box__badge.on {
            background: #e8f8ef;
            color: #1b7f4b;
        }

        .otp-skip-box__badge.off {
            background: #fde8e8;
            color: #c0392b;
        }

        .otp-skip-form {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .otp-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .otp-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .otp-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #ccc;
            border-radius: 999px;
            transition: 0.3s;
        }

        .otp-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
        }

        .otp-switch input:checked + .otp-slider {
            background-color: #20a779;
        }

        .otp-switch input:checked + .otp-slider:before {
            transform: translateX(22px);
        }

        .otp-update-btn {
            background: #20a779;
            color: #fff;
            border: none;
            padding: 7px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .quick-actions {
            margin: 0;
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: linear-gradient(135deg, #20a779 0%, #169970 100%);
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(32, 167, 121, 0.3);
        }

        .action-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .action-card h4 {
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .action-card p {
            font-size: 13px;
            opacity: 0.9;
            margin: 0;
        }
    </style>

<?php include 'footer.php'; ?>
