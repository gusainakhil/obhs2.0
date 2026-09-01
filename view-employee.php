<?php
session_start();
require_once './includes/connection.php';
require_once './includes/helpers.php';

// Optional: enable detailed error output in development only
$debug = false; // set to true only in local development
if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Call reusable login check
checkLogin();

function viewEmployeeEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function viewEmployeeJson($value)
{
    $encoded = json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    return $encoded === false ? '""' : $encoded;
}

function viewEmployeePhotoFilename($filename)
{
    $filename = basename(str_replace('\\', '/', trim((string) $filename)));

    if ($filename === '' || $filename === '.' || $filename === '..') {
        return '';
    }

    return $filename;
}

function viewEmployeePhotoPath($filename)
{
    $filename = viewEmployeePhotoFilename($filename);

    if ($filename === '') {
        return 'uploads/user_13984171.png';
    }

    $path = 'uploads/employee/' . $filename;

    return is_file($path) ? $path : 'user_13984171.png';
}

function viewEmployeeDeletePhoto($filename)
{
    $filename = viewEmployeePhotoFilename($filename);

    if ($filename === '') {
        return;
    }

    $path = 'uploads/employee/' . $filename;
    if (is_file($path)) {
        unlink($path);
    }
}

function viewEmployeeFetchPhoto($mysqli, $employee_id, $station_id)
{
    $sql = "SELECT photo FROM base_employees WHERE id = ? AND station_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        error_log('View employee photo fetch prepare failed: ' . $mysqli->error);
        return null;
    }

    $stmt->bind_param("ii", $employee_id, $station_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row;
}

function viewEmployeeResolvePerPage($value)
{
    $allowed = ['10', '25', '50', '100', 'all'];
    $value = (string) $value;

    return in_array($value, $allowed, true) ? $value : '10';
}

function viewEmployeeListUrl($perPage, $page, $editEmployeeId = null)
{
    $params = ['per_page' => $perPage, 'page' => $page];
    if ($editEmployeeId !== null) {
        $params['edit_employee'] = (int) $editEmployeeId;
    }

    return 'view-employee.php?' . http_build_query($params);
}

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$station_name = viewEmployeeEscape(getStationName($station_id));

// Handle delete request
if (isset($_POST['delete_employee'])) {
    $employee_id = (int) ($_POST['employee_id'] ?? 0);

    // Get employee photo before deleting
    $emp_data = $employee_id > 0 ? viewEmployeeFetchPhoto($mysqli, $employee_id, $station_id) : null;

    if ($emp_data) {
        // Delete from database
        $delete_query = "DELETE FROM base_employees WHERE id = ? AND station_id = ?";
        $stmt = $mysqli->prepare($delete_query);
        if ($stmt) {
            $stmt->bind_param("ii", $employee_id, $station_id);

            if ($stmt->execute()) {
                // Delete photo file if exists
                viewEmployeeDeletePhoto($emp_data['photo'] ?? '');
                $_SESSION['success_msg'] = 'Employee deleted successfully!';
            } else {
                $_SESSION['error_msg'] = 'Failed to delete employee.';
            }
            $stmt->close();
        } else {
            $_SESSION['error_msg'] = 'Failed to delete employee.';
        }
    }

    header("Location: view-employee.php");
    exit();
}

// Handle edit/update request
if (isset($_POST['update_employee'])) {
    $employee_id = (int) ($_POST['edit_employee_id'] ?? 0);
    $name = trim((string) ($_POST['edit_name'] ?? ''));
    $employee_code = trim((string) ($_POST['edit_employee_id_code'] ?? ''));
    $designation = trim((string) ($_POST['edit_designation'] ?? ''));
    $current_employee = $employee_id > 0 ? viewEmployeeFetchPhoto($mysqli, $employee_id, $station_id) : null;
    $old_photo = $current_employee['photo'] ?? '';
    $photo_name = $old_photo;

    // Handle photo upload if new photo is provided
    if ($current_employee && isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/employee/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['edit_photo']['tmp_name'];
        $file_name = $_FILES['edit_photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_extensions, true) && is_uploaded_file($file_tmp)) {
            $safe_employee_code = preg_replace('/[^A-Za-z0-9_-]+/', '_', $employee_code);
            $safe_employee_code = trim($safe_employee_code, '_') ?: 'employee';
            $photo_name = $safe_employee_code . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $photo_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                // Delete old photo if exists
                viewEmployeeDeletePhoto($old_photo);
            } else {
                $photo_name = $old_photo; // Keep old photo if upload fails
            }
        }
    }
    
    // Update database
    $update_query = "UPDATE base_employees SET name = ?, employee_id = ?, desination = ?, photo = ?, updated_at = NOW() WHERE id = ? AND station_id = ?";
    $stmt = $mysqli->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param("ssssii", $name, $employee_code, $designation, $photo_name, $employee_id, $station_id);

        if ($stmt->execute()) {
            $_SESSION['success_msg'] = 'Employee updated successfully!';
        } else {
            $_SESSION['error_msg'] = 'Failed to update employee.';
        }
        $stmt->close();
    } else {
        $_SESSION['error_msg'] = 'Failed to update employee.';
    }

    header("Location: view-employee.php");
    exit();
}

