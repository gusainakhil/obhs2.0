<?php session_start();
include '../includes/connection.php';
include '../includes/helpers.php';
$station_id=$_SESSION['station_id'];
$stationName = getStationName($station_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php echo $stationName;?> </title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Backend System - <?php echo isset($stationName) ? $stationName : ''; ?></h1>
        <a href="../backend/dashboard.php"><button class="logout-btn">User Dashboard</button></a>
    </div>
