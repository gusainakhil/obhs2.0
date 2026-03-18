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
$rows = [];
$searched = false;
$train_numbers = [];
$filter_date = trim($_GET['filter_date'] ?? '');
$filter_train_up = trim($_GET['filter_train_up'] ?? '');
$filter_train_down = trim($_GET['filter_train_down'] ?? '');
$openEditModal = false;
$modal_id = '';
$modal_train_up = '';
$modal_train_down = '';
$modal_date = '';

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

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pdf_attendence'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $train_up = trim($_POST['train_up'] ?? '');
    $train_down = trim($_POST['train_down'] ?? '');
    $from_date = trim($_POST['from_date'] ?? '');

    $modal_id = (string) $id;
    $modal_train_up = $train_up;
    $modal_train_down = $train_down;
    $modal_date = $from_date;

    if ($id <= 0 || $train_up === '' || $train_down === '' || $from_date === '') {
        $error_message = 'Please fill all required fields.';
        $openEditModal = true;
    } else {
        $currentSql = 'SELECT pdf_file FROM pdf_attendence WHERE id = ? AND station_id = ? LIMIT 1';
        $currentStmt = $mysqli->prepare($currentSql);
        $currentFile = '';

        if ($currentStmt) {
            $currentStmt->bind_param('ii', $id, $station_id);
            $currentStmt->execute();
            $currentResult = $currentStmt->get_result();
            $currentRow = $currentResult->fetch_assoc();
            $currentStmt->close();
            if (!$currentRow) {
                $error_message = 'Record not found.';
                $openEditModal = true;
            } else {
                $currentFile = (string) $currentRow['pdf_file'];
            }
        } else {
            $error_message = 'Database error: ' . $mysqli->error;
            $openEditModal = true;
        }

        if ($error_message === '') {
            $upload_dir = '../uploads/pdf/';
            $newFileName = $currentFile;

            if (isset($_FILES['attendance_pdf']) && $_FILES['attendance_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['attendance_pdf'];
                $maxSize = 10 * 1024 * 1024; // 10MB
                $allowedMime = ['application/pdf', 'application/x-pdf'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_message = 'File upload failed with code: ' . $file['error'];
                    $openEditModal = true;
                } elseif ($file['size'] > $maxSize) {
                    $error_message = 'PDF size must be less than 10MB.';
                    $openEditModal = true;
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
                        $openEditModal = true;
                    } else {
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $newFileName = $station_id . '_' . date('Ymd_His') . '_' . uniqid('', true) . '.pdf';
                        $destination = $upload_dir . $newFileName;
                        if (!move_uploaded_file($file['tmp_name'], $destination)) {
                            $error_message = 'Could not save PDF in uploads/pdf folder.';
                            $openEditModal = true;
                        }
                    }
                }
            }

            if ($error_message === '') {
                $updateSql = 'UPDATE pdf_attendence SET train_up = ?, train_down = ?, from_date = ?, pdf_file = ? WHERE id = ? AND station_id = ?';
                $updateStmt = $mysqli->prepare($updateSql);
                if (!$updateStmt) {
                    $error_message = 'Prepare failed: ' . $mysqli->error;
                    $openEditModal = true;
                } else {
                    $updateStmt->bind_param('ssssii', $train_up, $train_down, $from_date, $newFileName, $id, $station_id);
                    if ($updateStmt->execute()) {
                        $success_message = 'Record updated successfully.';
                        $openEditModal = false;
                        $modal_id = '';
                        $modal_train_up = '';
                        $modal_train_down = '';
                        $modal_date = '';

                        if ($newFileName !== $currentFile && $currentFile !== '') {
                            $oldPath = $upload_dir . $currentFile;
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    } else {
                        $error_message = 'Update failed: ' . $updateStmt->error;
                        $openEditModal = true;
                    }
                    $updateStmt->close();
                }
            }
        }
    }
}

if ($filter_date !== '' && $filter_train_up !== '' && $filter_train_down !== '') {
    $searched = true;
    $listSql = 'SELECT id, train_up, train_down, from_date, pdf_file, created_at FROM pdf_attendence WHERE station_id = ? AND train_up = ? AND train_down = ? AND from_date = ? ORDER BY id DESC';
    $listStmt = $mysqli->prepare($listSql);
    if ($listStmt) {
        $listStmt->bind_param('isss', $station_id, $filter_train_up, $filter_train_down, $filter_date);
        $listStmt->execute();
        $result = $listStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $listStmt->close();
    }
} elseif (isset($_GET['search_pdf'])) {
    $error_message = 'Please enter Train Up, Train Down and Date to search.';
}

$pageTitle = 'Edit PDF Attendence';
?>
<?php include 'header.php'; ?>

