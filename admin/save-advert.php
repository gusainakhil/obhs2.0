<?php
require_once __DIR__ . '/connection.php';
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: Global-advertisment.php');
  exit;
}

$advert_text = trim($_POST['advert_text'] ?? '');

// basic validation
if ($advert_text === '') {
  $_SESSION['flash_error'] = 'Advertisement text is required.';
  header('Location: Global-advertisment.php');
  exit;
}

// handle file upload
if (!isset($_FILES['advert_image']) || $_FILES['advert_image']['error'] !== UPLOAD_ERR_OK) {
  $_SESSION['flash_error'] = 'Advertisement image is required.';
  header('Location: Global-advertisment.php');
  exit;
}

$file = $_FILES['advert_image'];

// validate file is an image
$tmp = $file['tmp_name'];
$imginfo = @getimagesize($tmp);
if ($imginfo === false) {
  $_SESSION['flash_error'] = 'Uploaded file is not a valid image.';
  header('Location: Global-advertisment.php');
  exit;
}

$allowed_types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
$ext = $allowed_types[$imginfo[2]] ?? null;
if ($ext === null) {
  $_SESSION['flash_error'] = 'Only JPG, PNG, GIF or WEBP images are allowed.';
  header('Location: Global-advertisment.php');
  exit;
}

// prepare upload dir
$upload_dir_rel = 'assets/img/ads';
$upload_dir = __DIR__ . '/' . $upload_dir_rel;
if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

$base_name = bin2hex(random_bytes(8));
$filename = $base_name . '.' . $ext;
$dest = $upload_dir . '/' . $filename;

if (!move_uploaded_file($tmp, $dest)) {
  $_SESSION['flash_error'] = 'Failed to move uploaded file.';
  header('Location: Global-advertisment.php');
  exit;
}

$image_path = $upload_dir_rel . '/' . $filename; // store relative path

// insert into DB
$now = date('Y-m-d H:i:s');
$sql = "INSERT INTO `OBHS_Globaladvertisment` (`info`, `image`, `date`) VALUES (?, ?, ?)";
if ($stmt = mysqli_prepare($conn, $sql)) {
  mysqli_stmt_bind_param($stmt, 'sss', $advert_text, $image_path, $now);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
} else {
  $ok = false;
}

if ($ok) {
  $_SESSION['flash_success'] = 'Advertisement published successfully.';
} else {
  $_SESSION['flash_error'] = 'Failed to save advertisement to database.';
  // attempt to clean up uploaded file
  if (file_exists($dest)) @unlink($dest);
}

header('Location: Global-advertisment.php');
exit;
