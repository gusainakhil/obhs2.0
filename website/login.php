<?php
session_start();

require_once __DIR__ . '/../includes/connection.php';

$loginError = '';
$usernameValue = '';

if (isset($_SESSION['user_id']) && (int) ($_SESSION['type'] ?? 0) === 1) {
    header('Location: ../admin/index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameValue = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usernameValue === '' || $password === '') {
        $loginError = 'Username and password are required.';
    } elseif (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $loginError = 'Database connection error.';
    } else {
        $stmt = $mysqli->prepare(
            'SELECT user_id, organisation_name, username, email, station_id, password, type, status
             FROM OBHS_users
             WHERE (username = ? OR email = ?) AND type IN (1, 2)
             ORDER BY type ASC'
        );

        if ($stmt) {
            $stmt->bind_param('ss', $usernameValue, $usernameValue);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $loggedIn = false;

                while ($row = $result->fetch_assoc()) {
                    $userType = (int) $row['type'];
                    $passwordMatches = password_verify($password, $row['password']);

                    if ($userType === 1 && !$passwordMatches && $password === $row['password']) {
                        $passwordMatches = true;
                    }

                    if (!$passwordMatches) {
                        continue;
                    }

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['organisation_name'] = $row['organisation_name'] ?? '';
                    $_SESSION['email'] = $row['email'] ?? '';
                    $_SESSION['station_id'] = $row['station_id'];
                    $_SESSION['type'] = $row['type'];
                    $_SESSION['status'] = $row['status'];
                    $loggedIn = true;

                    if ($userType === 1) {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_user_id'] = $row['user_id'];
                        $_SESSION['admin_username'] = $row['username'];

                        header('Location: ../admin/index.php');
                        exit;
                    }

                    header('Location: ../dashboard.php');
                    exit;
                }

                if (!$loggedIn) {
                    $loginError = 'Invalid password!';
                }
            } else {
                $loginError = 'Username not found!';
            }

            $stmt->close();
        } else {
            $loginError = 'Unable to process login query right now.';
        }
    }
}

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login | Railway OBHS</title>
  <meta name="description" content="Railway OBHS by Beatle Analytics">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="container nav">
      <a href="index.html"><img class="brand-logo" src="assets/images/beatle-analytics-logo.png" alt="Beatle Analytics"></a>
      <nav class="nav-links">
        <a class="" href="index.html">Home</a>
        <a class="" href="features.html">Features</a>
        <a class="" href="modules.html">Modules</a>
        <a class="" href="dashboard.html">Dashboard</a>
        <a class="" href="benefits.html">Benefits</a>
        <a class="" href="contact.php">Contact</a>
      </nav>
      <a class="login-btn" href="login.php">🔒 Login</a>
      <button class="menu-btn" aria-label="Menu">☰</button>
    </div>
  </header>

  <main>
    <section class="login-wrap">
      <div class="login-card">
        <img class="brand-logo" src="assets/images/beatle-analytics-logo.png" alt="Beatle Analytics">
        <div class="kicker" style="text-align:center">Secure Access</div>
        <h1>OBHS Login</h1>
        <p>Enter your credentials to continue.</p>

        <?php if ($loginError !== ''): ?>
          <div class="login-alert" role="alert"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <div class="field">
            <label for="username">User ID</label>
            <input
              id="username"
              type="text"
              name="username"
              placeholder="Enter user ID"
              autocomplete="username"
              value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES, 'UTF-8'); ?>"
              required
            >
          </div>
          <div class="field" style="margin-top:13px">
            <label for="password">Password</label>
            <input
              id="password"
              type="password"
              name="password"
              placeholder="Enter password"
              autocomplete="current-password"
              required
            >
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:18px">🔒 Login</button>
        </form>
      </div>
    </section>
  </main>

  <footer>
    <div class="container">
      <div class="footer-grid">
        <div>
          <img class="brand-logo" src="assets/images/beatle-analytics-logo.png" alt="Beatle Analytics">
          <p>Railway OBHS by Beatle Analytics — digital outbound housekeeping monitoring for cleaner trains, accountable teams and stronger passenger experience.</p>
        </div>
        <div>
          <h4>Product</h4>
          <a href="features.html">Features</a>
          <a href="modules.html">Modules</a>
          <a href="dashboard.html">Dashboard</a>
        </div>
        <div>
          <h4>Access</h4>
          <a href="benefits.html">Benefits</a>
          <a href="contact.php">Contact</a>
          <a href="login.php">Login</a>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <span id="year"></span> Beatle Analytics. All rights reserved.</span>
        <span>Railway OBHS</span>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