// Fetch employees from database
$employees = [];

// Pagination settings
$per_page_param = viewEmployeeResolvePerPage($_GET['per_page'] ?? '10');
$records_per_page = ($per_page_param === 'all') ? 0 : (int) $per_page_param;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($per_page_param === 'all') ? 0 : (($current_page - 1) * $records_per_page);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM base_employees WHERE station_id = ?";
$stmt = $mysqli->prepare($count_query);
$total_records = 0;
if ($stmt) {
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_records = (int) ($count_result->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

// Fetch the selected employee for the no-JavaScript edit form.
$editing_employee = null;
$edit_employee_id = (int) ($_GET['edit_employee'] ?? 0);
if ($edit_employee_id > 0) {
    $stmt = $mysqli->prepare('SELECT id, name, employee_id, desination, photo FROM base_employees WHERE id = ? AND station_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ii', $edit_employee_id, $station_id);
        $stmt->execute();
        $editing_employee = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}

$total_pages = ($per_page_param === 'all') ? 1 : max(1, (int) ceil($total_records / $records_per_page));
if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($per_page_param === 'all') ? 0 : (($current_page - 1) * $records_per_page);
}

// Fetch paginated employees
$query = "SELECT id, name, employee_id, desination, photo FROM base_employees WHERE station_id = ? ORDER BY created_at DESC";
if ($per_page_param !== 'all') {
    $query .= " LIMIT ? OFFSET ?";
}

$stmt = $mysqli->prepare($query);
if ($stmt) {
    if ($per_page_param === 'all') {
        $stmt->bind_param("i", $station_id);
    } else {
        $stmt->bind_param("iii", $station_id, $records_per_page, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['photo_path'] = viewEmployeePhotoPath($row['photo'] ?? '');
        $employees[] = $row;
    }
    $stmt->close();
}

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
$display_to = ($per_page_param === 'all') ? $total_records : min($offset + $records_per_page, $total_records);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - <?= $station_name ?></title>
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

        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .entries-control {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
        }

        .entries-control select {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 13px;
        }

        .search-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-input {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 13px;
            width: 200px;
        }

        .export-buttons-top {
            display: flex;
            gap: 8px;
        }

        .btn-export-top {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
            text-decoration: none;
        }

        .btn-pdf {
            background-color: #dc2626;
        }

        .btn-pdf:hover {
            background-color: #b91c1c;
        }

        .btn-excel {
            background-color: #10b981;
        }

        .btn-excel:hover {
            background-color: #059669;
        }

        .employee-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .employee-table thead {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }

        .employee-table thead th {
            padding: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
        }

        .employee-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .employee-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .employee-table tbody td {
            padding: 12px;
            text-align: center;
            color: #334155;
            font-size: 13px;
        }

        .employee-photo {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }

        .action-btns {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .btn-card {
            background-color: #8b5cf6;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-card:hover {
            background-color: #7c3aed;
        }

        .btn-edit {
            background-color: #0ea5e9;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-edit:hover {
            background-color: #0284c7;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-delete:hover {
            background-color: #dc2626;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 0 10px;
        }

        .pagination-info {
            font-size: 13px;
            color: #475569;
        }

        .pagination-controls {
            display: flex;
            gap: 6px;
        }

        .pagination-controls button,
        .pagination-controls a,
        .pagination-controls span {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            background: white;
            color: #475569;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .pagination-controls button:hover:not(:disabled) {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-controls .active {
            background-color: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
        }

        .table-container {
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
                <h2 class="text-xl font-bold text-slate-800">Employee List</h2>
                <p class="text-sm text-slate-600 mt-1">Manage all employee records</p>
            </div>

            <?php if ($success_msg !== ''): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo viewEmployeeEscape($success_msg); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error_msg !== ''): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?php echo viewEmployeeEscape($error_msg); ?></span>
            </div>
            <?php endif; ?>

            <!-- Controls Bar -->
            <div class="controls-bar">
                <div class="entries-control">
                    <span>Show</span>
                    <select id="entriesPerPage">
                        <option value="10" <?php echo $per_page_param == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page_param == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page_param == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page_param == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="all" <?php echo $per_page_param === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                    <span>entries</span>
                </div>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <div class="export-buttons-top">
                        <a class="btn-export-top btn-pdf" href="export-employee-pdf.php" target="_blank">PDF</a>
                        <a class="btn-export-top btn-excel" href="export-employee-excel.php" target="_blank">Excel</a>
                    </div>

                    <div class="search-control">
                        <span>Search:</span>
                        <input type="text" class="search-input" id="searchInput"
                            placeholder="Search...">
                    </div>
                </div>
            </div>

            <?php if ($editing_employee): ?>
            <section class="page-header" aria-labelledby="edit-employee-title">
                <h3 id="edit-employee-title" class="text-lg font-bold text-slate-800">Edit Employee</h3>
                <form method="POST" action="" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="edit_employee_id" value="<?php echo (int) $editing_employee['id']; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" name="edit_name" required value="<?php echo viewEmployeeEscape($editing_employee['name']); ?>" class="px-3 py-2 border border-slate-300 rounded-md" aria-label="Employee name">
                        <input type="text" name="edit_employee_id_code" required value="<?php echo viewEmployeeEscape($editing_employee['employee_id']); ?>" class="px-3 py-2 border border-slate-300 rounded-md" aria-label="Employee ID">
                        <input type="text" name="edit_designation" required value="<?php echo viewEmployeeEscape($editing_employee['desination']); ?>" class="px-3 py-2 border border-slate-300 rounded-md" aria-label="Designation">
                    </div>
                    <div class="flex gap-3 mt-4 items-center">
                        <input type="file" name="edit_photo" accept="image/*" class="text-sm">
                        <button type="submit" name="update_employee" class="bg-blue-500 text-white px-4 py-2 rounded-md font-semibold">Update</button>
                        <a href="<?php echo viewEmployeeEscape(viewEmployeeListUrl($per_page_param, $current_page)); ?>" class="bg-slate-300 text-slate-700 px-4 py-2 rounded-md font-semibold">Cancel</a>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- Table Container -->
            <div class="table-container">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>SR. No.</th>
                            <th>Photo</th>
                            <th>Employee Name</th>
                            <th>Employee ID</th>
                            <th>Designation</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <?php 
                        if (!empty($employees)):
                            $sr_no = 1;
                            foreach ($employees as $employee):
                        ?>
                        <tr>
                            <td><?php echo $sr_no++; ?></td>
                            <td><img src="<?php echo viewEmployeeEscape($employee['photo_path']); ?>" alt="Employee" class="employee-photo mx-auto"></td>
                            <td><?php echo viewEmployeeEscape($employee['name']); ?></td>
                            <td><?php echo viewEmployeeEscape($employee['employee_id']); ?></td>
                            <td><?php echo viewEmployeeEscape($employee['desination']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a class="btn-edit" href="<?php echo viewEmployeeEscape(viewEmployeeListUrl($per_page_param, $current_page, $employee['id'])); ?>" aria-label="Edit <?php echo viewEmployeeEscape($employee['name']); ?>"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="delete_employee" value="1">
                                        <input type="hidden" name="employee_id" value="<?php echo (int) $employee['id']; ?>">
                                        <button type="submit" class="btn-delete" aria-label="Delete <?php echo viewEmployeeEscape($employee['name']); ?>"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <i class="fas fa-users text-4xl text-slate-300 mb-2"></i>
                                <p style="color: #64748b;">No employees found. <a href="create-employee.php" style="color: #0ea5e9;">Add your first employee</a></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing <?php echo $total_records > 0 ? $offset + 1 : 0; ?> to <?php echo $display_to; ?> of <?php echo $total_records; ?> entries
                </div>
                <div class="pagination-controls">
                    <?php if ($current_page > 1): ?>
                    <a href="<?php echo viewEmployeeEscape(viewEmployeeListUrl($per_page_param, $current_page - 1)); ?>">Previous</a>
                    <?php else: ?>
                    <button disabled>Previous</button>
                    <?php endif; ?>
                    
                    <?php
                    // Show pagination numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="' . viewEmployeeEscape(viewEmployeeListUrl($per_page_param, 1)) . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span>...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $current_page) {
                            echo '<span class="active">' . $i . '</span>';
                        } else {
                            echo '<a href="' . viewEmployeeEscape(viewEmployeeListUrl($per_page_param, $i)) . '">' . $i . '</a>';
                        }
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<a href="' . viewEmployeeEscape(viewEmployeeListUrl($per_page_param, $total_pages)) . '">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo viewEmployeeEscape(viewEmployeeListUrl($per_page_param, $current_page + 1)); ?>">Next</a>
                    <?php else: ?>
                    <button disabled>Next</button>
                    <?php endif; ?>
                </div>
            </div>

        
            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
           ?>

        </main>

    </div>

    <script>
        (() => {
            const menuToggleButton = document.getElementById('menuToggle');
            const sidebarElement = document.getElementById('sidebar');
            const sidebarOverlayElement = document.getElementById('sidebarOverlay');
            const closeSidebarButton = document.getElementById('closeSidebar');

            if (menuToggleButton && sidebarElement && sidebarOverlayElement && closeSidebarButton) {
                menuToggleButton.addEventListener('click', () => {
                    sidebarElement.classList.remove('-translate-x-full');
                    sidebarOverlayElement.classList.remove('hidden');
                });

                closeSidebarButton.addEventListener('click', () => {
                    sidebarElement.classList.add('-translate-x-full');
                    sidebarOverlayElement.classList.add('hidden');
                });

                sidebarOverlayElement.addEventListener('click', () => {
                    sidebarElement.classList.add('-translate-x-full');
                    sidebarOverlayElement.classList.add('hidden');
                });
            }
        })();
    </script>

</body>

</html>
