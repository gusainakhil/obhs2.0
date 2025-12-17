<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require "../includes/connection.php";

/* Generate passenger ID */
$passenger_id = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 32);

/* Read POST data */
$station_id = $_POST["station_id"] ?? "";
$pnr_number = $_POST["pnr_number"] ?? "";
$name       = $_POST["name"] ?? "";
$grade      = $_POST["grade"] ?? "";
$coach_type = $_POST["coach_type"] ?? "";
$train_no   = $_POST["train_no"] ?? "";
$coach_no   = $_POST["coach_no"] ?? "";
$seat_no    = $_POST["seat_no"] ?? "";
$ph_number  = $_POST["ph_number"] ?? "";
$verified   = $_POST["verified"] ?? 0;

/* ---------- PHOTO (OPTIONAL) ---------- */
$photo_name = null;

if (!empty($_FILES["photo"]) && $_FILES["photo"]["error"] === 0) {

    $upload_dir = "../passenger_photos/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png"];

    if (in_array($ext, $allowed)) {
        $photo_name = $passenger_id . "." . $ext;
        $photo_path = $upload_dir . $photo_name;
        move_uploaded_file($_FILES["photo"]["tmp_name"], $photo_path);
        // ❌ no exit even if move fails
    }
}

/* ---------- INSERT PASSENGER ---------- */
$sql = "INSERT INTO OBHS_passenger 
(id, station_id, pnr_number, name, grade, coach_type, train_no, coach_no, seat_no, ph_number, verified, photo)
VALUES 
('$passenger_id', '$station_id', '$pnr_number', '$name', '$grade', '$coach_type', '$train_no', '$coach_no', '$seat_no', '$ph_number', '$verified', '$photo_name')";

if (!mysqli_query($mysqli, $sql)) {
    echo json_encode([
        "status" => false,
        "msg" => mysqli_error($mysqli)
    ]);
    exit;
}

/* ---------- FEEDBACK DATA ---------- */
$feed_param = $_POST["feed_param"] ?? [];
$super_name = $_POST["super_name"] ?? [];
$value      = $_POST["value"] ?? [];

if (is_array($feed_param)) {
    for ($i = 0; $i < count($feed_param); $i++) {
        $p = $feed_param[$i];
        $s = $super_name[$i];
        $v = $value[$i];

        mysqli_query(
            $mysqli,
            "INSERT INTO OBHS_feedback (passenger_id, feed_param, super_name, value) 
             VALUES ('$passenger_id', '$p', '$s', '$v')"
        );
    }
}

/* ---------- SUCCESS RESPONSE ---------- */
echo json_encode([
    "status" => true,
    "msg" => "Data submitted successfully",
    "passenger_id" => $passenger_id,
    "photo" => $photo_name   // null if not uploaded
]);
?>
