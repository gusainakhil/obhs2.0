<?php
session_start();
include '../includes/connection.php';
include '../includes/helpers.php';

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check login
checkLogin();

// Get station information
$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];



// Decide employee table based on station
$employeeTable = 'base_employees';
if ($station_id == 17) {
    $employeeTable = 'base_employees_jodhpur';
}


// Fetch employees for the station
$employees = [];
$emp_query = "SELECT employee_id, name, desination FROM $employeeTable WHERE station_id = ? ORDER BY name";
$stmt = $mysqli->prepare($emp_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
$stmt->close();

// Fetch train numbers
$trains = [];
$train_query = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no";
$stmt = $mysqli->prepare($train_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trains[] = $row['train_no'];
}
$stmt->close();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $employee_ids = $_POST['employee_id'] ?? [];
    $train_nos = $_POST['train_no'] ?? [];
    $type_of_attendances = $_POST['type_of_attendance'] ?? [];
    $locations = $_POST['location'] ?? [];
    $grades = $_POST['grade'] ?? [];
    $designations = $_POST['designation'] ?? [];
    $tocs = $_POST['toc'] ?? [];
    $created_ats = $_POST['created_at'] ?? [];
    
    $upload_dir = '../uploads/attendence/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $success_count = 0;
    $error_count = 0;
    
    // Loop through each row
    for ($i = 0; $i < count($employee_ids); $i++) {
        if (empty($employee_ids[$i])) continue;
        
        $employee_id = $employee_ids[$i];
        $train_no = $train_nos[$i] ?? '';
        $type_of_attendance = $type_of_attendances[$i] ?? '';
        $location = $locations[$i] ?? '';
        $grade = $grades[$i] ?? '';
        $designation = $designations[$i] ?? '';
        $toc = $tocs[$i] ?? '';
        
        // Get employee name
        $employee_name = '';
        foreach ($employees as $emp) {
            if ($emp['employee_id'] == $employee_id) {
                $employee_name = $emp['name'];
                break;
            }
        }
        
        // Handle photo upload for this row
        $photo_filename = '';
        if (isset($_FILES['photo']['name'][$i]) && $_FILES['photo']['error'][$i] === UPLOAD_ERR_OK) {
            $file_extension = pathinfo($_FILES['photo']['name'][$i], PATHINFO_EXTENSION);
$photo_filename =
    $station_id . '_' .
    date('Ymd_His') . '_' .
    uniqid() . '.' . $file_extension;
$upload_path = $upload_dir . $photo_filename;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'][$i], $upload_path)) {
                $error_count++;
                continue;
            }
        }
        
        // Created at conversion (datetime-local -> MySQL DATETIME)
        $created_at_input = $created_ats[$i] ?? '';
        $created_at_mysql = !empty($created_at_input) ? str_replace('T', ' ', $created_at_input) : date('Y-m-d H:i:s');
        if (strlen($created_at_mysql) === 16) {
            $created_at_mysql .= ':00';
        }

        // Insert into database with provided created_at
        $insert_query = "INSERT INTO base_attendance 
                        (station_id, employee_id, employee_name, train_no, type_of_attendance, location, grade, desination, toc, photo, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("sssssssssss", $station_id, $employee_id, $employee_name, $train_no, $type_of_attendance, $location, $grade, $designation, $toc, $photo_filename, $created_at_mysql);
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
        $stmt->close();
    }
    
    if ($success_count > 0) {
        $success_message = "$success_count attendance record(s) created successfully!";
    }
    if ($error_count > 0) {
        $error_message = "$error_count attendance record(s) failed to create.";
    }
}

