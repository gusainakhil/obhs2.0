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

            <div class="form-container">
                <form id="employeeForm" onsubmit="submitForm(event)">
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
                        <button type="submit" class="btn btn-submit">
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

        // Submit form
        function submitForm(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const names = formData.getAll('name[]');
            const employeeIds = formData.getAll('employeeId[]');
            const designations = formData.getAll('designation[]');
            const photos = formData.getAll('photo[]');

            let employeeData = [];
            for (let i = 0; i < names.length; i++) {
                employeeData.push({
                    name: names[i],
                    employeeId: employeeIds[i],
                    designation: designations[i],
                    photo: photos[i].name || 'No file selected'
                });
            }

            console.log('Employee Data:', employeeData);
            alert(`Successfully added ${employeeData.length} employee(s)!\n\nData:\n${JSON.stringify(employeeData, null, 2)}`);

            // In production, send data to server
            // Reset form
            event.target.reset();
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