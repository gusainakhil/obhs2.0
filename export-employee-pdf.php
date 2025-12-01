<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

checkLogin();

$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];

// Fetch all employees
$query = "SELECT * FROM base_employees WHERE station_id = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee List - <?php echo htmlspecialchars($station_name); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #0ea5e9;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <h1>Employee List - <?php echo htmlspecialchars($station_name); ?></h1>
    <p><strong>Generated on:</strong> <?php echo date('d-M-Y H:i:s'); ?></p>
    <p><strong>Total Employees:</strong> <?php echo count($employees); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>SR. No.</th>
                <th>Employee Name</th>
                <th>Employee ID</th>
                <th>Designation</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sr_no = 1;
            foreach ($employees as $employee): 
            ?>
            <tr>
                <td><?php echo $sr_no++; ?></td>
                <td><?php echo htmlspecialchars($employee['name']); ?></td>
                <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                <td><?php echo htmlspecialchars($employee['desination']); ?></td>
                <td><?php echo date('d-M-Y', strtotime($employee['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            Print / Save as PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
