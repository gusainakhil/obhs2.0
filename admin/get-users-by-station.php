<?php
require_once __DIR__ . '/connection.php';
header('Content-Type: application/json; charset=utf-8');
// simple endpoint to return users for a station
$station_id = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 0;
$out = [];
if ($station_id > 0) {
  $sql = "SELECT user_id, username, organisation_name FROM `OBHS_users` WHERE station_id = ? AND type = 2 ORDER BY username ASC";
  if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $station_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
      while ($row = mysqli_fetch_assoc($res)) {
        $out[] = ['id' => (int)$row['user_id'], 'label' => $row['username'] . ' - ' . ($row['organisation_name'] ?? '')];
      }
    }
    mysqli_stmt_close($stmt);
  }
}
echo json_encode($out);
exit;
