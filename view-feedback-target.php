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
    <title>View Feedback Target - Jodhpur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table thead {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }

        .data-table thead th {
            padding: 12px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .data-table thead th:last-child {
            border-right: none;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .data-table tbody tr:nth-child(even):hover {
            background-color: #f1f5f9;
        }

        .data-table tbody td {
            padding: 10px;
            text-align: center;
            color: #334155;
            font-size: 13px;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            margin: 0 2px;
        }

        .edit-btn {
            background-color: #0ea5e9;
            color: white;
        }

        .edit-btn:hover {
            background-color: #0284c7;
        }

        .delete-btn {
            background-color: #ef4444;
            color: white;
        }

        .delete-btn:hover {
            background-color: #dc2626;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .pagination button,
        .pagination span {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            background: white;
            color: #475569;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .pagination button:hover:not(:disabled) {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .active {
            background-color: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
            font-weight: 600;
        }

        .entries-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #475569;
        }

        .entries-selector select {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }

        .table-info {
            font-size: 13px;
            color: #64748b;
            margin-top: 15px;
        }

        .sr-column {
            font-weight: 600;
            color: #475569;
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

            <!-- Table Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-800 mb-2">
                        <i class="fas fa-table text-cyan-500 mr-2"></i>
                        Feedback Target Records
                    </h2>
                    <p class="text-sm text-slate-600">View and manage all feedback targets for trains</p>
                </div>

                <!-- Entries Selector -->
                <div class="entries-selector">
                    <span>Show</span>
                    <select id="entriesPerPage" onchange="changeEntriesPerPage()">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SR. No. <i class="fas fa-sort ml-1"></i></th>
                                <th>Train No.</th>
                                <th>No AC Coach</th>
                                <th>Feedbacks Per AC Coach</th>
                                <th>No NON-AC Coach</th>
                                <th>Feedbacks Per NON-AC Coach</th>
                                <th>Feedback TTE</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Info and Controls -->
                <div class="flex flex-col sm:flex-row justify-between items-center mt-4 gap-4">
                    <div class="table-info" id="tableInfo">
                        Showing 1 to 10 of 62 entries
                    </div>
                    <div class="pagination" id="pagination">
                        <!-- Pagination will be generated by JavaScript -->
                    </div>
                </div>

            </div>

        
            <!-- Footer -->
           <?php
            require_once 'includes/footer.php'
           ?>

        </main>

    </div>

    <script>
        // Sample Data (In real application, this would come from an API)
        const feedbackData = [
            { trainNo: 12466, acCoach: 6, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14802, acCoach: 6, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12479, acCoach: 11, acFeedback: 2, nonAcCoach: 5, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12480, acCoach: 11, acFeedback: 2, nonAcCoach: 5, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14801, acCoach: 6, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12465, acCoach: 6, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14807, acCoach: 9, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14808, acCoach: 9, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14813, acCoach: 3, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14814, acCoach: 3, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14815, acCoach: 4, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14816, acCoach: 4, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14853, acCoach: 5, acFeedback: 2, nonAcCoach: 6, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14854, acCoach: 5, acFeedback: 2, nonAcCoach: 6, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14863, acCoach: 7, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14864, acCoach: 7, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14865, acCoach: 6, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14866, acCoach: 6, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14887, acCoach: 8, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14888, acCoach: 8, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20481, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20482, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20483, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20484, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20485, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20486, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20487, acCoach: 4, acFeedback: 2, nonAcCoach: 6, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20488, acCoach: 4, acFeedback: 2, nonAcCoach: 6, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20489, acCoach: 8, acFeedback: 2, nonAcCoach: 10, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20490, acCoach: 8, acFeedback: 2, nonAcCoach: 10, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20491, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20492, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20495, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20496, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 22481, acCoach: 9, acFeedback: 2, nonAcCoach: 11, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 22482, acCoach: 9, acFeedback: 2, nonAcCoach: 11, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 22483, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 22484, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: '04813', acCoach: 4, acFeedback: 2, nonAcCoach: 6, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: '04814', acCoach: 4, acFeedback: 2, nonAcCoach: 6, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: '04827', acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: '04828', acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: '04829', acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: '04830', acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12466, acCoach: 8, acFeedback: 2, nonAcCoach: 10, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12479, acCoach: 9, acFeedback: 2, nonAcCoach: 11, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14803, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14804, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14805, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14806, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20493, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20494, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 22485, acCoach: 8, acFeedback: 2, nonAcCoach: 10, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 22486, acCoach: 8, acFeedback: 2, nonAcCoach: 10, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12467, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 12468, acCoach: 7, acFeedback: 2, nonAcCoach: 9, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14809, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14810, acCoach: 6, acFeedback: 2, nonAcCoach: 8, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14811, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 14812, acCoach: 5, acFeedback: 2, nonAcCoach: 7, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20497, acCoach: 9, acFeedback: 2, nonAcCoach: 11, nonAcFeedback: 2, tteFeedback: 0 },
            { trainNo: 20498, acCoach: 9, acFeedback: 2, nonAcCoach: 11, nonAcFeedback: 2, tteFeedback: 0 }
        ];

        let currentPage = 1;
        let entriesPerPage = 10;

        // Initialize table
        function initTable() {
            renderTable();
            renderPagination();
        }

        // Render table rows
        function renderTable() {
            const tableBody = document.getElementById('tableBody');
            const start = (currentPage - 1) * entriesPerPage;
            const end = start + entriesPerPage;
            const pageData = feedbackData.slice(start, end);

            tableBody.innerHTML = '';

            pageData.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="sr-column">${start + index + 1}</td>
                    <td>${item.trainNo}</td>
                    <td>${item.acCoach}</td>
                    <td>${item.acFeedback}</td>
                    <td>${item.nonAcCoach}</td>
                    <td>${item.nonAcFeedback}</td>
                    <td>${item.tteFeedback}</td>
                    <td>
                        <button class="action-btn edit-btn" onclick="editRecord(${start + index})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteRecord(${start + index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            updateTableInfo();
        }

        // Render pagination
        function renderPagination() {
            const pagination = document.getElementById('pagination');
            const totalPages = Math.ceil(feedbackData.length / entriesPerPage);

            let paginationHTML = `
                <button onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Previous</button>
            `;

            // Show page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    paginationHTML += `
                        <span class="${i === currentPage ? 'active' : ''}" 
                              onclick="goToPage(${i})" 
                              style="cursor: pointer;">
                            ${i}
                        </span>
                    `;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    paginationHTML += `<span>...</span>`;
                }
            }

            paginationHTML += `
                <button onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
            `;

            pagination.innerHTML = paginationHTML;
        }

        // Update table info
        function updateTableInfo() {
            const start = (currentPage - 1) * entriesPerPage + 1;
            const end = Math.min(currentPage * entriesPerPage, feedbackData.length);
            const total = feedbackData.length;

            document.getElementById('tableInfo').textContent = `Showing ${start} to ${end} of ${total} entries`;
        }

        // Go to specific page
        function goToPage(page) {
            const totalPages = Math.ceil(feedbackData.length / entriesPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                renderTable();
                renderPagination();
            }
        }

        // Change entries per page
        function changeEntriesPerPage() {
            entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            currentPage = 1;
            renderTable();
            renderPagination();
        }

        // Edit record
        function editRecord(index) {
            const record = feedbackData[index];
            alert(`Edit Record:\nTrain No: ${record.trainNo}\n\n(Edit functionality would open a modal or redirect to edit page)`);
            // In real application, this would open an edit modal or redirect to edit page
        }

        // Delete record
        function deleteRecord(index) {
            const record = feedbackData[index];
            if (confirm(`Are you sure you want to delete the record for Train No: ${record.trainNo}?`)) {
                feedbackData.splice(index, 1);

                // Adjust current page if necessary
                const totalPages = Math.ceil(feedbackData.length / entriesPerPage);
                if (currentPage > totalPages && currentPage > 1) {
                    currentPage = totalPages;
                }

                renderTable();
                renderPagination();

                alert('Record deleted successfully!');
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

        // Initialize table on page load
        window.addEventListener('DOMContentLoaded', initTable);
    </script>

</body>

</html>