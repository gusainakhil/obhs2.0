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
$station_id = $_SESSION['station_id'];
?>
<?php $pageTitle = "Dashboard"; ?>
<?php include 'header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
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
                    </div>
                </div>
            </div>
        </div>

    <style>
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
