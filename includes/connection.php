<?php
$host = "184.168.122.185";
$user = "obhsbeatleanalyt_OBHSUSER";
$pass = "H59sjBnLJC}UZ};q";
$dbname = "obhsbeatleanalyt_OBHS";

// Create MySQLi connection
$mysqli = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
