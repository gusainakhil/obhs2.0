<?php
require_once __DIR__ . '/connection.php';
//check session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Handle form submission: insert new station into OBHS_stations
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $zone_id = isset($_POST['zone']) ? (int)$_POST['zone'] : 0;
  $division_id = isset($_POST['division']) ? (int)$_POST['division'] : 0;
  $station_name = trim($_POST['stationName'] ?? '');

  if ($zone_id <= 0 || $division_id <= 0 || $station_name === '') {
    $form_error = 'Please select zone, division and enter station name.';
  } else {
    // Assumption: stations table is named `OBHS_stations` with columns
    // station_id (auto), Zone_id, Division_id, station_name, created_at
    $insert_sql = "INSERT INTO `OBHS_station` (`Zone_id`, `Division_id`, `station_name`) VALUES (?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
      mysqli_stmt_bind_param($stmt, 'iis', $zone_id, $division_id, $station_name);
      if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        // Redirect to list page after successful insert
        header('Location: list-stations.php');
        exit;
      } else {
        $form_error = 'Database insert failed: ' . mysqli_error($conn);
        mysqli_stmt_close($stmt);
      }
    } else {
      $form_error = 'Database error: could not prepare statement.';
    }
  }
}
 ?>
<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>OBHS </title>
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="OBHS | General Form Elements" />
    <meta name="author" content="ColorlibHQ" />
    <meta
      name="description"
      content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance."
    />
    <meta
      name="keywords"
      content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant"
    />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media='all'"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
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
              <div class="col-sm-6"><h3 class="mb-0">Add station</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Add station</li>
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
                      <div class="card-title">Add New Station</div>
                    </div>
                    <!--end::Card Header-->
                    <!--begin::Form-->
                    <form method="post">
                      <?php if (!empty($form_error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($form_error); ?></div>
                      <?php endif; ?>
                      <!--begin::Body-->
                      <div class="card-body">
                        <!-- Location Information Group -->
                        <h5 class="mb-3">Location Information</h5>
                        <div class="row g-3 mb-4">
                          <div class="col-md-6">
                          <label for="zone" class="form-label">Zone</label>
                          <select class="form-select" id="zone" name="zone" required>
                            <option value="" selected disabled>Select Zone</option>
                            <?php
                            $zone_query = "SELECT `id`, `name`, `code` FROM `OBHS_zones` ORDER BY `name`";
                            $zone_result = mysqli_query($conn, $zone_query);
                            while($zone = mysqli_fetch_assoc($zone_result)) {
                              echo '<option value="'.$zone['id'].'">'.$zone['name'].'</option>';
                            }
                            ?>
                          </select>
                          </div>
                          <div class="col-md-6">
                          <label for="division" class="form-label">Division</label>
                          <select class="form-select" id="division" name="division" required>
                            <option value="" selected disabled>Select Division</option>
                          </select>
                          </div>
                        </div>

                        <div class="row g-3 mb-4">
                          <div class="col-md-6">
                            <label for="stationName" class="form-label">Station Name</label>
                            <input type="text" class="form-control" id="stationName" name="stationName" required />
                          </div>
                   
                        </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn btn-secondary ms-2">Reset</button>
                      </div>

                    </form>

                  </div>

                </div>
              </div>
  
            </div>
          </div>
        </div>

     </main>
      <!--end::App Main-->
      <!--begin::Footer-->
      <? include "footer.php" ?>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
    <!-- OverlayScrollbars configured below with other scripts -->
    <!-- Add custom scripts -->
    <script>
      // existing UI scripts configuration (kept small)
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

        // Division dropdown population when zone changes
        const zoneEl = document.getElementById('zone');
        const divisionSelect = document.getElementById('division');
        if (zoneEl && divisionSelect) {
          zoneEl.addEventListener('change', function () {
            const zoneId = this.value;

            // Clear existing options
            divisionSelect.innerHTML = '<option value="" selected disabled>Select Division</option>';

            if (zoneId) {
              fetch('get-divisions.php?zone_id=' + encodeURIComponent(zoneId))
                .then(response => {
                  if (!response.ok) throw new Error('Network response was not ok');
                  return response.json();
                })
                .then(data => {
                  if (!Array.isArray(data)) return;
                  data.forEach(division => {
                    const option = document.createElement('option');
                    option.value = division.id;
                    option.textContent = division.name;
                    divisionSelect.appendChild(option);
                  });
                })
                .catch(error => {
                  console.error('Error fetching divisions:', error);
                });
            }
          });
        }
      });
    </script>
  </body>
  <!--end::Body-->
</html>