$pageTitle = "Create Attendance";
?>
<?php include 'header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <h2>Create Attendance</h2>
                
                <?php if ($success_message): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form id="attendanceForm" method="POST" action="" enctype="multipart/form-data">
                    <div id="attendanceRows">
                        <!-- Row 1 (Template) -->
                        <div class="attendance-row" data-row="0" style="border: 2px solid #e0e0e0; padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #fafafa; position: relative;">
                            <div style="position: absolute; top: 10px; right: 10px;">
                                <button type="button" class="btn-remove" onclick="removeRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 14px;">Remove</button>
                            </div>
                            <h4 style="margin-top: 0; color: #20a779;">Attendance Entry #<span class="row-number">1</span></h4>
                            
                            <div class="form-row">
                                <div class="form-group" style="position: relative;">
                                    <label>Employee:</label>
                                    <input type="text" class="employee_search" placeholder="Type employee name or ID" 
                                           autocomplete="off" required oninput="filterEmployeesRow(this)">
                                    <input type="hidden" name="employee_id[]" class="employee_id">
                                    <div class="employeeList" style="display: none; position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 200px; overflow-y: auto; width: calc(100% - 2px); z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1); top: 100%; left: 0; margin-top: 2px;">
                                        <?php foreach ($employees as $emp): ?>
                                            <div class="employee-option" 
                                                 data-id="<?php echo htmlspecialchars($emp['employee_id']); ?>" 
                                                 data-name="<?php echo htmlspecialchars($emp['name']); ?>"
                                                 data-designation="<?php echo htmlspecialchars($emp['desination']); ?>"
                                                 onclick="selectEmployeeRow(this)"
                                                 style="padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                                                <?php echo htmlspecialchars($emp['name']); ?> - <?php echo htmlspecialchars($emp['employee_id']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Type Of Attendance:</label>
                                    <select name="type_of_attendance[]" required>
                                        <option value="">Select Type</option>
                                        <option value="Start of journey">Start of Journey</option>
                                        <option value="Mid of journey">Mid of Journey</option>
                                        <option value="End of journey">End of Journey</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Train No:</label>
                                    <select name="train_no[]" required>
                                        <option value="">Select Train No</option>
                                        <?php foreach ($trains as $train): ?>
                                            <option value="<?php echo htmlspecialchars($train); ?>">
                                                <?php echo htmlspecialchars($train); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Designation:</label>
                                    <input type="text" name="designation[]" class="designation" placeholder="Auto-filled from employee" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Location:</label>
                                    <input type="text" name="location[]" placeholder="e.g., lati: 26.2174382 longi: 78.1831218 Gwalior" required>
                                </div>
                                <div class="form-group">
                                    <label>Grade:</label>
                                    <select name="grade[]" required>
                                        <option value="">Select Grade</option>
                                        <option value="A">A - Monday</option>
                                        <option value="B">B - Tuesday</option>
                                        <option value="C">C - Wednesday</option>
                                        <option value="D">D - Thursday</option>
                                        <option value="E">E - Friday</option>
                                        <option value="F">F - Saturday</option>
                                        <option value="G">G - Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Type of Coach:</label>
                                    <select name="toc[]" required>
                                        <option value="">Select Type</option>
                                        <option value="AC">AC</option>
                                        <option value="Non-AC">Non-AC</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Photo:</label>
                                    <input type="file" name="photo[]" accept="image/*" required>
                                    <small style="color: #666; font-size: 12px;">Required: Upload attendance photo</small>
                                </div>
                                <div class="form-group">
                                    <label>Created At:</label>
                                    <input type="datetime-local" name="created_at[]" step="1" required>
                                    <small style="color: #666; font-size: 12px;">Specify local date & time</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <button type="button" class="btn btn-primary" onclick="addRow()" style="background: #20a779; border: none; padding: 10px 20px; color: white; border-radius: 5px; cursor: pointer; font-size: 16px;">
                            + Add Another Entry
                        </button>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="submit_attendance" class="btn btn-success">Submit All Attendance</button>
                        <button type="reset" class="btn btn-secondary">Reset Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let rowCount = 1;
        
        function addRow() {
            const container = document.getElementById('attendanceRows');
            const firstRow = container.querySelector('.attendance-row');
            const newRow = firstRow.cloneNode(true);
            
            rowCount++;
            newRow.setAttribute('data-row', rowCount - 1);
            newRow.querySelector('.row-number').textContent = rowCount;
            
            // Clear all input values
            newRow.querySelectorAll('input[type="text"], input[type="file"], input[type="hidden"], input[type="datetime-local"]').forEach(input => {
                input.value = '';
            });
            newRow.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Hide employee dropdown
            newRow.querySelector('.employeeList').style.display = 'none';
            
            container.appendChild(newRow);
            updateRowNumbers();
            attachHoverEffects(newRow);
        }
        
        function removeRow(button) {
            const rows = document.querySelectorAll('.attendance-row');
            if (rows.length > 1) {
                button.closest('.attendance-row').remove();
                rowCount--;
                updateRowNumbers();
            } else {
                alert('At least one attendance entry is required!');
            }
        }
        
        function updateRowNumbers() {
            const rows = document.querySelectorAll('.attendance-row');
            rows.forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
            });
        }
        
        function filterEmployeesRow(searchInput) {
            const row = searchInput.closest('.attendance-row');
            const employeeList = row.querySelector('.employeeList');
            const searchValue = searchInput.value.toLowerCase();
            const options = employeeList.querySelectorAll('.employee-option');
            let hasResults = false;
            
            if (searchValue.length === 0) {
                employeeList.style.display = 'none';
                row.querySelector('.employee_id').value = '';
                return;
            }
            
            options.forEach(option => {
                const name = option.dataset.name.toLowerCase();
                const id = option.dataset.id.toLowerCase();
                
                if (name.includes(searchValue) || id.includes(searchValue)) {
                    option.style.display = 'block';
                    hasResults = true;
                } else {
                    option.style.display = 'none';
                }
            });
            
            employeeList.style.display = hasResults ? 'block' : 'none';
        }
        
        function selectEmployeeRow(element) {
            const row = element.closest('.attendance-row');
            const id = element.dataset.id;
            const name = element.dataset.name;
            const designation = element.dataset.designation;
            
            row.querySelector('.employee_search').value = name + ' - ' + id;
            row.querySelector('.employee_id').value = id;
            row.querySelector('.designation').value = designation;
            row.querySelector('.employeeList').style.display = 'none';
        }
        
        function attachHoverEffects(row) {
            row.querySelectorAll('.employee-option').forEach(option => {
                option.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f9ff';
                });
                option.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'white';
                });
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.employee_search') && !event.target.closest('.employeeList')) {
                document.querySelectorAll('.employeeList').forEach(list => {
                    list.style.display = 'none';
                });
            }
        });
        
        // Initial hover effects
        document.querySelectorAll('.attendance-row').forEach(row => {
            attachHoverEffects(row);
        });
    </script>

<?php include 'footer.php'; ?>
