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
$user_id = $_SESSION['user_id'];

$success = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long!";
    } else {
        // Fetch current password from database
        $stmt = $mysqli->prepare("SELECT password FROM OBHS_users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verify current password
            if (password_verify($current_password, $row['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database
                $update_stmt = $mysqli->prepare("UPDATE OBHS_users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);

                if ($update_stmt->execute()) {
                    $success = "Password changed successfully!";
                    // Clear form fields
                    $_POST = array();
                } else {
                    $error = "Error updating password. Please try again!";
                }
                $update_stmt->close();
            } else {
                $error = "Current password is incorrect!";
            }
        } else {
            $error = "User not found!";
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
    <title>Change Password - OBHS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #1f2937;
        }

        .password-input-wrapper {
            position: relative;
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <?php include './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <?php include './includes/header.php'; ?>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">

                <div class="max-w-2xl mx-auto">
                    <!-- Page Title -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-key text-emerald-600 mr-2"></i>Change Password
                        </h2>
                        <p class="text-gray-600 mt-1">Update your account password</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (!empty($success)): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm flex items-center">
                            <i class="fas fa-check-circle text-xl mr-3"></i>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm flex items-center">
                            <i class="fas fa-exclamation-circle text-xl mr-3"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Change Password Form -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <form method="POST" action="change-password.php">

                            <!-- Current Password -->
                            <div class="mb-6">
                                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Current Password <span class="text-red-500">*</span>
                                </label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="current_password" name="current_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 pr-12"
                                        placeholder="Enter your current password" required>
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password', this)"></i>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="mb-6">
                                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    New Password <span class="text-red-500">*</span>
                                </label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="new_password" name="new_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 pr-12"
                                        placeholder="Enter your new password (min. 6 characters)" required>
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters long</p>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-6">
                                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Confirm New Password <span class="text-red-500">*</span>
                                </label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 pr-12"
                                        placeholder="Re-enter your new password" required>
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button type="submit"
                                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                                    <i class="fas fa-save mr-2"></i>Change Password
                                </button>
                                <a href="dashboard.php"
                                    class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md text-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>

                        </form>
                    </div>

                    <!-- Password Tips -->
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>Password Tips
                        </h3>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><i class="fas fa-check text-blue-500 mr-2"></i>If you change the password, it will automatically update on the mobile app.</li>
                            <li><i class="fas fa-check text-blue-500 mr-2"></i>Use at least 6 characters</li>
                            <li><i class="fas fa-check text-blue-500 mr-2"></i>Mix uppercase and lowercase letters</li>
                            <li><i class="fas fa-check text-blue-500 mr-2"></i>Include numbers and special characters</li>
                            <li><i class="fas fa-check text-blue-500 mr-2"></i>Avoid common words or patterns</li>
                            <li><i class="fas fa-check text-blue-500 mr-2"></i>Don't reuse old passwords</li>
                        </ul>
                    </div>

                </div>

            </main>

            <!-- Footer -->
            <?php include './includes/footer.php'; ?>

        </div>
    </div>

    <!-- Toggle Password Visibility Script -->
    <script>
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            const successAlert = document.querySelector('.bg-green-100');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
        }, 5000);
    </script>

    <!-- Sidebar Toggle Script -->
    <script src="./js/sidebar-toggle.js"></script>

</body>

</html>
