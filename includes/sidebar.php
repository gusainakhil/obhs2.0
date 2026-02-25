<?php include "includes/connection.php"; ?>

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
  $user_id = $_SESSION['user_id'];   // Logged-in user

        $sql = "SELECT reports_name, link FROM OBHS_reports WHERE user_id = $user_id ORDER BY id ASC";
        $result = mysqli_query($mysqli, $sql);

        if(mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                echo '
                <a href="'.$row['link'].'"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition">
                    <i class="fas fa-angle-right w-5"></i>
                    <span class="text-sm font-medium">'.$row['reports_name'].'</span>
                </a>';
            }
        } else {
            echo '<p class="text-slate-400 text-sm px-4">No menu assigned.</p>';
        }
        ?>
        <?php if($_SESSION['station_id'] == 8): ?>
        <a href="feedback-single-train-report.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Train Report</span> </a>
        <?php endif; ?>
        <a href="feedback-target.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Feedback Target</span> </a>
        <a href="view-feedback-target.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">View Feedback Target</span> </a>
        <?php if($_SESSION['station_id'] != 17): ?>
        <a href="create-employee.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">Create Employee</span> </a>
        <a href="view-employee.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-slate-300 mb-1 hover:bg-slate-700 transition"> <i class="fas fa-angle-right"></i> <span class="text-sm font-medium">View Employee</span> </a>
        <?php endif; ?>

          <?php if($_SESSION['station_id'] == 17): ?>
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
