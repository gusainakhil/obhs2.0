<?php 
session_start();
include '../includes/connection.php';
include '../includes/helpers.php';
$station_id=$_SESSION['station_id'];
$stationName = getStationName($station_id); ?>
<?php 
$pageTitle = "Edit Passenger Feedback";
include 'header.php';

// Set timezone
date_default_timezone_set('Asia/Kolkata');

$station_id = $_SESSION['station_id'] ?? 0;
$success_message = '';
$error_message = '';
$passengers = [];
$search_performed = false;

// Handle delete request
if (isset($_POST['delete_passenger'])) {
    $passenger_id = $_POST['passenger_id'] ?? '';
    
    if (!empty($passenger_id)) {
        // Delete feedback first (foreign key)
        $del_feedback = "DELETE FROM OBHS_feedback WHERE passenger_id = ?";
        if ($stmt = $mysqli->prepare($del_feedback)) {
            $stmt->bind_param("s", $passenger_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Delete passenger
        $del_passenger = "DELETE FROM OBHS_passenger WHERE id = ?";
        if ($stmt = $mysqli->prepare($del_passenger)) {
            $stmt->bind_param("s", $passenger_id);
            if ($stmt->execute()) {
                $success_message = 'Passenger and feedback deleted successfully!';
            } else {
                $error_message = 'Error deleting passenger.';
            }
            $stmt->close();
        }
    }
}

// Handle edit/update request
if (isset($_POST['update_passenger'])) {
    $passenger_id = $_POST['passenger_id'] ?? '';
    $passenger_name = trim($_POST['passenger_name'] ?? '');
    $pnr_number = trim($_POST['pnr_number'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $seat_no = intval($_POST['seat_no'] ?? 0);
    $coach_no = trim($_POST['coach_no'] ?? '');
    $train_no_update = trim($_POST['train_no'] ?? '');
    $grade_update = trim($_POST['grade'] ?? '');
    $created = trim($_POST['created'] ?? '');
    // Convert datetime-local format (2025-12-28T09:09:56) to database format (2025-12-28 09:09:56)
    $created = str_replace('T', ' ', $created);
    $ratings = $_POST['rating'] ?? [];
    
    // Store original search criteria for reuse (from hidden fields with prefix 'orig_')
    $from_date = $_POST['orig_from_date'] ?? '';
    $to_date = $_POST['orig_to_date'] ?? '';
    $train_no = $_POST['orig_train_no'] ?? '';
    $grade = $_POST['orig_grade'] ?? '';
       $created_by = 'BACKEND';
    
    if (!empty($passenger_id)) {
        // Update passenger info
        $upd_sql = "UPDATE OBHS_passenger SET name = ?, pnr_number = ?, ph_number = ?, seat_no = ?, coach_no = ?, train_no = ?, grade = ?, created = ?, created_by = ? WHERE id = ?";
        if ($stmt = $mysqli->prepare($upd_sql)) {
            // Correct bind types: name(s), pnr(s), phone(s), seat(i), coach(s), train(s), grade(s), created(s), created_by(s), id(s)
            $stmt->bind_param("ssisssssss", $passenger_name, $pnr_number, $phone_number, $seat_no, $coach_no, $train_no_update, $grade_update, $created, $created_by, $passenger_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Update feedback ratings
                foreach ($ratings as $question_id => $value) {
                    $upd_feedback = "UPDATE OBHS_feedback SET value = ? WHERE passenger_id = ? AND feed_param = ?";
                    if ($fstmt = $mysqli->prepare($upd_feedback)) {
                        $val = (float)$value;
                        $qid = (int)$question_id;
                        $fstmt->bind_param("dsi", $val, $passenger_id, $qid);
                        $fstmt->execute();
                        $fstmt->close();
                    }
                }
                
                $success_message = 'Passenger feedback updated successfully!';
                
                // Re-run search with original criteria to show filtered results
                if (!empty($from_date) && !empty($to_date) && !empty($train_no) && !empty($grade)) {
                    $search_performed = true;
                    $from_datetime = $from_date . ' 00:00:00';
                    $to_datetime = $to_date . ' 23:59:59';
                    
                    $search_sql = "SELECT id, name, pnr_number, ph_number, seat_no, coach_no, train_no, coach_type, grade, created 
                                   FROM OBHS_passenger 
                                   WHERE station_id = ? AND train_no = ? AND grade = ? AND created BETWEEN ? AND ? 
                                   ORDER BY created DESC";
                    
                    if ($stmt = $mysqli->prepare($search_sql)) {
                        $stmt->bind_param("issss", $station_id, $train_no, $grade, $from_datetime, $to_datetime);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $passengers = $result->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();
                    }
                }
            } else {
                $error_message = 'Error updating passenger: ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $error_message = 'Prepare failed: ' . $mysqli->error;
        }
    }
}

// If we have prior search criteria posted (from update/delete), re-run the search to persist results
if (!$search_performed && isset($_POST['from_date'], $_POST['to_date'], $_POST['train_no'], $_POST['grade'])) {
    $from_date = trim($_POST['from_date'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    $train_no = trim($_POST['train_no'] ?? '');
    $grade = trim($_POST['grade'] ?? '');

    if ($from_date !== '' && $to_date !== '' && $train_no !== '' && $grade !== '') {
        $search_performed = true;

        $from_datetime = $from_date . ' 00:00:00';
        $to_datetime = $to_date . ' 23:59:59';

        $search_sql = "SELECT id, name, pnr_number, ph_number, seat_no, coach_no, train_no, coach_type, grade, created 
                       FROM OBHS_passenger 
                       WHERE station_id = ? AND train_no = ? AND grade = ? AND created BETWEEN ? AND ? 
                       ORDER BY created DESC";

        if ($stmt = $mysqli->prepare($search_sql)) {
            $stmt->bind_param("issss", $station_id, $train_no, $grade, $from_datetime, $to_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            $passengers = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

// Handle search request
if (isset($_POST['search_passengers'])) {
    $from_date = trim($_POST['from_date'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    $train_no = trim($_POST['train_no'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    
    if (!empty($from_date) && !empty($to_date) && !empty($train_no) && !empty($grade)) {
        $search_performed = true;
        
        $from_datetime = $from_date . ' 00:00:00';
        $to_datetime = $to_date . ' 23:59:59';
        
        $search_sql = "SELECT id, name, pnr_number, ph_number, seat_no, coach_no, train_no, coach_type, grade, created 
                       FROM OBHS_passenger 
                       WHERE station_id = ? AND train_no = ? AND grade = ? AND created BETWEEN ? AND ? 
                       ORDER BY created DESC";
        
        if ($stmt = $mysqli->prepare($search_sql)) {
            $stmt->bind_param("issss", $station_id, $train_no, $grade, $from_datetime, $to_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            $passengers = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        $error_message = 'Please fill all search fields.';
    }
}

?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <h2>Edit Passenger Feedback</h2>
                
                <?php if ($success_message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <form method="POST" action="" style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; margin-bottom: 15px; color: #334155;">Search Passengers</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>From Date:</label>
                            <input type="date" name="from_date" required value="<?php echo htmlspecialchars(isset($from_date) ? $from_date : ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>To Date:</label>
                            <input type="date" name="to_date" required value="<?php echo htmlspecialchars(isset($to_date) ? $to_date : ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Train No:</label>
                            <select name="train_no" required>
                                <option value="">Select Train</option>
                                <?php
                                $sql = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
                                $stmt = $mysqli->prepare($sql);
                                $stmt->bind_param("i", $station_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $current_train = isset($train_no) ? $train_no : '';
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($current_train == $row['train_no']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['train_no']) . '" ' . $selected . '>' . htmlspecialchars($row['train_no']) . '</option>';
                                }
                                $stmt->close();
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Grade:</label>
                            <select name="grade" required>
                                <option value="">Select Grade</option>
                                <?php
                                $grades = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                                $current_grade = isset($grade) ? $grade : '';
                                foreach ($grades as $g) {
                                    $selected = ($current_grade == $g) ? 'selected' : '';
                                    echo '<option value="' . $g . '" ' . $selected . '>' . $g . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" name="search_passengers" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                
                <!-- Results Table -->
                <?php if ($search_performed): ?>
                    <?php if (count($passengers) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Passenger Name</th>
                                    <th>PNR</th>
                                    <th>Phone</th>
                                    <th>Train No</th>
                                    <th>Coach</th>
                                    <th>Seat</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($passengers as $passenger): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($passenger['name']); ?></td>
                                        <td><?php echo htmlspecialchars($passenger['pnr_number']); ?></td>
                                        <td><?php echo htmlspecialchars($passenger['ph_number']); ?></td>
                                        <td><?php echo htmlspecialchars($passenger['train_no']); ?></td>
                                        <td><?php echo htmlspecialchars($passenger['coach_no']); ?></td>
                                        <td><?php echo htmlspecialchars($passenger['seat_no']); ?></td>
                                        <td><?php echo htmlspecialchars($passenger['coach_type']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($passenger['created'])); ?></td>
                                        <td>
                                            <button class="action-btn edit-btn" onclick="editPassenger('<?php echo htmlspecialchars($passenger['id']); ?>')">Edit</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this passenger and all feedback?');">
                                                <input type="hidden" name="passenger_id" value="<?php echo htmlspecialchars($passenger['id']); ?>">
                                                <!-- Persist search criteria on delete submit -->
                                                <input type="hidden" name="from_date" value="<?php echo htmlspecialchars($from_date ?? ''); ?>">
                                                <input type="hidden" name="to_date" value="<?php echo htmlspecialchars($to_date ?? ''); ?>">
                                                <input type="hidden" name="train_no" value="<?php echo htmlspecialchars($train_no ?? ''); ?>">
                                                <input type="hidden" name="grade" value="<?php echo htmlspecialchars($grade ?? ''); ?>">
                                                <button type="submit" name="delete_passenger" class="action-btn delete-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>No passengers found for the selected criteria.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:#fff; max-width:800px; margin:50px auto; padding:30px; border-radius:10px; max-height:90vh; overflow-y:auto;">
            <h2>Edit Passenger Feedback</h2>
            <div id="editFormContent"></div>
        </div>
    </div>

    <script>
        // Simple HTML-attribute escape
        function escAttr(val) {
            if (val === null || val === undefined) return '';
            return String(val)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function editPassenger(passengerId) {
            // Fetch passenger details and feedback via AJAX
            fetch('get-passenger-details.php?id=' + passengerId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    // Read current search criteria to persist on submit
                    const fromDate = document.querySelector('input[name="from_date"]').value || '';
                    const toDate = document.querySelector('input[name="to_date"]').value || '';
                    const trainNo = document.querySelector('select[name="train_no"]').value || '';
                    const gradeVal = document.querySelector('select[name="grade"]').value || '';

                    // Build edit form
                    let html = '<form method="POST" action="">';
                    html += '<input type="hidden" name="passenger_id" value="' + data.passenger.id + '">';
                    // Persist original search criteria on update submit (use orig_ prefix)
                    html += '<input type="hidden" name="orig_from_date" value="' + escAttr(fromDate) + '">';
                    html += '<input type="hidden" name="orig_to_date" value="' + escAttr(toDate) + '">';
                    html += '<input type="hidden" name="orig_train_no" value="' + escAttr(trainNo) + '">';
                    html += '<input type="hidden" name="orig_grade" value="' + escAttr(gradeVal) + '">';
                    
                    html += '<div class="form-row">';
                    html += '<div class="form-group"><label>Passenger Name:</label><input type="text" name="passenger_name" value="' + escAttr(data.passenger.name) + '" required></div>';
                    html += '<div class="form-group"><label>PNR:</label><input type="text" name="pnr_number" value="' + escAttr(data.passenger.pnr_number) + '" required></div>';
                    html += '</div>';
                    
                    html += '<div class="form-row">';
                    html += '<div class="form-group"><label>Phone:</label><input type="text" name="phone_number" value="' + escAttr(data.passenger.ph_number) + '" required></div>';
                    html += '<div class="form-group"><label>Train No:</label><input type="text" name="train_no" value="' + escAttr(data.passenger.train_no) + '" required></div>';
                    html += '</div>';
                    
                    html += '<div class="form-row">';
                    html += '<div class="form-group"><label>Coach:</label><input type="text" name="coach_no" value="' + escAttr(data.passenger.coach_no) + '" required></div>';
                    html += '<div class="form-group"><label>Seat:</label><input type="number" name="seat_no" value="' + escAttr(data.passenger.seat_no) + '" required></div>';
                    html += '<div class="form-group"><label>Grade:</label><select name="grade" required>';
                    const currentGrade = String(data.passenger.grade).trim();
                    ['A','B','C','D','E','F','G'].forEach(g => {
                        const isSelected = currentGrade === g ? ' selected' : '';
                        html += '<option value="' + g + '"' + isSelected + '>' + g + '</option>';
                    });
                    html += '</select></div>';
                    // html += '</div>';
                    
                    // html += '<div class="form-row">';
                    // Get current datetime in format YYYY-MM-DDTHH:MM
                    const now = new Date();
                    const maxDateTime = now.getFullYear() + '-' + 
                                       String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                                       String(now.getDate()).padStart(2, '0') + 'T' + 
                                       String(now.getHours()).padStart(2, '0') + ':' + 
                                       String(now.getMinutes()).padStart(2, '0');
                    html += '<div class="form-group"><label>Date & Time (with seconds):</label><input type="datetime-local" name="created" value="' + escAttr(data.passenger.created.substring(0, 19).replace(' ', 'T')) + '" step="1" max="' + maxDateTime + '"></div>';
                    html += '</div>';
                    
                    // Feedback ratings
                    html += '<h3 style="margin-top:20px;">Feedback Ratings</h3>';
                    html += '<table class="data-table"><thead><tr><th>Question</th><th>Rating</th></tr></thead><tbody>';
                    
                    data.feedback.forEach(fb => {
                        html += '<tr><td>' + fb.question + '</td><td>';
                        html += '<div class="rating-group">';
                        data.markings.forEach(mark => {
                            let checked = (parseFloat(fb.value) === parseFloat(mark.value)) ? 'checked' : '';
                            html += '<input type="radio" id="q' + fb.feed_param + '_' + mark.value + '" name="rating[' + fb.feed_param + ']" value="' + mark.value + '" ' + checked + '>';
                            html += '<label for="q' + fb.feed_param + '_' + mark.value + '">' + mark.category + ' - ' + mark.value + '</label>';
                        });
                        html += '</div></td></tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    html += '<div style="margin-top:20px; display:flex; gap:10px;">';
                    html += '<button type="submit" name="update_passenger" class="btn btn-success">Update</button>';
                    html += '<button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>';
                    html += '</div>';
                    html += '</form>';
                    
                    document.getElementById('editFormContent').innerHTML = html;
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => {
                    alert('Error loading passenger details: ' + error);
                });
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    
    <style>
        .rating-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .rating-group input[type="radio"] { display: none; }
        .rating-group label { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; background: #fff; color: #334155; font-size: 13px; }
        .rating-group input[type="radio"]:checked + label { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>

<?php include 'footer.php'; ?>
