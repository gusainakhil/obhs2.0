<?php
session_start();
include '../includes/connection.php';
include '../includes/helpers.php';

// Enable error reporting for development
$debug = true; // set to false in production
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Check login
checkLogin();

// Set timezone to Asia/Kolkata
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Kolkata');
}

// Get station information
$station_name = getStationName($_SESSION['station_id']);
$station_id = (int) $_SESSION['station_id'];

$success_message = '';
$error_message = '';
$train_numbers = [];
$train_up = '';
$train_down = '';
$from_date = '';

$trainSql = 'SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no';
$trainStmt = $mysqli->prepare($trainSql);
if ($trainStmt) {
    $trainStmt->bind_param('i', $station_id);
    $trainStmt->execute();
    $trainResult = $trainStmt->get_result();
    while ($trainRow = $trainResult->fetch_assoc()) {
        $train_numbers[] = (string) $trainRow['train_no'];
    }
    $trainStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pdf_attendence'])) {
    $train_up = trim($_POST['train_up'] ?? '');
    $train_down = trim($_POST['train_down'] ?? '');
    $from_date = trim($_POST['from_date'] ?? '');

    if ($train_up === '' || $train_down === '' || $from_date === '') {
        $error_message = 'Please fill Train Up, Train Down and Date.';
    } elseif (!isset($_FILES['attendance_pdf']) || $_FILES['attendance_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
        $error_message = 'Please upload a PDF file.';
    } else {
        $file = $_FILES['attendance_pdf'];

        $upload_dir = '../uploads/pdf/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedMime = ['application/pdf', 'application/x-pdf'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'File upload failed with code: ' . $file['error'];
        } elseif ($file['size'] > $maxSize) {
            $error_message = 'PDF size must be less than 10MB.';
        } else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = '';

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = strtolower((string) finfo_file($finfo, $file['tmp_name']));
                    finfo_close($finfo);
                }
            }

            if ($extension !== 'pdf' || ($mime !== '' && !in_array($mime, $allowedMime, true))) {
                $error_message = 'Only valid PDF files are allowed.';
            } else {
                $new_file_name = $station_id . '_' . date('Ymd_His') . '_' . uniqid('', true) . '.pdf';
                $destination = $upload_dir . $new_file_name;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error_message = 'Could not save PDF in uploads/pdf folder.';
                } else {
                    $created_by = 'BACKEND';
                    $insertSql = 'INSERT INTO pdf_attendence (station_id, train_up, train_down, from_date, pdf_file, created_by) VALUES (?, ?, ?, ?, ?, ?)';
                    $stmt = $mysqli->prepare($insertSql);

                    if (!$stmt) {
                        $error_message = 'Prepare failed: ' . $mysqli->error;
                    } else {
                        $stmt->bind_param('isssss', $station_id, $train_up, $train_down, $from_date, $new_file_name, $created_by);
                        if ($stmt->execute()) {
                            $success_message = 'PDF uploaded and saved successfully.';
                        } else {
                            $error_message = 'Database insert failed: ' . $stmt->error;
                            if (file_exists($destination)) {
                                unlink($destination);
                            }
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

$pageTitle = 'Create PDF Attendence';
?>
<?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="content">
            <div class="content-section">
                <h2>Create PDF Attendence</h2>

                <?php if ($success_message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="train_up">Train Up</label>
                            <select name="train_up" id="train_up" required>
                                <option value="">Select Train Up</option>
                                <?php foreach ($train_numbers as $train_no): ?>
                                    <option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $train_up === $train_no ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train_no); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="train_down">Train Down</label>
                            <select name="train_down" id="train_down" required>
                                <option value="">Select Train Down</option>
                                <?php foreach ($train_numbers as $train_no): ?>
                                    <option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $train_down === $train_no ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train_no); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="from_date">Date</label>
                            <input type="date" name="from_date" id="from_date" required value="<?php echo htmlspecialchars($from_date); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="attendance_pdf">Upload PDF</label>
                            <input type="file" name="attendance_pdf" id="attendance_pdf" accept="application/pdf,.pdf" required>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="submit_pdf_attendence" class="btn btn-primary">Upload PDF</button>
                        <a href="edit-pdf-attendence.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Show / Edit PDF</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
