<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$station_name = trim((string) ($_SESSION['station_name'] ?? $_SESSION['organisation_name'] ?? 'Station'));
$user_display_name = trim((string) ($_SESSION['username'] ?? 'User'));
$profile_label = $station_name;
$ai_message = 'Chemical stock is ready for review.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daily Chemical Report | OBHS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="round_wiseSummary.css">
  <link rel="stylesheet" href="dashboard-v2-assets/css/chemical-report.css">
</head>
<body class="chemical-page bg-slate-50">
  <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="lg:ml-64 min-h-screen chemical-shell">
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="chemical-main p-4 lg:p-6">
      <section class="chemical-content" aria-labelledby="reportTitle">
        <section class="ledger-panel">
          <div class="ledger-head">
            <div><span class="section-kicker">DAILY CHEMICAL REPORT</span><h2 id="reportTitle">Date-wise stock register</h2><p>Closing stock is carried forward as the next opening stock.</p></div>
            <div class="ledger-controls">
              <select id="ledgerChemicalSelect" aria-label="Select chemical"></select>
              <button class="primary-button" id="addDailyEntryButton" type="button"><span>+</span> Daily entry</button>
            </div>
          </div>
          <div class="ledger-summary" id="ledgerSummary"></div>
          <div class="table-wrap">
            <table class="ledger-table">
              <thead><tr><th>Date</th><th>Opening stock</th><th>Used</th><th>Received</th><th>Closing stock</th><th>Notes</th><th></th></tr></thead>
              <tbody id="ledgerTableBody"></tbody>
            </table>
            <div class="ledger-empty" id="ledgerEmpty">No daily entries for this chemical. Click “Daily entry” to begin.</div>
          </div>
        </section>

      </section>
      <footer class="chemical-footer">OBHS Operations Portal <b>•</b> Frontend chemical register</footer>
    </main>
  </div>
  <div class="modal-backdrop" id="dailyEntryModal" hidden>
    <section class="chemical-modal" role="dialog" aria-modal="true" aria-labelledby="dailyModalTitle">
      <div class="modal-head"><div><span class="section-kicker">DAILY CHEMICAL REPORT</span><h2 id="dailyModalTitle">Add daily entry</h2></div><button class="close-button" id="closeDailyModalButton" type="button" aria-label="Close">×</button></div>
      <form id="dailyEntryForm">
        <input id="dailyChemicalId" type="hidden">
        <div class="selected-chemical"><span>⚗</span><div><small>Selected chemical</small><strong id="dailyChemicalName"></strong></div><b id="dailyChemicalUnit"></b></div>
        <label class="form-field"><span>Entry date <b>*</b></span><input id="dailyDate" type="date" required></label>
        <div class="opening-stock"><span>Opening stock for this entry</span><strong id="dailyOpening">0 L</strong></div>
        <div class="form-row">
          <label class="form-field"><span>Quantity used <b>*</b></span><input id="dailyUsed" type="number" min="0" step="0.01" value="0" required></label>
          <label class="form-field"><span>Quantity received <b>*</b></span><input id="dailyReceived" type="number" min="0" step="0.01" value="0" required></label>
        </div>
        <div class="closing-stock"><span>Calculated closing stock</span><strong id="dailyClosing">0 L</strong></div>
        <label class="form-field"><span>Notes (optional)</span><input id="dailyNotes" type="text" maxlength="120" placeholder="Receipt reference or usage note"></label>
        <p class="calculation-note">Closing stock = Opening stock − Used + Received</p>
        <div class="modal-actions"><button class="secondary-button" id="cancelDailyModalButton" type="button">Cancel</button><button class="primary-button" type="submit">Save daily entry</button></div>
      </form>
    </section>
  </div>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>
  <script src="dashboard-v2-assets/js/chemical-daily-report-api.js"></script>
</body>
</html>
