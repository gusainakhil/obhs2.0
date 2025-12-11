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
          . $where_sql . " ORDER BY   m.value DESC";
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
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($show_select_prompt) {
                      echo "<tr><td colspan=7 class=\"text-center\">Please select a station to view markings.</td></tr>";
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
                        echo "<td>
                          <button type=\"button\" class=\"btn btn-sm btn-primary edit-marking-btn\" data-id=\"{$id}\" data-category=\"{$category}\" data-value=\"{$value}\" title=\"Edit\"><i class=\"bi bi-pencil\"></i></button>
                          <button type=\"button\" class=\"btn btn-sm btn-danger delete-marking-btn\" data-id=\"{$id}\" data-category=\"{$category}\" title=\"Delete\"><i class=\"bi bi-trash\"></i></button>
                        </td>\n";
                        echo "</tr>\n";
                      }
                    } else {
                      echo "<tr><td colspan=7 class=\"text-center\">No marking records found for the selected filters.</td></tr>";
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

  <!-- Edit Marking Modal -->
  <div class="modal fade" id="editMarkingModal" tabindex="-1" aria-labelledby="editMarkingLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editMarkingLabel">Edit Marking Value</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="editMarkingAlert" class="alert d-none" role="alert"></div>
          <form id="editMarkingForm">
            <input type="hidden" name="marking_id" id="modalMarkingId" />
            <div class="mb-3">
              <label for="modalCategory" class="form-label">Category</label>
              <input type="text" class="form-control" id="modalCategory" readonly />
            </div>
            <div class="mb-3">
              <label for="modalValue" class="form-label">Value</label>
              <input type="number" class="form-control" id="modalValue" name="value" required />
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="saveMarkingBtn" class="btn btn-primary">Save changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteMarkingModal" tabindex="-1" aria-labelledby="deleteMarkingLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteMarkingLabel">Delete Marking</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="deleteMarkingAlert" class="alert d-none" role="alert"></div>
          <p>Are you sure you want to delete this marking?</p>
          <p><strong>Category:</strong> <span id="deleteCategory"></span></p>
          <input type="hidden" id="deleteMarkingId" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
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

    // Edit marking modal handling
    (function () {
      const editButtons = document.querySelectorAll('.edit-marking-btn');
      const editModalEl = document.getElementById('editMarkingModal');
      let editModal;
      if (editModalEl) {
        editModal = new bootstrap.Modal(editModalEl);
      }

      editButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          const id = this.dataset.id;
          const category = this.dataset.category || '';
          const value = this.dataset.value || '';
          document.getElementById('modalMarkingId').value = id;
          document.getElementById('modalCategory').value = category;
          document.getElementById('modalValue').value = value;
          const alertEl = document.getElementById('editMarkingAlert');
          alertEl.className = 'alert d-none';
          alertEl.textContent = '';
          if (editModal) editModal.show();
        });
      });

      const saveBtn = document.getElementById('saveMarkingBtn');
      if (saveBtn) {
        saveBtn.addEventListener('click', function () {
          const markingId = document.getElementById('modalMarkingId').value;
          const newValue = document.getElementById('modalValue').value.trim();
          const alertEl = document.getElementById('editMarkingAlert');

          if (!newValue) {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = 'Value cannot be empty.';
            return;
          }

          saveBtn.disabled = true;
          fetch('update-marking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ marking_id: markingId, value: newValue })
          })
          .then(r => {
            const ct = r.headers.get('content-type') || '';
            if (r.ok && ct.includes('application/json')) return r.json();
            return r.text().then(text => { throw new Error(text || 'Server error'); });
          })
          .then(data => {
            if (data.success) {
              // Reload the page to show updated value
              window.location.reload();
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

    // Delete marking modal handling
    (function () {
      const deleteButtons = document.querySelectorAll('.delete-marking-btn');
      const deleteModalEl = document.getElementById('deleteMarkingModal');
      let deleteModal;
      if (deleteModalEl) {
        deleteModal = new bootstrap.Modal(deleteModalEl);
      }

      deleteButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          const id = this.dataset.id;
          const category = this.dataset.category || '';
          document.getElementById('deleteMarkingId').value = id;
          document.getElementById('deleteCategory').textContent = category;
          const alertEl = document.getElementById('deleteMarkingAlert');
          alertEl.className = 'alert d-none';
          alertEl.textContent = '';
          if (deleteModal) deleteModal.show();
        });
      });

      const confirmBtn = document.getElementById('confirmDeleteBtn');
      if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
          const markingId = document.getElementById('deleteMarkingId').value;
          const alertEl = document.getElementById('deleteMarkingAlert');

          confirmBtn.disabled = true;
          fetch('update-marking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ marking_id: markingId, action: 'delete' })
          })
          .then(r => {
            const ct = r.headers.get('content-type') || '';
            if (r.ok && ct.includes('application/json')) return r.json();
            return r.text().then(text => { throw new Error(text || 'Server error'); });
          })
          .then(data => {
            if (data.success) {
              window.location.reload();
            } else {
              alertEl.className = 'alert alert-danger';
              alertEl.textContent = data.message || 'Delete failed';
            }
          })
          .catch(err => {
            alertEl.className = 'alert alert-danger';
            alertEl.textContent = 'Error: ' + (err.message || err);
          })
          .finally(() => { confirmBtn.disabled = false; });
        });
      }
    })();
  </script>
</body>
</html>
