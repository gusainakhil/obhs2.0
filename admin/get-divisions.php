<?php
require_once __DIR__ . '/connection.php';

header('Content-Type: application/json');

if(isset($_GET['zone_id']) && !empty($_GET['zone_id'])) {
    $zone_id = mysqli_real_escape_string($conn, $_GET['zone_id']);
    
    $division_query = "SELECT `id`, `name` FROM `OBHS_divisions` WHERE `zone_id` = '$zone_id' ORDER BY `name`";
    $division_result = mysqli_query($conn, $division_query);
    
    $divisions = array();
    while($division = mysqli_fetch_assoc($division_result)) {
        $divisions[] = array(
            'id' => $division['id'],
            'name' => $division['name']
            
        );
    }
    
    echo json_encode($divisions);
} else {
    echo json_encode(array());
}
?>