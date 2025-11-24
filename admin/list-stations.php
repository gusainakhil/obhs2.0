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
                          <th>Zone Name</th>
                          <th>Division Name</th>
                          <th>Station Name</th>
                          <th style="width: 80px">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        // Fetch stations joined with zone and division names
                        $stations_sql = "SELECT s.station_id, s.Zone_id, s.Division_id, s.station_name, s.created_at, 
                          z.name AS zone_name, d.name AS division_name
                          FROM `OBHS_station` s
                          LEFT JOIN `OBHS_zones` z ON s.Zone_id = z.id
                          LEFT JOIN `OBHS_divisions` d ON s.Division_id = d.id
                          ORDER BY s.station_id DESC";

                        $result = mysqli_query($conn, $stations_sql);
                        if ($result && mysqli_num_rows($result) > 0) {
                          $i = 1;
                          while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr class="align-middle">
                          <td><?php echo $i++; ?></td>
                          <td><?php echo htmlspecialchars($row['zone_name'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($row['division_name'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($row['station_name']); ?></td>
                       
                          <td>
                            <button type="button" class="btn btn-sm btn-primary edit-station-btn" title="Edit"
                              data-id="<?php echo (int)$row['station_id']; ?>"
                              data-name="<?php echo htmlspecialchars($row['station_name'], ENT_QUOTES); ?>">
                              <i class="bi bi-pencil"></i>
                            </button>
                          </td>
                        </tr>
                        <?php
                          }
                        } else {
                        ?>
                        <tr>
                          <td colspan="6" class="text-center">No stations found.</td>
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
    <!-- Edit Station Modal -->
    <div class="modal fade" id="editStationModal" tabindex="-1" aria-labelledby="editStationLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editStationLabel">Edit Station</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="editStationAlert" class="alert d-none" role="alert"></div>
            <form id="editStationForm">
              <input type="hidden" name="station_id" id="modalStationId" />
              <div class="mb-3">
                <label for="modalStationName" class="form-label">Station Name</label>
                <input type="text" class="form-control" id="modalStationName" name="station_name" required />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" id="saveStationBtn" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Modal handling: open, submit via fetch to update-station.php
      (function () {
        const editButtons = document.querySelectorAll('.edit-station-btn');
        const editModalEl = document.getElementById('editStationModal');
        let editModal;
        if (editModalEl) {
          editModal = new bootstrap.Modal(editModalEl);
        }

        editButtons.forEach(btn => {
          btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name || '';
            document.getElementById('modalStationId').value = id;
            document.getElementById('modalStationName').value = name;
            // clear alerts
            const alertEl = document.getElementById('editStationAlert');
            alertEl.className = 'alert d-none';
            alertEl.textContent = '';
            if (editModal) editModal.show();
          });
        });

        const saveBtn = document.getElementById('saveStationBtn');
        if (saveBtn) {
          saveBtn.addEventListener('click', function () {
            const stationId = document.getElementById('modalStationId').value;
            const stationName = document.getElementById('modalStationName').value.trim();
            const alertEl = document.getElementById('editStationAlert');

            if (!stationName) {
              alertEl.className = 'alert alert-danger';
              alertEl.textContent = 'Station name cannot be empty.';
              return;
            }

            saveBtn.disabled = true;
            fetch('update-station.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ station_id: stationId, station_name: stationName })
            })
            .then(r => {
              const ct = r.headers.get('content-type') || '';
              if (r.ok && ct.includes('application/json')) return r.json();
              // if not JSON, return text and throw so it's handled as an error
              return r.text().then(text => { throw new Error(text || 'Server returned non-JSON response'); });
            })
            .then(data => {
              if (data.success) {
                // update table row text for station name
                const btn = document.querySelector('.edit-station-btn[data-id="' + stationId + '"]');
                if (btn) {
                  // find the row and the station name cell (4th td)
                  const row = btn.closest('tr');
                  if (row) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 4) cells[3].textContent = stationName;
                  }
                  // update data-name attribute
                  btn.dataset.name = stationName;
                }
                if (editModal) editModal.hide();
              } else {
                alertEl.className = 'alert alert-danger';
                alertEl.textContent = data.message || 'Update failed';
              }
            })
            .catch(err => {
              alertEl.className = 'alert alert-danger';
              alertEl.textContent = 'Error: ' + (err.message || err);
            })
            .finally(() => { saveBtn.disabled = false; });
          });
        }
      })();
    </script>
    <!--end::OverlayScrollbars Configure-->
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
