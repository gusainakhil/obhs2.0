<?php
session_start();
// Set proper content type and encoding
header('Content-Type: text/html; charset=utf-8');
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

// Get station information
$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];

// Fetch trains
$trains = [];
$train_query = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no";
$stmt = $mysqli->prepare($train_query);
$stmt->bind_param("s", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trains[] = $row['train_no'];
}
$stmt->close();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $delete_query = "DELETE FROM base_attendance WHERE id = ? AND station_id = ?";
    $stmt = $mysqli->prepare($delete_query);
    $stmt->bind_param("is", $delete_id, $station_id);
    $stmt->execute();
    $stmt->close();
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $update_id = $_POST['update_id'];
    $employee_name = $_POST['employee_name'];
    $employee_id = $_POST['employee_id'];
    $desination = $_POST['desination'];
    $toc = $_POST['toc'];
    $train_no = $_POST['train_no'];
    $type_of_attendance = $_POST['type_of_attendance'];
    $location = $_POST['location'];
    $grade = $_POST['grade'];
    // Created at (datetime-local -> MySQL DATETIME)
    $created_at_input = $_POST['created_at'] ?? '';
    $created_at_mysql = null;
    if (!empty($created_at_input)) {
        // Convert "YYYY-MM-DDTHH:MM:SS" or "YYYY-MM-DDTHH:MM" to "YYYY-MM-DD HH:MM:SS"
        $created_at_mysql = str_replace('T', ' ', $created_at_input);
        // If seconds are missing, add them
        if (strlen($created_at_mysql) === 16) {
            $created_at_mysql .= ':00';
        }
        // If only 19 chars, it already has seconds
    }
    
    // -----------------------------------------
    // HANDLE PHOTO - Compress and convert to WebP
    // -----------------------------------------
    $photo_filename = "";
    $uploadDir = '../uploads/attendence/';
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowed = ['image/jpeg', 'image/jpg', 'image/png'];
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

        $f = $_FILES['photo'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $photo_filename = null;
        } elseif ($f['size'] > $maxSize) {
            $photo_filename = null;
        } else {
            $mime = @getimagesize($f['tmp_name'])['mime'] ?? '';
            if (!in_array($mime, $allowed)) {
                $photo_filename = null;
            } else {
                // Build filename with .webp extension
                $filename = $station_id . '_' . date('Ymd_His') . '_' . uniqid() . '.webp';
                $dest     = $uploadDir . $filename;

                // Create image resource from uploaded file
                $sourceImage = null;
                if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                    $sourceImage = @imagecreatefromjpeg($f['tmp_name']);
                } elseif ($mime === 'image/png') {
                    $sourceImage = @imagecreatefrompng($f['tmp_name']);
                }

                if (!$sourceImage) {
                    $photo_filename = null;
                } else {
                    // Get original dimensions
                    $origWidth = imagesx($sourceImage);
                    $origHeight = imagesy($sourceImage);

                    // Resize if image is too large (max 1920px on longest side)
                    $maxDimension = 1920;
                    $newWidth = $origWidth;
                    $newHeight = $origHeight;

                    if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                        if ($origWidth > $origHeight) {
                            $newWidth = $maxDimension;
                            $newHeight = (int)($origHeight * ($maxDimension / $origWidth));
                        } else {
                            $newHeight = $maxDimension;
                            $newWidth = (int)($origWidth * ($maxDimension / $origHeight));
                        }
                        
                        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                        // Preserve transparency for PNG
                        imagealphablending($resizedImage, false);
                        imagesavealpha($resizedImage, true);
                        
                        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                        imagedestroy($sourceImage);
                        $sourceImage = $resizedImage;
                    }

                    // Convert and compress to WebP (quality 80)
                    $webpQuality = 80;
                    if (!imagewebp($sourceImage, $dest, $webpQuality)) {
                        imagedestroy($sourceImage);
                        $photo_filename = null;
                    } else {
                        imagedestroy($sourceImage);
                        $photo_filename = $filename;
                    }
                }
            }
        }
    }
    
    // Update query with or without photo
    $created_by = 'BACKEND';
    if ($photo_filename) {
        $update_query = "UPDATE base_attendance 
                         SET employee_name = ?, employee_id = ?, desination = ?, toc = ?, train_no = ?, type_of_attendance = ?, location = ?, grade = ?, photo = ?, created_by = ?, created_at = ? 
                         WHERE id = ? AND station_id = ?";
        $stmt = $mysqli->prepare($update_query);
        $stmt->bind_param("sssssssssssis", $employee_name, $employee_id, $desination, $toc, $train_no, $type_of_attendance, $location, $grade, $photo_filename, $created_by, $created_at_mysql, $update_id, $station_id);
    } else {
        $update_query = "UPDATE base_attendance 
                         SET employee_name = ?, employee_id = ?, desination = ?, toc = ?, train_no = ?, type_of_attendance = ?, location = ?, grade = ?, created_by = ?, created_at = ? 
                         WHERE id = ? AND station_id = ?";
        $stmt = $mysqli->prepare($update_query);
        $stmt->bind_param("ssssssssssis", $employee_name, $employee_id, $desination, $toc, $train_no, $type_of_attendance, $location, $grade, $created_by, $created_at_mysql, $update_id, $station_id);
    }
    
    $stmt->execute();
    $stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_REQUEST));
    exit;
}

