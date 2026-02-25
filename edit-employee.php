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
    <title>Edit Employee - Jodhpur</title>
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
            max-width: 800px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .form-input:disabled {
            background-color: #f1f5f9;
            cursor: not-allowed;
        }

        .file-input {
            padding: 8px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            width: 100%;
        }

        .photo-preview {
            margin-top: 10px;
            display: none;
        }

        .photo-preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }

        .current-photo {
            margin-top: 10px;
        }

        .current-photo img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-update {
            background-color: #0ea5e9;
            color: white;
        }

        .btn-update:hover {
            background-color: #0284c7;
        }

        .btn-cancel {
            background-color: #64748b;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #475569;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .required {
            color: #ef4444;
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
                <h2 class="text-xl font-bold text-slate-800">Edit Employee Details</h2>
                <p class="text-sm text-slate-600 mt-1">Update employee information below</p>
            </div>

            <div class="form-container">
                <form id="editEmployeeForm" onsubmit="updateEmployee(event)">

                    <div class="form-grid">
                        <!-- Name -->
                        <div class="form-group">
                            <label class="form-label">Name <span class="required">*</span></label>
                            <input type="text" name="name" id="name" class="form-input" placeholder="Enter name"
                                value="ABDUL SAHID" required>
                        </div>

                        <!-- Employee ID -->
                        <div class="form-group">
                            <label class="form-label">Employee ID <span class="required">*</span></label>
                            <input type="text" name="employeeId" id="employeeId" class="form-input"
                                placeholder="Employee ID" value="SKS-002" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <!-- Designation -->
                        <div class="form-group">
                            <label class="form-label">Designation <span class="required">*</span></label>
                            <input type="text" name="designation" id="designation" class="form-input"
                                placeholder="Designation" value="JANITOR" required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" id="phone" class="form-input" placeholder="Phone Number"
                                value="9876543210">
                        </div>
                    </div>

                    <div class="form-grid">
                        <!-- Email -->
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-input" placeholder="Email"
                                value="abdul.sahid@example.com">
                        </div>

                        <!-- Date of Joining -->
                        <div class="form-group">
                            <label class="form-label">Date of Joining</label>
                            <input type="date" name="dateOfJoining" id="dateOfJoining" class="form-input"
                                value="2024-01-15">
                        </div>
                    </div>

                    <!-- Photo -->
                    <div class="form-group">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" id="photo" class="file-input" accept="image/*"
                            onchange="previewPhoto(this)">

                        <!-- Current Photo -->
                        <div class="current-photo" id="currentPhoto">
                            <p class="text-sm text-slate-600 mt-2 mb-2">Current Photo:</p>
                            <img src="https://via.placeholder.com/120" alt="Current Photo">
                        </div>

                        <!-- New Photo Preview -->
                        <div class="photo-preview" id="photoPreview">
                            <p class="text-sm text-slate-600 mt-2 mb-2">New Photo Preview:</p>
                            <img id="previewImage" src="" alt="Preview">
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-input" rows="3"
                            placeholder="Enter address">123 Main Street, Jodhpur, Rajasthan - 342001</textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-update">
                            <i class="fas fa-save mr-2"></i>Update Employee
                        </button>
                        <button type="button" class="btn btn-cancel" onclick="cancelEdit()">
                            <i class="fas fa-times mr-2"></i>Cancel
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
        // Get employee ID from URL parameter
        function getEmployeeIdFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('id');
        }

        // Load employee data (in real app, fetch from API)
        function loadEmployeeData() {
            const employeeId = getEmployeeIdFromURL();
            if (employeeId) {
                console.log('Loading employee data for ID:', employeeId);
                // In production, fetch data from API using employeeId
                // For now, sample data is already filled in the form
            }
        }

        // Preview photo before upload
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const previewImage = document.getElementById('previewImage');
            const currentPhoto = document.getElementById('currentPhoto');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    previewImage.src = e.target.result;
                    preview.style.display = 'block';
                    currentPhoto.style.display = 'none';
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        // Update employee
        function updateEmployee(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const employeeData = {
                name: formData.get('name'),
                employeeId: formData.get('employeeId'),
                designation: formData.get('designation'),
                phone: formData.get('phone'),
                email: formData.get('email'),
                dateOfJoining: formData.get('dateOfJoining'),
                address: formData.get('address'),
                photo: formData.get('photo').name || 'No change'
            };

            console.log('Updated Employee Data:', employeeData);
            alert('Employee updated successfully!\n\nData:\n' + JSON.stringify(employeeData, null, 2));

            // In production, send data to server API
            // Then redirect to view-employee.php
            // window.location.href = 'view-employee.php';
        }

        // Cancel edit
        function cancelEdit() {
            if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                window.location.href = 'view-employee.php';
            }
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

        // Load employee data on page load
        window.addEventListener('DOMContentLoaded', loadEmployeeData);
    </script>

</body>

</html>