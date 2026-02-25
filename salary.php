<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

// Optional: enable detailed error output in development only
$debug = true; // set to false in production
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Call reusable login check
checkLogin();

// Now fetch station name
$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>|| Prime OBHS ||</title>
    <style>
        body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
}

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; text-align: center; padding: 5px; font-size: 10px; }
        th { background-color: #0057a3; color: white; }
        .highlight-yellow { background-color: #ffff99; color: black; }
        
        .header {
            text-transform: uppercase;
            font-size: 18px;
            font-weight: 600;
            font-family: 'Varela Round', sans-serif;
            background: linear-gradient(to right, #07a759, #48a9d4) !important;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            
        }

        .header i {
            line-height: 2;
            margin-right: 8px;
        }

        .header-title {
            display: flex;
            align-items: center;
        }

        .btn-danger {
            background-color: #d9534f;
            border: none;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c9302c;
        }

        .filter-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 20px 0;
        }

        .filter-section label {
            font-weight: bold;
        }

        .filter-section input[type="date"],
        .filter-section button {
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .filter-section button {
            background-color: #0057a3;
            color: white;
            cursor: pointer;
            border: none;
        }

        .filter-section button:hover {
            background-color: #003d75;
        }

        footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    
    <style>
  /* Modal Styling */
  .custom-modal {
      display: none; /* Hidden by default */
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
  }

  .modal-content {
      background-color: white;
      margin: 15% auto;
      padding: 20px;
      border-radius: 10px;
      width: 30%;
      text-align: center;
  }

  .close-btn {
      background: red;
      color: white;
      border: none;
      padding: 10px;
      cursor: pointer;
      border-radius: 5px;
  }
    </style>
    
    <!-- Modal -->
    <!--<div id="popupModal" class="custom-modal">-->
    <!--    <div class="modal-content">-->
    <!--        <h2>Subscription Expired</h2>-->
    <!--        <p>Your subscription has expired. Please contact your administrator.</p>-->
    <!--        <button class="close-btn" onclick="closeModal()">Close</button>-->
    <!--    </div>-->
    <!--</div>-->
    
    <!-- JavaScript to Show Modal -->
    <!--<script>-->
    <!--  document.addEventListener("DOMContentLoaded", function () {-->
    <!--      document.getElementById("popupModal").style.display = "block";-->
    <!--  });-->
    
    <!--  function closeModal() {-->
    <!--      document.getElementById("popupModal").style.display = "none";-->
    <!--  }-->
    <!--</script>-->

<div class="header">
  <div class="header-title">
    <img src="uploads/indian-railways.png" alt="Logo" style="height: 50px; margin-right: 10px;">
    <i class="fa fa-tachometer" aria-hidden="true"></i> 
    <?php echo htmlspecialchars($station_name); ?> OBHS Salaries
  </div>
  <a class="btn" href="dashboard.php">
  <img src="uploads/back.png" width="35px" alt="Back">
  </a>
</div>

<div class="filter-section">
    <form action="" method="GET" style="display: flex; align-items: center; gap: 10px;">
        <label for="from-date">From:</label>
        <input type="date" name="from_date" required>
        <label for="to-date">To:</label>
        <input type="date" name="to_date" required>
        <button type="submit">Go</button>
    </form>
    <button onclick="window.print();" style="padding: 10px 20px; background-color: #0057a3; color: white; border: none; cursor: pointer;">Print Report</button>
    <!--<form action="export_excel.php" method="GET">-->
    <!--<input type="hidden" name="from_date" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">-->
    <!--<input type="hidden" name="to_date" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">-->
    <!--<button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer;">Download as Excel</button>-->
    <!--</form>-->
</div>
<div style="overflow-x: auto; width: 98%; padding: 10px 20px">
    
<?php
if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
    $from_date = $_GET['from_date'];
    $to_date = $_GET['to_date'];
    
    $from_date_1 = isset($_GET['from_date']) ? date("d-m-Y", strtotime($_GET['from_date'])) : '';
    $to_date_1 = isset($_GET['to_date']) ? date("d-m-Y", strtotime($_GET['to_date'])) : '';

    echo "<h2 style='text-align: center;'>" . htmlspecialchars($station_name) . " OBHS Salaries - from: $from_date_1 To: $to_date_1</h2>";
    
    $trainGroups = [
    [12803, 12804], [12807, 12808], [20805, 20806], [58501, 58502],
    [12861, 12862], [18567, 18568], [20815, 20816], [18503, 18504],
    [22869, 22870], [18573, 18574], [22801, 22802], ["08583", "08584"],  
    [22701, 22702], ["08508", "08507"], ["08539", "08540"], [20803, 20804],  
    ["08557", "08558"], ["08585", "08586"], ["08551", "08552"]  
    ];

    echo "<table>";
    echo "<thead>";
    echo "<tr><th rowspan='2'>SL No</th><th rowspan='2' class='highlight-yellow'>Name Of Staff</th><th rowspan='2' class='highlight-yellow'>Staff_ID</th>";
    

    foreach ($trainGroups as $group) {
        echo "<th colspan='3'>" . implode('/', $group) . "</th>";
    }
    echo "<th rowspan='2' class='highlight-yellow'>Tot. AC</th><th rowspan='2' class='highlight-yellow'>Tot. Sv.</th><th rowspan='2' class='highlight-yellow'>Tot. N-AC</th><th rowspan='2' class='highlight-yellow'>Total</th></tr>";

    echo "<tr>";
    foreach ($trainGroups as $group) {
        echo "<th>AC</th><th>Sv.</th><th style='width:1.4%'>N-AC</th>";
    }
    echo "</tr>";
    echo "</thead><tbody>";

    $a = 0; 
    $from_date = $_GET['from_date'];
    $to_date = $_GET['to_date'];
    
    // Convert them to full DATETIME ranges for the database
    $start_datetime = $from_date . " 00:00:00"; // Start of the first day
    $end_datetime = $to_date . " 23:59:59";

    $query = "
    SELECT 
        employee_id, employee_name, desination, train_no, toc,employee_name_unique,
        COUNT(CASE WHEN toc = 'AC' AND desination = 'Janitor' THEN type_of_attendance END) AS AC_count,
        COUNT(CASE WHEN toc = 'Non-AC' AND desination = 'Janitor' THEN type_of_attendance END) AS Non_AC_count,
        COUNT(CASE WHEN desination = 'Supervisor' THEN type_of_attendance END) AS Supervisor_count
        
    FROM base_attendance
    WHERE station_id = ? 
        AND employee_name IS NOT NULL
        AND created_at BETWEEN ? AND ?
    GROUP BY employee_id, employee_name, desination,  toc, employee_name_unique
    HAVING 
        FIND_IN_SET('Start of journey', GROUP_CONCAT(DISTINCT type_of_attendance)) > 0
        AND FIND_IN_SET('Mid of journey', GROUP_CONCAT(DISTINCT type_of_attendance)) > 0
        AND FIND_IN_SET('End of journey', GROUP_CONCAT(DISTINCT type_of_attendance)) > 0
    ORDER BY employee_name;
    ";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sss", $station_id, $start_datetime, $end_datetime);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $employeeData = [];

    foreach ($results as $row) {
        if (!isset($employeeData[$row['employee_id']])) {
            $employeeData[$row['employee_id']] = [
                'employee_name' => $row['employee_name'],
                'employee_id' => $row['employee_id'],
                'data' => []
            ];
        }

        $trainGroupKey = $row['train_no'];
        if (!isset($employeeData[$row['employee_id']]['data'][$trainGroupKey])) {
            $employeeData[$row['employee_id']]['data'][$trainGroupKey] = ['AC' => 0, 'Supervisor' => 0, 'Non-AC' => 0];
        }

        if ($row['AC_count'] >= 6) {
            $employeeData[$row['employee_id']]['data'][$trainGroupKey]['AC']++;
        }
        if ($row['Supervisor_count'] >= 6) {
            $employeeData[$row['employee_id']]['data'][$trainGroupKey]['Supervisor']++;
        }
        if ($row['Non_AC_count'] >= 6) {
            $employeeData[$row['employee_id']]['data'][$trainGroupKey]['Non-AC']++;
        }
    }

    
    $totalAcCount = 0;
    $totalSupervisorCount = 0;
    $totalNonAcCount = 0;
    $totalWageDays = 0;

    
    $totalTrainAcCounts = array_fill(0, count($trainGroups), 0);
    $totalTrainSupervisorCounts = array_fill(0, count($trainGroups), 0);
    $totalTrainNonAcCounts = array_fill(0, count($trainGroups), 0);

    foreach ($employeeData as $employee) {
        $employeeAcCount = 0;
        $employeeSupervisorCount = 0;
        $employeeNonAcCount = 0;
        $employeeWageDays = 0;

        
        foreach ($trainGroups as $groupIndex => $group) {
            foreach ($group as $train) {
                if (isset($employee['data'][$train])) {
                    $employeeAcCount += $employee['data'][$train]['AC'];
                    $employeeSupervisorCount += $employee['data'][$train]['Supervisor'];
                    $employeeNonAcCount += $employee['data'][$train]['Non-AC'];
                    $employeeWageDays += array_sum($employee['data'][$train]);

                    
                    $totalTrainAcCounts[$groupIndex] += $employee['data'][$train]['AC'];
                    $totalTrainSupervisorCounts[$groupIndex] += $employee['data'][$train]['Supervisor'];
                    $totalTrainNonAcCounts[$groupIndex] += $employee['data'][$train]['Non-AC'];
                }
            }
        }

       
        if ($employeeWageDays > 0) {
            $a++;
            echo "<tr>";
            echo "<td>$a</td>"; 
            echo "<td>" . htmlspecialchars($employee['employee_name']) . "</td>";
            echo "<td>" . htmlspecialchars($employee['employee_id']) . "</td>";

            
            foreach ($trainGroups as $groupIndex => $group) {
                $trainAcCount = 0;
                $trainSupervisorCount = 0;
                $trainNonAcCount = 0;

                foreach ($group as $train) {
                    if (isset($employee['data'][$train])) {
                        $trainAcCount += $employee['data'][$train]['AC'];
                        $trainSupervisorCount += $employee['data'][$train]['Supervisor'];
                        $trainNonAcCount += $employee['data'][$train]['Non-AC'];
                    }
                }

                echo "<td>" . ($trainAcCount > 0 ? $trainAcCount : "-") . "</td>";
                echo "<td>" . ($trainSupervisorCount > 0 ? $trainSupervisorCount : "-") . "</td>";
                echo "<td>" . ($trainNonAcCount > 0 ? $trainNonAcCount : "-") . "</td>";
            }

            
            echo "<td>$employeeAcCount</td>";
            echo "<td>$employeeNonAcCount</td>";
            echo "<td>$employeeSupervisorCount</td>";
            echo "<td>$employeeWageDays</td>";
            echo "</tr>";

            
            $totalAcCount += $employeeAcCount;
            $totalNonAcCount += $employeeNonAcCount;
            $totalSupervisorCount += $employeeSupervisorCount;
            $totalWageDays += $employeeWageDays;
        }
    }

    
    echo "<tr style='font-weight: bold;'>";
    echo "<td class='highlight-yellow' colspan='3'>Grand Total</td>";

    
    foreach ($trainGroups as $groupIndex => $group) {
        echo "<td class='highlight-yellow'>" . $totalTrainAcCounts[$groupIndex] . "</td>";
        echo "<td class='highlight-yellow'>" . $totalTrainSupervisorCounts[$groupIndex] . "</td>";
        echo "<td class='highlight-yellow'>" . $totalTrainNonAcCounts[$groupIndex] . "</td>";
    }

    
    echo "<td class='highlight-yellow'>$totalAcCount</td>";
    echo "<td class='highlight-yellow'>$totalSupervisorCount</td>";
    echo "<td class='highlight-yellow'>$totalNonAcCount</td>";
    echo "<td class='highlight-yellow'>$totalWageDays</td>";
    echo "</tr>";

    echo "</tbody></table>";
}
?>
</div>
<footer>
    <p> Copyright © 2016 | All Rights Reserved | Designed & Developed by Beatle Analytics</p>
</footer>
</body>
</html>
