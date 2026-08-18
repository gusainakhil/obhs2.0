<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "./includes/connection.php";

//create a condition to check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard if already logged in
    header("Location: dashboard.php");
    exit;
}


$info = "";
$error = "";

// If login form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare query (Prevents SQL Injection)
    $stmt = $mysqli->prepare("SELECT username, station_id, user_id, password , status  FROM OBHS_users WHERE username = ? AND type = 2");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    // Fetch the record
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password'])) {

            // Store user data in session
            $_SESSION['station_id'] = $row['station_id'];
            $_SESSION['user_id'] = $row['user_id'];
              $_SESSION['status'] = $row['status'];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Invalid password!";
        }

    } else {
        $error = "Username not found!";
    }

    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Railway OBHS </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
        }

        .login-left {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 60px 40px;
            color: white;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-right {
            padding: 60px 50px;
            flex: 1;
        }

        .train-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .input-icon input {
            padding-left: 45px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .version-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 20px;
            }

            .login-left {
                padding: 40px 30px;
            }

            .login-right {
                padding: 40px 30px;
            }
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #64748b;
        }
    </style>
</head>

<body>

    <div class="login-container">
        
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="train-icon">
                <i class="fas fa-train text-5xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold mb-3">Railway OBHS</h1>
            <p class="text-lg opacity-90 mb-4">On-Board Housekeeping Services</p>
            <p class="text-sm opacity-75 max-w-sm">Manage train cleanliness, employee attendance, and feedback targets efficiently.</p>
            <div class="version-badge">
                <i class="fas fa-info-circle mr-2"></i>Beta Version 2.0.0
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <h2 class="text-3xl font-bold text-slate-800 mb-2">Welcome Back!</h2>
            <p class="text-slate-600 mb-8">Please login to your account</p>

            <?php if ($info): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-3">
                <i class="fas fa-info-circle text-lg"></i>
                <span><?php echo htmlspecialchars($info); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                
                <!-- Username Field -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input 
                            type="text" 
                            name="username" 
                            class="form-input" 
                            placeholder="Enter your username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-emerald-500 rounded">
                        <span class="ml-2 text-sm text-slate-600">Remember me</span>
                    </label>
                    <!-- <a href="#" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Forgot Password?</a> -->
                </div>

                <!-- Login Button -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>

            </form>

            <!-- Login Credentials Info (For Development) -->
            <!-- <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                <p class="text-xs text-slate-500 font-semibold mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Development Login Credentials:
                </p>
                <p class="text-xs text-slate-600">Username: <strong>jodhpur</strong></p>
                <p class="text-xs text-slate-600">Password: <strong>123456</strong></p>
            </div> -->

        </div>

    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus on username field
        window.addEventListener('DOMContentLoaded', () => {
            const usernameField = document.querySelector('input[name="username"]');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            }
        });
    </script>

</body>

</html>
