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
    <title>View Employee - Jodhpur</title>
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

            <!-- Controls Bar -->
            <div class="controls-bar">
                <div class="entries-control">
                    <span>Show</span>
                    <select id="entriesPerPage" onchange="changeEntries()">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
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
                        <tr>
                            <td>1</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>ABDUL SAHID</td>
                            <td>SKS-002</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(1)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(1)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>RAHUL YADAV</td>
                            <td>SKS-003</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(2)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(2)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>AKASH YADAV</td>
                            <td>SKS-004</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(3)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(3)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>DINESH PAL</td>
                            <td>SKS-005</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(4)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(4)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>SANTOSH KUMAR SAHU</td>
                            <td>SKS-006</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(5)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(5)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>SUNIL PAIKRA</td>
                            <td>SKS-007</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(6)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(6)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>KISHAN KUMAR</td>
                            <td>SKS-008</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(7)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(7)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>ABHILASH SINGH</td>
                            <td>SKS-009</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(8)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(8)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>RAHUL BANJARE</td>
                            <td>SKS-010</td>
                            <td>JANITOR</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(9)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(9)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td><img src="https://via.placeholder.com/40" alt="Employee" class="employee-photo mx-auto">
                            </td>
                            <td>SURESH KHANDEKAR</td>
                            <td>SKS-011</td>
                            <td>EHK</td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editEmployee(10)"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteEmployee(10)"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing 1 to 10 of 294 entries
                </div>
                <div class="pagination-controls">
                    <button disabled>Previous</button>
                    <span class="active">1</span>
                    <span onclick="goToPage(2)">2</span>
                    <span onclick="goToPage(3)">3</span>
                    <span onclick="goToPage(4)">4</span>
                    <span onclick="goToPage(5)">5</span>
                    <span>...</span>
                    <span onclick="goToPage(30)">30</span>
                    <button onclick="goToPage(2)">Next</button>
                </div>
            </div>

        
            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
           ?>

        </main>

    </div>

    <script>
        // Edit employee
        function editEmployee(id) {
            alert('Edit employee with ID: ' + id + '\n\n(This would open an edit form or modal)');
        }

        // Delete employee
        function deleteEmployee(id) {
            if (confirm('Are you sure you want to delete this employee?')) {
                alert('Employee deleted successfully!');
                // In real app, delete from table and refresh
            }
        }

        // Export PDF
        function exportPDF() {
            alert('Exporting to PDF...\nIn production, this would generate a PDF report.');
        }

        // Export Excel
        function exportExcel() {
            alert('Exporting to Excel...\nIn production, this would generate an Excel file.');
        }

        // Change entries per page
        function changeEntries() {
            const entries = document.getElementById('entriesPerPage').value;
            alert('Showing ' + entries + ' entries per page\n(In production, this would reload the table)');
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
            alert('Going to page ' + page + '\n(In production, this would load page ' + page + ' data)');
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