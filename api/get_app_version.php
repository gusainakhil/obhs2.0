<?php
// Allow cross-origin requests and return JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require "../includes/connection.php";

// Create MySQLi connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Initialize response
$response = [];

// Query the latest version
$query = "SELECT version FROM app_version ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $response = [
        "status" => "success",
        "version" => $row['version']
    ];
} else {
    $response = [
        "status" => "error",
        "message" => "No version found"
    ];
}

// Output the JSON response
echo json_encode($response);

// Close connection
mysqli_close($conn);
?>