// Get filter parameters
$selected_grade = $_REQUEST['grade'] ?? '';
$selected_train_from = $_REQUEST['trainFrom'] ?? '';
$selected_train_to = $_REQUEST['trainTo'] ?? '';
$date_from = $_REQUEST['dateFrom'] ?? date('Y-m-01');
$date_to = $_REQUEST['dateTo'] ?? date('Y-m-d');

// Build query based on filters
$where_conditions = ["station_id = ?"];
$params = [$station_id];
$types = "s";

if (!empty($selected_grade)) {
    $where_conditions[] = "grade = ?";
    $params[] = $selected_grade;
    $types .= "s";
}

if (!empty($selected_train_from)) {
    $where_conditions[] = "train_no = ?";
    $params[] = $selected_train_from;
    $types .= "s";
}

if (!empty($selected_train_to)) {
    $where_conditions[] = "train_no = ?";
    $params[] = $selected_train_to;
    $types .= "s";
}

if (!empty($date_from) && !empty($date_to)) {
    $where_conditions[] = "DATE(created_at) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Fetch attendance data grouped by employee
$attendance_data = [];
if (!empty($selected_grade) && !empty($selected_train_from) && !empty($selected_train_to)) {
    $query = "SELECT 
                ba.id,
                ba.employee_id,
                ba.employee_name,
                ba.desination,
                ba.toc,
                ba.train_no,
                ba.type_of_attendance,
                ba.location,
                ba.photo,
                ba.created_at,
                be.photo as employee_photo
              FROM base_attendance ba
              LEFT JOIN base_employees be ON ba.employee_id = be.employee_id AND be.station = ?
              WHERE ba.station_id = ?
              AND ba.grade = ?
              AND ba.train_no IN (?, ?)
              AND DATE(ba.created_at) BETWEEN ? AND ?
              ORDER BY ba.employee_name, ba.train_no, FIELD(ba.type_of_attendance, 'Start of journey', 'Mid of journey', 'End of journey')";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssssss", $station_id, $station_id, $selected_grade, $selected_train_from, $selected_train_to, $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Organize data by employee
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['employee_id'];
        
        if (!isset($attendance_data[$emp_id])) {
            $employee_photo = $row['employee_photo'] ?? '';
            
            $attendance_data[$emp_id] = [
                'employee_name' => $row['employee_name'],
                'employee_id' => $row['employee_id'],
                'employee_photo' => $employee_photo,
                'train_from' => [],
                'train_to' => []
            ];
        }
        
        // Organize by train and checkpoint type
        if ($row['train_no'] == $selected_train_from) {
            $attendance_data[$emp_id]['train_from'][$row['type_of_attendance']] = [
                'id' => $row['id'],
                'desination' => $row['desination'],
                'toc' => $row['toc'],
                'location' => $row['location'],
                'photo' => $row['photo'],
                'created_at' => $row['created_at']
            ];
        } elseif ($row['train_no'] == $selected_train_to) {
            $attendance_data[$emp_id]['train_to'][$row['type_of_attendance']] = [
                'id' => $row['id'],
                'desination' => $row['desination'],
                'toc' => $row['toc'],
                'location' => $row['location'],
                'photo' => $row['photo'],
                'created_at' => $row['created_at']
            ];
        }
    }
    $stmt->close();
}

