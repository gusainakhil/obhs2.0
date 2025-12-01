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

    // load reports
    $r_sql = "SELECT `reports_name` FROM `OBHS_reports` WHERE `user_id` = ?";
    if ($rstmt = mysqli_prepare($conn, $r_sql)) {
        mysqli_stmt_bind_param($rstmt, 'i', $editing_id);
        mysqli_stmt_execute($rstmt);
        $rres = mysqli_stmt_get_result($rstmt);
        if ($rres) {
            while ($rr = mysqli_fetch_assoc($rres))
                $existing_reports[] = $rr['reports_name'];
        }
        mysqli_stmt_close($rstmt);
    }

    // load questions
    $q_sql = "SELECT `eng_question`, `hin_question`, `type` FROM `OBHS_questions` WHERE `user_id` = ? ORDER BY id ASC";
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
    $reports = $_POST['reports'] ?? [];
    $eng_questions = $_POST['eng_question'] ?? [];
    $hin_questions = $_POST['hin_question'] ?? [];
    $q_types = $_POST['q_type'] ?? [];

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
            $update_sql = "UPDATE `OBHS_users` SET `organisation_name` = ?, `username` = ?, `mobile` = ?, `email` = ?, `station_id` = ?, `password` = ?, `start_date` = ?, `end_date` = ?, `type` = ? WHERE `user_id` = ?";
            if ($ustmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($ustmt, 'ssssisssii', $organisation_name, $username, $mobile, $email, $station_id, $hashed, $start_date, $end_date, $type, $user_id_to_update);
                $ok = mysqli_stmt_execute($ustmt);
                mysqli_stmt_close($ustmt);
            } else {
                $ok = false;
            }
        } else {
            $update_sql = "UPDATE `OBHS_users` SET `organisation_name` = ?, `username` = ?, `mobile` = ?, `email` = ?, `station_id` = ?, `start_date` = ?, `end_date` = ?, `type` = ? WHERE `user_id` = ?";
            if ($ustmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($ustmt, 'ssssissii', $organisation_name, $username, $mobile, $email, $station_id, $start_date, $end_date, $type, $user_id_to_update);
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
                $insert_report_sql = "INSERT INTO `OBHS_reports` (`user_id`, `reports_name`, `link`) VALUES (?, ?, ?)";
                foreach ($reports as $r) {
                    $r_name = trim($r);
                    if ($r_name === '')
                        continue;
                    if ($rstmt = mysqli_prepare($conn, $insert_report_sql)) {
                        if ($r_name === 'Round Wise Summary') {
                            $link = 'round_wise_summary.php';
                        } elseif ($r_name === 'Photo Report') {
                            $link = 'photo_report_before_after.php';
                        } elseif ($r_name === 'Photo Report Time Slot') {
                            $link = 'photo_report.php';
                        } elseif ($r_name === 'Attendance Report') {
                            $link = 'view-no-photo-attendance.php';
                        } elseif ($r_name === 'Attendance Photo Report') {
                            $link = 'view-attendance.php';
                        } elseif ($r_name === 'Time Interval Attendance') {
                            $link = 'attendance-report-row-wise.php';
                        } else {
                            $link = '';
                        }
                        mysqli_stmt_bind_param($rstmt, 'iss', $user_id_to_update, $r_name, $link);
                        mysqli_stmt_execute($rstmt);
                        mysqli_stmt_close($rstmt);
                    }
                }
            }

            // replace questions
            $delq = mysqli_prepare($conn, "DELETE FROM `OBHS_questions` WHERE `user_id` = ?");
            if ($delq) {
                mysqli_stmt_bind_param($delq, 'i', $user_id_to_update);
                mysqli_stmt_execute($delq);
                mysqli_stmt_close($delq);
            }

            if (!empty($eng_questions) && is_array($eng_questions)) {
                $insert_q_sql = "INSERT INTO `OBHS_questions` (`user_id`, `eng_question`, `hin_question`, `type`) VALUES (?, ?, ?, ?)";
                for ($i = 0; $i < count($eng_questions); $i++) {
                    $eng = trim($eng_questions[$i] ?? '');
                    $hin = trim($hin_questions[$i] ?? '');
                    $qt = trim($q_types[$i] ?? '');
                    if ($eng === '' && $hin === '')
                        continue;
                    if ($qstmt = mysqli_prepare($conn, $insert_q_sql)) {
                        mysqli_stmt_bind_param($qstmt, 'isss', $user_id_to_update, $eng, $hin, $qt);
                        mysqli_stmt_execute($qstmt);
                        mysqli_stmt_close($qstmt);
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
                                            <div class="col-md-12">
                                                <label class="form-label">Type of Reports</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Round Wise Summary" id="roundwisesummary" <?php if (in_array('Round Wise Summary', $existing_reports))
                                                            echo 'checked'; ?>>
                                                    <label class="form-check-label" for="roundwisesummary">
                                                        Round wise Summary Report
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Photo Report" id="photoreportafterbefore" <?php if (in_array('Photo Report', $existing_reports))
                                                            echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photoreportafterbefore">
                                                        Photo Report After Before
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Photo Report Time Slot" id="photoReportTimeSlot" <?php if (in_array('Photo Report Time Slot', $existing_reports))
                                                            echo 'checked'; ?>>
                                                    <label class="form-check-label" for="photoReportTimeSlot">
                                                        Photo Report Time Slot
                                                    </label>
                                                </div>
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="reports[]"
                                                           value="Attendance Photo Report" id="attendancePhotoReport" <?php if (in_array('Attendance Photo Report', $existing_reports))
                                                               echo 'checked'; ?>>
                                                       <label class="form-check-label" for="attendancePhotoReport">
                                                           Attendance Photo Report
                                                       </label>
                                                   </div>
                                                   <div class="form-check">
                                                       <input class="form-check-input" type="checkbox" name="reports[]"
                                                           value="Time Interval Attendance" id="timeIntervalAttendance" <?php if (in_array('Time Interval Attendance', $existing_reports))
                                                               echo 'checked'; ?>>
                                                       <label class="form-check-label" for="timeIntervalAttendance">
                                                           Time Interval Attendance
                                                       </label>
                                                   </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reports[]"
                                                        value="Attendance Report" id="attendancereport" <?php if (in_array('Attendance Report', $existing_reports))
                                                            echo 'checked'; ?>>
                                                    <label class="form-check-label" for="attendancereport">
                                                        Attendance Report
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Questions Section -->
                                        <div id="questionsSectionRoundWise"
                                            style="display: <?php echo in_array('Round Wise Summary', $existing_reports) ? 'block' : 'none'; ?>;">
                                            <h5 class="mb-3">Questions for Round Wise Summary</h5>
                                            <div id="questionsContainer">
                                                <?php if (!empty($existing_questions)): ?>
                                                    <?php foreach ($existing_questions as $q): ?>
                                                        <div class="question-item border rounded p-3 mb-3">
                                                            <div class="row g-3">
                                                                <div class="col-md-5">
                                                                    <label class="form-label">Question (English)</label>
                                                                    <input type="text" name="eng_question[]"
                                                                        class="form-control"
                                                                        placeholder="Enter question in English"
                                                                        value="<?php echo htmlspecialchars($q['eng_question']); ?>"
                                                                        required />
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <label class="form-label">Question (Hindi)</label>
                                                                    <input type="text" name="hin_question[]"
                                                                        class="form-control"
                                                                        placeholder="प्रश्न हिंदी में दर्ज करें"
                                                                        value="<?php echo htmlspecialchars($q['hin_question']); ?>"
                                                                        required />
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Type</label>
                                                                    <select class="form-select" name="q_type[]" required>
                                                                        <option value="">Select Type</option>
                                                                        <option value="AC" <?php if ($q['type'] === 'AC')
                                                                            echo 'selected'; ?>>AC</option>
                                                                        <option value="NON-AC" <?php if ($q['type'] === 'NON-AC')
                                                                            echo 'selected'; ?>>NON-AC</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <button type="button"
                                                                class="btn btn-danger btn-sm mt-2 remove-question"><i
                                                                    class="bi bi-trash"></i> Remove</button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="question-item border rounded p-3 mb-3">
                                                        <div class="row g-3">
                                                            <div class="col-md-5">
                                                                <label class="form-label">Question (English)</label>
                                                                <input type="text" name="eng_question[]"
                                                                    class="form-control"
                                                                    placeholder="Enter question in English" required />
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label">Question (Hindi)</label>
                                                                <input type="text" name="hin_question[]"
                                                                    class="form-control"
                                                                    placeholder="प्रश्न हिंदी में दर्ज करें" required />
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label">Type</label>
                                                                <select class="form-select" name="q_type[]" required>
                                                                    <option value="">Select Type</option>
                                                                    <option value="AC">AC</option>
                                                                    <option value="NON-AC">NON-AC</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm mt-2 remove-question"
                                                            style="display:none;"><i class="bi bi-trash"></i>
                                                            Remove</button>
                                                    </div>
                                                <?php endif; ?>
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
                                        const questionsContainer = document.getElementById('questionsContainer');
                                        const addQuestionBtn = document.getElementById('addQuestion');
                                        const questionsSectionRoundWise = document.getElementById('questionsSectionRoundWise');
                                        const roundwisesummary = document.getElementById('roundwisesummary');

                                        // Show/hide questions section based on checkbox
                                        roundwisesummary.addEventListener('change', function () {
                                            if (this.checked) {
                                                questionsSectionRoundWise.style.display = 'block';
                                            } else {
                                                questionsSectionRoundWise.style.display = 'none';
                                            }
                                        });

                                        addQuestionBtn.addEventListener('click', function () {
                                            const questionItem = document.createElement('div');
                                            questionItem.className = 'question-item border rounded p-3 mb-3';
                                            questionItem.innerHTML = `
                            <div class="row g-3">
                              <div class="col-md-5">
                                <label class="form-label">Question (English)</label>
                                <input type="text" name="eng_question[]" class="form-control" placeholder="Enter question in English" required />
                              </div>
                              <div class="col-md-5">
                                <label class="form-label">Question (Hindi)</label>
                                <input type="text" name="hin_question[]" class="form-control" placeholder="प्रश्न हिंदी में दर्ज करें" required />
                              </div>
                              <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="q_type[]" required>
                                  <option value="" selected disabled>Select Type</option>
                                  <option value="AC">AC</option>
                                  <option value="NON-AC">Non-AC</option>
                                </select>
                              </div>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm mt-2 remove-question">
                              <i class="bi bi-trash"></i> Remove
                            </button>
                          `;
                                            questionsContainer.appendChild(questionItem);
                                            updateRemoveButtons();
                                        });

                                        questionsContainer.addEventListener('click', function (e) {
                                            if (e.target.closest('.remove-question')) {
                                                e.target.closest('.question-item').remove();
                                                updateRemoveButtons();
                                            }
                                        });

                                        function updateRemoveButtons() {
                                            const items = questionsContainer.querySelectorAll('.question-item');
                                            items.forEach((item, index) => {
                                                const removeBtn = item.querySelector('.remove-question');
                                                if (items.length > 1) {
                                                    removeBtn.style.display = 'inline-block';
                                                } else {
                                                    removeBtn.style.display = 'none';
                                                }
                                            });
                                        }
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