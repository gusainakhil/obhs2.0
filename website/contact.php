<?php
$formStatus = '';
$formMessage = '';
$formValues = [
    'name' => '',
    'email' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['name'] = trim($_POST['name'] ?? '');
    $formValues['email'] = trim($_POST['email'] ?? '');
    $formValues['message'] = trim($_POST['message'] ?? '');

    if (
        $formValues['name'] === '' ||
        $formValues['email'] === '' ||
        $formValues['message'] === ''
    ) {
        $formStatus = 'error';
        $formMessage = 'Please fill in your name, email, and message.';
    } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $formStatus = 'error';
        $formMessage = 'Please enter a valid email address.';
    } else {
        $safeName = preg_replace('/[\r\n]+/', ' ', $formValues['name']);
        $safeEmail = filter_var($formValues['email'], FILTER_SANITIZE_EMAIL);

        $fromDomain = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        $fromDomain = preg_replace('/[^A-Za-z0-9.-]/', '', $fromDomain);
        if ($fromDomain === '' || strpos($fromDomain, '.') === false) {
            $fromDomain = 'beatleanalytics.in';
        }

        $subject = 'OBHS Website Contact Form Submission';
        $body = "A new enquiry was submitted from the Railway OBHS website contact form.\n\n";
        $body .= "Name: {$safeName}\n";
        $body .= "Email: {$safeEmail}\n";
        $body .= 'Submitted At: ' . date('Y-m-d H:i:s') . "\n\n";
        $body .= "Message:\n{$formValues['message']}\n";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: OBHS Website <noreply@' . $fromDomain . '>',
            'Reply-To: ' . $safeName . ' <' . $safeEmail . '>',
            'X-Mailer: PHP/' . phpversion(),
        ];

        $mailSent = function_exists('mail') && @mail(
            'info@beatleanalytics.com',
            $subject,
            $body,
            implode("\r\n", $headers)
        );

        if ($mailSent) {
            $formStatus = 'success';
            $formMessage = 'Thank you. Your message has been sent successfully.';
            $formValues = [
                'name' => '',
                'email' => '',
                'message' => '',
            ];
        } else {
            $formStatus = 'error';
            $formMessage = 'Sorry, the message could not be sent right now. Please try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contact | Railway OBHS</title>
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
        <a class="active" href="contact.php">Contact</a>
      </nav>
      <a class="login-btn" href="login.php">🔒 Login</a>
      <button class="menu-btn" aria-label="Menu">☰</button>
    </div>
  </header>
  <main>
    <section class="page-hero">
      <div class="container">
        <div class="breadcrumb">Home / Contact</div>
        <h1>Get in Touch</h1>
        <p>Contact Beatle Analytics for Railway OBHS product information and deployment support.</p>
      </div>
    </section>
    <section class="section">
      <div class="container contact-wrap">
        <div class="contact-card">
          <h2>Send a Message</h2>
          <form id="contactForm" method="post" action="">
            <div class="form-grid">
              <div class="field">
                <label for="contactName">Your Name</label>
                <input
                  id="contactName"
                  name="name"
                  required
                  type="text"
                  value="<?php echo htmlspecialchars($formValues['name'], ENT_QUOTES, 'UTF-8'); ?>"
                >
              </div>
              <div class="field">
                <label for="contactEmail">Email</label>
                <input
                  id="contactEmail"
                  name="email"
                  required
                  type="email"
                  value="<?php echo htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8'); ?>"
                >
              </div>
              <div class="field full">
                <label for="contactMessage">Message</label>
                <textarea id="contactMessage" name="message" required><?php echo htmlspecialchars($formValues['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>
            </div>
            <button class="btn btn-primary" style="margin-top:15px" type="submit">Send Message</button>
            <div
              id="formNotice"
              class="notice<?php echo $formStatus !== '' ? ' is-visible ' . $formStatus : ''; ?>"
              <?php echo $formStatus !== '' ? 'role="status"' : ''; ?>
            ><?php echo htmlspecialchars($formMessage, ENT_QUOTES, 'UTF-8'); ?></div>
          </form>
        </div>
        <aside class="contact-card">
          <div class="kicker">Beatle Analytics</div>
          <h2>Railway OBHS</h2>
          <p>For deployment, customization and account access, contact the Beatle Analytics team.</p>
          <div class="contact-points">
            <div class="contact-point">
              <span class="ico"><span class="svg-icon icon-float">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
              </span></span>
              <div><strong>Website</strong><span>beatleanalytics.com</span></div>
            </div>
            <div class="contact-point">
              <span class="ico"><span class="svg-icon icon-pop">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 7 9 5 9-5M3 7v10l9 5 9-5V7M12 12v10"/></svg>
              </span></span>
              <div><strong>Product</strong><span>Outbound Housekeeping System</span></div>
            </div>
            <div class="contact-point">
              <span class="ico"><span class="svg-icon icon-pulse">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v2"/><circle cx="18" cy="8" r="3"/><path d="M18 14a4 4 0 0 1 4 4v2"/></svg>
              </span></span>
              <div><strong>Existing Users</strong><span>Use the Login page to access OBHS.</span></div>
            </div>
          </div>
        </aside>
      </div>
    </section>
  </main>
  <footer>
    <div class="container">
      <div class="footer-grid">
        <div><img class="brand-logo" src="assets/images/beatle-analytics-logo.png" alt="Beatle Analytics"><p>Railway OBHS by Beatle Analytics — digital outbound housekeeping monitoring for cleaner trains, accountable teams and stronger passenger experience.</p></div>
        <div><h4>Product</h4><a href="features.html">Features</a><a href="modules.html">Modules</a><a href="dashboard.html">Dashboard</a></div>
        <div><h4>Access</h4><a href="benefits.html">Benefits</a><a href="contact.php">Contact</a><a href="login.php">Login</a></div>
      </div>
      <div class="footer-bottom"><span>© <span id="year"></span> Beatle Analytics. All rights reserved.</span><span>Railway OBHS</span></div>
    </div>
  </footer>
  <script src="assets/js/main.js"></script>
</body>
</html>
