<?php
require_once __DIR__ . '/connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// filter inputs (via GET)
$station_id = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Load stations for the filter select
$stations_result = mysqli_query($conn, "SELECT station_id, station_name FROM OBHS_station ORDER BY station_name");

// If a station is selected, load users for that station (to prepopulate the user select)
$users_for_station = [];
if ($station_id > 0) {
  $uqr = mysqli_prepare($conn, "SELECT user_id, username, organisation_name FROM OBHS_users WHERE station_id = ? ORDER BY username");
  if ($uqr) {
    mysqli_stmt_bind_param($uqr, 'i', $station_id);
    mysqli_stmt_execute($uqr);
    $ures = mysqli_stmt_get_result($uqr);
    while ($u = mysqli_fetch_assoc($ures)) {
      $users_for_station[] = $u;
    }
    mysqli_stmt_close($uqr);
  }
}

// Only query when a station is selected. If no station selected, don't show entries.
$result = false;
$show_select_prompt = false;
if ($station_id > 0) {
  // Build main query with optional filters
  $where = [];
  $where[] = 'm.station_id = ' . $station_id;
  if ($user_id > 0) $where[] = 'm.user_id = ' . $user_id;
  $where_sql = '';
  if (!empty($where)) $where_sql = ' WHERE ' . implode(' AND ', $where);

  $sql = "SELECT m.id, m.station_id, m.user_id, m.category, m.value, m.created_at,
                 s.station_name, u.username, u.organisation_name
          FROM OBHS_marking m
          LEFT JOIN OBHS_station s ON m.station_id = s.station_id
          LEFT JOIN OBHS_users u ON m.user_id = u.user_id"
          . $where_sql . " ORDER BY m.created_at DESC";
  $result = mysqli_query($conn, $sql);
} else {
  // No station selected — prompt user to pick one and do not query the markings table
  $show_select_prompt = true;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Markings — OBHS</title>
  <link rel="stylesheet" href="css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
  <div class="app-wrapper">
    <?php include "header.php" ?>
    <main class="app-main">
      <div class="app-content-header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              <h3 class="mb-0">Markings</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Markings</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <div class="app-content">
        <div class="container-fluid">
          <div class="row g-4">
            <div class="col-12">
              <div class="card card-outline card-primary">
                <div class="card-header">
                  <h3 class="card-title">All Marking Entries</h3>
                  <div class="card-tools">
                    <a href="update-calculation.php" class="btn btn-sm btn-primary">Add Marks</a>
                  </div>
                </div>
                <div class="card-body">
                  <form method="get" class="row g-2 align-items-end mb-3" id="filterForm">
                    <div class="col-md-4">
                      <label class="form-label">Station</label>
                      <select name="station_id" id="filter_station" class="form-select">
                        <option value="">All stations</option>
                        <?php if ($stations_result) {
                          while ($s = mysqli_fetch_assoc($stations_result)) {
                            $sid = (int)$s['station_id'];
                            $sel = $station_id === $sid ? ' selected' : '';
                            echo '<option value="' . $sid . '"' . $sel . '>' . htmlspecialchars($s['station_name']) . '</option>';
                          }
                        } ?>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">User</label>
                      <select name="user_id" id="filter_user" class="form-select" <?php echo $station_id ? '' : 'disabled'; ?>>
                        <option value=""><?php echo $station_id ? 'Choose user...' : 'Select station first...'; ?></option>
                        <?php if (!empty($users_for_station)) {
                          foreach ($users_for_station as $u) {
                            $uid = (int)$u['user_id'];
                            $label = htmlspecialchars($u['username'] . (isset($u['organisation_name']) && $u['organisation_name'] ? ' (' . $u['organisation_name'] . ')' : ''));
                            $sel2 = $user_id === $uid ? ' selected' : '';
                            echo "<option value=\"{$uid}\"{$sel2}>{$label}</option>";
                          }
                        } ?>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="list-markings.php" class="btn btn-outline-secondary">Clear</a>
                      </div>
                    </div>
                  </form>
                  <div class="table-responsive">
                  <table class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Station</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Value</th>
                        <th>Created At</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($show_select_prompt) {
                      echo "<tr><td colspan=6 class=\"text-center\">Please select a station to view markings.</td></tr>";
                    } elseif ($result && mysqli_num_rows($result) > 0) {
                      while ($row = mysqli_fetch_assoc($result)) {
                        $id = (int)$row['id'];
                        $station = htmlspecialchars($row['station_name'] ?? ('#' . ($row['station_id'] ?? '')));
                        $user = htmlspecialchars(($row['username'] ?? '') . (isset($row['organisation_name']) && $row['organisation_name'] ? ' (' . $row['organisation_name'] . ')' : ''));
                        $category = htmlspecialchars($row['category']);
                        $value = htmlspecialchars($row['value']);
                        $created = htmlspecialchars($row['created_at']);
                        echo "<tr>\n";
                        echo "<td>{$id}</td>\n";
                        echo "<td>{$station}</td>\n";
                        echo "<td>{$user}</td>\n";
                        echo "<td>{$category}</td>\n";
                        echo "<td>{$value}</td>\n";
                        echo "<td>{$created}</td>\n";
                        echo "</tr>\n";
                      }
                    } else {
                      echo "<tr><td colspan=6 class=\"text-center\">No marking records found for the selected filters.</td></tr>";
                    }
                    ?>
                    </tbody>
                  </table>
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
  <script>
    // Populate user select when station filter changes
    (function () {
      const stationEl = document.getElementById('filter_station');
      const userEl = document.getElementById('filter_user');
      if (!stationEl || !userEl) return;

      function setLoading() {
        userEl.innerHTML = '<option>Loading...</option>';
        userEl.disabled = true;
      }

      stationEl.addEventListener('change', function () {
        const sid = this.value;
        if (!sid) {
          userEl.innerHTML = '<option value="">Select station first...</option>';
          userEl.disabled = true;
          return;
        }
        setLoading();
        fetch('get-users-by-station.php?station_id=' + encodeURIComponent(sid))
          .then(r => r.json())
          .then(data => {
            userEl.innerHTML = '';
            if (Array.isArray(data) && data.length) {
              userEl.appendChild(new Option('Choose user...', '', true, false));
              data.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = u.label;
                userEl.appendChild(opt);
              });
              userEl.disabled = false;
            } else {
              userEl.appendChild(new Option('No users found', '', true, false));
              userEl.disabled = true;
            }
          })
          .catch(() => {
            userEl.innerHTML = '';
            userEl.appendChild(new Option('Error loading users', '', true, false));
            userEl.disabled = true;
          });
      });
    })();
  </script>
</body>
</html>
