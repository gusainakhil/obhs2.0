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
$station_id = $_SESSION['station_id'];
$user_id = $_SESSION['user_id'] ?? 0;

$errors = [];
$max_train_limit = 0;
$current_train_count = 0;

// Fetch max allowed train count for logged-in user
$limit_stmt = $mysqli->prepare("SELECT no_of_train FROM OBHS_users WHERE user_id = ? LIMIT 1");
$limit_stmt->bind_param("i", $user_id);
$limit_stmt->execute();
$limit_result = $limit_stmt->get_result();
if ($limit_result && $limit_result->num_rows > 0) {
    $limit_row = $limit_result->fetch_assoc();
    $max_train_limit = (int)($limit_row['no_of_train'] ?? 0);
}
$limit_stmt->close();

// Fetch existing train target count station-wise
$count_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM base_fb_target WHERE station = ?");
$count_stmt->bind_param("i", $station_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
if ($count_result && $count_result->num_rows > 0) {
    $count_row = $count_result->fetch_assoc();
    $current_train_count = (int)($count_row['total'] ?? 0);
}
$count_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $success = true;
    
    // Get arrays from POST
    $train_numbers = $_POST['train_no'] ?? [];
    $no_ac_coaches = $_POST['no_ac_coach'] ?? [];
    $feed_per_ac_coaches = $_POST['feed_per_ac_coach'] ?? [];
    $no_non_ac_coaches = $_POST['no_non_ac_coach'] ?? [];
    $feed_per_non_ac_coaches = $_POST['feed_per_non_ac_coach'] ?? [];
    $feedback_ttes = $_POST['feedback_tte'] ?? [];

    // Count how many non-empty rows user is trying to add
    $new_train_rows = 0;
    foreach ($train_numbers as $train_no) {
        if (trim((string)$train_no) !== '') {
            $new_train_rows++;
        }
    }

    // Block insert if user exceeds max allowed train count
    if (($current_train_count + $new_train_rows) > $max_train_limit) {
        $success = false;
        $errors[] = "You have added maximum of train ({$max_train_limit}). You cannot upload more trains.";
    }
    
    // Loop through each row and insert
    foreach ($train_numbers as $index => $train_no) {
        if (!$success) {
            break;
        }

        $train_no = trim((string)$train_no);
        if (empty($train_no)) {
            continue; // Skip empty rows
        }
        
        $no_ac_coach = $no_ac_coaches[$index] ?? 0;
        $feed_per_ac_coach = $feed_per_ac_coaches[$index] ?? 0;
        $no_non_ac_coach = $no_non_ac_coaches[$index] ?? 0;
        $feed_per_non_ac_coach = $feed_per_non_ac_coaches[$index] ?? 0;
        $feedback_tte = $feedback_ttes[$index] ?? 0;
        
        // Prepare SQL statement - use station_id from session
        $sql = "INSERT INTO base_fb_target (train_no, no_ac_coach, feed_per_ac_coach, no_non_ac_coach, feed_per_non_ac_coach, feedback_tte, station, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siiiisi", $train_no, $no_ac_coach, $feed_per_ac_coach, $no_non_ac_coach, $feed_per_non_ac_coach, $feedback_tte, $station_id);
        
        if (!$stmt->execute()) {
            $success = false;
            $errors[] = "Error inserting train $train_no: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    if ($success && empty($errors)) {
        $_SESSION['success_msg'] = "Feedback targets added successfully!";
        header("Location: view-feedback-target.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Feedback Target - <?php echo htmlspecialchars($station_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .form-card {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .form-table thead {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        .form-table thead th {
            padding: 12px 10px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-table tbody td {
            padding: 8px;
            border: 1px solid #e2e8f0;
        }

        .form-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .btn-add-row {
            background-color: #10b981;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
        }

        .btn-add-row:hover {
            background-color: #059669;
        }

        .btn-remove {
            background-color: #ef4444;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn-remove:hover {
            background-color: #dc2626;
        }

        .btn-submit {
            background-color: #0ea5e9;
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background-color: #0284c7;
        }

        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body class="bg-slate-50">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <!-- sidebar  -->
    <?php require_once 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">

        <!-- Top Navigation Bar -->
        <?php require_once 'includes/header.php' ?>

        <!-- Main Content Area -->
        <main class="p-4 lg:p-6">

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Add Feedback Target</h1>
                <p class="text-gray-600 mt-1">Add multiple feedback targets for different trains</p>
                <p class="text-sm text-cyan-700 mt-2 font-medium">
                    Max trains allowed: <?php echo (int)$max_train_limit; ?> |
                    Already added: <?php echo (int)$current_train_count; ?> |
                    Remaining: <?php echo max(0, (int)$max_train_limit - (int)$current_train_count); ?>
                </p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <strong>Errors:</strong>
                    <ul class="list-disc ml-5 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="form-card">
                <form method="POST" action="" id="feedbackForm">
                    <div class="overflow-x-auto">
                        <table class="form-table" id="targetTable">
                            <thead>
                                <tr>
                                    <th>Train No</th>
                                    <th>No. AC Coach</th>
                                    <th>Feed Per AC Coach</th>
                                    <th>No. Non-AC Coach</th>
                                    <th>Feed Per Non-AC Coach</th>
                                    <th>Feedback TTE</th>
                                    <th>Station</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr>
                                    <td><input type="text" name="train_no[]" class="form-input" placeholder="Enter train no" required></td>
                                    <td><input type="number" name="no_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                                    <td><input type="number" name="feed_per_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                                    <td><input type="number" name="no_non_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                                    <td><input type="number" name="feed_per_non_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                                    <td><input type="number" name="feedback_tte[]" class="form-input" placeholder="0" min="0" required></td>
                                    <td><input type="text" class="form-input" placeholder="Station" value="<?php echo htmlspecialchars($station_name); ?>" readonly></td>
                                    <td>
                                        <button type="button" class="btn-remove" onclick="removeRow(this)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn-add-row" onclick="addRow()">
                        <i class="fas fa-plus mr-2"></i>Add Row
                    </button>

                    <div class="flex gap-3 mt-6">
                        <button type="submit" name="submit" class="btn-submit">
                            <i class="fas fa-save mr-2"></i>Save All
                        </button>
                        <a href="feedback-target.php" class="btn-submit" style="background-color: #6b7280; display: inline-block; text-decoration: none;">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </a>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <?php require_once 'includes/footer.php' ?>

        </main>

    </div>

    <script>
        // Add new row
        function addRow() {
            const tableBody = document.getElementById('tableBody');
            const newRow = document.createElement('tr');
            const stationName = '<?php echo htmlspecialchars($station_name); ?>';
            
            newRow.innerHTML = `
                <td><input type="text" name="train_no[]" class="form-input" placeholder="Enter train no" required></td>
                <td><input type="number" name="no_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                <td><input type="number" name="feed_per_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                <td><input type="number" name="no_non_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                <td><input type="number" name="feed_per_non_ac_coach[]" class="form-input" placeholder="0" min="0" required></td>
                <td><input type="number" name="feedback_tte[]" class="form-input" placeholder="0" min="0" required></td>
                <td><input type="text" class="form-input" placeholder="Station" value="${stationName}" readonly></td>
                <td>
                    <button type="button" class="btn-remove" onclick="removeRow(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
        }

        // Remove row
        function removeRow(button) {
            const tableBody = document.getElementById('tableBody');
            const rows = tableBody.getElementsByTagName('tr');
            
            // Keep at least one row
            if (rows.length > 1) {
                button.closest('tr').remove();
            } else {
                alert('At least one row is required!');
            }
        }

        // Mobile Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const closeSidebar = document.getElementById('closeSidebar');

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
    </script>

</body>

</html>