$pageTitle = "Edit Attendance";
?>
<?php include 'header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <h2>Edit Attendance</h2>
                
                <!-- Search Filters -->
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Grade:</label>
                            <select name="grade">
                                <option value="">All Grades</option>
                                <option value="A" <?php echo $selected_grade === 'A' ? 'selected' : ''; ?>>A - Monday</option>
                                <option value="B" <?php echo $selected_grade === 'B' ? 'selected' : ''; ?>>B - Tuesday</option>
                                <option value="C" <?php echo $selected_grade === 'C' ? 'selected' : ''; ?>>C - Wednesday</option>
                                <option value="D" <?php echo $selected_grade === 'D' ? 'selected' : ''; ?>>D - Thursday</option>
                                <option value="E" <?php echo $selected_grade === 'E' ? 'selected' : ''; ?>>E - Friday</option>
                                <option value="F" <?php echo $selected_grade === 'F' ? 'selected' : ''; ?>>F - Saturday</option>
                                <option value="G" <?php echo $selected_grade === 'G' ? 'selected' : ''; ?>>G - Sunday</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Train From:</label>
                            <select name="trainFrom">
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo htmlspecialchars($train); ?>" <?php echo $selected_train_from === $train ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Train To:</label>
                            <select name="trainTo">
                                <option value="">Select Train</option>
                                <?php foreach ($trains as $train): ?>
                                    <option value="<?php echo htmlspecialchars($train); ?>" <?php echo $selected_train_to === $train ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($train); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date From:</label>
                            <input type="date" name="dateFrom" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="form-group">
                            <label>Date To:</label>
                            <input type="date" name="dateTo" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                
                <!-- Attendance Table -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 200px;">Employee</th>
                            <th colspan="3" style="background: #20a779;">Train From: <?php echo htmlspecialchars($selected_train_from ?: 'N/A'); ?></th>
                            <th colspan="3" style="background: #20a779;">Train To: <?php echo htmlspecialchars($selected_train_to ?: 'N/A'); ?></th>
                        </tr>
                        <tr>
                            <th>Start of Journey</th>
                            <th>Mid of Journey</th>
                            <th>End of Journey</th>
                            <th>Start of Journey</th>
                            <th>Mid of Journey</th>
                            <th>End of Journey</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_data)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($attendance_data as $emp_id => $employee): ?>
                                <tr>
                                    <td style="text-align: left; padding: 10px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php 
                                            $employee_photo = '../uploads/employee/' . $employee['employee_photo'];
                                            if (empty($employee['employee_photo']) || !file_exists($employee_photo)) {
                                                $employee_photo = 'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png';
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($employee_photo); ?>" alt="Photo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                            <div>
                                                <strong><?php echo $counter++; ?>. <?php echo htmlspecialchars($employee['employee_name']); ?></strong><br>
                                                <small style="color: #666;"><?php echo htmlspecialchars($employee['employee_id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <?php 
                                    $checkpoints = ['Start of journey', 'Mid of journey', 'End of journey'];
                                    foreach ($checkpoints as $checkpoint): 
                                        $data = $employee['train_from'][$checkpoint] ?? null;
                                    ?>
                                        <td style="vertical-align: top; padding: 10px;">
                                            <?php if ($data): ?>
                                                <?php 
                                                $photo_path = '../uploads/attendence/' . $data['photo'];
                                                if (!file_exists($photo_path)) {
                                                    $photo_path = '../assets/No_image_available.svg.png';
                                                }
                                                // Sanitize location to remove corrupted characters
                                                $clean_location = iconv('UTF-8', 'UTF-8//IGNORE', $data['location']);
                                                ?>
                                                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Report" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; display: block; margin: 0 auto 5px;">
                                                <small style="display: block; color: #666; margin-bottom: 3px;"><?php echo htmlspecialchars($clean_location); ?></small>
                                                <small style="display: block; font-weight: bold;"><?php echo date('d-m-Y H:i:s', strtotime($data['created_at'])); ?></small>
                                                <div style="margin-top: 5px;">
                                                    <button class="action-btn edit-btn" style="padding: 4px 8px; font-size: 11px;" onclick="editRecord(<?php echo $data['id']; ?>)">Edit</button>
                                                    <button class="action-btn delete-btn" style="padding: 4px 8px; font-size: 11px;" onclick="deleteRecord(<?php echo $data['id']; ?>)">Delete</button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #999;">No Data</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($checkpoints as $checkpoint): 
                                        $data = $employee['train_to'][$checkpoint] ?? null;
                                    ?>
                                        <td style="vertical-align: top; padding: 10px;">
                                            <?php if ($data): ?>
                                                <?php 
                                                $photo_path = '../uploads/attendence/' . $data['photo'];
                                                if (!file_exists($photo_path)) {
                                                    $photo_path = '../assets/No_image_available.svg.png';
                                                }
                                                // Sanitize location to remove corrupted characters
                                                $clean_location = iconv('UTF-8', 'UTF-8//IGNORE', $data['location']);
                                                ?>
                                                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Report" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; display: block; margin: 0 auto 5px;">
                                                <small style="display: block; color: #666; margin-bottom: 3px;"><?php echo htmlspecialchars($clean_location); ?></small>
                                                <small style="display: block; font-weight: bold;"><?php echo date('d-m-Y H:i:s', strtotime($data['created_at'])); ?></small>
                                                <div style="margin-top: 5px;">
                                                    <button class="action-btn edit-btn" style="padding: 4px 8px; font-size: 11px;" onclick="editRecord(<?php echo $data['id']; ?>)">Edit</button>
                                                    <button class="action-btn delete-btn" style="padding: 4px 8px; font-size: 11px;" onclick="deleteRecord(<?php echo $data['id']; ?>)">Delete</button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #999;">No Data</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #999;">
                                    <?php if (empty($selected_grade)): ?>
                                        Please select filters and click Search to view attendance.
                                    <?php else: ?>
                                        No attendance records found for the selected filters.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 20px; color: #20a779;">Edit Attendance Record</h3>
            <form id="editForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="update_id" id="edit_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Employee Name:</label>
                    <input type="text" name="employee_name" id="edit_employee_name" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Employee ID:</label>
                    <input type="text" name="employee_id" id="edit_employee_id" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Desination:</label>
                    <input type="text" name="desination" id="edit_desination" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>TOC:</label>
                    <input type="text" name="toc" id="edit_toc" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Train No:</label>
                    <select name="train_no" id="edit_train_no" required>
                        <?php foreach ($trains as $train): ?>
                            <option value="<?php echo htmlspecialchars($train); ?>"><?php echo htmlspecialchars($train); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Type of Attendance:</label>
                    <select name="type_of_attendance" id="edit_type" required>
                        <option value="Start of journey">Start of Journey</option>
                        <option value="Mid of journey">Mid of Journey</option>
                        <option value="End of journey">End of Journey</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Location:</label>
                    <input type="text" name="location" id="edit_location" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Grade:</label>
                    <select name="grade" id="edit_grade" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                        <option value="G">G</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Created At:</label>
                    <input type="datetime-local" name="created_at" id="edit_created_at" step="1" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Upload New Photo (Optional):</label>
                    <input type="file" name="photo" id="edit_photo" accept="image/*" style="margin-bottom: 10px;">
                    <div id="photo_preview" style="margin-top: 10px; text-align: center;">
                        <img id="preview_img" style="max-width: 200px; max-height: 200px; border-radius: 4px; display: none;">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update_attendance" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function definitions - must be available regardless of data
        function editRecord(id) {
            const record = records.find(r => r.id == id);
            if (record) {
                document.getElementById('edit_id').value = record.id;
                document.getElementById('edit_employee_name').value = record.employee_name || '';
                document.getElementById('edit_employee_id').value = record.employee_id || '';
                document.getElementById('edit_desination').value = record.desination || '';
                document.getElementById('edit_toc').value = record.toc || ''; // Default to empty string if missing
                document.getElementById('edit_train_no').value = record.train_no || '';
                document.getElementById('edit_type').value = record.type_of_attendance || '';
                document.getElementById('edit_location').value = record.location || '';
                document.getElementById('edit_grade').value = record.grade || 'A';
                document.getElementById('edit_created_at').value = toDatetimeLocal(record.created_at);
                
                document.getElementById('editModal').style.display = 'flex';
                console.log('Editing record:', record);
            } else {
                console.error('Record not found with id:', id);
                console.log('Available records:', records);
            }
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('edit_photo').value = '';
            document.getElementById('preview_img').style.display = 'none';
        }
        
        function deleteRecord(id) {
            if (confirm('Are you sure you want to delete this attendance record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Convert "YYYY-MM-DD HH:MM:SS" to "YYYY-MM-DDTHH:MM:SS" for datetime-local
        function toDatetimeLocal(value) {
            if (!value) return '';
            // Replace space with 'T' and keep seconds
            const v = value.replace(' ', 'T');
            return v.length >= 19 ? v.slice(0, 19) : v;
        }

        // Flatten attendance data for editing
        const records = [];
        <?php if (!empty($attendance_data)): ?>
            <?php foreach ($attendance_data as $emp_id => $employee): ?>
                <?php 
                $checkpoints = ['Start of journey', 'Mid of journey', 'End of journey'];
                foreach ($checkpoints as $checkpoint): 
                    if (isset($employee['train_from'][$checkpoint])):
                        $data = $employee['train_from'][$checkpoint];
                        // Sanitize location to remove corrupted characters
                        $location = iconv('UTF-8', 'UTF-8//IGNORE', $data['location']);
                        // Sanitize and provide default for toc
                        $toc = !empty($data['toc']) ? iconv('UTF-8', 'UTF-8//IGNORE', $data['toc']) : '';
                ?>
                records.push({
                    id: <?php echo $data['id']; ?>,
                    employee_name: <?php echo json_encode($employee['employee_name'], JSON_UNESCAPED_UNICODE); ?>,
                    employee_id: <?php echo json_encode($employee['employee_id'], JSON_UNESCAPED_UNICODE); ?>,
                    desination: <?php echo json_encode($data['desination'], JSON_UNESCAPED_UNICODE); ?>,
                    toc: <?php echo json_encode($toc, JSON_UNESCAPED_UNICODE); ?>,
                    train_no: <?php echo json_encode($selected_train_from, JSON_UNESCAPED_UNICODE); ?>,
                    type_of_attendance: <?php echo json_encode($checkpoint, JSON_UNESCAPED_UNICODE); ?>,
                    location: <?php echo json_encode($location, JSON_UNESCAPED_UNICODE); ?>,
                    grade: <?php echo json_encode($selected_grade, JSON_UNESCAPED_UNICODE); ?>,
                    created_at: <?php echo json_encode($data['created_at'], JSON_UNESCAPED_UNICODE); ?>
                });
                <?php 
                    endif;
                endforeach; 
                ?>
                <?php 
                foreach ($checkpoints as $checkpoint): 
                    if (isset($employee['train_to'][$checkpoint])):
                        $data = $employee['train_to'][$checkpoint];
                        // Sanitize location to remove corrupted characters
                        $location = iconv('UTF-8', 'UTF-8//IGNORE', $data['location']);
                        // Sanitize and provide default for toc
                        $toc = !empty($data['toc']) ? iconv('UTF-8', 'UTF-8//IGNORE', $data['toc']) : '';
                ?>
                records.push({
                    id: <?php echo $data['id']; ?>,
                    employee_name: <?php echo json_encode($employee['employee_name'], JSON_UNESCAPED_UNICODE); ?>,
                    employee_id: <?php echo json_encode($employee['employee_id'], JSON_UNESCAPED_UNICODE); ?>,
                    desination: <?php echo json_encode($data['desination'], JSON_UNESCAPED_UNICODE); ?>,
                    toc: <?php echo json_encode($toc, JSON_UNESCAPED_UNICODE); ?>,
                    train_no: <?php echo json_encode($selected_train_to, JSON_UNESCAPED_UNICODE); ?>,
                    type_of_attendance: <?php echo json_encode($checkpoint, JSON_UNESCAPED_UNICODE); ?>,
                    location: <?php echo json_encode($location, JSON_UNESCAPED_UNICODE); ?>,
                    grade: <?php echo json_encode($selected_grade, JSON_UNESCAPED_UNICODE); ?>,
                    created_at: <?php echo json_encode($data['created_at'], JSON_UNESCAPED_UNICODE); ?>
                });
                <?php 
                    endif;
                endforeach; 
                ?>
            <?php endforeach; ?>
            console.log('Records loaded:', records.length);
        <?php else: ?>
            console.log('No attendance data available');
        <?php endif; ?>
        
        // Photo preview functionality
        document.getElementById('edit_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview_img');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>

<?php include 'footer.php'; ?>