<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="content-section">
            <h2>Edit PDF Attendence</h2>

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

            <form method="GET" style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="filter_train_up">Train Up</label>
                        <select name="filter_train_up" id="filter_train_up" required>
                            <option value="">Select Train Up</option>
                            <?php foreach ($train_numbers as $train_no): ?>
                                <option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $filter_train_up === $train_no ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($train_no); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_train_down">Train Down</label>
                        <select name="filter_train_down" id="filter_train_down" required>
                            <option value="">Select Train Down</option>
                            <?php foreach ($train_numbers as $train_no): ?>
                                <option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $filter_train_down === $train_no ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($train_no); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_date">Date</label>
                        <input type="date" name="filter_date" id="filter_date" required value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                </div>
                <div class="button-group">
                    <button type="submit" name="search_pdf" value="1" class="btn btn-primary">Show PDF</button>
                    <a href="edit-pdf-attendence.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Reset</a>
                </div>
            </form>

            <?php if ($searched): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Train Up</th>
                        <th>Train Down</th>
                        <th>Date</th>
                        <th>PDF</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="7">No records found for selected Train Up, Train Down and Date.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo (int) $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['train_up']); ?></td>
                                <td><?php echo htmlspecialchars($row['train_down']); ?></td>
                                <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                <td>
                                    <a href="../uploads/pdf/<?php echo rawurlencode($row['pdf_file']); ?>" target="_blank">View PDF</a>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <button type="button"
                                            class="btn btn-secondary open-edit-modal"
                                            style="padding: 7px 12px;"
                                            data-id="<?php echo (int) $row['id']; ?>"
                                            data-train-up="<?php echo htmlspecialchars($row['train_up'], ENT_QUOTES); ?>"
                                            data-train-down="<?php echo htmlspecialchars($row['train_down'], ENT_QUOTES); ?>"
                                            data-date="<?php echo htmlspecialchars($row['from_date'], ENT_QUOTES); ?>">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; margin-top: 10px;">
                Enter Train Up, Train Down and Date, then click Show PDF.
            </div>
            <?php endif; ?>

            <div id="editPdfModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 9999; padding: 18px; overflow-y: auto;">
                <div style="max-width: 860px; margin: 40px auto; background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h3 style="margin: 0; color: #20a779;">Edit PDF Attendence</h3>
                        <button type="button" id="closeEditModalBtn" class="btn btn-danger" style="padding: 7px 12px;">Close</button>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="modal_id" value="<?php echo htmlspecialchars($modal_id); ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_train_up">Train Up</label>
                                <select name="train_up" id="modal_train_up" required>
                                    <option value="">Select Train Up</option>
                                    <?php foreach ($train_numbers as $train_no): ?>
                                        <option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $modal_train_up === $train_no ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($train_no); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_train_down">Train Down</label>
                                <select name="train_down" id="modal_train_down" required>
                                    <option value="">Select Train Down</option>
                                    <?php foreach ($train_numbers as $train_no): ?>
                                        <option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $modal_train_down === $train_no ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($train_no); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_date">Date</label>
                                <input type="date" name="from_date" id="modal_date" required value="<?php echo htmlspecialchars($modal_date); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_pdf">Replace PDF (optional)</label>
                                <input type="file" name="attendance_pdf" id="modal_pdf" accept="application/pdf,.pdf">
                            </div>
                        </div>

                        <div class="button-group">
                            <button type="submit" name="update_pdf_attendence" class="btn btn-primary">Update</button>
                            <button type="button" id="cancelEditModalBtn" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('editPdfModal');
        var closeBtn = document.getElementById('closeEditModalBtn');
        var cancelBtn = document.getElementById('cancelEditModalBtn');
        var openButtons = document.querySelectorAll('.open-edit-modal');

        function openModal() {
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function closeModal() {
            if (modal) {
                modal.style.display = 'none';
            }
        }

        openButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id') || '';
                var trainUp = btn.getAttribute('data-train-up') || '';
                var trainDown = btn.getAttribute('data-train-down') || '';
                var date = btn.getAttribute('data-date') || '';

                var idInput = document.getElementById('modal_id');
                var upInput = document.getElementById('modal_train_up');
                var downInput = document.getElementById('modal_train_down');
                var dateInput = document.getElementById('modal_date');

                if (idInput) idInput.value = id;
                if (upInput) upInput.value = trainUp;
                if (downInput) downInput.value = trainDown;
                if (dateInput) dateInput.value = date;

                openModal();
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        var shouldOpenOnLoad = <?php echo $openEditModal ? 'true' : 'false'; ?>;
        if (shouldOpenOnLoad) {
            openModal();
        }
    })();
</script>

<?php include 'footer.php'; ?>
