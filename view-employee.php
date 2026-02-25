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

// Handle delete request
if (isset($_POST['delete_employee'])) {
    $employee_id = intval($_POST['employee_id']);
    
    // Get employee photo before deleting
    $query = "SELECT photo FROM base_employees WHERE id = ? AND station_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $employee_id, $station_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $emp_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($emp_data) {
        // Delete from database
        $delete_query = "DELETE FROM base_employees WHERE id = ? AND station_id = ?";
        $stmt = $mysqli->prepare($delete_query);
        $stmt->bind_param("ii", $employee_id, $station_id);
        
        if ($stmt->execute()) {
            // Delete photo file if exists
            if (!empty($emp_data['photo'])) {
                $photo_file = 'uploads/employee/' . $emp_data['photo'];
                if (file_exists($photo_file)) {
                    unlink($photo_file);
                }
            }
            $_SESSION['success_msg'] = 'Employee deleted successfully!';
        } else {
            $_SESSION['error_msg'] = 'Failed to delete employee.';
        }
        $stmt->close();
    }
    
    header("Location: view-employee.php");
    exit();
}

// Handle edit/update request
if (isset($_POST['update_employee'])) {
    $employee_id = intval($_POST['edit_employee_id']);
    $name = $_POST['edit_name'];
    $employee_code = $_POST['edit_employee_id_code'];
    $designation = $_POST['edit_designation'];
    $old_photo = $_POST['old_photo'];
    $photo_name = $old_photo;
    
    // Handle photo upload if new photo is provided
    if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] == 0) {
        $upload_dir = 'uploads/employee/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_tmp = $_FILES['edit_photo']['tmp_name'];
        $file_name = $_FILES['edit_photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            $photo_name = $employee_code . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $photo_name;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Delete old photo if exists
                if (!empty($old_photo) && file_exists($upload_dir . $old_photo)) {
                    unlink($upload_dir . $old_photo);
                }
            } else {
                $photo_name = $old_photo; // Keep old photo if upload fails
            }
        }
    }
    
    // Update database
    $update_query = "UPDATE base_employees SET name = ?, employee_id = ?, desination = ?, photo = ?, updated_at = NOW() WHERE id = ? AND station_id = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param("ssssii", $name, $employee_code, $designation, $photo_name, $employee_id, $station_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Employee updated successfully!';
    } else {
        $_SESSION['error_msg'] = 'Failed to update employee.';
    }
    $stmt->close();
    
    header("Location: view-employee.php");
    exit();
}

// Fetch employees from database
$employees = [];

