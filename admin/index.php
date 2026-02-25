<?php

//check session
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
require_once __DIR__ . '/connection.php';
?>
<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>OBHS | Dashboard</title>
  <!--begin::Accessibility Meta Tags-->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
  <meta name="color-scheme" content="light dark" />
  <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
  <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
  <!--end::Accessibility Meta Tags-->
  <!--begin::Primary Meta Tags-->
  <meta name="title" content="AdminLTE v4 | Dashboard" />
  <meta name="author" content="ColorlibHQ" />
  <meta name="description"
    content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance." />
  <meta name="keywords"
    content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
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

  <link rel="stylesheet" href="css/adminlte.css" />
  <!--end::Required Plugin(AdminLTE)-->
  <!-- apexcharts -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
    integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />
  <!-- jsvectormap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
    integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous" />
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
              <h3 class="mb-0">Dashboard</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
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
            <!--begin::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 1-->
              <div class="small-box text-bg-primary">
                <div class="inner">
                  <?php
                  $zone_count_query = "SELECT COUNT(*) as total FROM OBHS_zones";
                  $zone_count_result = mysqli_query($conn, $zone_count_query);
                  $zone_count = mysqli_fetch_assoc($zone_count_result)['total'];
                  ?>
                  <h3><?php echo $zone_count; ?></h3>
                  <p>Zone</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path
                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12 6a.75.75 0 01.75.75v5.25H18a.75.75 0 010 1.5h-5.25V18a.75.75 0 01-1.5 0v-4.5H6a.75.75 0 010-1.5h5.25V6.75A.75.75 0 0112 6z">
                  </path>
                </svg>
              </div>
              <!--end::Small Box Widget 1-->
            </div>
            <!--end::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 2-->
              <div class="small-box text-bg-success">
                <div class="inner">
                  <?php
                  $division_count_query = "SELECT COUNT(*) as total FROM OBHS_divisions";
                  $division_count_result = mysqli_query($conn, $division_count_query);
                  $division_count = mysqli_fetch_assoc($division_count_result)['total'];
                  ?>
                  <h3><?php echo $division_count; ?></h3>
                  <p>Divisions</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"></path>
                </svg>

              </div>
              <!--end::Small Box Widget 2-->
            </div>
            <!--end::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 3-->
              <div class="small-box text-bg-warning">
                <div class="inner">
                  <?php
                  $station_count_query = "SELECT COUNT(*) as total FROM OBHS_station ";
                  $station_count_result = mysqli_query($conn, $station_count_query);
                  $station_count = mysqli_fetch_assoc($station_count_result)['total'];
                  ?>
                  <h3><?php echo $station_count; ?></h3>
                  <p>Stations</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path d="M8.25 10.875a2.625 2.625 0 115.25 0 2.625 2.625 0 01-5.25 0z"></path>
                  <path fill-rule="evenodd"
                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.125 4.5a4.125 4.125 0 102.338 7.524l2.007 2.006a.75.75 0 101.06-1.06l-2.006-2.007a4.125 4.125 0 00-3.399-6.463z"
                    clip-rule="evenodd"></path>
                </svg>

              </div>
              <!--end::Small Box Widget 3-->
            </div>
            <!--end::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 4-->
              <div class="small-box text-bg-danger">
                <div class="inner">
                  <?php
                  $org_count_query = "SELECT COUNT(*) as total FROM OBHS_users WHERE type = 2";
                  $org_count_result = mysqli_query($conn, $org_count_query);
                  $org_count = mysqli_fetch_assoc($org_count_result)['total'];
                  ?>
                  <h3><?php echo $org_count; ?></h3>
                  <p>Organisation</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path
                    d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z">
                  </path>
                </svg>

              </div>
              <!--end::Small Box Widget 4-->
            </div>
            <!--end::Col-->
          </div>
          <div class="row">
            <!--begin::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 1-->
              <div class="small-box text-bg-primary">
                <div class="inner">
                  <?php
                  $expire_30_query = "SELECT COUNT(*) as total FROM OBHS_users WHERE type = 2 AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                  $expire_30_result = mysqli_query($conn, $expire_30_query);
                  $expire_30_count = mysqli_fetch_assoc($expire_30_result)['total'];
                  ?>
                  <h3><?php echo $expire_30_count; ?></h3>
                  <p>Expire in 30 days Organisation</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path fill-rule="evenodd"
                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"
                    clip-rule="evenodd"></path>
                </svg>

              </div>
              <!--end::Small Box Widget 1-->
            </div>
            <!--end::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 2-->
              <div class="small-box text-bg-success">
                <div class="inner">
                  <?php
                  $expired_org_query = "SELECT COUNT(*) as total FROM OBHS_users WHERE type = 2 AND end_date < CURDATE()";
                  $expired_org_result = mysqli_query($conn, $expired_org_query);
                  $expired_org_count = mysqli_fetch_assoc($expired_org_result)['total'];
                  ?>
                  <h3><?php echo $expired_org_count; ?></h3>
                  <p>Expire Organisation</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path fill-rule="evenodd"
                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"
                    clip-rule="evenodd"></path>
                  <path
                    d="M16.28 17.28a.75.75 0 10-1.06-1.06L12 19.44l-3.22-3.22a.75.75 0 00-1.06 1.06l3.75 3.75a.75.75 0 001.06 0l3.75-3.75z">
                  </path>
                </svg>

              </div>
              <!--end::Small Box Widget 2-->
            </div>
            <!--end::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 3-->
              <div class="small-box text-bg-warning">
                <div class="inner">
                  <?php
                  $active_org_query = "SELECT COUNT(*) as total FROM OBHS_users WHERE type = 2 AND status = 0";
                  $active_org_result = mysqli_query($conn, $active_org_query);
                  $active_org_count = mysqli_fetch_assoc($active_org_result)['total'];
                  ?>
                  <h3><?php echo $active_org_count; ?></h3>
                  <p>Active Organisation</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z"></path>
                  <path d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z"></path>
                </svg>

              </div>
              <!--end::Small Box Widget 3-->
            </div>
            <!--end::Col-->
            <div class="col-lg-3 col-6">
              <!--begin::Small Box Widget 4-->
              <div class="small-box text-bg-danger">
                <div class="inner">
                  <?php
                  $total_users_query = "SELECT COUNT(*) as total FROM OBHS_users WHERE type = 2 OR type = 3";
                  $total_users_result = mysqli_query($conn, $total_users_query);
                  $total_users_count = mysqli_fetch_assoc($total_users_result)['total'];
                  ?>
                  <h3><?php echo $total_users_count; ?></h3>
                  <p>Total user</p>
                </div>
                <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true">
                  <path
                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z">
                  </path>
                </svg>

              </div>
              <!--end::Small Box Widget 4-->
            </div>
            <!--end::Col-->
          </div>
          <div class="row">
            <div class="col-md-8">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Latest Organisation</h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-remove">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table m-0">
                      <thead>
                        <tr>
                          <th>S.No</th>
                          <th>Station name</th>
                          <th>Organisation name</th>
                          <th>Start date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $latest_users_query = "SELECT u.user_id, u.start_date, u.organisation_name, s.station_name 
                                               FROM OBHS_users u 
                                               LEFT JOIN OBHS_station s ON u.station_id = s.station_id 
                                               WHERE u.type = 2
                                               ORDER BY u.user_id DESC 
                                               LIMIT 5";
                        $latest_users_result = mysqli_query($conn, $latest_users_query);
                        $sno = 1;
                        
                        if (mysqli_num_rows($latest_users_result) > 0) {
                          while ($user = mysqli_fetch_assoc($latest_users_result)) {
                            ?>
                            <tr>
                              <td><?php echo $sno++; ?></td>
                              <td><?php echo htmlspecialchars($user['station_name'] ?? 'N/A'); ?></td>
                              <td><?php echo htmlspecialchars($user['organisation_name'] ?? 'N/A'); ?></td>
                              <td><?php echo date('d M Y', strtotime($user['start_date'])); ?></td>
                            </tr>
                            <?php
                          }
                        } else {
                          ?>
                          <tr>
                            <td colspan="4" class="text-center">No users found</td>
                          </tr>
                          <?php
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <!-- /.card-body -->
               
                <!-- /.card-footer -->
              </div>
            </div>
            <div class="col-md-4">

              <div class="col-md-12">
                <!-- Info Boxes Style 2 -->
                <div class="info-box mb-3 text-bg-warning">
                  <span class="info-box-icon"> <i class="bi bi-train-front-fill"></i> </span>
                  <div class="info-box-content">
                    <span class="info-box-text">Total Train </span>
                    <span class="info-box-number"><?php  $sql = "SELECT COUNT(*) as count FROM `base_fb_target`"; $result = mysqli_query($conn, $sql); $row = mysqli_fetch_assoc($result); echo $row['count']; ?></span>
                  </div>
                  <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
                <div class="info-box mb-3 text-bg-success">
                  <span class="info-box-icon"> <i class="bi bi-person-badge-fill"></i> </span>
                  <div class="info-box-content">
                    <span class="info-box-text">Total Employee</span>
                    <span class="info-box-number"><?php  $sql = "SELECT COUNT(*) as count FROM `base_employees`"; $result = mysqli_query($conn, $sql); $row = mysqli_fetch_assoc($result); echo $row['count']; ?></span>
                  </div>
                  <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
                <div class="info-box mb-3 text-bg-danger">
                    <span class="info-box-icon"> <i class="bi bi-chat-left-text-fill"></i> </span>
                  <div class="info-box-content">
                    <span class="info-box-text">Today Feedback Count</span>
                    <span class="info-box-number"><?php
                    $sql = "SELECT COUNT(*) as count FROM `OBHS_passenger` WHERE DATE(created_at) = CURDATE()";
                    $result = mysqli_query($conn, $sql);
                    $row = mysqli_fetch_assoc($result);
                    echo $row['count'];
                    ?></span>
                  </div>
                  <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
                <div class="info-box mb-3 text-bg-info">
                  <span class="info-box-icon"> <i class="bi bi-calendar-month-fill"></i> </span>
                  <div class="info-box-content">
                    <span class="info-box-text">Monthly Feedback Count</span>
                    <!-- <span class="info-box-number"><?php  $sql = "SELECT COUNT(*) as count FROM `OBHS_passenger`"; $result = mysqli_query($conn, $sql); $row = mysqli_fetch_assoc($result); echo $row['count']; ?></span> -->
                  <?php
                  $monthly_sql = "SELECT COUNT(*) AS monthly_count FROM `OBHS_passenger` WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
                  $monthly_result = mysqli_query($conn, $monthly_sql);
                  $monthly_row = mysqli_fetch_assoc($monthly_result);
                  echo $monthly_row['monthly_count'];
                  ?>
                  <!-- /.info-box-content -->
                </div>
              </div>
            </div>

          </div>


          <!--end::Row-->
          <!--begin::Row-->
        </div>
        <!--end::Container-->
      </div>

      <!--end::App Content-->
    </main>
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
  <!-- OPTIONAL SCRIPTS -->
  <!-- sortablejs -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" crossorigin="anonymous"></script>
  <!-- sortablejs -->
  <script>
    new Sortable(document.querySelector('.connectedSortable'), {
      group: 'shared',
      handle: '.card-header',
    });

    const cardHeaders = document.querySelectorAll('.connectedSortable .card-header');
    cardHeaders.forEach((cardHeader) => {
      cardHeader.style.cursor = 'move';
    });
  </script>
  <!-- apexcharts -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
  <!-- ChartJS -->

  <!-- jsvectormap -->
  <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"
    integrity="sha256-/t1nN2956BT869E6H4V1dnt0X5pAQHPytli+1nTZm2Y=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"
    integrity="sha256-XPpPaZlU8S/HWf7FZLAncLg2SAkP8ScUTII89x9D3lY=" crossorigin="anonymous"></script>
  <!-- jsvectormap -->

  <!--end::Script-->
</body>
<!--end::Body-->

</html>