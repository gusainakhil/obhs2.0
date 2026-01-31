<?php
require_once __DIR__ . '/connection.php';
//check session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Handle update user form submission and loading for edit
$form_error = '';
$editing_id = 0;
if (isset($_GET['id']))
    $editing_id = (int) $_GET['id'];
if (isset($_POST['id']))
    $editing_id = (int) $_POST['id'];

// load existing user, reports and questions when editing
$existing_reports = [];
$existing_questions = [];
$existing_user = null;
if ($editing_id > 0) {
    $u_sql = "SELECT * FROM `OBHS_users` WHERE `user_id` = ? LIMIT 1";
    if ($ustmt = mysqli_prepare($conn, $u_sql)) {
        mysqli_stmt_bind_param($ustmt, 'i', $editing_id);
        mysqli_stmt_execute($ustmt);
        $ures = mysqli_stmt_get_result($ustmt);
        if ($ures)
            $existing_user = mysqli_fetch_assoc($ures);
        mysqli_stmt_close($ustmt);
    }

    // load reports (include id and status)
    $r_sql = "SELECT `id`, `reports_name`, COALESCE(`status`, 1) AS status FROM `OBHS_reports` WHERE `user_id` = ?";
    if ($rstmt = mysqli_prepare($conn, $r_sql)) {
        mysqli_stmt_bind_param($rstmt, 'i', $editing_id);
        mysqli_stmt_execute($rstmt);
        $rres = mysqli_stmt_get_result($rstmt);
        if ($rres) {
            while ($rr = mysqli_fetch_assoc($rres)) {
                $existing_reports[] = $rr['reports_name'];
                // create map for quick lookup with id/status
                $existing_reports_map[$rr['reports_name']] = ['id' => (int)$rr['id'], 'status' => (int)$rr['status']];
            }
        }
        mysqli_stmt_close($rstmt);
    }

    // load questions
    $q_sql = "SELECT `id`, `eng_question`, `hin_question`, `type` FROM `OBHS_questions` WHERE `user_id` = ? ORDER BY id ASC";
    if ($qstmt = mysqli_prepare($conn, $q_sql)) {
        mysqli_stmt_bind_param($qstmt, 'i', $editing_id);
        mysqli_stmt_execute($qstmt);
        $qres = mysqli_stmt_get_result($qstmt);
        if ($qres) {
            while ($qr = mysqli_fetch_assoc($qres))
                $existing_questions[] = $qr;
        }
        mysqli_stmt_close($qstmt);
    }
}

