<?php
session_start();
include './includes/connection.php';
include './includes/helpers.php';

$debug = true; // set to false in production
if ($debug) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

checkLogin();
checkSubscription($_SESSION['station_id']);

$station_name = getStationName($_SESSION['station_id']);
$station_id = (int) $_SESSION['station_id'];

$train_numbers = [];
$rows = [];
$error_message = '';
$show_results = false;

$filter_from_date = trim($_GET['filter_from_date'] ?? '');
$filter_to_date = trim($_GET['filter_to_date'] ?? '');
$filter_train_up = trim($_GET['filter_train_up'] ?? '');
$filter_train_down = trim($_GET['filter_train_down'] ?? '');

$trainSql = 'SELECT DISTINCT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no';
$trainStmt = $mysqli->prepare($trainSql);
if ($trainStmt) {
	$trainStmt->bind_param('i', $station_id);
	$trainStmt->execute();
	$trainResult = $trainStmt->get_result();
	while ($trainRow = $trainResult->fetch_assoc()) {
		$train_numbers[] = (string) $trainRow['train_no'];
	}
	$trainStmt->close();
}

$query = 'SELECT id, train_up, train_down, from_date, pdf_file, created_at FROM pdf_attendence WHERE station_id = ?';
$types = 'i';
$params = [$station_id];

if (isset($_GET['apply_filter'])) {
	if ($filter_from_date === '' || $filter_to_date === '') {
		$error_message = 'Please select both From Date and To Date.';
	} elseif ($filter_from_date > $filter_to_date) {
		$error_message = 'From Date cannot be greater than To Date.';
	} else {
		$show_results = true;
		$query .= ' AND from_date BETWEEN ? AND ?';
		$types .= 'ss';
		$params[] = $filter_from_date;
		$params[] = $filter_to_date;

		if ($filter_train_up !== '') {
			$query .= ' AND train_up = ?';
			$types .= 's';
			$params[] = $filter_train_up;
		}
		if ($filter_train_down !== '') {
			$query .= ' AND train_down = ?';
			$types .= 's';
			$params[] = $filter_train_down;
		}
	}
}

$query .= ' ORDER BY id DESC';


if ($show_results && $error_message === '') {
	$stmt = $mysqli->prepare($query);
	if ($stmt) {
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		$stmt->close();
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>View PDF Attendence - <?php echo htmlspecialchars($station_name); ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50">

<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
<?php require_once 'includes/sidebar.php'; ?>

<div class="lg:ml-64 min-h-screen">
	<?php require_once 'includes/header.php'; ?>

	<main class="p-4 lg:p-6">
		<div class="bg-white rounded-xl shadow-md border border-slate-200 p-5 mb-5">
			<div class="flex items-center justify-between gap-3 flex-wrap mb-4">
				<div class="flex items-center gap-2">
					<i class="fas fa-file-pdf text-red-500"></i>
					<h2 class="text-sm font-semibold text-slate-700">PDF Attendence Report</h2>
				</div>
				<a href="dashboard.php" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-700 text-white text-xs font-semibold hover:bg-slate-600 transition">
					<i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
				</a>
			</div>

			<?php if ($error_message !== ''): ?>
				<div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
					<?php echo htmlspecialchars($error_message); ?>
				</div>
			<?php endif; ?>

			<form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
				<div>
					<label for="filter_train_up" class="block text-xs font-semibold text-slate-600 mb-1">Train Up</label>
					<select name="filter_train_up" id="filter_train_up" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
						<option value="">All Train Up</option>
						<?php foreach ($train_numbers as $train_no): ?>
							<option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $filter_train_up === $train_no ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($train_no); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="filter_train_down" class="block text-xs font-semibold text-slate-600 mb-1">Train Down</label>
					<select name="filter_train_down" id="filter_train_down" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
						<option value="">All Train Down</option>
						<?php foreach ($train_numbers as $train_no): ?>
							<option value="<?php echo htmlspecialchars($train_no); ?>" <?php echo $filter_train_down === $train_no ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($train_no); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="filter_from_date" class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
					<input type="date" name="filter_from_date" id="filter_from_date" value="<?php echo htmlspecialchars($filter_from_date); ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
				</div>
				<div>
					<label for="filter_to_date" class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
					<input type="date" name="filter_to_date" id="filter_to_date" value="<?php echo htmlspecialchars($filter_to_date); ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
				</div>
				<div class="flex items-end gap-2">
					<button type="submit" name="apply_filter" value="1" class="px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">Filter</button>
					<a href="view-pdf-attendece.php" class="px-4 py-2 rounded-md bg-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-300 transition">Reset</a>
				</div>
			</form>

			<?php if (!$show_results && !isset($_GET['apply_filter'])): ?>
				<div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-3 py-2 text-sm">
					Please select Train Up, Train Down, From Date and To Date, then click Filter.
				</div>
			<?php endif; ?>

			<div class="overflow-x-auto rounded-lg border border-slate-200">
				<table class="min-w-full text-xs">
					<thead class="bg-slate-100 text-slate-700 uppercase tracking-wide">
						<tr>
							<th class="px-3 py-2 text-left">ID</th>
							<th class="px-3 py-2 text-left">Train Up</th>
							<th class="px-3 py-2 text-left">Train Down</th>
							<th class="px-3 py-2 text-left">Date</th>
							<th class="px-3 py-2 text-left">PDF</th>
							<th class="px-3 py-2 text-left">Created</th>
						</tr>
					</thead>
					<tbody>
						<?php if (count($rows) > 0): ?>
							<?php foreach ($rows as $row): ?>
								<tr class="border-t border-slate-100 hover:bg-slate-50">
									<td class="px-3 py-2 font-semibold text-slate-700"><?php echo (int) $row['id']; ?></td>
									<td class="px-3 py-2"><?php echo htmlspecialchars($row['train_up']); ?></td>
									<td class="px-3 py-2"><?php echo htmlspecialchars($row['train_down']); ?></td>
									<td class="px-3 py-2"><?php echo htmlspecialchars($row['from_date']); ?></td>
									<td class="px-3 py-2"><a href="uploads/pdf/<?php echo rawurlencode($row['pdf_file']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-semibold">View PDF</a></td>
									<td class="px-3 py-2 text-slate-500"><?php echo !empty($row['created_at']) ? date('d M Y, h:i A', strtotime($row['created_at'])) : '-'; ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="6" class="px-3 py-6 text-center text-slate-500">No PDF attendence records found.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</main>
</div>

<script>
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
</script>

</body>
</html>
