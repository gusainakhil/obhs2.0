<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

// Optional: enable detailed error output in development only
$debug = true; // set to false in production
if ($debug) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

// Call reusable login check
checkLogin();

// Now fetch station name
$station_name = getStationName($_SESSION['station_id']);

$grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$train_no = isset($_GET['train_no']) ? $_GET['train_no'] : '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<style>
		.print-footer {
			display: none;
		}

		.print-header {
			display: none;
		}

		@media print {
			@page {
				size: portrait;
				margin: 0;
			}

			body * {
				visibility: hidden;
			}

			body {
				color: #000 !important;
			}

			.summary-header,
			.summary-info,
			.print-header,
			.table-wrapper,
			.table-wrapper *,
			.print-footer {
				visibility: visible;
			}

			.summary-header {
				display: none !important;
			}

			.print-header {
				display: block !important;
				position: absolute;
				left: 0;
				top: 0;
				width: 100%;
				padding: 6px 8px !important;
				margin: 0 !important;
				font-size: 14px !important;
				font-weight: bold !important;
				color: #000 !important;
				text-align: center;
			}

			.summary-info {
				display: none !important;
			}

			.table-wrapper {
				position: absolute;
				left: 0;
				top: 40px;
				width: 100%;
				padding: 0 !important;
				margin: 0 !important;
			}

			.report-table {
				width: 100% !important;
				font-size: 12px !important;
				margin: 0 !important;
				padding: 0 !important;
				border-collapse: collapse !important;
				border: 2px solid #000 !important;
			}

			.report-table th {
				padding: 4px 6px !important;
				margin: 0 !important;
				font-size: 12px !important;
				border: 1px solid #000 !important;
				background-color: #e0e0e0 !important;
				color: #000 !important;
				font-weight: bold !important;
				-webkit-print-color-adjust: exact !important;
				print-color-adjust: exact !important;
			}

			.report-table td {
				padding: 4px 6px !important;
				margin: 0 !important;
				font-size: 12px !important;
				border: 1px solid #000 !important;
				color: #000 !important;
				font-weight: 600 !important;
			}

			.report-table tfoot tr {
				background-color: #d0d0d0 !important;
				font-weight: bold !important;
				-webkit-print-color-adjust: exact !important;
				print-color-adjust: exact !important;
			}

			.print-footer {
				display: none !important;
			}

			.filter-section,
			#menuToggle,
			.export-buttons,
			button,
			nav,
			aside,
			footer {
				display: none !important;
			}
		}
	</style>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Single Train Report - <?php echo $station_name ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="round_wiseSummary.css">

</head>

