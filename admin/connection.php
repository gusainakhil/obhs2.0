<?php
//set timezone 
date_default_timezone_set('Asia/Kolkata');

$servername = "184.168.122.185";
$username = "obhsbeatleanalyt_OBHSUSER";
$password = "H59sjBnLJC}UZ};q";
$dbname = "obhsbeatleanalyt_OBHS";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
