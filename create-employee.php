<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

// Optional: enable detailed error output in development only
$debug = true; // set to false in production
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
// Call reusable login check
checkLogin();

// Now fetch station name
$station_name = getStationName($_SESSION['station_id']);
$station_id = $_SESSION['station_id'];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_employees'])) {
    $names = $_POST['name'] ?? [];
    $employee_ids = $_POST['employeeId'] ?? [];
    $designations = $_POST['designation'] ?? [];
    
    // Create upload directory if it doesn't exist
    $upload_dir = 'uploads/employee/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $inserted_count = 0;
    $failed_count = 0;
    
    for ($i = 0; $i < count($names); $i++) {
        $name = $mysqli->real_escape_string(trim($names[$i]));
        $employee_id = $mysqli->real_escape_string(trim($employee_ids[$i]));
        $designation = $mysqli->real_escape_string(trim($designations[$i]));
        $photo_name = '';
        
        // Handle photo upload
        if (isset($_FILES['photo']['name'][$i]) && $_FILES['photo']['error'][$i] == 0) {
            $file_tmp = $_FILES['photo']['tmp_name'][$i];
            $file_name = $_FILES['photo']['name'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_extensions)) {
                // Generate unique filename
                $photo_name = $employee_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                $target_file = $upload_dir . $photo_name;
                
                if (!move_uploaded_file($file_tmp, $target_file)) {
                    $photo_name = ''; // Failed to upload
                }
            }
        }
        
        // Insert into database
        $insert_query = "INSERT INTO base_employees (employee_id, name, station, desination, photo, station_id, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("sssssi", $employee_id, $name, $station_name, $designation, $photo_name, $station_id);
        
        if ($stmt->execute()) {
            $inserted_count++;
        } else {
            $failed_count++;
            // Delete uploaded photo if database insert failed
            if ($photo_name && file_exists($upload_dir . $photo_name)) {
                unlink($upload_dir . $photo_name);
            }
        }
        $stmt->close();
    }
    
    if ($inserted_count > 0) {
        $success_message = "Successfully added $inserted_count employee(s)!";
    }
    if ($failed_count > 0) {
        $error_message = "Failed to add $failed_count employee(s).";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Employee - Jodhpur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-container {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .form-table thead {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-table thead th {
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }

        .form-table tbody td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .file-input {
            padding: 6px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            width: 100%;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-add {
            background-color: #06b6d4;
            color: white;
        }

        .btn-add:hover {
            background-color: #0891b2;
        }

        .btn-submit {
            background-color: #10b981;
            color: white;
        }

        .btn-submit:hover {
            background-color: #059669;
        }

        .btn-remove {
            background-color: #ef4444;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-remove:hover {
            background-color: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
        }
    </style>
</head>

<body class="bg-slate-50">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <!-- sidebar  -->
   <?php 
   require_once 'includes/sidebar.php'
   ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">

        <!-- Top Navigation Bar -->
       <?php 
       require_once 'includes/header.php'
       ?>

        <!-- Main Content Area -->
        <main class="p-4 lg:p-6">

            <div class="page-header">
                <h2 class="text-xl font-bold text-slate-800">Add New Employees</h2>
                <p class="text-sm text-slate-600 mt-1">Fill in the employee details below</p>
            </div>

            <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>

            <div class="form-container">
                <form id="employeeForm" method="POST" action="" enctype="multipart/form-data">
                    <div class="table-wrapper">
                        <table class="form-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Photo</th>
                                    <th>Employee ID</th>
                                    <th>Designation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="employeeRows">
                                <tr>
                                    <td>
                                        <input type="text" name="name[]" class="form-input" placeholder="Enter name"
                                            required>
                                    </td>
                                    <td>
                                        <input type="file" name="photo[]" class="file-input" accept="image/*">
                                    </td>
                                    <td>
                                        <input type="text" name="employeeId[]" class="form-input"
                                            placeholder="Employee ID" required>
                                    </td>
                                    <td>
                                        <input type="text" name="designation[]" class="form-input"
                                            placeholder="Designation" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-remove" onclick="removeRow(this)"
                                            disabled>Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn btn-add" onclick="addRow()">
                            <i class="fas fa-plus mr-2"></i>Add Row
                        </button>
                        <button type="submit" name="submit_employees" class="btn btn-submit">
                            <i class="fas fa-check mr-2"></i>Submit
                        </button>
                    </div>
                </form>
            </div>

        
            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
           ?>

        </main>

    </div>

    <script>
        // Add new row
        function addRow() {
            const tbody = document.getElementById('employeeRows');
            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td>
                    <input type="text" name="name[]" class="form-input" placeholder="Enter name" required>
                </td>
                <td>
                    <input type="file" name="photo[]" class="file-input" accept="image/*">
                </td>
                <td>
                    <input type="text" name="employeeId[]" class="form-input" placeholder="Employee ID" required>
                </td>
                <td>
                    <input type="text" name="designation[]" class="form-input" placeholder="Designation" required>
                </td>
                <td>
                    <button type="button" class="btn btn-remove" onclick="removeRow(this)">Remove</button>
                </td>
            `;

            tbody.appendChild(newRow);
            updateRemoveButtons();
        }

        // Remove row
        function removeRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateRemoveButtons();
        }

        // Update remove buttons (disable if only one row)
        function updateRemoveButtons() {
            const rows = document.querySelectorAll('#employeeRows tr');
            const removeButtons = document.querySelectorAll('.btn-remove');

            removeButtons.forEach((btn, index) => {
                if (rows.length === 1) {
                    btn.disabled = true;
                } else {
                    btn.disabled = false;
                }
            });
        }

        // Mobile Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const closeSidebar = document.getElementById('closeSidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });

        // Initialize remove buttons on load
        updateRemoveButtons();
    </script>

</body>

</html>