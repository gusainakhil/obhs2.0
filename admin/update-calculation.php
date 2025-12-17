
 <?php
require_once __DIR__ . '/connection.php';
//check session
// session_start();
// if (!isset($_SESSION['user_id'])) {
//   header('Location: login.php');
//   exit;
// }
// Handle create user form submission
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
              <h3 class="mb-0">Create values & Marks</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create values & Marks</li>
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
                  <div class="card-title">Create values & Marks</div>
                </div>
                <!--end::Card Header-->
                <!--begin::Form-->
                <?php if (isset($_SESSION['calc_flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                  <?php echo htmlspecialchars($_SESSION['calc_flash_success']); unset($_SESSION['calc_flash_success']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['calc_flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                  <?php echo htmlspecialchars($_SESSION['calc_flash_error']); unset($_SESSION['calc_flash_error']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="save-marking.php" class="needs-validation" novalidate>
                    <div class="card-body">
                        <!-- Station Selection -->
                        <div class="mb-3">
                            <label for="station" class="form-label">Select Station</label>
                            <select class="form-select" id="station" name="station" required>
                                <option value="">Choose Station...</option>
                                <?php
                                $result = mysqli_query($conn, "SELECT station_id, station_name FROM OBHS_station ORDER BY station_name");
                                while ($station = mysqli_fetch_assoc($result)) {
                                    echo '<option value="' . htmlspecialchars($station['station_id']) . '">' . htmlspecialchars($station['station_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- User Selection (populated dynamically based on station) -->
                        <div class="mb-3">
                            <label for="user" class="form-label">Select User</label>
              <select class="form-select" id="user" name="user_id" required disabled>
                <option value="">Select station first...</option>
              </select>
                        </div>

                        <!-- Value 1 -->
                        <div class="mb-3">
                            <label for="value1" class="form-label">Value 1 For Excellent</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="value1" name="value1" 
                                           min="0" max="100" placeholder="Enter marks (0-100)" required>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="value1_rating" required>
                                        <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 2 -->
                        <div class="mb-3">
                            <label for="value2" class="form-label">Value 2 Very Good</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="value2" name="value2" 
                                           min="0" max="100" placeholder="Enter marks (0-100)" required>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="value2_rating" required>
                                       <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="NOt_attended">Not attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 3 -->
                        <div class="mb-3">
                            <label for="value3" class="form-label">Value Good</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="value3" name="value3" 
                                           min="0" max="100" placeholder="Enter marks (0-100)" required>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="value3_rating" required>
                                       <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 4 -->
                        <div class="mb-3">
                            <label for="value4" class="form-label">Value 4 Average</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="value4" name="value4" 
                                           min="0" max="100" placeholder="Enter marks (0-100)" required>
                                </div>
                                <div class="col-md-6">
                                     <select class="form-select" name="value4_rating" required>
                                       <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Value 5 -->
                        <div class="mb-3">
                            <label for="value5" class="form-label">Value 5 Poor</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="value5" name="value5" 
                                           min="0" max="100" placeholder="Enter marks (0-100)" required>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="value5_rating" required>
                                       <option value="">Select Rating...</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Satisfactory">Satisfactory</option>
                                        <option value="Very Good">Very Good</option>
                                        <option value="Good">Good</option>
                                        <option value="Average">Average</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Not Attended">Not Attended</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        </div>

                    <div class="card-footer">
                        <button type="submit" name="submit_marks" class="btn btn-primary">Submit Marks</button>
                    </div>
                </form>

                <script>
          // Dynamic user loading based on station selection
          (function () {
            const stationEl = document.getElementById('station');
            const userSelect = document.getElementById('user');
            if (!stationEl || !userSelect) return;

            stationEl.addEventListener('change', function () {
              const stationId = this.value;
              userSelect.innerHTML = '<option value="">Loading users...</option>';
              userSelect.disabled = true;
              if (!stationId) {
                userSelect.innerHTML = '<option value="">Select station first...</option>';
                userSelect.disabled = true;
                return;
              }

              fetch('get-users-by-station.php?station_id=' + encodeURIComponent(stationId))
                .then(response => response.json())
                .then(data => {
                  userSelect.innerHTML = '';
                  if (Array.isArray(data) && data.length > 0) {
                    userSelect.appendChild(new Option('Choose User...', '', true, false));
                    data.forEach(u => {
                      const opt = document.createElement('option');
                      opt.value = u.id;
                      opt.textContent = u.label;
                      userSelect.appendChild(opt);
                    });
                    userSelect.disabled = false;
                  } else {
                    userSelect.appendChild(new Option('No users found', '', true, false));
                    userSelect.disabled = true;
                  }
                })
                .catch(err => {
                  console.error(err);
                  userSelect.innerHTML = '';
                  userSelect.appendChild(new Option('Error loading users', '', true, false));
                  userSelect.disabled = true;
                });
            });
          })();
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