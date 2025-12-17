<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require "../includes/connection.php";

/* ---------- GENERATE PASSENGER ID ---------- */
$passenger_id = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 32);

/* ---------- READ BASIC POST DATA ---------- */
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
        move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $photo_name);
    }
}

/* ---------- START TRANSACTION ---------- */
mysqli_begin_transaction($mysqli);

/* ---------- INSERT PASSENGER ---------- */
$sql = "INSERT INTO OBHS_passenger 
(id, station_id, pnr_number, name, grade, coach_type, train_no, coach_no, seat_no, ph_number, verified, photo)
VALUES 
('$passenger_id', '$station_id', '$pnr_number', '$name', '$grade', '$coach_type', '$train_no', '$coach_no', '$seat_no', '$ph_number', '$verified', '$photo_name')";

if (!mysqli_query($mysqli, $sql)) {
    mysqli_rollback($mysqli);
    echo json_encode(["status" => false, "msg" => mysqli_error($mysqli)]);
    exit;
}

/* ---------- FEEDBACK DATA (JSON STRING → ARRAY) ---------- */
$feed_param = json_decode($_POST["feed_param"] ?? "[]", true);
$super_name = json_decode($_POST["super_name"] ?? "[]", true);
$value      = json_decode($_POST["value"] ?? "[]", true);

/* ---------- VALIDATE & INSERT FEEDBACK ---------- */
if (
    is_array($feed_param) &&
    is_array($super_name) &&
    is_array($value) &&
    count($feed_param) === count($super_name) &&
    count($feed_param) === count($value)
) {
    for ($i = 0; $i < count($feed_param); $i++) {

        $p = mysqli_real_escape_string($mysqli, $feed_param[$i]);
        $s = mysqli_real_escape_string($mysqli, $super_name[$i]);
        $v = mysqli_real_escape_string($mysqli, $value[$i]);

        $q = "INSERT INTO OBHS_feedback 
              (passenger_id, feed_param, super_name, value) 
              VALUES ('$passenger_id', '$p', '$s', '$v')";

        if (!mysqli_query($mysqli, $q)) {
            mysqli_rollback($mysqli);
            echo json_encode(["status" => false, "msg" => mysqli_error($mysqli)]);
            exit;
        }
    }
}

/* ---------- COMMIT TRANSACTION ---------- */
mysqli_commit($mysqli);

/* ---------- SUCCESS RESPONSE ---------- */
echo json_encode([
    "status" => true,
    "msg" => "Passenger & feedback submitted successfully",
    "passenger_id" => $passenger_id,
    "photo" => $photo_name
]);
