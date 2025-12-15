<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header('Location: user-list.php');
    exit;
}

// Fetch user details
$user_sql = "SELECT u.*, s.station_name 
             FROM OBHS_users u 
             LEFT JOIN OBHS_station s ON u.station_id = s.station_id 
             WHERE u.user_id = ? LIMIT 1";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: user-list.php');
    exit;
}

// Fetch assigned reports
$reports_sql = "SELECT reports_name, link FROM OBHS_reports WHERE user_id = ? ORDER BY reports_name";
$stmt = $conn->prepare($reports_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$reports_result = $stmt->get_result();
$reports = [];
while ($r = $reports_result->fetch_assoc()) {
    $reports[] = $r;
}
$stmt->close();

// Fetch questions
$questions_sql = "SELECT eng_question, hin_question, type FROM OBHS_questions WHERE user_id = ? ORDER BY id";
$stmt = $conn->prepare($questions_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$questions_result = $stmt->get_result();
$questions = [];
while ($q = $questions_result->fetch_assoc()) {
    $questions[] = $q;
}
$stmt->close();

// Fetch markings
$markings_sql = "SELECT id, category, value, created_at FROM OBHS_marking WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($markings_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$markings_result = $stmt->get_result();
$markings = [];
while ($m = $markings_result->fetch_assoc()) {
    $markings[] = $m;
}
$stmt->close();

// Calculate days remaining
$end_date = new DateTime($user['end_date']);
$today = new DateTime();
$days_left = $today->diff($end_date)->days;
$is_active = $end_date > $today;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Info - OBHS</title>
    <link rel="stylesheet" href="css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
        }
        .info-label { font-weight: 600; color: #6c757d; }
        .info-value { color: #212529; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
        <?php include "header.php" ?>
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">User Information</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="user-list.php">Users</a></li>
                                <li class="breadcrumb-item active">User Info</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">User Details</h3>
                                    <div class="card-tools no-print">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                                            <i class="bi bi-printer"></i> Print / Download PDF
                                        </button>
                                        <a href="update-user.php?id=<?php echo $user_id; ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="user-list.php" class="btn btn-secondary btn-sm">
                                            <i class="bi bi-arrow-left"></i> Back
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Basic Information -->
                                    <h5 class="border-bottom pb-2 mb-3">Basic Information</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <span class="info-label">User ID:</span>
                                                <span class="info-value">#<?php echo htmlspecialchars($user['user_id']); ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">Username:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">Organisation Name:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['organisation_name']); ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">Station:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['station_name'] ?? 'N/A'); ?></span>
                                            </div>
                                             <div class="mb-3">
                                                <span class="info-label">Login In Link:</span>
                                                <span class="info-value"><a href="https://obhs.beatleanalytics.in/" target="_blank">https://obhs.beatleanalytics.in/</a></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <span class="info-label">Email:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">Mobile:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['mobile'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">PNR Functionality:</span>
                                                <span class="info-value">
                                                    <?php 
                                                    $pnr = isset($user['PNR']) ? (int)$user['PNR'] : 0;
                                                    echo $pnr === 1 ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>'; 
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">Status:</span>
                                                <span class="info-value">
                                                    <?php 
                                                    $status = isset($user['status']) ? (int)$user['status'] : 0;
                                                    echo $status === 0 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; 
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="mb-3">
                                                <span class="info-label">Mobile:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['mobile'] ?? 'N/A'); ?></span>
                                            </div>
                                            
                                        </div>
                                    </div>

                                    <!-- Subscription Information -->
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Subscription Information</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <span class="info-label">Start Date:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['start_date']); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <span class="info-label">End Date:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($user['end_date']); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <span class="info-label">Days Remaining:</span>
                                                <span class="info-value">
                                                    <?php 
                                                    if ($is_active) {
                                                        echo '<span class="badge bg-primary">' . max(0, $days_left) . ' days</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger">Expired</span>';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assigned Reports -->
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Assigned Reports</h5>
                                    <?php if (!empty($reports)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Report Name</th>
                                                        
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i = 1; foreach ($reports as $report): ?>
                                                    <tr>
                                                        <td><?php echo $i++; ?></td>
                                                        <td><?php echo htmlspecialchars($report['reports_name']); ?></td>
                                                        
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No reports assigned to this user.</p>
                                    <?php endif; ?>

                                    <!-- Questions -->
                                    <?php if (!empty($questions)): ?>
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Questions</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Question (English)</th>
                                                    <th>Question (Hindi)</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; foreach ($questions as $q): ?>
                                                <tr>
                                                    <td><?php echo $i++; ?></td>
                                                    <td><?php echo htmlspecialchars($q['eng_question']); ?></td>
                                                    <td><?php echo htmlspecialchars($q['hin_question']); ?></td>
                                                    <td><?php echo htmlspecialchars($q['type']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Markings -->
                                    <?php if (!empty($markings)): ?>
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Markings</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Category</th>
                                                    <th>Value</th>
                                                    <th>Created At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; foreach ($markings as $m): ?>
                                                <tr>
                                                    <td><?php echo $i++; ?></td>
                                                    <td><?php echo htmlspecialchars($m['category']); ?></td>
                                                    <td><?php echo htmlspecialchars($m['value']); ?></td>
                                                    <td><?php echo htmlspecialchars($m['created_at']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Account Created -->
                                    <div class="mt-4 text-muted small">
                                        <span class="info-label">Account Created:</span>
                                        <span class="info-value"><?php echo isset($user['created_at']) ? htmlspecialchars($user['created_at']) : 'N/A'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include "footer.php" ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="js/adminlte.js"></script>
</body>
</html>
