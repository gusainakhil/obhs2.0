<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
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
$markings_sql = "SELECT id, category, value, created_at FROM OBHS_marking WHERE user_id = ? ORDER BY value DESC";
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
    <title>User Info - <?php echo $user['station_name']; ?></title>
    <link rel="stylesheet" href="css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <style>
        @media print {

            /* Hide navigation and non-essential elements */
            .no-print,
            .app-header,
            .app-sidebar,
            .breadcrumb,
            .action-buttons,
            .card-tools {
                display: none !important;
            }

            /* Show print header */
            .print-header {
                display: block !important;
                padding: 15px 0 10px 0 !important;
                margin: 0 0 15px 0 !important;
                border-bottom: 2px solid #667eea !important;
                page-break-after: avoid !important;
            }

            .print-header h1 {
                color: #667eea !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-header p {
                margin: 5px 0 0 0 !important;
            }

            /* Reset body and layout for print */
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .app-wrapper,
            .app-main,
            .app-content,
            .container-fluid {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            .app-content-header {
                display: none !important;
            }

            /* Card styling for print - KEEP GRADIENTS */
            .info-card {
                page-break-inside: avoid;
                border: none !important;
                border-radius: 12px !important;
                box-shadow: none !important;
                margin-bottom: 15px !important;
                background: white !important;
                overflow: visible !important;
            }

            .info-card .card-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                padding: 12px 20px !important;
                border-radius: 12px 12px 0 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .info-card .card-header h3 {
                font-size: 18px !important;
                margin: 0 !important;
            }

            .info-card .card-body {
                padding: 15px 20px !important;
                background: white !important;
            }

            /* Section titles - KEEP COLORS */
            .section-title {
                color: #1a202c !important;
                font-size: 13px !important;
                font-weight: bold !important;
                border-bottom: 2px solid #667eea !important;
                padding-bottom: 6px !important;
                margin-bottom: 10px !important;
                margin-top: 15px !important;
                page-break-after: avoid !important;
            }

            .section-title i {
                color: #667eea !important;
            }

            .section-title::after {
                background: #764ba2 !important;
                width: 60px !important;
                height: 2px !important;
            }

            /* Info rows - KEEP GRADIENT */
            .info-row {
                background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
                border: 1px solid #e2e8f0 !important;
                border-left: 3px solid #667eea !important;
                padding: 8px 12px !important;
                margin-bottom: 8px !important;
                page-break-inside: avoid !important;
                border-radius: 6px !important;
            }

            .info-row:hover {
                transform: none !important;
                box-shadow: none !important;
            }

            .info-label {
                color: #64748b !important;
                font-size: 9px !important;
                font-weight: bold !important;
            }

            .info-label i {
                color: #667eea !important;
            }

            .info-value {
                color: #1e293b !important;
                font-size: 11px !important;
                font-weight: 600 !important;
            }

            /* Password display - KEEP GRADIENT */
            .password-display {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
                color: #10b981 !important;
                border: 1px solid #475569 !important;
                padding: 6px 10px !important;
                font-size: 8px !important;
                word-break: break-all !important;
                border-radius: 6px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Tables - KEEP GRADIENT */
            .modern-table {
                border: none !important;
                border-collapse: collapse !important;
                width: 100% !important;
                page-break-inside: auto !important;
                border-radius: 8px !important;
                overflow: hidden !important;
            }

            .modern-table thead {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .modern-table thead th {
                border: none !important;
                padding: 8px 12px !important;
                font-size: 10px !important;
                font-weight: bold !important;
            }

            .modern-table tbody tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }

            .modern-table tbody tr:hover {
                background: white !important;
                transform: none !important;
            }

            .modern-table tbody td {
                border: none !important;
                padding: 8px 12px !important;
                font-size: 10px !important;
            }

            /* Badges - KEEP GRADIENTS */
            .badge {
                padding: 4px 10px !important;
                font-size: 9px !important;
                border-radius: 6px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-custom-active {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                color: white !important;
            }

            .badge-custom-inactive {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                color: white !important;
            }

            .badge-custom-info {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
                color: white !important;
            }

            .badge-custom-warning {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                color: white !important;
            }

            .table-badge {
                font-size: 8px !important;
                padding: 4px 8px !important;
            }

            /* Links */
            .link-display {
                color: #667eea !important;
                text-decoration: none !important;
            }

            .link-display::after {
                content: '' !important;
            }

            /* Empty state */
            .empty-state {
                padding: 20px !important;
                border: 1px dashed #999 !important;
            }

            .empty-state i {
                color: #999 !important;
                font-size: 2.5rem !important;
            }

            .empty-state p {
                font-size: 11px !important;
            }

            /* Account footer */
            .account-footer {
                border-top: 2px solid #e2e8f0 !important;
                padding: 10px !important;
                margin-top: 15px !important;
                background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
                border-radius: 8px !important;
                page-break-inside: avoid !important;
            }

            .account-footer small {
                color: #64748b !important;
                font-size: 10px !important;
            }

            /* Force backgrounds to print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Reduce row spacing */
            .row {
                margin-bottom: 0 !important;
            }

            /* Compact spacing */
            .col-md-6,
            .col-md-4 {
                padding: 0 8px !important;
            }
        }

        /* Print header - hidden by default */
        .print-header {
            display: none;
            text-align: center;
            padding: 15px 0 10px 0;
            margin: 0 0 15px 0;
            border-bottom: 2px solid #667eea;
            page-break-after: avoid;
        }

        .print-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .print-header p {
            margin: 5px 0 0 0;
            color: #64748b;
            font-size: 14px;
        }

        body {
            /* background: #f0f2f5; */
        }

        .info-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .info-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .info-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 24px 28px;
            position: relative;
            overflow: hidden;
        }

        .info-card .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .info-card .card-header h3 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .info-card .card-body {
            padding: 32px;
            background: white;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 24px;
            padding-bottom: 14px;
            border-bottom: 3px solid #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 80px;
            height: 3px;
            background: #764ba2;
        }

        .section-title i {
            color: #667eea;
            font-size: 1.3rem;
        }

        .info-row {
            margin-bottom: 20px;
            padding: 18px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 10px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
            position: relative;
        }

        .info-row:hover {
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
            transform: translateX(6px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .info-label {
            font-weight: 700;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .info-label i {
            color: #667eea;
            font-size: 1rem;
        }

        .info-value {
            color: #1e293b;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .password-display {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #10b981;
            padding: 12px 16px;
            border-radius: 8px;
            display: inline-block;
            word-break: break-all;
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.3), 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #475569;
            max-width: 100%;
        }

        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modern-table thead th {
            border: none;
            padding: 16px 18px;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            transform: scale(1.01);
        }

        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        .modern-table tbody td {
            padding: 16px 18px;
            vertical-align: middle;
            border: none;
        }

        .badge {
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-custom-active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .badge-custom-inactive {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .badge-custom-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .badge-custom-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .action-buttons .btn {
            margin-left: 8px;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            z-index: 1;
        }

        .action-buttons .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .action-buttons .btn-light {
            background: white;
            color: #667eea;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .action-buttons .btn-light:hover {
            background: #f8fafc;
            border-color: white;
        }

        .link-display {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .link-display:hover {
            color: #764ba2;
            text-decoration: none;
            transform: translateX(4px);
        }

        .link-display::after {
            content: '\F1C5';
            font-family: 'bootstrap-icons';
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.4;
            display: block;
        }

        .empty-state p {
            font-size: 1.1rem;
            font-style: italic;
            margin: 0;
        }

        .account-footer {
            margin-top: 32px;
            padding-top: 24px;
            text-align: center;
            border-top: 2px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            padding: 20px;
            border-radius: 10px;
        }

        .account-footer small {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .table-badge {
            font-size: 0.75rem;
            padding: 6px 12px;
        }
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
                                <li class="breadcrumb-item"><a href="user-list.php">Users </a></li>
                                <li class="breadcrumb-item active">User Info</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <!-- Print Header - Only visible when printing -->


                    <div class="row">
                        <div class="col-12">
                            <div class="card info-card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="bi bi-person-circle"></i> User Details Beatle
                                        Analytics <img src="assets/img/logo-white.png" alt="Beatle Analytics Logo"
                                            height="35px"> </h3>
                                    <div class="card-tools no-print action-buttons">
                                        <button type="button" class="btn btn-light btn-sm" onclick="printInNewTab()">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <a href="update-user.php?id=<?php echo $user_id; ?>"
                                            class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="user-list.php" class="btn btn-secondary btn-sm">
                                            <i class="bi bi-arrow-left"></i> Back
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Basic Information -->
                                    <h5 class="section-title"><i class="bi bi-info-circle-fill"></i> Basic Information
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <!-- <div class="info-row">
                                                <span class="info-label"><i class="bi bi-hash"></i> User ID</span>
                                                <span class="info-value">#<?php echo htmlspecialchars($user['user_id']); ?></span>
                                            </div> -->
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-building"></i> Organisation
                                                    Name</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['organisation_name']); ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-geo-alt-fill"></i>
                                                    Station</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['station_name'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-person-fill"></i>
                                                    Username</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-key-fill"></i> Password</span>
                                                <span class="info-value">
                                                    <div class="password-display">
                                                        <?php htmlspecialchars($user['password']); ?>123456</div>
                                                </span>
                                            </div>

                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-box-arrow-up-right"></i> How to
                                                    Login </span>
                                                <span class="info-value"><a href="http://obhs.beatleanalytics.in/"
                                                        target="_blank"
                                                        class="link-display">http://obhs.beatleanalytics.in// -> Login
                                                        -> BeatleAnalytics OBHS</a></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-envelope-fill"></i>
                                                    Email</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-phone-fill"></i> Mobile</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['mobile'] ?? 'N/A'); ?></span>
                                            </div>
                                                <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> No 0f Train maxmimum create </span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['no_of_train'] ?? 'N/A'); ?></span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">


                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> PNR
                                                    Functionality</span>
                                                <span class="info-value">
                                                    <?php
                                                    $pnr = isset($user['PNR']) ? (int) $user['PNR'] : 0;
                                                    echo $pnr === 1 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> ON</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill"></i> OFF</span>';
                                                    ?>
                                                </span>
                                            </div>
                                                   <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> PNR Skip
                                                    Functionality</span>
                                                <span class="info-value">
                                                    <?php
                                                    $pnr_skip = isset($user['pnr_skip']) ? (int) $user['pnr_skip'] : 0;
                                                    echo $pnr_skip === 1 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> ON</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill"></i> OFF</span>';
                                                    ?>
                                                </span>
                                            </div>

                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> OTP
                                                    Functionality</span>
                                                <span class="info-value">
                                                    <?php
                                                    $otp = isset($user['otp']) ? (int) $user['otp'] : 0;
                                                    echo $otp === 1 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> ON</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill"></i> OFF</span>';
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> OTP Skip
                                                    Functionality</span>
                                                <span class="info-value">
                                                    <?php
                                                    $otp_skip = isset($user['otp_skip']) ? (int) $user['otp_skip'] : 0;
                                                    echo $otp_skip === 1 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> ON</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill"></i> OFF</span>';
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> Photo 
                                                    Functionality</span>
                                                <span class="info-value">
                                                    <?php
                                                    $photo = isset($user['photo']) ? (int) $user['photo'] : 0;
                                                    echo $photo === 1 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> ON</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill"></i> OFF</span>';
                                                    ?>
                                                </span>
                                            </div>
                                             <div class="info-row">
                                                <span class="info-label"><i class="bi bi-ticket-perforated"></i> Photo Skip
                                                    Functionality</span>
                                                <span class="info-value">
                                                    <?php
                                                    $photo_skip = isset($user['photo_skip']) ? (int) $user['photo_skip'] : 0;
                                                    echo $photo_skip === 1 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> ON</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle-fill"></i> OFF</span>';
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-shield-check"></i>
                                                    Status</span>
                                                <span class="info-value">
                                                    <?php
                                                    $status = isset($user['status']) ? (int) $user['status'] : 0;
                                                    echo $status === 0 ? '<span class="badge badge-custom-active"><i class="bi bi-check-circle-fill"></i> Active</span>' : '<span class="badge badge-custom-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>';
                                                    ?>
                                                </span>
                                            </div>


                                        </div>
                                    </div>

                                    <!-- Subscription Information -->
                                    <h5 class="section-title mt-4"><i class="bi bi-calendar-check-fill"></i>
                                        Subscription Information</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-calendar-plus"></i> Start
                                                    Date</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['start_date']); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-calendar-x"></i> End
                                                    Date</span>
                                                <span
                                                    class="info-value"><?php echo htmlspecialchars($user['end_date']); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-hourglass-split"></i> Days
                                                    Remaining</span>
                                                <span class="info-value">
                                                    <?php
                                                    if ($is_active) {
                                                        echo '<span class="badge badge-custom-info"><i class="bi bi-clock-fill"></i> ' . max(0, $days_left) . ' days</span>';
                                                    } else {
                                                        echo '<span class="badge badge-custom-inactive"><i class="bi bi-exclamation-triangle-fill"></i> Expired</span>';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assigned Reports -->
                                    <h5 class="section-title mt-4"><i class="bi bi-file-earmark-text-fill"></i> Assigned
                                        Reports</h5>
                                    <?php if (!empty($reports)): ?>
                                        <div class="table-responsive">
                                            <table class="table modern-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 80px;">#</th>
                                                        <th>Report Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i = 1;
                                                    foreach ($reports as $report): ?>
                                                        <tr>
                                                            <td><span
                                                                    class="badge badge-custom-info table-badge"><?php echo $i++; ?></span>
                                                            </td>
                                                            <td><i
                                                                    class="bi bi-file-text me-2"></i><?php echo htmlspecialchars($report['reports_name']); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>No reports assigned to this user.</p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Questions -->
                                    <?php if (!empty($questions)): ?>
                                        <h5 class="section-title mt-4"><i class="bi bi-question-circle-fill"></i> Questions
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table modern-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 80px;">#</th>
                                                        <th>Question (English)</th>
                                                        <th>Question (Hindi)</th>
                                                        <th style="width: 140px;">Type</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i = 1;
                                                    foreach ($questions as $q): ?>
                                                        <tr>
                                                            <td><span
                                                                    class="badge badge-custom-info table-badge"><?php echo $i++; ?></span>
                                                            </td>
                                                            <td><i
                                                                    class="bi bi-chat-left-text me-2"></i><?php echo htmlspecialchars($q['eng_question']); ?>
                                                            </td>
                                                            <td><i
                                                                    class="bi bi-translate me-2"></i><?php echo htmlspecialchars($q['hin_question']); ?>
                                                            </td>
                                                            <td><span
                                                                    class="badge badge-custom-warning"><?php echo htmlspecialchars($q['type']); ?></span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Markings -->
                                    <?php if (!empty($markings)): ?>
                                        <h5 class="section-title mt-4"><i class="bi bi-bookmark-star-fill"></i> Markings
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table modern-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 80px;">#</th>
                                                        <th>Category</th>
                                                        <th>Value</th>
                                                        <!-- <th style="width: 200px;">Created At</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i = 1;
                                                    foreach ($markings as $m): ?>
                                                        <tr>
                                                            <td><span
                                                                    class="badge badge-custom-info table-badge"><?php echo $i++; ?></span>
                                                            </td>
                                                            <td><i
                                                                    class="bi bi-tag-fill me-2"></i><?php echo htmlspecialchars($m['category']); ?>
                                                            </td>
                                                            <td><strong><?php echo htmlspecialchars($m['value']); ?></strong>
                                                            </td>
                                                            <!-- <td><i class="bi bi-clock me-2"></i><?php echo htmlspecialchars($m['created_at']); ?></td> -->
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Account Created -->
                                    <!-- <div class="account-footer">
                                        <small>
                                            <i class="bi bi-calendar-event"></i>
                                            <strong>Account Created:</strong>
                                            <span><?php echo isset($user['created_at']) ? htmlspecialchars($user['created_at']) : 'N/A'; ?></span>
                                        </small>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include "footer.php" ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
        crossorigin="anonymous"></script>
    <script src="js/adminlte.js"></script>
    <script>
        function printInNewTab() {
            // Get the current page URL
            const currentUrl = window.location.href;

            // Open in new tab
            const printWindow = window.open(currentUrl, '_blank');

            // Wait for the new tab to load, then trigger print
            if (printWindow) {
                printWindow.onload = function () {
                    printWindow.print();
                };
            }
        }
    </script>
</body>

</html>