// Pagination settings
$per_page_param = isset($_GET['per_page']) ? $_GET['per_page'] : '10';
$records_per_page = ($per_page_param === 'all') ? PHP_INT_MAX : intval($per_page_param);
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($per_page_param === 'all') ? 0 : (($current_page - 1) * $records_per_page);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM base_employees WHERE station_id = ?";
$stmt = $mysqli->prepare($count_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$stmt->close();

$total_pages = ($per_page_param === 'all') ? 1 : ceil($total_records / $records_per_page);

// Fetch paginated employees
$query = "SELECT * FROM base_employees WHERE station_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("iii", $station_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
$stmt->close();

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

            <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Controls Bar -->
            <div class="controls-bar">
                <div class="entries-control">
                    <span>Show</span>
                    <select id="entriesPerPage" onchange="changeEntries(this.value)">
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
                        <button class="btn-export-top btn-pdf" onclick="exportPDF()">PDF</button>
                        <button class="btn-export-top btn-excel" onclick="exportExcel()">Excel</button>
                    </div>

                    <div class="search-control">
                        <span>Search:</span>
                        <input type="text" class="search-input" id="searchInput" onkeyup="searchTable()"
                            placeholder="Search...">
                    </div>
                </div>
            </div>

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
                        if (count($employees) > 0):
                            $sr_no = 1;
                            foreach ($employees as $employee):
                                $photo_path = !empty($employee['photo']) ? 'uploads/employee/' . $employee['photo'] : 'https://via.placeholder.com/40';
                                if (!empty($employee['photo']) && !file_exists($photo_path)) {
                                    $photo_path = 'https://via.placeholder.com/40';
                                }
                        ?>
                        <tr>
                            <td><?php echo $sr_no++; ?></td>
                            <td><img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Employee" class="employee-photo mx-auto"></td>
                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($employee['desination']); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $employee['id']; ?>, '<?php echo addslashes($employee['name']); ?>', '<?php echo addslashes($employee['employee_id']); ?>', '<?php echo addslashes($employee['desination']); ?>', '<?php echo addslashes($employee['photo']); ?>')"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(<?php echo $employee['id']; ?>)"><i class="fas fa-trash"></i></button>
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
                    Showing <?php echo $total_records > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                </div>
                <div class="pagination-controls">
                    <?php if ($current_page > 1): ?>
                    <button onclick="goToPage(<?php echo $current_page - 1; ?>)">Previous</button>
                    <?php else: ?>
                    <button disabled>Previous</button>
                    <?php endif; ?>
                    
                    <?php
                    // Show pagination numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1) {
                        echo '<span onclick="goToPage(1)">1</span>';
                        if ($start_page > 2) {
                            echo '<span>...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $current_page) {
                            echo '<span class="active">' . $i . '</span>';
                        } else {
                            echo '<span onclick="goToPage(' . $i . ')">' . $i . '</span>';
                        }
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<span onclick="goToPage(' . $total_pages . ')">' . $total_pages . '</span>';
                    }
                    ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                    <button onclick="goToPage(<?php echo $current_page + 1; ?>)">Next</button>
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

    <!-- Edit Employee Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-800">Edit Employee</h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="edit_employee_id" id="edit_employee_id">
                <input type="hidden" name="old_photo" id="old_photo">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Employee Name</label>
                    <input type="text" name="edit_name" id="edit_name" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Employee ID</label>
                    <input type="text" name="edit_employee_id_code" id="edit_employee_id_code" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Designation</label>
                    <input type="text" name="edit_designation" id="edit_designation" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Photo</label>
                    <div class="mb-2" id="current_photo_preview"></div>
                    <input type="file" name="edit_photo" id="edit_photo" accept="image/*"
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-slate-500 mt-1">Leave empty to keep current photo</p>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" name="update_employee"
                        class="flex-1 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 font-semibold">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button type="button" onclick="closeEditModal()"
                        class="flex-1 bg-slate-300 text-slate-700 px-4 py-2 rounded-md hover:bg-slate-400 font-semibold">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="delete_employee" value="1">
        <input type="hidden" name="employee_id" id="delete_employee_id">
    </form>

    <script>
        // Open edit modal
        function openEditModal(id, name, employeeId, designation, photo) {
            document.getElementById('edit_employee_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_employee_id_code').value = employeeId;
            document.getElementById('edit_designation').value = designation;
            document.getElementById('old_photo').value = photo;
            
            // Show current photo
            const photoPreview = document.getElementById('current_photo_preview');
            if (photo) {
                photoPreview.innerHTML = '<img src="uploads/employee/' + photo + '" alt="Current Photo" class="w-20 h-20 object-cover rounded border">';
            } else {
                photoPreview.innerHTML = '<p class="text-sm text-slate-500">No photo uploaded</p>';
            }
            
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Delete employee
        function deleteEmployee(id) {
            if (confirm('Are you sure you want to delete this employee? This will also delete their photo.')) {
                document.getElementById('delete_employee_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Export PDF
        function exportPDF() {
            window.open('export-employee-pdf.php', '_blank');
        }

        // Export Excel
        function exportExcel() {
            window.open('export-employee-excel.php', '_blank');
        }

        // Change entries per page
        function changeEntries(perPage) {
            window.location.href = '?per_page=' + perPage + '&page=1';
        }

        // Search table
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('employeeTableBody');
            const tr = table.getElementsByTagName('tr');

            for (let i = 0; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? '' : 'none';
            }
        }

        // Go to page
        function goToPage(page) {
            const perPage = document.getElementById('entriesPerPage').value;
            window.location.href = '?per_page=' + perPage + '&page=' + page;
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
    </script>

</body>

</html>