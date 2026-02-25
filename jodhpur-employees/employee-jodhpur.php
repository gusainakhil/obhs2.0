<?php
session_start();
include '../includes/connection.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Get photo filename first
    $photo_query = "SELECT photo FROM base_employees_jodhpur WHERE id = ?";
    $stmt = $mysqli->prepare($photo_query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $photo = $row['photo'];
        
        // Delete from database
        $delete_query = "DELETE FROM base_employees_jodhpur WHERE id = ?";
        $stmt = $mysqli->prepare($delete_query);
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            // Delete photo file if exists
            if ($photo && file_exists("../uploads/employee/" . $photo)) {
                unlink("../uploads/employee/" . $photo);
            }
            $_SESSION['success'] = "Employee deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting employee.";
        }
    }
    $stmt->close();
    header("Location: employee-jodhpur.php");
    exit();
}

// Fetch all employees
$data = [];
$query = "SELECT * FROM base_employees_jodhpur ORDER BY created_at DESC";
$result = $mysqli->query($query);
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .dataTables_wrapper {
            font-family: inherit;
        }

        .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_length {
            margin-bottom: 1rem;
        }

        .table th {
            white-space: nowrap;
            font-size: 0.8rem;
            padding: 0.5rem 0.3rem;
            background-color: #212529 !important;
            color: white !important;
            border-color: #444 !important;
        }

        .table td {
            padding: 0.5rem 0.3rem;
            font-size: 0.75rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.7rem;
            margin: 0 1px;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 70px;
            text-align: center;
        }

        .table th:last-child,
        .table td:last-child {
            width: 100px;
            text-align: center;
        }

        .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .employee-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
        }

        .btn-group-custom {
            gap: 0.5rem;
        }

        .dt-buttons {
            margin-bottom: 1rem;
        }

        .dt-button {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: white !important;
            margin-right: 0.5rem;
        }

        .employee-name-link {
            color: #0d6efd !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            transition: all 0.3s ease;
        }

        .employee-name-link:hover {
            color: #0a58ca !important;
            text-decoration: underline !important;
            transform: translateX(2px);
        }

        .dataTables_scrollBody {
            overflow-x: auto !important;
        }

        @media (max-width: 768px) {
            .table th,
            .table td {
                font-size: 0.7rem;
                padding: 0.3rem 0.2rem;
            }
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="employee-header rounded mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="../dashboard.php" class="btn btn-light me-3">
                        <i class="fa fa-home"></i> Back to Home
                    </a>
                    <h2 class="fw-bold mb-0">Employee Directory - Jodhpur</h2>
                </div>
                <div class="btn-group-custom d-flex">
                    <a href="add-employee-jodhpur.php" class="btn btn-success me-2">
                        <i class="fa fa-plus"></i> Add Employee
                    </a>
                    <button class="btn btn-light me-2" onclick="printPDF()"><i class="fa fa-file-pdf"></i> PDF</button>
                    <button class="btn btn-light" onclick="exportToExcel()"><i class="fa fa-file-excel"></i> Excel</button>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="employeeTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Action</th>
                            <th>Employee ID</th>
                            <th>Designation</th>
                            <th>Rakshak_ID</th>
                            <th>FATHER_NAME</th>
                            <th>MOBILE_NO</th>
                            <th>ADHAR_NO</th>
                            <th>DOB</th>
                            <th>AGE</th>
                            <th>ADDRESH</th>
                            <th>PVC</th>
                            <th>PVC_Ok_Applied</th>
                            <th>PVC_Issue_Month</th>
                            <th>MEDICAL</th>
                            <th>MEDICAL_ISSUE_MONTH</th>
                            <th>PAN_CARD</th>
                            <th>AC_NAME</th>
                            <th>AC_NO</th>
                            <th>IFSC_CODE</th>
                            <th>EDU</th>
                            <th>REMARK</th>
                            <th>STATUS</th>
                            <th>Issue_Date</th>
                            <th>Valid_Upto_date</th>
                            <th>notification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $variable = 1; ?>
                        <?php foreach($data as $val): ?>
                            <tr>
                                <td><?php echo $variable; ?></td>
                                <td><img src="../uploads/employee/<?php echo htmlspecialchars($val['photo']); ?>" width="60" class="border" style="height: 60px; object-fit: cover;"></td>
                                <td>
                                    <a href="employee-details-jodhpur.php?id=<?php echo $val['id']; ?>" class="employee-name-link" target="_blank">
                                        <?php echo htmlspecialchars($val['employee_id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="showPasswordModal('edit', 'edit-employee-jodhpur.php?id=<?php echo $val['id']; ?>')">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="showPasswordModal('delete', 'employee-jodhpur.php?delete_id=<?php echo $val['id']; ?>')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($val['name']); ?></td>
                                <td><?php echo htmlspecialchars($val['desination']); ?></td>
                                <td><?php echo htmlspecialchars($val['Rakshak_ID'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['FATHER_NAME'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['MOBILE_NO'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['ADHAR_NO'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['DOB'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['AGE'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['ADDRESH'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['PVC'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['PVC_Ok_Applied'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['PVC_Issue_Month'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['MEDICAL'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['MEDICAL_ISSUE_MONTH'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['PAN_CARD'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['AC_NAME'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['AC_NO'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['IFSC_CODE'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['EDU'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['REMARK'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['STATUS'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['Issue_Date'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['Valid_Upto_date'] ?? 'NA'); ?></td>
                                <td><?php echo htmlspecialchars($val['notification'] ?? 'NA'); ?></td>
                            </tr>
                            <?php $variable++; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Password Confirmation Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Authentication Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="passwordInput" class="form-label">Please enter password to proceed:</label>
                        <input type="password" class="form-control" id="passwordInput" placeholder="Enter password">
                        <div id="passwordError" class="text-danger mt-2" style="display: none;">
                            Incorrect password. Please try again.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmPasswordBtn">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <!-- XLSX library for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script>
        let currentAction = '';
        let currentUrl = '';
        const ADMIN_PASSWORD = 'admin123';

        $(document).ready(function () {
            var table = $('#employeeTable').DataTable({
                stateSave: false,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                pageLength: 10,
                scrollX: true,
                scrollCollapse: true,
                autoWidth: false,
                columnDefs: [
                    { width: "50px", targets: 0 },
                    { width: "80px", targets: 1 },
                    { width: "120px", targets: 2 },
                    { width: "100px", targets: 3 },
                    { width: "120px", targets: -1 }
                ]
            });

            table.settings()[0].oPreviousSearch.sSearch = null;
            setupPasswordModal();
        });

        function showPasswordModal(action, url) {
            currentAction = action;
            currentUrl = url;

            $('#passwordInput').val('');
            $('#passwordError').hide();

            let actionText = (action === 'edit') ? 'Edit' : 'Delete';
            $('#passwordModalLabel').text(`Authentication Required for ${actionText}`);

            const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
            passwordModal.show();

            $('#passwordInput').focus();
        }

        function setupPasswordModal() {
            $('#confirmPasswordBtn').on('click', function () {
                validatePassword();
            });

            $('#passwordInput').on('keypress', function (e) {
                if (e.which === 13) {
                    validatePassword();
                }
            });

            $('#passwordInput').on('input', function () {
                $('#passwordError').hide();
            });
        }

        function validatePassword() {
            const enteredPassword = $('#passwordInput').val();

            if (enteredPassword === ADMIN_PASSWORD) {
                window.location.href = currentUrl;
            } else {
                $('#passwordError').show();
                $('#passwordInput').val('').focus();
            }
        }

        function printPDF() {
            try {
                $('#employeeTable').DataTable().button('.buttons-pdf').trigger();
            } catch (e) {
                console.error('PDF export failed:', e);
                window.print();
            }
        }

        function exportToExcel() {
            try {
                $('#employeeTable').DataTable().button('.buttons-excel').trigger();
            } catch (e) {
                console.error('Excel export failed:', e);
                alert('Excel export is not available. Please use the DataTable buttons below the search.');
            }
        }

        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>
