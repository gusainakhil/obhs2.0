<?php
require_once __DIR__ . '/connection.php';
//check session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
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
    <meta name="title" content="OBHS | Simple Tables" />
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
      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Stations List</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Stations List</li>
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
            <div class="row">
         
              <!-- /.col -->
              <div class="col-md-12">
                
                <div class="card mb-4">
                  <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                      <thead>
                        <tr>
                          <th style="width: 10px">#</th>
                          <th>Station Name</th>
                          <th>Organisation Name</th>
                          <th>Start Date</th>
                          <th>End Date</th>
                          <th>Info</th>
                          <th>Login</th>
                          <th>Status</th>
                          <th style="width: 100px">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        // Query all users and join station name if available
                        $users_sql = "SELECT u.user_id, u.organisation_name, u.username, u.mobile, u.email, u.station_id, u.start_date , u.end_date, u.status, u.type, 
                                      s.station_name
                                      FROM `OBHS_users` u
                                      LEFT JOIN `OBHS_station` s ON u.station_id = s.station_id
                                      WHERE u.type = 2
                                      ORDER BY u.user_id DESC";
                        $res = mysqli_query($conn, $users_sql);
                        if ($res && mysqli_num_rows($res) > 0) {
                          $i = 1;
                          while ($user = mysqli_fetch_assoc($res)) {
                            $status = $user['status'] ?? 0;
                            $badgeClass = ($status == 0) ? 'text-bg-success' : 'text-bg-danger';
                            $statusText = ($status == 0) ? 'Active' : 'Inactive';
                        ?>
                        <tr class="align-middle">
                          <td><?php echo $i++; ?></td>
                          <td><?php echo htmlspecialchars($user['station_name'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($user['organisation_name'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($user['start_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['end_date'] ?? ''); ?></td>
                            <td><a href="user-info.php?id=<?php echo (int)$user['user_id']; ?>" class="btn btn-sm btn-primary" title="Info"><i class="bi bi-info-circle"></i></a></td>
                            <td><a href="auto-login.php?user_id=<?php echo (int)$user['user_id']; ?>" class="btn btn-sm btn-primary" target="_blank">Login</a></td>
                            <td>
                           
                            <button type="button" class="badge <?php echo $badgeClass; ?> border-0" 
                                data-bs-toggle="modal" 
                                data-bs-target="#statusModal"
                                data-user-id="<?php echo (int)$user['user_id']; ?>"
                                data-current-status="<?php echo (int)$status; ?>"
                                data-station-name="<?php echo htmlspecialchars($user['station_name'] ?? ''); ?>">
                              <?php echo $statusText; ?>
                            </button>
                            </td>
                          <td>
                            <a href="update-user.php?id=<?php echo (int)$user['user_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                              <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete-user.php?id=<?php echo (int)$user['user_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this user?');">
                              <i class="bi bi-trash"></i>
                            </a>
                          </td>
                        </tr>
                        <?php
                          }
                        } else {
                        ?>
                        <tr>
                          <td colspan="7" class="text-center">No users found.</td>
                        </tr>
                        <?php
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                  <!-- /.card-body -->
                </div>
                <!-- /.card -->
              </div>
              <!-- /.col -->
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
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
    
    <!-- Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-id" id="statusModalLabel">Change Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Change status for <strong id="modalStationName"></strong>?</p>
            <form id="statusForm" method="POST" action="update-status.php">
              <input type="hidden" name="user_id" id="modalUserId">
              <input type="hidden" name="new_status" id="modalNewStatus">
              <div class="mb-3">
                <label class="form-label">New Status:</label>
                <select class="form-select" name="status_select" id="statusSelect">
                  <option value="0">Active</option>
                  <option value="1">Inactive</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmStatusChange">Save Changes</button>
          </div>
        </div>
      </div>
    </div>
    
    <script>
      // Handle status modal
      document.addEventListener('DOMContentLoaded', function() {
        const statusModal = document.getElementById('statusModal');
        if (statusModal) {
          statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const currentStatus = button.getAttribute('data-current-status');
            const stationName = button.getAttribute('data-station-name');
            
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalStationName').textContent = stationName;
            document.getElementById('statusSelect').value = currentStatus;
          });
          
          document.getElementById('confirmStatusChange').addEventListener('click', function() {
            const userId = document.getElementById('modalUserId').value;
            const newStatus = document.getElementById('statusSelect').value;
            document.getElementById('modalNewStatus').value = newStatus;
            document.getElementById('statusForm').submit();
          });
        }
      });
    </script>
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
