
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Backend </title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>
    Backend System -
    <?php
        if (!empty($station_name)) {
            echo $station_name;
        } elseif (!empty($stationName)) {
            echo $stationName;
        }
    ?>
</h1>

        <a href="../dashboard.php"><button class="logout-btn">User Dashboard</button></a>
    </div>
