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
if (isset($_POST['delete_target'])) {
    $target_id = intval($_POST['target_id']);
    
    $delete_query = "DELETE FROM base_fb_target WHERE id = ? AND station = ?";
    $stmt = $mysqli->prepare($delete_query);
    $stmt->bind_param("is", $target_id, $station_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Feedback target deleted successfully!';
    } else {
        $_SESSION['error_msg'] = 'Failed to delete feedback target.';
    }
    $stmt->close();
    
    header("Location: view-feedback-target.php");
    exit();
}

// Handle edit/update request
if (isset($_POST['update_target'])) {
    $target_id = intval($_POST['edit_target_id']);
    $train_no = $_POST['edit_train_no'];
    $no_ac_coach = intval($_POST['edit_no_ac_coach']);
    $feed_per_ac_coach = intval($_POST['edit_feed_per_ac_coach']);
    $no_non_ac_coach = intval($_POST['edit_no_non_ac_coach']);
    $feed_per_non_ac_coach = intval($_POST['edit_feed_per_non_ac_coach']);
    $feedback_tte = intval($_POST['edit_feedback_tte']);
    
    $update_query = "UPDATE base_fb_target SET train_no = ?, no_ac_coach = ?, feed_per_ac_coach = ?, no_non_ac_coach = ?, feed_per_non_ac_coach = ?, feedback_tte = ?, updated_at = NOW() WHERE id = ? AND station = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param("siiiiiis", $train_no, $no_ac_coach, $feed_per_ac_coach, $no_non_ac_coach, $feed_per_non_ac_coach, $feedback_tte, $target_id, $station_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Feedback target updated successfully!';
    } else {
        $_SESSION['error_msg'] = 'Failed to update feedback target.';
    }
    $stmt->close();
    
    header("Location: view-feedback-target.php");
    exit();
}

// Fetch feedback targets from database
$feedback_targets = [];
$query = "SELECT * FROM base_fb_target WHERE station = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $station_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $feedback_targets[] = $row;
}
$stmt->close();

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
                        Showing 1 to 10 of 0 entries
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

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-800">Edit Feedback Target</h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="edit_target_id" id="edit_target_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Train No</label>
                        <input type="text" name="edit_train_no" id="edit_train_no" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. AC Coach</label>
                        <input type="number" name="edit_no_ac_coach" id="edit_no_ac_coach" min="0" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Feed Per AC Coach</label>
                        <input type="number" name="edit_feed_per_ac_coach" id="edit_feed_per_ac_coach" min="0" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. Non-AC Coach</label>
                        <input type="number" name="edit_no_non_ac_coach" id="edit_no_non_ac_coach" min="0" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Feed Per Non-AC Coach</label>
                        <input type="number" name="edit_feed_per_non_ac_coach" id="edit_feed_per_non_ac_coach" min="0" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Feedback TTE</label>
                        <input type="number" name="edit_feedback_tte" id="edit_feedback_tte" min="0" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="submit" name="update_target"
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
        <input type="hidden" name="delete_target" value="1">
        <input type="hidden" name="target_id" id="delete_target_id">
    </form>

    <script>
        // Feedback data from PHP
        const feedbackData = <?php echo json_encode($feedback_targets); ?>;

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

            if (pageData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">No feedback targets found.</td></tr>';
                updateTableInfo();
                return;
            }

            pageData.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="sr-column">${start + index + 1}</td>
                    <td>${item.train_no}</td>
                    <td>${item.no_ac_coach}</td>
                    <td>${item.feed_per_ac_coach}</td>
                    <td>${item.no_non_ac_coach}</td>
                    <td>${item.feed_per_non_ac_coach}</td>
                    <td>${item.feedback_tte}</td>
                    <td>
                        <button class="action-btn edit-btn" onclick="editRecord(${item.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteRecord(${item.id})">
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
        function editRecord(id) {
            const record = feedbackData.find(item => item.id == id);
            if (!record) return;
            
            document.getElementById('edit_target_id').value = record.id;
            document.getElementById('edit_train_no').value = record.train_no;
            document.getElementById('edit_no_ac_coach').value = record.no_ac_coach;
            document.getElementById('edit_feed_per_ac_coach').value = record.feed_per_ac_coach;
            document.getElementById('edit_no_non_ac_coach').value = record.no_non_ac_coach;
            document.getElementById('edit_feed_per_non_ac_coach').value = record.feed_per_non_ac_coach;
            document.getElementById('edit_feedback_tte').value = record.feedback_tte;
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Delete record
        function deleteRecord(id) {
            const record = feedbackData.find(item => item.id == id);
            if (!record) return;
            
            if (confirm(`Are you sure you want to delete the feedback target for Train No: ${record.train_no}?`)) {
                document.getElementById('delete_target_id').value = id;
                document.getElementById('deleteForm').submit();
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