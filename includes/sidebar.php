<?php require_once __DIR__ . "/connection.php"; ?>

<aside id="sidebar" 
    class="fixed left-0 top-0 h-full w-64 bg-slate-800 text-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">

    <!-- Logo Section -->
    <div class="h-16 flex items-center justify-between px-6 bg-slate-900 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-train text-white text-lg"></i>
            </div>
            <span class="font-bold text-lg">Railway OBHS</span>
        </div>
        <button id="closeSidebar" class="lg:hidden text-gray-400 hover:text-white">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Dynamic Navigation Menu -->
    <nav class="mt-6 px-3">
        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-home w-5"></i> <span class="text-sm font-medium">Dashboard</span> </a>
        <?php
        // session_start();
        $user_id = (int) ($_SESSION['user_id'] ?? 0);   // Logged-in user
        $current_station_id = (int) ($_SESSION['station_id'] ?? 0);

        $sql = "SELECT reports_name, link FROM OBHS_reports WHERE user_id = ? ORDER BY id ASC";
        $stmt = $mysqli->prepare($sql);
        $result = null;

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        if($result && $result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                echo '
                <a href="'.htmlspecialchars($row['link'], ENT_QUOTES, 'UTF-8').'"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition">
                    <i class="fas fa-angle-right w-5"></i>
                    <span class="text-sm font-medium">'.htmlspecialchars($row['reports_name'], ENT_QUOTES, 'UTF-8').'</span>
                </a>';
            }
        } else {
            echo '<p class="text-slate-400 text-sm px-4">No menu assigned.</p>';
        }

        if ($stmt) {
            $stmt->close();
        }
        ?>
        <?php if($current_station_id == 8): ?>
        <a href="feedback-single-train-report.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Train Report</span> </a>
        <a href="view-pdf-attendece.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">View PDF Attendence</span> </a>   
        <?php endif; ?>
        <a href="feedback-target.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Feedback Target</span> </a>
        <a href="view-feedback-target.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">View Feedback Target</span> </a>
        <?php if($current_station_id != 17): ?>
        <a href="create-employee.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Create Employee</span> </a>
        <a href="view-employee.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">View Employee</span> </a>
        <?php endif; ?>

          <?php if($current_station_id == 17): ?>
        <a href="../jodhpur-employees/add-employee-jodhpur.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Create Employee</span> </a>
        <a href="../jodhpur-employees/employee-jodhpur.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">View Employee</span> </a>
        <?php endif; ?>
        <!-- chnage password   -->
        <a href="change-password.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-key w-5"></i> <span class="text-sm font-medium">Change Dashboard Password</span> </a>
        <a href="change-app-password.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-key w-5"></i> <span class="text-sm font-medium">Change App Password</span> </a>
        
        
        <!--Remove -->
        <!--<a href="view-attendance.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-calendar-check w-5"></i> <span class="text-sm font-medium">View Attendance</span> </a>-->
        <!--<a href="attendance-report-row-wise.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-calendar-check w-5"></i> <span class="text-sm font-medium">View Attendance (ANDVH)</span> </a>-->
        <!--<a href="view-no-photo-attendance.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-calendar-check w-5"></i> <span class="text-sm font-medium">View no photo Attendance</span> </a>-->
        <!--<a href="salary.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-calendar-check w-5"></i> <span class="text-sm font-medium">Salary Report</span> </a>-->
        
    </nav>

</aside>
