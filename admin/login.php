<?php
session_start();

require_once __DIR__ . '/../includes/connection.php';

$loginError = '';

if (isset($_SESSION['user_id']) && (int)($_SESSION['type'] ?? 0) === 1) {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $loginError = 'Username and password are required.';
    } elseif (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $loginError = 'Database connection error.';
    } else {
        $stmt = $mysqli->prepare("SELECT user_id, organisation_name, username, email, station_id, password, type, status FROM OBHS_users WHERE (username = ? OR email = ?) AND type IN (1, 2) ORDER BY type ASC");

        if ($stmt) {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $loggedIn = false;

                while ($row = $result->fetch_assoc()) {
                    $userType = (int)$row['type'];
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

                        header('Location: index.php');
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
  <meta name="description" content="Secure access portal for Beatle Analytics products and operational platforms.">
  <title>Login | Beatle Analytics</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../login_assets/css/style.css">
</head>
<body class="login-portal-page">
  <canvas id="networkCanvas" aria-hidden="true"></canvas>
  <div class="cursor-glow" aria-hidden="true"></div>

  <header class="site-header" id="top">
    <a class="brand" href="https://beatleanalytics.com/index.html"><img src="../login_assets/images/beatle-logo.png" alt="Beatle Analytics"></a>
    <nav class="desktop-nav">
      <a href="https://beatleanalytics.com/index.html">Home</a>
      <a href="https://beatleanalytics.com/solutions.html">Solutions</a>
      <a href="https://beatleanalytics.com/industries.html">Industries</a>
      <a href="https://beatleanalytics.com/products.html">Products</a>
      <a href="https://beatleanalytics.com/technology.html">Technology</a>
      <a href="https://beatleanalytics.com/impact.html">Impact</a>
      <a href="https://beatleanalytics.com/index.html#contact">Contact</a>
    </nav>
    <a class="nav-cta login-cta active-page" href="https://beatleanalytics.com/login.html">
      <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0"/></svg>
      <span>Log In</span>
    </a>
    <button class="menu-button" aria-label="Open menu" aria-expanded="false"><span></span><span></span></button>
  </header>

  <div class="mobile-menu" aria-hidden="true">
    <a href="https://beatleanalytics.com/index.html">Home</a><a href="https://beatleanalytics.com/solutions.html">Solutions</a><a href="https://beatleanalytics.com/industries.html">Industries</a>
    <a href="https://beatleanalytics.com/products.html">Products</a><a href="https://beatleanalytics.com/technology.html">Technology</a><a href="https://beatleanalytics.com/impact.html">Impact</a><a href="https://beatleanalytics.com/index.html#contact">Contact</a>
  </div>

  <main>

    <section class="login-workspace section-pad">
      <div class="platform-selector-panel reveal">

        <div class="platform-selector-head">
          <div>
            <span class="kicker">Select a platform</span>
            <h2>Choose a product to access your dashboard.</h2>
          </div>

          <label class="platform-search">
            <input
              id="platformSearch"
              type="search"
              placeholder="Search login options..."
              autocomplete="off"
            >
            <span>⌕</span>
          </label>
        </div>

        <div class="platform-grid" id="platformGrid">

          <!-- Platform Mechanized Cleaning -->
          <a
            class="platform-card active"
            href="#"
            data-platform="pmc"
            data-title="Platform Mechanized Cleaning"
            data-subtitle="Data collection and survey management"
            aria-label="Open Platform Mechanized Cleaning platform"
          >
            <i>▣</i>
            <h3>Platform Mechanized Cleaning</h3>
            <p>Data collection and survey management</p>
            <span>→</span>
          </a>

          <!-- Passenger Amenities -->
          <a
            class="platform-card"
            href="https://pa.beatlebuddy.com/"
            data-platform="amenities"
            data-title="Passenger Amenities"
            data-subtitle="Passenger Facilities Management"
            aria-label="Open Passenger Amenities platform"
          >
            <i>⌁</i>
            <h3>Passenger Amenities</h3>
            <p>Passenger facilities management</p>
            <span>→</span>
          </a>

          <!-- OBHS Feedback -->
          <a
            class="platform-card"
            href="#"
            data-platform="obhs"
            data-title="OBHS Feedback"
            data-subtitle="Outbound Housekeeping Feedback"
            aria-label="Open OBHS Feedback platform"
          >
            <i>◉</i>
            <h3>OBHS Feedback</h3>
            <p>Outbound housekeeping feedback</p>
            <span>→</span>
          </a>

          <!-- Score Cards -->
          <a
            class="platform-card"
            href="#"
            data-platform="scorecards"
            data-title="Score Cards"
            data-subtitle="Performance Score Cards & Analytics"
            aria-label="Open Score Cards platform"
          >
            <i>◔</i>
            <h3>Score Cards</h3>
            <p>Performance score cards and analytics</p>
            <span>→</span>
          </a>

          <!-- Running Room Management System -->
          <a
            class="platform-card"
            href="https://rrms.beatlebuddy.com/"
            data-platform="running_rooms"
            data-title="Running Rooms"
            data-subtitle="Running Room Management System"
            aria-label="Open Running Room Management System"
            target="_blank"
            rel="noopener noreferrer"
          >
            <i>▤</i>
            <h3>Running Rooms</h3>
            <p>Running Room Management System</p>
            <span>→</span>
          </a>

          <!-- Caution Order -->
          <a
            class="platform-card"
            href="https://co.beatlebuddy.com/"
            data-platform="caution"
            data-title="Caution Order"
            data-subtitle="Caution Order Management"
            aria-label="Open Caution Order Management"
            target="_blank"
            rel="noopener noreferrer"
          >
            <i>△</i>
            <h3>Caution Order</h3>
            <p>Caution order management</p>
            <span>→</span>
          </a>

          <!-- Station Cleanliness -->
          <a
            class="platform-card"
            href="https://pmc.beatleme.co.in/"
            data-platform="cleanliness"
            data-title="Station Cleanliness"
            data-subtitle="Cleanliness Monitoring & Reporting"
            aria-label="Open Station Cleanliness platform"
            target="_blank"
            rel="noopener noreferrer"
          >
            <i>⌁</i>
            <h3>Station Cleanliness</h3>
            <p>Cleanliness monitoring and reporting</p>
            <span>→</span>
          </a>

          <!-- Station Cleanliness Feedbacks -->
          <a
            class="platform-card"
            href="#"
            data-platform="cleanliness_feedback"
            data-title="Station Cleanliness Feedbacks"
            data-subtitle="Cleanliness Feedback Management"
            aria-label="Open Station Cleanliness Feedback platform"
          >
            <i>◉</i>
            <h3>Station Cleanliness Feedbacks</h3>
            <p>Cleanliness feedback management</p>
            <span>→</span>
          </a>

          <!-- QR Station Cleanliness -->
          <a
            class="platform-card"
            href="#"
            data-platform="qr_cleanliness"
            data-title="QR Station Cleanliness"
            data-subtitle="QR Based Cleanliness Feedback System"
            aria-label="Open QR Station Cleanliness platform"
          >
            <i>▦</i>
            <h3>QR Station Cleanliness</h3>
            <p>QR based cleanliness feedback system</p>
            <span>→</span>
          </a>

          <!-- Signal & Telecom -->
          <a
            class="platform-card"
            href="#"
            data-platform="signal_telecom"
            data-title="Signal & Telecom"
            data-subtitle="Signal & Telecom Management"
            aria-label="Open Signal and Telecom platform"
          >
            <i>⌁</i>
            <h3>Signal &amp; Telecom</h3>
            <p>Signal and telecom management</p>
            <span>→</span>
          </a>

          <!-- Colony Management -->
          <a
            class="platform-card"
            href="#"
            data-platform="colony"
            data-title="Colony Management"
            data-subtitle="Railway Colony Management"
            aria-label="Open Colony Management platform"
          >
            <i>⌂</i>
            <h3>Colony Management</h3>
            <p>Railway colony management</p>
            <span>→</span>
          </a>

          <!-- More Solutions -->
          <a
            class="platform-card"
            href="#"
            data-platform="more"
            data-title="More Solutions"
            data-subtitle="Explore More Products"
            aria-label="Explore more Beatle Analytics solutions"
          >
            <i>▦</i>
            <h3>More Solutions</h3>
            <p>Explore more products</p>
            <span>→</span>
          </a>

        </div>

      </div>

      <aside class="login-panel reveal" id="loginPanel">
        <div class="login-panel-product">
          <div class="login-panel-icon">▣</div>
          <div>
            <h2 id="selectedPlatformTitle">OBHS Feedback</h2>
            <p id="selectedPlatformSubtitle">Data collection and survey management</p>
          </div>
        </div>

        <div class="login-divider"></div>

        <div class="login-panel-heading">
          <h3>Login to your account</h3>
          <p>Enter your credentials to continue</p>
        </div>

        <?php if (!empty($loginError)): ?>
          <div class="login-error-alert" style="color: #ff4d4d; background: rgba(255, 77, 77, 0.1); border: 1px solid rgba(255, 77, 77, 0.3); padding: 10px 14px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 15px;">
            ⚠️ <?php echo htmlspecialchars($loginError); ?>
          </div>
        <?php endif; ?>

        <form class="platform-login-form" method="POST" action="">
          <input type="hidden" name="platform" id="selectedPlatformInput" value="pmc">

          <label>
            <span>👤</span>
            <input type="text" name="username" placeholder="Enter username" autocomplete="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
          </label>
          <label>
            <span>🔒</span>
            <input id="loginPassword" type="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">◉</button>
          </label>


          <button class="login-submit" type="submit" name="login" value="1">Login <span>→</span></button>
        </form>

        <div class="login-security-note"><span>◇</span><p>Your access is protected with enterprise-grade security and encryption.</p></div>
      </aside>
    </section>
  </main>

  <footer class="site-footer login-footer">
    <div><img src="login_assets/images/beatle-logo.png" alt="Beatle Analytics"><p>Secure access to intelligent digital platforms for connected operations.</p></div>
    <div><b>Quick links</b><a href="https://beatleanalytics.com/index.html">Home</a><a href="https://beatleanalytics.com/products.html">Products</a><a href="https://beatleanalytics.com/solutions.html">Solutions</a></div>
    <div><b>Solutions</b><a href="https://beatleanalytics.com/products.html">Running Rooms</a><a href="https://beatleanalytics.com/products.html">Passenger Amenities</a><a href="https://beatleanalytics.com/products.html">Cleanliness</a></div>
    <div><b>Contact</b><a href="tel:+918000221818">+91 80002 21818</a><a href="mailto:info@beatleanalytics.com">info@beatleanalytics.com</a><a href="https://beatleanalytics.com/index.html#contact">Contact us</a></div>
    <small>© <span id="year"></span> Beatle Analytics. All rights reserved.</small>
  </footer>

  <script src="login_assets/js/main.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Dynamic footer copyright year
      var yearEl = document.getElementById('year');
      if (yearEl) yearEl.textContent = new Date().getFullYear();

      // Password visibility toggle
      var passwordInput = document.getElementById('loginPassword');
      var passwordToggle = document.getElementById('passwordToggle');
      if (passwordInput && passwordToggle) {
        passwordToggle.addEventListener('click', function() {
          var isPassword = passwordInput.type === 'password';
          passwordInput.type = isPassword ? 'text' : 'password';
          passwordToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
      }

      // Platform grid selection & search filtering
      var cards = document.querySelectorAll('.platform-card');
      var platformInput = document.getElementById('selectedPlatformInput');
      var platformTitle = document.getElementById('selectedPlatformTitle');
      var platformSubtitle = document.getElementById('selectedPlatformSubtitle');
      var panelIcon = document.querySelector('.login-panel-icon');
      var searchInput = document.getElementById('platformSearch');

      cards.forEach(function(card) {
        card.addEventListener('click', function(e) {
          var platform = card.getAttribute('data-platform');
          var href = card.getAttribute('href');

          // If external platform link, let default navigation happen
          if (href && href !== '#' && !href.startsWith('javascript:')) {
            return;
          }

          e.preventDefault();

          cards.forEach(function(c) { c.classList.remove('active'); });
          card.classList.add('active');

          if (platformInput && platform) platformInput.value = platform;
          if (platformTitle && card.getAttribute('data-title')) {
            platformTitle.textContent = card.getAttribute('data-title');
          }
          if (platformSubtitle && card.getAttribute('data-subtitle')) {
            platformSubtitle.textContent = card.getAttribute('data-subtitle');
          }
          var iconEl = card.querySelector('i');
          if (panelIcon && iconEl) {
            panelIcon.textContent = iconEl.textContent;
          }

          // Smooth scroll to login panel on smaller screens
          var loginPanel = document.getElementById('loginPanel');
          if (loginPanel && window.innerWidth < 992) {
            loginPanel.scrollIntoView({ behavior: 'smooth' });
          }
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', function() {
          var query = searchInput.value.toLowerCase().trim();
          cards.forEach(function(card) {
            var text = card.textContent.toLowerCase();
            card.style.display = text.includes(query) ? '' : 'none';
          });
        });
      }
    });
  </script>
</body>
</html>
