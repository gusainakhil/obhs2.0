<?php
session_start();
include '../includes/connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $name = $_POST['name'];
    $desination = $_POST['desination'];
    $AGE = $_POST['AGE'] ?? '';
    
    // Personal Info
    $FATHER_NAME = $_POST['FATHER_NAME'] ?? '';
    $FORMULA_DOB = $_POST['FORMULA_DOB'] ?? '';
    $MOBILE_NO = $_POST['MOBILE_NO'] ?? '';
    $DOB = $_POST['DOB'] ?? '';
    $ADHAR_NO = $_POST['ADHAR_NO'] ?? '';
    $ADDRESH = $_POST['ADDRESH'] ?? '';
    
    // ID Info
    $REN_ID = $_POST['REN_ID'] ?? '';
    $Rakshak_ID = $_POST['Rakshak_ID'] ?? '';
    $PAN_CARD = $_POST['PAN_CARD'] ?? '';
    $Police_ver = $_POST['Police_ver'] ?? '';
    $Police_ver_dt = $_POST['Police_ver_dt'] ?? '';
    
    // Documents
    $PVC = $_POST['PVC'] ?? '';
    $PVC_Ok_Applied = $_POST['PVC_Ok_Applied'] ?? '';
    $PVC_Issue_Month = $_POST['PVC_Issue_Month'] ?? '';
    $MEDICAL = $_POST['MEDICAL'] ?? '';
    $MEDICAL_ISSUE_MONTH = $_POST['MEDICAL_ISSUE_MONTH'] ?? '';
    $EDU = $_POST['EDU'] ?? '';
    $Doc_Status = $_POST['Doc_Status'] ?? '';
    
    // Bank Info
    $AC_NAME = $_POST['AC_NAME'] ?? '';
    $AC_NO = $_POST['AC_NO'] ?? '';
    $IFSC_CODE = $_POST['IFSC_CODE'] ?? '';
    
    // Status
    $STATUS = $_POST['STATUS'] ?? '';
    $Issue_Date = $_POST['Issue_Date'] ?? '';
    $Valid_Upto_date = $_POST['Valid_Upto_date'] ?? '';
    $Valid_Upto_Month = $_POST['Valid_Upto_Month'] ?? '';
    $FORMULA_Valid_Upto = $_POST['FORMULA_Valid_Upto'] ?? '';
    $DOCUMENT_LINK = $_POST['DOCUMENT_LINK'] ?? '';
    $notification = $_POST['notification'] ?? '';
    $REMARK = $_POST['REMARK'] ?? '';
    
    // Handle photo upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $photo = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/employee/' . $photo);
        }
    }
    
    // Insert into database
    $query = "INSERT INTO base_employees_jodhpur 
              (employee_id, name, station, desination, photo, REN_ID, Rakshak_ID, FATHER_NAME, Police_ver, 
               Police_ver_dt, MOBILE_NO, ADHAR_NO, DOB, FORMULA_DOB, AGE, ADDRESH, PVC, PVC_Ok_Applied, 
               PVC_Issue_Month, MEDICAL, MEDICAL_ISSUE_MONTH, PAN_CARD, AC_NAME, AC_NO, IFSC_CODE, EDU, 
               Doc_Status, REMARK, STATUS, Issue_Date, Valid_Upto_date, FORMULA_Valid_Upto, Valid_Upto_Month, 
               DOCUMENT_LINK, notification, created_at, updated_at) 
              VALUES (?, ?, 'jodhpur', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ssssssssssssssssssssssssssssssssss", 
        $employee_id, $name, $desination, $photo, $REN_ID, $Rakshak_ID, $FATHER_NAME, 
        $Police_ver, $Police_ver_dt, $MOBILE_NO, $ADHAR_NO, $DOB, $FORMULA_DOB, $AGE, $ADDRESH, 
        $PVC, $PVC_Ok_Applied, $PVC_Issue_Month, $MEDICAL, $MEDICAL_ISSUE_MONTH, $PAN_CARD, 
        $AC_NAME, $AC_NO, $IFSC_CODE, $EDU, $Doc_Status, $REMARK, $STATUS, $Issue_Date, 
        $Valid_Upto_date, $FORMULA_Valid_Upto, $Valid_Upto_Month, $DOCUMENT_LINK, $notification);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee created successfully!";
        header("Location: employee-jodhpur.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating employee: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Employee - Prime OBHS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .employee-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .form-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .section-title {
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .btn-custom {
            padding: 0.75rem 2rem;
            border-radius: 0.375rem;
        }

        .required-field {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="employee-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="employee-jodhpur.php" class="btn btn-light me-3">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                    <h2 class="fw-bold mb-0">Create New Employee</h2>
                </div>
                <div class="text-end">
                    <small class="opacity-75">Add a new employee to the system</small>
                </div>
            </div>
        </div>

        <!-- Alerts -->
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

        <!-- Create Form -->
        <div class="form-card p-4">
            <form method="POST" enctype="multipart/form-data">

                <!-- Basic Information -->
                <h4 class="section-title">
                    <i class="fa fa-user me-2"></i>Basic Information
                </h4>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Employee ID <span class="required-field">*</span></label>
                        <input type="text" name="employee_id" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Full Name <span class="required-field">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Designation <span class="required-field">*</span></label>
                        <input type="text" name="desination" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Age</label>
                        <input type="text" name="AGE" class="form-control">
                    </div>
                </div>

                <!-- Photo Section -->
                <h4 class="section-title">
                    <i class="fa fa-camera me-2"></i>Photo
                </h4>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Employee Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Supported formats: JPG, PNG, GIF</small>
                    </div>
                </div>

                <!-- Personal Information -->
                <h4 class="section-title">
                    <i class="fa fa-id-card me-2"></i>Personal Information
                </h4>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="FATHER_NAME" class="form-control">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="FORMULA_DOB" class="form-control">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="MOBILE_NO" class="form-control">
                    </div>
                    
                </div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="DOB" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Aadhar Number</label>
                        <input type="text" name="ADHAR_NO" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Address</label>
                        <input type="text" name="ADDRESH" class="form-control">
                    </div>
                </div>

                <!-- ID Information -->
                <h4 class="section-title">
                    <i class="fa fa-id-badge me-2"></i>ID Information
                </h4>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">REN ID</label>
                        <input type="text" name="REN_ID" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rakshak ID</label>
                        <input type="text" name="Rakshak_ID" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN Card</label>
                        <input type="text" name="PAN_CARD" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Police Ver.</label>
                        <input type="text" name="Police_ver" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Police Ver. Dt</label>
                        <input type="text" name="Police_ver_dt" class="form-control">
                    </div>
                </div>

                <!-- Documents & Medical -->
                <h4 class="section-title">
                    <i class="fa fa-file-medical me-2"></i>Documents & Medical
                </h4>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">PVC</label>
                        <input type="text" name="PVC" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PVC Ok Applied</label>
                        <input type="text" name="PVC_Ok_Applied" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PVC Issue Month</label>
                        <input type="text" name="PVC_Issue_Month" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Medical</label>
                        <input type="text" name="MEDICAL" class="form-control">
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Medical Issue Month</label>
                        <input type="text" name="MEDICAL_ISSUE_MONTH" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Education</label>
                        <input type="text" name="EDU" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Document Status</label>
                        <input type="text" name="Doc_Status" class="form-control">
                    </div>
                </div>

                <!-- Bank Information -->
                <h4 class="section-title">
                    <i class="fa fa-university me-2"></i>Bank Information
                </h4>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="AC_NAME" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="AC_NO" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IFSC Code</label>
                        <input type="text" name="IFSC_CODE" class="form-control">
                    </div>
                </div>

                <!-- Status & Validity -->
                <h4 class="section-title">
                    <i class="fa fa-calendar-check me-2"></i>Status & Validity
                </h4>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <input type="text" name="STATUS" class="form-control" placeholder="Enter Status">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="Issue_Date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valid Upto Date</label>
                        <input type="date" name="Valid_Upto_date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valid Upto Month</label>
                        <input type="text" name="Valid_Upto_Month" class="form-control">
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Formula Valid Upto</label>
                        <input type="text" name="FORMULA_Valid_Upto" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Document Link</label>
                        <input type="text" name="DOCUMENT_LINK" class="form-control" placeholder="https://">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notification</label>
                        <input type="text" name="notification" class="form-control">
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="REMARK" class="form-control" rows="3"
                            placeholder="Add any additional remarks or notes..."></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-3 mt-5">
                    <a href="employee-jodhpur.php" class="btn btn-secondary btn-custom">
                        <i class="fa fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-success btn-custom">
                        <i class="fa fa-save me-2"></i>Create Employee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