// prepare prefill values (handle datetime formats and alternate column names)
$org_val = '';
$start_val = '';
$end_val = '';
if (!empty($existing_user)) {
    // organisation_name might be stored under different keys in some places
    $org_raw = $existing_user['organisation_name'] ?? $existing_user['organisation'] ?? '';
    $org_val = htmlspecialchars($org_raw);

    // start/end may be datetime; convert to YYYY-MM-DD for input[type=date]
    $raw_start = $existing_user['start_date'] ?? $existing_user['startdate'] ?? $existing_user['start'] ?? '';
    if ($raw_start !== '') {
        $start_val = htmlspecialchars(substr($raw_start, 0, 10));
    }
    $raw_end = $existing_user['end_date'] ?? $existing_user['enddate'] ?? $existing_user['end'] ?? '';
    if ($raw_end !== '') {
        $end_val = htmlspecialchars(substr($raw_end, 0, 10));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate inputs
    $station_id = isset($_POST['station_id']) ? (int) $_POST['station_id'] : 0;
    $username = trim($_POST['username'] ?? '');
    $organisation_name = trim($_POST['organisation_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pnr = isset($_POST['pnr']) ? (int) $_POST['pnr'] : 0;
    $pnr_skip = isset($_POST['pnr_skip']) ? (int) $_POST['pnr_skip'] : 0;
    $otp = isset($_POST['otp']) ? (int) $_POST['otp'] : 0;
    $otp_skip = isset($_POST['otp_skip']) ? (int) $_POST['otp_skip'] : 0;
    $photo = isset($_POST['photo']) ? (int) $_POST['photo'] : 0;
    $photo_skip = isset($_POST['photo_skip']) ? (int) $_POST['photo_skip'] : 0;
    $no_of_train = isset($_POST['no_of_train']) ? (int) $_POST['no_of_train'] : 0;
    $reports = $_POST['reports'] ?? [];
    $eng_questions = $_POST['eng_question'] ?? [];
    $hin_questions = $_POST['hin_question'] ?? [];
    $q_types = $_POST['q_type'] ?? [];
    $q_ids = $_POST['q_id'] ?? [];

    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $form_error = 'Invalid CSRF token.';
    }
    // basic required fields check (password optional for update)
    if ($form_error === '' && ($username === '' || $organisation_name === '' || $email === '')) {
        $form_error = 'Please fill required fields.';
    } else if ($form_error === '') {
        $type = 2; // organisation
        $user_id_to_update = $editing_id;

        // Build update SQL depending on whether password provided
            if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE `OBHS_users` SET `organisation_name` = ?, `username` = ?, `mobile` = ?, `email` = ?, `station_id` = ?, `password` = ?, `start_date` = ?, `end_date` = ?, `type` = ?, `PNR` = ?, `PNR_skip` = ?, `OTP` = ?, `OTP_skip` = ?, `photo` = ?, `photo_skip` = ?, `no_of_train` = ? WHERE `user_id` = ?";
            if ($ustmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($ustmt, 'ssssisssiiiiiiiii', $organisation_name, $username, $mobile, $email, $station_id, $hashed, $start_date, $end_date, $type, $pnr, $pnr_skip, $otp, $otp_skip, $photo, $photo_skip, $no_of_train, $user_id_to_update);
                $ok = mysqli_stmt_execute($ustmt);
                mysqli_stmt_close($ustmt);
            } else {
                $ok = false;
            }
        } else {
            $update_sql = "UPDATE `OBHS_users` SET `organisation_name` = ?, `username` = ?, `mobile` = ?, `email` = ?, `station_id` = ?, `start_date` = ?, `end_date` = ?, `type` = ?, `PNR` = ?, `PNR_skip` = ?, `OTP` = ?, `OTP_skip` = ?, `photo` = ?, `photo_skip` = ?, `no_of_train` = ? WHERE `user_id` = ?";
            if ($ustmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($ustmt, 'ssssissiiiiiiiii', $organisation_name, $username, $mobile, $email, $station_id, $start_date, $end_date, $type, $pnr, $pnr_skip, $otp, $otp_skip, $photo, $photo_skip, $no_of_train, $user_id_to_update);
                $ok = mysqli_stmt_execute($ustmt);
                mysqli_stmt_close($ustmt);
            } else {
                $ok = false;
            }
        }

        if ($ok) {
            // replace reports: delete existing then insert new
            $delr = mysqli_prepare($conn, "DELETE FROM `OBHS_reports` WHERE `user_id` = ?");
            if ($delr) {
                mysqli_stmt_bind_param($delr, 'i', $user_id_to_update);
                mysqli_stmt_execute($delr);
                mysqli_stmt_close($delr);
            }

            if (!empty($reports) && is_array($reports)) {
                $insert_report_sql = "INSERT INTO `OBHS_reports` (`user_id`, `reports_name`, `link`, `type`, `station_id`) VALUES (?, ?, ?, ?, ?)";
                foreach ($reports as $r) {
                    $r_name = trim($r);
                    if ($r_name === '')
                        continue;
                    if ($rstmt = mysqli_prepare($conn, $insert_report_sql)) {
                        if ($r_name === 'Round Wise Summary') {
                            $link = 'round_wise_summary.php';
                            $type='Feedback';
                        } elseif ($r_name === 'Photo Report') {
                            $link = 'photo_report_before_after.php';
                            $type='photo_report';
                        } elseif ($r_name === 'Photo Report Time Slot') {
                            $link = 'photo_report.php';
                            $type='photo_report';
                        } elseif ($r_name === 'Photo Report Coach Wise') {
                            $link = 'photo_report_coach_wise.php';
                            $type='photo_report';  
                        } elseif ($r_name === 'Attendance Report') {
                            $link = 'view-no-photo-attendance.php';
                            $type='Attendance';
                        } elseif ($r_name === 'Attendance Photo Report') {
                            $link = 'view-attendance.php';
                            $type='Attendance';
                        } elseif ($r_name === 'Time Interval Attendance') {
                            $link = 'attendance-report-row-wise.php';
                            $type='Attendance';
                        } elseif ($r_name === 'Daily Attendance Report') {
                            $link = 'daily-attendance.php';
                            $type='Attendance2';
                        } else {
                            $link = '';
                            $type='';
                        }
                        mysqli_stmt_bind_param($rstmt, 'isssi', $user_id_to_update, $r_name, $link, $type, $station_id);
                        mysqli_stmt_execute($rstmt);
                        mysqli_stmt_close($rstmt);
                    }
                }
            }

            // update existing and add new questions
            if (!empty($eng_questions) && is_array($eng_questions)) {
                for ($i = 0; $i < count($eng_questions); $i++) {
                    $eng = trim($eng_questions[$i] ?? '');
                    $hin = trim($hin_questions[$i] ?? '');
                    $qt = trim($q_types[$i] ?? '');
                    $q_id = isset($q_ids[$i]) ? (int)$q_ids[$i] : 0;
                    
                    if ($eng === '' && $hin === '')
                        continue;
                    
                    // If question ID exists, update the existing question
                    if ($q_id > 0) {
                        $update_q_sql = "UPDATE `OBHS_questions` SET `eng_question` = ?, `hin_question` = ?, `type` = ? WHERE `id` = ? AND `user_id` = ?";
                        if ($qstmt = mysqli_prepare($conn, $update_q_sql)) {
                            mysqli_stmt_bind_param($qstmt, 'sssii', $eng, $hin, $qt, $q_id, $user_id_to_update);
                            mysqli_stmt_execute($qstmt);
                            mysqli_stmt_close($qstmt);
                        }
                    } else {
                        // Otherwise, insert as new question
                        $insert_q_sql = "INSERT INTO `OBHS_questions` (`user_id`, `eng_question`, `hin_question`, `type`, `station_id`) VALUES (?, ?, ?, ?, ?)";
                        if ($qstmt = mysqli_prepare($conn, $insert_q_sql)) {
                            mysqli_stmt_bind_param($qstmt, 'isssi', $user_id_to_update, $eng, $hin, $qt , $station_id);
                            mysqli_stmt_execute($qstmt);
                            mysqli_stmt_close($qstmt);
                        }
                    }
                }
            }

            header('Location: user-list.php');
            exit;
        } else {
            $form_error = 'Failed to update user.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>OBHS</title>
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="OBHS | General Form Elements" />
    <meta name="author" content="ColorlibHQ" />
    <meta name="description"
        content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance." />
    <meta name="keywords"
        content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print"
        onload="this.media='all'" />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <!--begin::Header-->
        <?php include "header.php" ?>
        <!--end::Sidebar-->
        <!--begin::App Main-->
        <main class="app-main">
            <!--begin::App Content Header-->
            <div class="app-content-header">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0"><?php echo ($editing_id > 0) ? 'Update user' : 'Create user'; ?></h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?php echo ($editing_id > 0) ? 'Update user' : 'Create user'; ?></li>
                            </ol>
                        </div>
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content Header-->
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row g-4">

                        <div class="col-md-12">
                            <!--begin::Quick Example-->
                            <div class="card card-primary card-outline mb-4">
                                <!--begin::Card Header-->
                                <div class="card-header">
                                    <div class="card-title">Add user</div>
                                </div>
                                <!--end::Card Header-->
                                <!--begin::Form-->
                                <form method="post">
                                    <!--begin::Body-->
                                    <div class="card-body">
                                        <!-- hidden id and csrf -->
                                        <input type="hidden" name="id" value="<?php echo (int) $editing_id; ?>">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <div class="row g-3 mb-6">
                                            <div class="col-md-6">
                                                <label for="stationName" class="form-label">Station Name</label>
                                                <select class="form-select" id="stationName" name="station_id" required>
                                                    <option value="" selected disabled>Select Station</option>
                                                    <?php
                                                    // use plural table name for stations (assumption). If your table is different, tell me and I'll change it.
                                                    $query = "SELECT station_id, station_name FROM OBHS_station ORDER BY station_name ASC";
                                                    $result = mysqli_query($conn, $query);
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $sel = '';
                                                        if (!empty($existing_user) && isset($existing_user['station_id']) && $existing_user['station_id'] == $row['station_id']) {
                                                            $sel = ' selected';
                                                        }
                                                        echo '<option value="' . htmlspecialchars($row['station_id']) . '"' . $sel . '>' . htmlspecialchars($row['station_name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" name="username"
                                                    required oninput="this.value = this.value.replace(/\s/g, '')"
                                                    value="<?php echo htmlspecialchars($existing_user['username'] ?? ''); ?>" />
                                            </div>

                                        </div>
                                        <br>

                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label for="organisationName" class="form-label">Organisation
                                                    Name</label>
                                                <input type="text" class="form-control" id="organisationName"
                                                    name="organisation_name" required value="<?php echo $org_val; ?>" />
                                            </div>
                                            <div class="col-md-6">
                                                <label for="password" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="password"
                                                    name="password" <?php if ($editing_id > 0)
                                                        echo '';
                                                    else
                                                        echo 'required'; ?> />
                                                <?php if ($editing_id > 0): ?>
                                                    <div class="form-text">Leave blank to keep the current password.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>


                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label for="startDate" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="startDate" name="start_date"
                                                    required value="<?php echo $start_val; ?>" />
                                            </div>
                                            <div class="col-md-6">
                                                <label for="endDate" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="endDate" name="end_date"
                                                    required value="<?php echo $end_val; ?>" />
                                            </div>
                                        </div>

                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label for="phoneNumber" class="form-label">Phone number</label>
                                                <input type="text" class="form-control" id="phoneNumber" name="mobile"
                                                    required
                                                    value="<?php echo htmlspecialchars($existing_user['mobile'] ?? ''); ?>" />
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    required
                                                    value="<?php echo htmlspecialchars($existing_user['email'] ?? ''); ?>" />
                                            </div>
                                        </div>

                                                                                <div class="row g-3 mb-4">
                                                                                    <div class="col-md-6">
                                                                                        <label for="no_of_train" class="form-label">No. of Train Maximum Add</label>
                                                                                        <input type="number" class="form-control" id="no_of_train" name="no_of_train" required value="<?php echo htmlspecialchars($existing_user['no_of_train'] ?? ''); ?>" />
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <p class="text-danger small mt-2">NOTE : After Submitting the form, please update Marks calculation if you select Round wise summary</p>
                                                                                        <p class="text-danger small mt-2">NOTE : If PNR ,OTP , Photo Functionality is ON, User will be able to see related data.</p>
                                                                                    </div>
                                                                                </div>


                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Type of Reports</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Round Wise Summary" id="roundwisesummary" <?php if (isset($existing_reports_map['Round Wise Summary'])) echo 'checked data-locked="1"'; elseif(in_array('Round Wise Summary', $existing_reports)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="roundwisesummary">
                                                        Round wise Summary Report
                                                    </label>
                                                    <?php if (!empty($existing_reports_map['Round Wise Summary'])): ?>
                                                        <?php $rep = $existing_reports_map['Round Wise Summary']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Photo Report" id="photoreportafterbefore" <?php if (isset($existing_reports_map['Photo Report'])) echo 'checked data-locked="1"'; elseif(in_array('Photo Report', $existing_reports)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photoreportafterbefore">
                                                        Photo Report After Before
                                                    </label>
                                                    <?php if (!empty($existing_reports_map['Photo Report'])): ?>
                                                        <?php $rep = $existing_reports_map['Photo Report']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Photo Report Time Slot" id="photoReportTimeSlot" <?php if (isset($existing_reports_map['Photo Report Time Slot'])) echo 'checked data-locked="1"'; elseif(in_array('Photo Report Time Slot', $existing_reports)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photoReportTimeSlot">
                                                        Photo Report Time Slot
                                                    </label>
                                                    <?php if (!empty($existing_reports_map['Photo Report Time Slot'])): ?>
                                                        <?php $rep = $existing_reports_map['Photo Report Time Slot']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Photo Report Coach Wise" id="photoReportCoachWise" <?php if (isset($existing_reports_map['Photo Report Coach Wise'])) echo 'checked data-locked="1"'; elseif(in_array('Photo Report Coach Wise', $existing_reports)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photoReportCoachWise">
                                                        Photo Report Coach Wise
                                                    </label>
                                                    <?php if (!empty($existing_reports_map['Photo Report Coach Wise'])): ?>
                                                        <?php $rep = $existing_reports_map['Photo Report Coach Wise']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="reports[]"
                                                           value="Attendance Photo Report" id="attendancePhotoReport" <?php if (isset($existing_reports_map['Attendance Photo Report'])) echo 'checked data-locked="1"'; elseif(in_array('Attendance Photo Report', $existing_reports)) echo 'checked'; ?>>
                                                       <label class="form-check-label" for="attendancePhotoReport">
                                                           Attendance Photo Report
                                                       </label>
                                                    <?php if (!empty($existing_reports_map['Attendance Photo Report'])): ?>
                                                        <?php $rep = $existing_reports_map['Attendance Photo Report']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                   </div>
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="reports[]"
                                                           value="Time Interval Attendance" id="timeIntervalAttendance" <?php if (isset($existing_reports_map['Time Interval Attendance'])) echo 'checked data-locked="1"'; elseif(in_array('Time Interval Attendance', $existing_reports)) echo 'checked'; ?>>
                                                       <label class="form-check-label" for="timeIntervalAttendance">
                                                           Time Interval Attendance
                                                       </label>
                                                    <?php if (!empty($existing_reports_map['Time Interval Attendance'])): ?>
                                                        <?php $rep = $existing_reports_map['Time Interval Attendance']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                   </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Attendance Report" id="attendancereport" <?php if (isset($existing_reports_map['Attendance Report'])) echo 'checked data-locked="1"'; elseif(in_array('Attendance Report', $existing_reports)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="attendancereport">
                                                        Attendance Report
                                                    </label>
                                                    <?php if (!empty($existing_reports_map['Attendance Report'])): ?>
                                                        <?php $rep = $existing_reports_map['Attendance Report']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                  <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Daily Attendance Report" id="dailyAttendanceReport" <?php if (isset($existing_reports_map['Daily Attendance Report'])) echo 'checked data-locked="1"'; elseif(in_array('Daily Attendance Report', $existing_reports)) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="dailyAttendanceReport">
                                                        Daily Attendance Report
                                                    </label>
                                                    <?php if (!empty($existing_reports_map['Daily Attendance Report'])): ?>
                                                        <?php $rep = $existing_reports_map['Daily Attendance Report']; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-report" data-id="<?php echo $rep['id']; ?>" data-status="<?php echo $rep['status']; ?>">
                                                            <?php echo $rep['status'] == 1 ? 'Hide' : 'Unhide'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6" >
                                                <div class="row g-3 mb-4">
                                            <div class="col-md-2">
                                                <label class="form-label">PNR Functionality</label>
                                                <input type="hidden" name="pnr" value="0" />
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="pnr" value="1"
                                                        id="pnr" <?php if (!empty($existing_user['PNR']) && $existing_user['PNR'] == 1) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="pnr">
                                                        On / Off
                                                    </label>
                                                </div><br><br>
                                                 <label class="form-label">Skip PNR Functionality</label>
                                                <input type="hidden" name="pnr_skip" value="0" />
                                                <div class="form-check">

                                                    <input class="form-check-input" type="checkbox" name="pnr_skip" value="1"
                                                        id="pnr_skip" <?php if (!empty($existing_user['pnr_skip']) && $existing_user['pnr_skip'] == 1) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="pnr_skip">
                                                        On / Off
                                                    </label>
                                                </div>
                                                
                                            </div>
                                             <div class="col-md-2">
                                                <label class="form-label">Mobile Otp Functionality</label>
                                                <input type="hidden" name="otp" value="0" />
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="otp" value="1"
                                                        id="otp" <?php if (!empty($existing_user['otp']) && $existing_user['otp'] == 1) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="otp">
                                                        On / Off
                                                    </label>
                                                </div><br><br>
                                                 <label class="form-label">Skip OTP Functionality</label>
                                                <input type="hidden" name="otp_skip" value="0" />
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="otp_skip" value="1"
                                                        id="otp_skip" <?php if (!empty($existing_user['otp_skip']) && $existing_user['otp_skip'] == 1) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="otp_skip">
                                                        On / Off
                                                    </label>
                                                </div>
                                                
                                            </div>
                                             <div class="col-md-2">
                                                <label class="form-label">Photo Functionality</label>
                                                <input type="hidden" name="photo" value="0" />
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="photo" value="1"
                                                        id="photo" <?php if (!empty($existing_user['photo']) && $existing_user['photo'] == 1) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photo">
                                                        On / Off
                                                    </label>
                                                </div><br><br>
                                                 <label class="form-label">Skip Photo Functionality</label>
                                                <input type="hidden" name="photo_skip" value="0" />
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="photo_skip" value="1"
                                                        id="photo_skip" <?php if (!empty($existing_user['photo_skip']) && $existing_user['photo_skip'] == 1) echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photo_skip">
                                                        On / Off
                                                    </label>
                                                </div>
                                                
                                            </div>
                                        </div>


                                            </div>
                                        </div>
                                        <!-- update pnr functionality -->
                                        
                                        <!-- Questions Section -->
                                        <div id="questionsSectionRoundWise"
                                            style="display: <?php echo in_array('Round Wise Summary', $existing_reports) ? 'block' : 'none'; ?>;">
                                            <h5 class="mb-3">Questions for Round Wise Summary</h5>
                                            <div id="questionsContainer">
                                                <?php if (!empty($existing_questions)): ?>
                                                    <h6 class="mb-3" style="color: #666;"><i class="bi bi-pencil-square"></i> Existing Questions (Editable)</h6>
                                                    <?php foreach ($existing_questions as $q): ?>
                                                        <div class="question-item border rounded p-3 mb-3" style="background-color: #f0f7ff;">
                                                            <div class="row g-3">
                                                                <div class="col-md-5">
                                                                    <label class="form-label">Question (English)</label>
                                                                    <input type="text" name="eng_question[]"
                                                                        class="form-control"
                                                                        placeholder="Enter question in English"
                                                                        value="<?php echo htmlspecialchars($q['eng_question']); ?>" />
                                                                    <input type="hidden" name="q_id[]" value="<?php echo (int)$q['id']; ?>" />
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <label class="form-label">Question (Hindi)</label>
                                                                    <input type="text" name="hin_question[]"
                                                                        class="form-control"
                                                                        placeholder="प्रश्न हिंदी में दर्ज करें"
                                                                        value="<?php echo htmlspecialchars($q['hin_question']); ?>" />
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Type</label>
                                                                    <select class="form-select" name="q_type[]">
                                                                        <option value="">Select Type</option>
                                                                        <option value="AC" <?php if ($q['type'] === 'AC')
                                                                            echo 'selected'; ?>>AC</option>
                                                                        <option value="NON-AC" <?php if ($q['type'] === 'NON-AC')
                                                                            echo 'selected'; ?>>NON-AC</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <hr class="my-4" />
                                                    <h6 class="mb-3"><i class="bi bi-plus-circle-fill"></i> Add New Questions</h6>
                                                <?php endif; ?>
                                                <div id="newQuestionsContainer">
                                                    <div class="question-item border rounded p-3 mb-3">
                                                        <div class="row g-3">
                                                            <div class="col-md-5">
                                                                <label class="form-label">Question (English)</label>
                                                                <input type="text" name="eng_question[]"
                                                                    class="form-control"
                                                                    placeholder="Enter question in English" />
                                                                <input type="hidden" name="q_id[]" value="0" />
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label">Question (Hindi)</label>
                                                                <input type="text" name="hin_question[]"
                                                                    class="form-control"
                                                                    placeholder="प्रश्न हिंदी में दर्ज करें" />
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label">Type</label>
                                                                <select class="form-select" name="q_type[]">
                                                                    <option value="">Select Type</option>
                                                                    <option value="AC">AC</option>
                                                                    <option value="NON-AC">NON-AC</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm mb-4" id="addQuestion">
                                                <i class="bi bi-plus-circle"></i> Add Question
                                            </button>
                                        </div>
                                    </div>
                                    <!--end::Body-->
                                    <!--begin::Footer-->
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <button type="reset" class="btn btn-secondary ms-2">Reset</button>
                                    </div>
                                    <!--end::Footer-->
                                </form>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function () {
                                            const addQuestionBtn = document.getElementById('addQuestion');
                                            const questionsSectionRoundWise = document.getElementById('questionsSectionRoundWise');
                                            const roundwisesummary = document.getElementById('roundwisesummary');

                                            // Show/hide questions section based on checkbox
                                            roundwisesummary.addEventListener('change', function () {
                                                if (this.checked) {
                                                    questionsSectionRoundWise.style.display = 'block';
                                                    const newQuestionsContainer = document.getElementById('newQuestionsContainer');
                                                    newQuestionsContainer.querySelectorAll('input, select').forEach(el => el.required = true);
                                                } else {
                                                    questionsSectionRoundWise.style.display = 'none';
                                                    const newQuestionsContainer = document.getElementById('newQuestionsContainer');
                                                    newQuestionsContainer.querySelectorAll('input, select').forEach(el => el.required = false);
                                                }
                                            });

                                            addQuestionBtn.addEventListener('click', function () {
                                                const newQuestionsContainer = document.getElementById('newQuestionsContainer');
                                                const questionItem = document.createElement('div');
                                                questionItem.className = 'question-item border rounded p-3 mb-3';
                                                questionItem.innerHTML = `
                            <div class="row g-3">
                              <div class="col-md-5">
                                <label class="form-label">Question (English)</label>
                                <input type="text" name="eng_question[]" class="form-control" placeholder="Enter question in English" />
                                <input type="hidden" name="q_id[]" value="0" />
                              </div>
                              <div class="col-md-5">
                                <label class="form-label">Question (Hindi)</label>
                                <input type="text" name="hin_question[]" class="form-control" placeholder="प्रश्न हिंदी में दर्ज करें" />
                              </div>
                              <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="q_type[]">
                                  <option value="" selected disabled>Select Type</option>
                                  <option value="AC">AC</option>
                                  <option value="NON-AC">Non-AC</option>
                                </select>
                              </div>
                            </div>
                          `;
                                                newQuestionsContainer.appendChild(questionItem);
                                            });
                                            // toggle-report button handler
                                            document.querySelectorAll('.toggle-report').forEach(btn => {
                                                btn.addEventListener('click', function () {
                                                    const id = this.dataset.id;
                                                    const self = this;
                                                    const formData = new FormData();
                                                    formData.append('id', id);
                                                    fetch('toggle-report.php', { method: 'POST', body: formData })
                                                        .then(r => r.json())
                                                        .then(js => {
                                                            if (js.status) {
                                                                const newStatus = js.status_value;
                                                                self.dataset.status = newStatus;
                                                                self.textContent = newStatus == 1 ? 'Hide' : 'Unhide';
                                                            } else {
                                                                alert('Toggle failed: ' + js.message);
                                                            }
                                                        }).catch(e => alert('Request failed'));
                                                });
                                            });
                                            // prevent unchecking of already assigned reports
                                            document.querySelectorAll('input[type="checkbox"][data-locked="1"]').forEach(cb => {
                                                cb.addEventListener('change', function () {
                                                    if (!this.checked) {
                                                        this.checked = true;
                                                        alert('This report is already assigned. Use Hide/Unhide to change visibility.');
                                                    }
                                                });
                                            });
                                        });
                                    </script>
                                <!--end::Form-->
                            </div>
                            <!--end::Form-->
                        </div>


                    </div>

                </div>
                <!--end::Row-->
            </div>
            <!--end::Container-->


        </main>
        <!--end::App Main-->
        <!--begin::Footer-->
       <? include "footer.php" ?>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
        crossorigin="anonymous"></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        crossorigin="anonymous"></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
        crossorigin="anonymous"></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>
    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
</body>
<!--end::Body-->

</html>