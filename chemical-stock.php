<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$station_name = trim((string) ($_SESSION['station_name'] ?? $_SESSION['organisation_name'] ?? 'Station'));
$user_display_name = trim((string) ($_SESSION['username'] ?? 'User'));
$profile_label = $station_name;
$ai_message = 'Chemical stock is ready for review.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chemical Stock | OBHS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css"><link rel="stylesheet" href="round_wiseSummary.css"><link rel="stylesheet" href="dashboard-v2-assets/css/chemical-report.css">
</head>
<body class="chemical-page bg-slate-50">
  <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="lg:ml-64 min-h-screen chemical-shell">
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="chemical-main p-4 lg:p-6">
      <section class="chemical-content" aria-labelledby="stockTitle">
        <div class="report-intro"><div><span class="section-kicker">STOCK CONTROL</span><h2 id="stockTitle">Regular chemical quantity</h2><p>Current quantities are saved on this browser only.</p></div><button class="primary-button" id="addChemicalButton" type="button"><span>+</span> Add chemical</button></div>
        <div class="summary-grid">
          <article class="summary-card cyan-card"><span class="summary-icon">⚗</span><div><small>Total chemicals</small><strong id="totalChemicals">0</strong><p>Items in this register</p></div></article>
          <article class="summary-card green-card"><span class="summary-icon">▲</span><div><small>Total stock</small><strong id="totalStock">0</strong><p>Across all units</p></div></article>
          <article class="summary-card orange-card"><span class="summary-icon">!</span><div><small>Low stock</small><strong id="lowStock">0</strong><p>Needs attention</p></div></article>
          <article class="summary-card purple-card"><span class="summary-icon">↻</span><div><small>Updated today</small><strong id="updatedToday">0</strong><p>Quantity changes</p></div></article>
        </div>
        <section class="inventory-panel">
          <div class="panel-toolbar"><div class="search-box"><span>⌕</span><input id="chemicalSearch" type="search" placeholder="Search chemical name..." autocomplete="off"></div><div class="filter-group"><button class="filter-button active" type="button" data-filter="all">All</button><button class="filter-button" type="button" data-filter="low">Low stock</button></div><a class="secondary-button stock-report-link" href="chemical-report.php">View daily report →</a></div>
          <div class="table-wrap"><table class="chemical-table"><thead><tr><th>Chemical</th><th>Category</th><th>Regular quantity</th><th>Status</th><th>Last updated</th><th class="actions-column">Actions</th></tr></thead><tbody id="chemicalTableBody"></tbody></table><div class="empty-state" id="emptyState" hidden><span>⚗</span><h3>No chemicals found</h3><p>Add your first chemical to begin.</p></div></div>
        </section>
      </section>
      <footer class="chemical-footer">OBHS Operations Portal <b>•</b> Chemical stock control</footer>
    </main>
  </div>
  <div class="modal-backdrop" id="chemicalModal" hidden><section class="chemical-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-head"><div><span class="section-kicker">CHEMICAL REGISTER</span><h2 id="modalTitle">Add chemical</h2></div><button class="close-button" id="closeModalButton" type="button">×</button></div>
    <form id="chemicalForm"><input id="chemicalId" type="hidden"><label class="form-field"><span>Chemical name <b>*</b></span><input id="chemicalName" type="text" maxlength="80" required placeholder="Enter chemical name"></label><div class="form-row"><label class="form-field"><span>Category</span><select id="chemicalCategory"><option>Cleaning</option><option>Disinfectant</option><option>Washroom</option><option>Laundry</option><option>Other</option></select></label><label class="form-field"><span>Unit</span><select id="chemicalUnit"><option value="L">Litres (L)</option><option value="kg">Kilograms (kg)</option><option value="pcs">Pieces (pcs)</option></select></label></div><div class="form-row"><label class="form-field"><span>Regular quantity <b>*</b></span><input id="chemicalQuantity" type="number" min="0" step="0.01" required></label><label class="form-field"><span>Low-stock alert at</span><input id="chemicalThreshold" type="number" min="0" step="0.01" value="10" required></label></div><div class="modal-actions"><button class="secondary-button" id="cancelModalButton" type="button">Cancel</button><button class="primary-button" type="submit">Save chemical</button></div></form>
  </section></div>
  <div class="toast" id="toast" role="status" aria-live="polite"></div>
  <script src="dashboard-v2-assets/js/chemical-stock-api.js"></script>
</body></html>