<body class="bg-slate-50">

	<!-- Mobile Sidebar Overlay -->
	<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
	<!-- sidebar  -->
	<?php
	require_once 'includes/sidebar.php'
		?>

	<!-- Main Content -->
	<div class="lg:ml-64 min-h-screen">

		<!-- Top Navigation Bar -->
		<?php
		require_once 'includes/header.php'
			?>

		<!-- Main Content Area -->
		<main class="p-4 lg:p-6">

			<!-- Filter Section -->
			<form class="filter-section" method="get" action="">
				<div class="filter-row" style="display: flex; flex-wrap: nowrap; align-items: flex-end; gap: 10px; overflow-x: auto;">
					<div class="">
						<label for="gradeFilter">Grade</label>
						<select id="gradeFilter" name="grade" class="filter-select">
							<option value="">-- All --</option>
							<option value="A" <?php echo ($grade === 'A') ? 'selected' : ''; ?>>A - Monday</option>
							<option value="B" <?php echo ($grade === 'B') ? 'selected' : ''; ?>>B - Tuesday</option>
							<option value="C" <?php echo ($grade === 'C') ? 'selected' : ''; ?>>C - Wednesday</option>
							<option value="D" <?php echo ($grade === 'D') ? 'selected' : ''; ?>>D - Thursday</option>
							<option value="E" <?php echo ($grade === 'E') ? 'selected' : ''; ?>>E - Friday</option>
							<option value="F" <?php echo ($grade === 'F') ? 'selected' : ''; ?>>F - Saturday</option>
							<option value="G" <?php echo ($grade === 'G') ? 'selected' : ''; ?>>G - Sunday</option>
						</select>
					</div>

					<?php
					// Fetch train numbers
					$train_query = "SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
					$stmt = $mysqli->prepare($train_query);
					$stmt->bind_param("i", $_SESSION['station_id']);
					$stmt->execute();
					$train_result = $stmt->get_result();
					?>
					<div class="">
						<label for="trainFilter">Train No</label>
						<select id="trainFilter" name="train_no" class="filter-select">
							<option value="">-- All --</option>
							<?php
							$first = true;
							while ($train = $train_result->fetch_assoc()) {
								$tn = $train['train_no'];
								if (isset($_GET['train_no'])) {
									$selected = ($_GET['train_no'] == $tn) ? 'selected' : '';
								} else {
									$selected = $first ? 'selected' : '';
								}
								echo '<option value="' . htmlspecialchars($tn) . '" ' . $selected . '>' . htmlspecialchars($tn) . '</option>';
								$first = false;
							}
							$stmt->close();
							?>
						</select>
					</div>

					<div class="">
						<label for="fromDate">From</label>
						<input type="date" id="fromDate" name="from_date" class="filter-input"
							value="<?php echo $from_date ? htmlspecialchars($from_date) : date('Y-m-d'); ?>">
					</div>

					<div class="">
						<label for="toDate">To</label>
						<input type="date" id="toDate" name="to_date" class="filter-input"
							value="<?php echo $to_date ? htmlspecialchars($to_date) : date('Y-m-d'); ?>">
					</div>

					<div class="filter-group" style="flex-shrink: 0;">
						<input type="submit" class="btn-submit" value="Submit">
					</div>
					<div class="export-buttons" style="flex-shrink: 0; display: flex; gap: 2px;">
						<button type="button" class="btn-submit" id="printButton">Print</button>
						<button type="button" class="btn-submit" id="excelButton">Export to Excel</button>
					</div>
				</div>
			</form>

			<script>
				function formatDateDMY(dateStr) {
					if (!dateStr) return '';
					const parts = dateStr.split('-');
					if (parts.length !== 3) return dateStr;
					return parts[2] + '/' + parts[1] + '/' + parts[0];
				}

				function exportToExcel() {
					const summaryHeader = document.querySelector('.summary-header');
					if (!summaryHeader) {
						alert('Please submit the form first to generate the report.');
						return;
					}

					const headerText = summaryHeader.innerText;
					const stationMatch = headerText.match(/Station:\s*([^\|]+)/);
					const trainMatch = headerText.match(/Train:\s*([^\|]+)/);
					const fromMatch = headerText.match(/From:\s*([^\|]+)/);
					const toMatch = headerText.match(/To:\s*([^\|]+)/);
					const gradeMatch = headerText.match(/Grade:\s*([^\s]+)/);

					const stationName = stationMatch ? stationMatch[1].trim() : '';
					const trainNo = trainMatch ? trainMatch[1].trim() : '';
					const fromDate = fromMatch ? formatDateDMY(fromMatch[1].trim()) : '';
					const toDate = toMatch ? formatDateDMY(toMatch[1].trim()) : '';
					const grade = gradeMatch ? gradeMatch[1].trim() : '';

					const table = document.querySelector('.report-table');
					const rows = table.querySelectorAll('tr');

					let excelData = '<table border="1">';

					excelData += '<tr><td colspan="7" style="text-align:center; font-weight:bold; font-size:16px; background-color:#4472C4; color:white;">Single Train PSI Report</td></tr>';
					excelData += '<tr><td colspan="7" style="text-align:center; background-color:#D9E1F2;"></td></tr>';
					excelData += '<tr><td style="font-weight:bold;">Station:</td><td colspan="2">' + stationName + '</td><td style="font-weight:bold;">Train:</td><td colspan="2">' + trainNo + '</td><td style="font-weight:bold;">Grade:</td></tr>';
					excelData += '<tr><td style="font-weight:bold;">From Date:</td><td colspan="2">' + fromDate + '</td><td style="font-weight:bold;">To Date:</td><td colspan="2">' + toDate + '</td><td>' + grade + '</td></tr>';
					excelData += '<tr><td colspan="7" style="background-color:#D9E1F2;"></td></tr>';

					rows.forEach(row => {
						excelData += '<tr>';
						const cols = row.querySelectorAll('td, th');
						cols.forEach(col => {
							const tag = col.tagName === 'TH' ? 'th' : 'td';
							const rowspan = col.getAttribute('rowspan') || '';
							const colspan = col.getAttribute('colspan') || '';
							const style = col.tagName === 'TH' ? 'style="background-color:#4472C4; color:white; font-weight:bold;"' : '';
							excelData += `<${tag}${rowspan ? ' rowspan="'+rowspan+'"' : ''}${colspan ? ' colspan="'+colspan+'"' : ''} ${style}>${col.innerText}</${tag}>`;
						});
						excelData += '</tr>';
					});

					excelData += '</table>';

					const blob = new Blob([excelData], {
						type: 'application/vnd.ms-excel'
					});
					const url = window.URL.createObjectURL(blob);
					const link = document.createElement('a');
					link.href = url;
					link.download = 'single_train_report.xls';
					link.click();
					window.URL.revokeObjectURL(url);
				}

				document.getElementById('printButton').addEventListener('click', function () {
					window.print();
				});

				document.getElementById('excelButton').addEventListener('click', function () {
					exportToExcel();
				});
			</script>

					<?php
					if (isset($_GET['from_date']) && isset($_GET['to_date']) && isset($_GET['train_no'])) {
						$from_date = htmlspecialchars($_GET['from_date']);
						$to_date = htmlspecialchars($_GET['to_date']);
						$grade = $_GET['grade'];
						$station_id = $_SESSION['station_id'];
						$train_no = $_GET['train_no'];

						$grade = isset($grade) ? $grade : '';
						$train_no = isset($train_no) ? $train_no : '';
					} else {
						echo '<p> </p>';
						exit();
					}
					?>
					<!-- Summary Information -->
					<div class="summary-header" style="text-align: center;">
						Station: <?php echo $station_name ?> &nbsp;&nbsp;|&nbsp;&nbsp; Train: <?php echo $train_no ?>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						From: <span id="displayFrom"><?php echo $from_date ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
						To: <span id="displayTo"><?php echo $to_date ?></span> &nbsp;&nbsp;|&nbsp;&nbsp;
						Grade: <span class="grade-badge"><?php echo $grade ?> </span>
					</div>

					<div class="print-header">
						Station: <?php echo $station_name ?> | Train: <?php echo $train_no ?> | Grade: <?php echo $grade ?> | Report Date: From <?php echo $from_date ?> To <?php echo $to_date ?>
					</div>

					<div class="summary-info" id="summaryInfo">
						<!-- Summary info will be populated by JavaScript -->
					</div>

					<!-- Report Table -->
					<div class="table-wrapper">
						<table class="report-table">
							<thead>
								<tr>
									<th>Date</th>
									<th>Passenger Name</th>
									<th>PNR No.</th>
									<?php if ($_SESSION['station_id'] != 16): ?>
									<th>Mobile No</th>
									<?php endif; ?>
									<th>Coach No</th>
									<th>Seat No</th>
									<th>P.S.I</th>
								</tr>
							</thead>
							<?php
							$highest_marking = check_highest_marking($_SESSION['station_id']);
							$total_feedback_sum_all = 0;
							$total_max_total_all = 0;
							$total_attended = 0;

							$coach_types = ['AC', 'NON-AC', 'TTE'];
							$all_rows = [];

							foreach ($coach_types as $coach_type) {
								$OBHS_question = get_questions_data($_SESSION['station_id'], $coach_type);
								$totalQuestions = !empty($OBHS_question) ? count($OBHS_question) : 0;
								$passengers = get_passenger_details_data_coach_type_wise(
									$train_no,
									$coach_type,
									$grade,
									$from_date,
									$to_date
								);

								if (!empty($passengers)) {
									foreach ($passengers as $pd) {
										$total_feedback_sum = (float) $pd['total_feedback_sum'];
										$max_total = $totalQuestions * (float) $highest_marking;
										$psi = 0.0;
										if ($max_total > 0) {
											$psi = ($total_feedback_sum / $max_total) * 100;
										}
										$psi = min($psi, 100.0);

										$total_feedback_sum_all += $total_feedback_sum;
										$total_max_total_all += $max_total;
										$total_attended += 1;

										$all_rows[] = [
											'feedback_date' => $pd['feedback_date'],
											'name' => $pd['name'],
											'pnr_number' => $pd['pnr_number'],
											'ph_number' => $pd['ph_number'],
											'coach_no' => $pd['coach_no'],
											'seat_no' => $pd['seat_no'],
											'psi' => $psi
										];
									}
								}
							}

							usort($all_rows, function ($a, $b) {
								return strtotime($a['feedback_date']) <=> strtotime($b['feedback_date']);
							});
							?>
							<tbody>
								<?php
								if (!empty($all_rows)) {
									foreach ($all_rows as $row) {
										echo '<tr>';
										if ($_SESSION['station_id'] == 16 || $_SESSION['station_id'] == 23 || $_SESSION['station_id'] == 8) {
											echo '<td>' . date('d/m/Y', strtotime($row['feedback_date'])) . '</td>';
										} else {
											echo '<td>' . date('d/m/Y H:i:s', strtotime($row['feedback_date'])) . '</td>';
										}
										echo '<td>' . htmlspecialchars($row['name']) . '</td>';
										echo '<td>' . htmlspecialchars($row['pnr_number']) . '</td>';
										if ($_SESSION['station_id'] != 16) {
											echo '<td>' . htmlspecialchars($row['ph_number']) . '</td>';
										}
										echo '<td>' . htmlspecialchars($row['coach_no']) . '</td>';
										echo '<td>' . htmlspecialchars($row['seat_no']) . '</td>';
										echo '<td>' . number_format($row['psi'], 2) . '%</td>';
										echo '</tr>';
									}
								} else {
									$colspan = $_SESSION['station_id'] != 16 ? 7 : 6;
									echo "<tr><td colspan='" . $colspan . "' style='text-align:center;color:red;'>No Data Found</td></tr>";
								}
								?>
							</tbody>
							<tfoot>
								<tr class="font-bold bg-slate-100">
									<?php if ($_SESSION['station_id'] != 16): ?>
									<td colspan="5" style="text-align:right;">Total Attended: <?= $total_attended ?></td>
									<td colspan="2" style="text-align:right;">Total PSI: <?= number_format(min($total_max_total_all > 0 ? ($total_feedback_sum_all / $total_max_total_all) * 100 : 0, 100), 2) ?>%</td>
									<?php else: ?>
									<td colspan="4" style="text-align:right;">Total Attended: <?= $total_attended ?></td>
									<td colspan="2" style="text-align:right;">Total PSI: <?= number_format(min($total_max_total_all > 0 ? ($total_feedback_sum_all / $total_max_total_all) * 100 : 0, 100), 2) ?>%</td>
									<?php endif; ?>
								</tr>
							</tfoot>
						</table>
					</div>

					<!-- Print Footer (only visible when printing) -->
					<div class="print-footer" style=" margin-top: 20px; margin-bottom: 20px;">
						Station: <?php echo $station_name; ?> | Train: <?php echo $train_no; ?> | Grade: <?php echo $grade; ?> | Report Date: From <?php echo $from_date; ?> To <?php echo $to_date; ?>
					</div>


			<!-- Footer -->
			<?php
			require_once 'includes/footer.php'
				?>

		</main>

	</div>

	<script>
		// Mobile Sidebar Toggle (guarded)
		(function () {
			const menuToggle = document.getElementById('menuToggle');
			const sidebar = document.getElementById('sidebar');
			const sidebarOverlay = document.getElementById('sidebarOverlay');
			const closeSidebar = document.getElementById('closeSidebar');

			if (menuToggle && sidebar && sidebarOverlay && closeSidebar) {
				menuToggle.addEventListener('click', () => {
					sidebar.classList.remove('-translate-x-full');
					sidebarOverlay.classList.remove('hidden');
				});

				closeSidebar.addEventListener('click', () => {
					sidebar.classList.add('-translate-x-full');
					sidebarOverlay.classList.add('hidden');
				});

				sidebarOverlay.addEventListener('click', () => {
					sidebar.classList.add('-translate-x-full');
					sidebarOverlay.classList.add('hidden');
				});
			}
		})();
	</script>

</body>

</html>
