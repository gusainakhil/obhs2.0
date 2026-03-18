    <!-- Sidebar -->
    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="create-ac-feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create-ac-feedback.php' ? 'active' : ''; ?>">Create AC Feedback</a></li>
            <li><a href="create-non-ac-feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create-non-ac-feedback.php' ? 'active' : ''; ?>">Create Non AC Feedback</a></li>
            <li><a href="create-tte-feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create-tte-feedback.php' ? 'active' : ''; ?>">Create TTE Feedback</a></li>
            <li><a href="edit-passenger-feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'edit-passenger-feedback.php' ? 'active' : ''; ?>">Edit Passenger Feedback</a></li>
            <li><a href="create-attendance.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create-attendance.php' ? 'active' : ''; ?>">Create Attendance</a></li>
            
            <li><a href="edit-attendance.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'edit-attendance.php' ? 'active' : ''; ?>">Edit Attendance</a></li>
            <?php if (isset($_SESSION['station_id']) && $_SESSION['station_id'] == 8) { ?>
            <li><a href="create-pdf-attendence.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create-pdf-attendence.php' ? 'active' : ''; ?>">Create PDF Attendence</a></li>
            <li><a href="edit-pdf-attendence.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'edit-pdf-attendence.php' ? 'active' : ''; ?>">Edit PDF Attendence</a></li>
            <?php } ?>
        </ul>
    </nav>
