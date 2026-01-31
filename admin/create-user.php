<?php
//print error messages
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/connection.php';
//check session
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// Handle create user form submission
$form_error = '';
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
  $pnr = isset($_POST['pnr']) ? (int) $_POST['pnr'] : 1; // default to 1 (on)
  $pnr_skip = isset($_POST['pnr_skip']) ? (int) $_POST['pnr_skip'] : 0; // default to 0 (off)
  $otp = isset($_POST['otp']) ? (int) $_POST['otp'] : 1; // default to 1 (on)
  $otp_skip = isset($_POST['otp_skip']) ? (int) $_POST['otp_skip'] : 0; // default to 0 (off)
  $photo = isset($_POST['photo']) ? (int) $_POST['photo'] : 1; // default to 1 (on)
  $photo_skip = isset($_POST['photo_skip']) ? (int) $_POST['photo_skip'] : 0; // default to 0 (off)
  $reports = $_POST['reports'] ?? [];
  $eng_questions = $_POST['eng_question'] ?? [];
  $hin_questions = $_POST['hin_question'] ?? [];
  $q_types = $_POST['q_type'] ?? [];
  $no_of_train = isset($_POST['no_of_train']) ? (int) $_POST['no_of_train'] : 0;
  // Basic validation
  

  if ($username === '' || $organisation_name === '' || $password === '' || $email === '') {
    $form_error = 'Please fill required fields.';
  } else {
    // Insert user into OBHS_users
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $type = 2; // organisation

    $insert_user_sql = "INSERT INTO `OBHS_users` (`organisation_name`, `username`, `mobile`, `email`, `station_id`, `password`, `start_date`, `end_date`, `type`, `PNR` , `PNR_skip`, `OTP`, `OTP_skip`, `photo`, `photo_skip`, `no_of_train`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $insert_user_sql)) {
      mysqli_stmt_bind_param($stmt, 'ssssisssiiiiiiii', $organisation_name, $username, $mobile, $email, $station_id, $hashed, $start_date, $end_date, $type, $pnr , $pnr_skip, $otp, $otp_skip, $photo, $photo_skip, $no_of_train); ;
      if (mysqli_stmt_execute($stmt)) {
        $new_user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Insert reports if any
        if (!empty($reports) && is_array($reports)) {
          $insert_report_sql = "INSERT INTO `OBHS_reports` (`user_id`, `reports_name`, `link` , `type` ,`station_id`) VALUES (?, ?, ? , ?, ?)";
          foreach ($reports as $r) {
            $r_name = trim($r);
            if ($r_name === '')
              continue;
            if ($rstmt = mysqli_prepare($conn, $insert_report_sql)) {

              // if $r_name is "Round Wise Summary", insert round_wise_summary.php link
              // elseif $r_name is "Photo Report", insert photo_report.php link
              // elseif $r_name is "Photo Report Time Slot", insert photo_report.php link
              // elseif $r_name is "Attendance Report", insert view-attendance.php link

              if ($r_name === 'Round Wise Summary') {
                $empty_link = 'round_wise_summary.php';
                $type = 'Feedback';
              } elseif ($r_name === 'Photo Report') {
                $empty_link = 'photo_report_before_after.php';
                $type = 'photo_report';
              } elseif ($r_name === 'Photo Report Time Slot') {
                $empty_link = 'photo_report.php';
                $type = 'photo_report';
              } elseif ($r_name === 'Photo Report Coach Wise') {
                $empty_link = 'photo_report_coach_wise.php';
                $type = 'photo_report';
              } elseif ($r_name === 'Attendance Report') {
                $empty_link = 'view-no-photo-attendance.php';
                $type = 'Attendance';
              } elseif ($r_name === 'Attendance Photo Report') {
                $empty_link = 'view-attendance.php';
                $type = 'Attendance';
              } elseif ($r_name === 'Time Interval Attendance') {
                $empty_link = 'attendance-report-row-wise.php';
                $type = 'Attendance';
              } elseif ($r_name === 'Daily Attendance Report') {
                $empty_link = 'daily-attendance.php';
                $type = 'Attendance2';
              } else {
                $empty_link = '';
              }
              mysqli_stmt_bind_param($rstmt, 'isssi', $new_user_id, $r_name, $empty_link, $type, $station_id);
              mysqli_stmt_execute($rstmt);
              mysqli_stmt_close($rstmt);
            }
          }
        }

        // Insert questions if provided
        if (!empty($eng_questions) && is_array($eng_questions)) {
          $insert_q_sql = "INSERT INTO `OBHS_questions` (`user_id`, `eng_question`, `hin_question`, `type`, `station_id`) VALUES (?, ?, ?, ?, ?)";
          for ($i = 0; $i < count($eng_questions); $i++) {
            $eng = trim($eng_questions[$i] ?? '');
            $hin = trim($hin_questions[$i] ?? '');
            $qt = trim($q_types[$i] ?? '');
            if ($eng === '' && $hin === '')
              continue;
            if ($qstmt = mysqli_prepare($conn, $insert_q_sql)) {
              mysqli_stmt_bind_param($qstmt, 'isssi', $new_user_id, $eng, $hin, $qt, $station_id);
              mysqli_stmt_execute($qstmt);
              mysqli_stmt_close($qstmt);
            }
          }
        }

        // Success - redirect to user list
        header('Location: user-list.php');
        exit;
      } else {
        $form_error = 'Failed to create user: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
      }
    } else {
      $form_error = 'Database error: ' . mysqli_error($conn);
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
              <h3 class="mb-0">Create user</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create user</li>
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

                    <div class="row g-3 mb-6">
                      <div class="col-md-6">
                        <label for="stationName" class="form-label">Station Name</label>
                        <select class="form-select" id="stationName" name="station_id" required>
                          <option value="" selected disabled>Select Station</option>
                          <?php
                          $query = "SELECT station_id, station_name FROM OBHS_station ORDER BY station_name ASC";
                          $result = mysqli_query($conn, $query);
                          while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . htmlspecialchars($row['station_id']) . '">' . htmlspecialchars($row['station_name']) . '</option>';
                          }
                          ?>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required
                          oninput="this.value = this.value.replace(/\s/g, '')" />
                      </div>

                    </div>
                    <br>

                    <div class="row g-3 mb-4">
                      <div class="col-md-6">
                        <label for="organisationName" class="form-label">Organisation Name</label>
                        <input type="text" class="form-control" id="organisationName" name="organisation_name"
                          required />
                      </div>
                      <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required />
                      </div>
                    </div>


                    <div class="row g-3 mb-4">
                      <div class="col-md-6">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" required />
                      </div>
                      <div class="col-md-6">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end_date" required />
                      </div>
                    </div>

                    <div class="row g-3 mb-4">
                      <div class="col-md-6">
                        <label for="phoneNumber" class="form-label">Phone number</label>
                        <input type="text" class="form-control" id="phoneNumber" name="mobile" required />
                      </div>
                      <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required />
                      </div>
                    </div>
                    <div class="row g-3 mb-4">
                      <div class="col-md-6">
                        <label for="no_of_train" class="form-label">No. of Train Maximum Add</label>
                        <input type="Number" class="form-control" id="no_of_train" name="no_of_train" required />
                      </div>
                      <div class="col-md-6">
                        <p class="text-danger small mt-2">NOTE : After Submitting the form, please update Marks
                          calculation if you select Round wise summary</p>
                          <p class="text-danger small mt-2">NOTE : If PNR ,OTP , Photo Functionality is ON, User will be able to see
                           related data.</p>
                        
                      </div>
                    </div>



                    <div class="row g-3 mb-4">

                      <div class="col-md-6">
                        <label class="form-label">Type of Reports</label>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]" value="Round Wise Summary"
                            id="roundwisesummary">
                          <label class="form-check-label" for="roundwisesummary">
                            Round wise Summary Report
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]" value="Photo Report"
                            id="photoreportafterbefore">
                          <label class="form-check-label" for="photoreportafterbefore">
                            Photo Report After Before
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]"
                            value="Photo Report Time Slot" id="photoReportTimeSlot">
                          <label class="form-check-label" for="photoReportTimeSlot">
                            Photo Report Time Slot
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]"
                            value="Photo Report Coach Wise" id="photoReportTimeSlot">
                          <label class="form-check-label" for="photoReportTimeSlot">
                            Photo Report Coach Wise
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]"
                            value="Attendance Photo Report" id="attendancePhotoReport">
                          <label class="form-check-label" for="attendancePhotoReport">
                            Attendance Photo Report
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]"
                            value="Time Interval Attendance" id="timeIntervalAttendance">
                          <label class="form-check-label" for="timeIntervalAttendance">
                            Time Interval Attendance
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]" value="Attendance Report"
                            id="attendancereport">
                          <label class="form-check-label" for="attendancereport">
                            Attendance Report
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="reports[]"
                            value="Daily Attendance Report" id="dailyAttendanceReport">
                          <label class="form-check-label" for="dailyAttendanceReport">
                            Daily Attendance Report
                          </label>
                        </div>

                      </div>
                      <div class="col-md-2">
                        <label for="pnr" class="form-label">PNR Functionality</label>
                        <!-- default = on (1). If user turns it Off the checkbox sends 0 -->
                        <input type="hidden" name="pnr" value="0" />
                        <!-- ensure unchecked sends 0 for skip -->
                        <input type="hidden" name="pnr_skip" value="0" />
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="pnr" name="pnr" value="1" checked>
                          <label class="form-check-label" for="pnr">On / Off </label>
                        </div>
                        <br><br>
                         <label for="pnr_skip" class="form-label"> Skip PNR Functionality</label>
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="pnr_skip" name="pnr_skip" value="1" checked>
                          <label class="form-check-label" for="pnr_skip">On / Off </label>
                        </div>

                        
                      </div>
                       <div class="col-md-2">
                        <label for="otp" class="form-label">Mobile OTP Functionality</label>
                        <!-- default = on (1). If user turns it Off the checkbox sends 0 -->
                        <input type="hidden" name="otp" value="0" />
                        <!-- ensure unchecked sends 0 for skip -->
                        <input type="hidden" name="otp_skip" value="0" />
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="otp" name="otp" value="1" checked>
                          <label class="form-check-label" for="otp">On / Off </label>
                        </div>
                           <br><br>
                         <label for="otp_skip" class="form-label"> Skip Mobile OTP Functionality</label>
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="otp_skip" name="otp_skip" value="1" checked>
                          <label class="form-check-label" for="otp_skip">On / Off </label>
                        </div>

                        
                      </div>
                       <div class="col-md-2">
                        <label for="photo" class="form-label">Photo  Functionality</label>
                        <!-- default = on (1). If user turns it Off the checkbox sends 0 -->
                        <input type="hidden" name="photo" value="0" />
                        <!-- ensure unchecked sends 0 for skip -->
                        <input type="hidden" name="photo_skip" value="0" />
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="photo" name="photo" value="1" checked>
                          <label class="form-check-label" for="photo">On / Off </label>
                        </div>
                           <br><br>
                         <label for="photo_skip" class="form-label"> Skip Photo Functionality</label>
                        <div class="form-check form-switch mt-2">
                          <input class="form-check-input" type="checkbox" id="photo_skip" name="photo_skip" value="1" checked>
                          <label class="form-check-label" for="photo_skip">On / Off </label>
                        </div>
                        
                        
                      </div>
                    </div>

                    <!-- Questions Section -->
                    <div id="questionsSectionRoundWise" style="display: none;">
                      <h5 class="mb-3">Questions for Round Wise Summary</h5>
                      <div id="questionsContainer">
                        <div class="question-item border rounded p-3 mb-3">
                          <div class="row g-3">
                            <div class="col-md-5">
                              <label class="form-label">Question (English)</label>
                              <input type="text" name="eng_question[]" class="form-control"
                                placeholder="Enter question in English" />
                            </div>
                            <div class="col-md-5">
                              <label class="form-label">Question (Hindi)</label>
                              <input type="text" name="hin_question[]" class="form-control"
                                placeholder="प्रश्न हिंदी में दर्ज करें" />
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
                          <button type="button" class="btn btn-danger btn-sm mt-2 remove-question"
                            style="display: none;">
                            <i class="bi bi-trash"></i> Remove
                          </button>
                        </div>
                      </div>
                      <button type="button" class="btn btn-success btn-sm mb-4" id="addQuestion">
                        <i class="bi bi-plus-circle"></i> Add Question
                      </button>
                    </div>
                  </div>
           <H6 class="text-center" style="color:red">123456 is a system-generated mobile application password. Please inform the user of their login credentials and update them from the dashboard login.</H6>
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
                        // Enable required on questions
                        questionsContainer.querySelectorAll('input, select').forEach(el => el.required = true);
                      } else {
                        questionsSectionRoundWise.style.display = 'none';
                        // Disable required on questions
                        questionsContainer.querySelectorAll('input, select').forEach(el => el.required = false);
                      }
                    });

                    addQuestionBtn.addEventListener('click', function () {
                      const questionItem = document.createElement('div');
                      questionItem.className = 'question-item border rounded p-3 mb-3';
                      questionItem.innerHTML = `
                                        <div class="row g-3">
                                          <div class="col-md-5">
                                            <label class="form-label">Question (English)</label>
                                            <input type="text" name="eng_question[]" class="form-control" placeholder="Enter question in English" />
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
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