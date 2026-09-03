<?php
require_once __DIR__ . '/connection.php';

$current_script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$executed_script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$executed_directory = basename(dirname($executed_script !== '' ? $executed_script : $current_script));
$is_newui_sidebar = $executed_directory === 'newui';
$current_page = basename($executed_script !== '' ? $executed_script : $current_script);
$dashboard_href = $is_newui_sidebar ? '../dashboard.php' : 'dashboard.php';
$logo_src = $is_newui_sidebar ? '../dashboard-v2-assets/images/beatle-analytics-logo.png' : 'dashboard-v2-assets/images/beatle-analytics-logo.png';

$escape = static function ($value) {
    if (function_exists('uiEscape')) {
        return uiEscape($value);
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$build_project_href = static function ($href) use ($is_newui_sidebar) {
    $href = trim((string) $href);

    if ($href === '') {
        return '';
    }

    if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $href) || $href[0] === '#') {
        return $href;
    }

    $normalized = preg_replace('#^(\./)+#', '', $href);
    while (strpos($normalized, '../') === 0) {
        $normalized = substr($normalized, 3);
    }
    $normalized = ltrim($normalized, '/');

    if ($is_newui_sidebar && $normalized !== '') {
        return '../' . $normalized;
    }

    return $normalized;
};

$resolve_sidebar_icon = static function ($label) {
    static $icon_map = [
        'Dashboard' => '▦',
        'Round Wise Summary' => '◉',
        'Round Wise Summary Without Grade' => '◎',
        'Photo Report' => '▣',
        'Photo Report Time Slot' => '◫',
        'Photo Report Coach Wise' => '▣',
        'Attendance Report' => '◍',
        'Attendance Photo Report' => '♙',
        'Time Interval Attendance' => '⋯',
        'Daily Attendance Report' => '☰',
        'Train Report' => '◬',
        'View PDF Attendence' => '⌘',
        'Feedback Target' => '◌',
        'View Feedback Target' => '◈',
        'Create Employee' => '♟',
        'View Employee' => '♧',
        'Change Dashboard Password' => '🔑',
        'Change App Password' => '🔐',
    ];

    return $icon_map[$label] ?? '›';
};

$station_id = (int) ($_SESSION['station_id'] ?? 0);
$session_user_id = (int) ($_SESSION['user_id'] ?? 0);
$session_username = trim((string) ($_SESSION['username'] ?? ''));
$session_organisation = trim((string) ($_SESSION['organisation_name'] ?? ''));
$resolved_station_name = isset($station_name)
    ? html_entity_decode(trim((string) $station_name), ENT_QUOTES, 'UTF-8')
    : '';

if ($resolved_station_name === '' && function_exists('getStationName') && $station_id > 0) {
    $resolved_station_name = (string) getStationName($station_id);
}

$resolved_user_display = isset($user_display_name)
    ? trim((string) $user_display_name)
    : ($session_username !== '' ? $session_username : ($session_organisation !== '' ? $session_organisation : 'User'));
$resolved_profile_label = isset($profile_label)
    ? html_entity_decode(trim((string) $profile_label), ENT_QUOTES, 'UTF-8')
    : ($session_organisation !== '' ? $session_organisation : $resolved_station_name);
$resolved_avatar_text = isset($avatar_text) && trim((string) $avatar_text) !== ''
    ? trim((string) $avatar_text)
    : strtoupper(substr($resolved_user_display, 0, 1));
$resolved_ai_message = isset($ai_message) && trim((string) $ai_message) !== ''
    ? trim((string) $ai_message)
    : ($resolved_station_name !== '' ? 'Daily operations are ready for ' . $resolved_station_name . '.' : 'Daily operations overview is ready.');
$resolved_user_id = isset($user_id) && trim((string) $user_id) !== ''
    ? (string) $user_id
    : (string) ($_SESSION['user_id'] ?? '--');

$assigned_report_links = [];

$reports_sql = 'SELECT reports_name, link FROM OBHS_reports WHERE user_id = ? ORDER BY id ASC';
$reports_stmt = $mysqli->prepare($reports_sql);

if ($reports_stmt) {
    $reports_stmt->bind_param('i', $session_user_id);
    $reports_stmt->execute();
    $reports_result = $reports_stmt->get_result();

    if ($reports_result) {
        while ($row = $reports_result->fetch_assoc()) {
            $report_label = trim((string) ($row['reports_name'] ?? ''));
            $report_href = $build_project_href($row['link'] ?? '');

            if ($report_label === '' || $report_href === '') {
                continue;
            }

            $assigned_report_links[] = [
                'href' => $report_href,
                'icon' => $resolve_sidebar_icon($report_label),
                'label' => $report_label,
            ];
        }
    }

    $reports_stmt->close();
}

$sidebar_links = [
    ['href' => $dashboard_href, 'icon' => $resolve_sidebar_icon('Dashboard'), 'label' => 'Dashboard'],
];

$sidebar_links = array_merge($sidebar_links, $assigned_report_links);

if ($station_id === 8) {
    $sidebar_links[] = [
        'href' => $build_project_href('feedback-single-train-report.php'),
        'icon' => $resolve_sidebar_icon('Train Report'),
        'label' => 'Train Report',
    ];
    $sidebar_links[] = [
        'href' => $build_project_href('view-pdf-attendece.php'),
        'icon' => $resolve_sidebar_icon('View PDF Attendence'),
        'label' => 'View PDF Attendence',
    ];
}

$sidebar_links[] = [
    'href' => $build_project_href('feedback-target.php'),
    'icon' => $resolve_sidebar_icon('Feedback Target'),
    'label' => 'Feedback Target',
];
$sidebar_links[] = [
    'href' => $build_project_href('view-feedback-target.php'),
    'icon' => $resolve_sidebar_icon('View Feedback Target'),
    'label' => 'View Feedback Target',
];

if ($station_id === 17) {
    $sidebar_links[] = [
        'href' => $build_project_href('jodhpur-employees/add-employee-jodhpur.php'),
        'icon' => $resolve_sidebar_icon('Create Employee'),
        'label' => 'Create Employee',
    ];
    $sidebar_links[] = [
        'href' => $build_project_href('jodhpur-employees/employee-jodhpur.php'),
        'icon' => $resolve_sidebar_icon('View Employee'),
        'label' => 'View Employee',
    ];
} else {
    $sidebar_links[] = [
        'href' => $build_project_href('create-employee.php'),
        'icon' => $resolve_sidebar_icon('Create Employee'),
        'label' => 'Create Employee',
    ];
    $sidebar_links[] = [
        'href' => $build_project_href('view-employee.php'),
        'icon' => $resolve_sidebar_icon('View Employee'),
        'label' => 'View Employee',
    ];
}

$sidebar_links[] = [
    'href' => $build_project_href('change-password.php'),
    'icon' => $resolve_sidebar_icon('Change Dashboard Password'),
    'label' => 'Change Dashboard Password',
];
$sidebar_links[] = [
    'href' => $build_project_href('change-app-password.php'),
    'icon' => $resolve_sidebar_icon('Change App Password'),
    'label' => 'Change App Password',
];

if (!$is_newui_sidebar):
?>
<style id="obhs-shared-sidebar-style">
  #sidebar.sidebar{
    width:16rem;
    max-width:16rem;
    height:100vh;
    padding:16px 14px;
    display:flex;
    flex-direction:column;
    border-right:1px solid #18344c;
    background:#030b13;
    color:#f7fbff;
    overflow-y:auto;
  }
  #sidebar.sidebar .brand{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
  }
  #sidebar.sidebar .brand-copy{min-width:0}
  #sidebar.sidebar .brand img{
    width:100%;
    max-width:100%;
    height:auto;
    display:block;
    object-fit:contain;
    object-position:left;
  }
  #sidebar.sidebar .brand h2{
    margin:5px 0 0;
    font-size:22px;
    line-height:1;
    color:#ef2437;
  }
  #sidebar.sidebar .brand p{
    margin:2px 0 0;
    font-size:10px;
    color:#8e9cab;
    line-height:1.4;
  }
  #sidebar.sidebar nav{
    display:grid;
    gap:5px;
    margin-top:18px;
  }
  #sidebar.sidebar nav a{
    min-height:40px;
    padding:0 11px;
    border-radius:6px;
    border:1px solid transparent;
    display:grid;
    grid-template-columns:28px 1fr;
    align-items:center;
    gap:8px;
    color:#f7fbff;
    text-decoration:none;
    font-size:11px;
    transition:.2s ease;
  }
  #sidebar.sidebar nav a span{font-size:16px;line-height:1}
  #sidebar.sidebar nav a b{font-weight:600;line-height:1.3}
  #sidebar.sidebar nav a:hover{
    background:rgba(255,255,255,.03);
    border-color:#25445e;
  }
  #sidebar.sidebar nav a.active{
    background:linear-gradient(90deg,#9f111e,#461019 80%,transparent);
    border-color:#ff3044;
    box-shadow:0 0 14px rgba(239,36,55,.16);
  }
  #sidebar.sidebar .ai-card{
    margin-top:auto;
    border:1px solid #6546ba;
    border-left:2px solid #ef2437;
    border-radius:7px;
    padding:12px 11px;
    background:linear-gradient(135deg,rgba(79,42,142,.14),rgba(239,36,55,.03));
  }
  #sidebar.sidebar .ai-head{
    display:flex;
    justify-content:space-between;
    gap:8px;
  }
  #sidebar.sidebar .ai-head strong{font-size:11px}
  #sidebar.sidebar .ai-head em{
    font-style:normal;
    font-size:8px;
    color:#c3b4ff;
    border:1px solid #584287;
    border-radius:4px;
    padding:2px 6px;
  }
  #sidebar.sidebar .ai-card p{
    margin:7px 0;
    font-size:9px;
    line-height:1.55;
    color:#8e9cab;
  }
  #sidebar.sidebar .ai-card button,
  #sidebar.sidebar .collapse{
    width:100%;
    min-height:33px;
    border:1px solid #25445e;
    border-radius:6px;
    background:#07131e;
    color:#f7fbff;
    font-size:9px;
  }
  #sidebar.sidebar .profile{
    margin-top:12px;
    border:1px solid #18344c;
    border-radius:7px;
    padding:10px;
    background:#07131e;
    display:grid;
    grid-template-columns:36px 1fr 12px;
    gap:8px;
    align-items:center;
  }
  #sidebar.sidebar .avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    display:grid;
    place-items:center;
    background:#1b3143;
    border:1px solid #3d607c;
    font-size:12px;
    font-weight:700;
  }
  #sidebar.sidebar .profile strong{font-size:10px}
  #sidebar.sidebar .profile span{
    display:block;
    margin-top:2px;
    font-size:8px;
    color:#8e9cab;
  }
  #sidebar.sidebar .profile i{font-style:normal;color:#8e9cab}
  #sidebar.sidebar .profile small{
    display:block;
    font-size:7.4px;
    color:#8e9cab;
  }
  #sidebar.sidebar .profile small:nth-of-type(1){grid-column:1 / 3}
  #sidebar.sidebar .profile .online{color:#25dc68}
  #sidebar.sidebar .collapse{
    margin-top:11px;
    padding:0 10px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    color:#8e9cab;
  }
  #sidebar.sidebar .sidebar-close{
    width:32px;
    height:32px;
    border:1px solid #25445e;
    border-radius:6px;
    background:#07131e;
    color:#78ade3;
    display:grid;
    place-items:center;
    flex-shrink:0;
    cursor:pointer;
  }
  @media (min-width:1024px){
    #sidebar.sidebar .sidebar-close{display:none}
  }
  @media (max-width:1023px){
    #sidebar.sidebar{box-shadow:0 24px 60px rgba(0,0,0,.45)}
  }
</style>
<?php endif; ?>

<aside
  id="sidebar"
  class="<?php echo $is_newui_sidebar ? 'sidebar' : 'sidebar fixed left-0 top-0 z-50 h-full w-64 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0'; ?>"
>
  <div class="brand">
    <div class="brand-copy">
      <img src="<?php echo $escape($logo_src); ?>" alt="Beatle Analytics">
      <h2 style=" text-align: center;">OBHS</h2>
      <p style="text-align: center;">Outbound Housekeeping System</p>
    </div>
    <?php if (!$is_newui_sidebar): ?>
    <button id="closeSidebar" class="sidebar-close" type="button" aria-label="Close sidebar">✕</button>
    <?php endif; ?>
  </div>

  <nav>
    <?php foreach ($sidebar_links as $link): ?>
    <?php
    $href = (string) $link['href'];
    $href_path = (string) parse_url($href, PHP_URL_PATH);
    $href_page = basename($href_path !== '' ? $href_path : $href);
    $is_active = $href_page !== '' && $href_page === $current_page;
    ?>
    <a href="<?php echo $escape($href); ?>"<?php echo $is_active ? ' class="active"' : ''; ?>>
      <span><?php echo $escape($link['icon']); ?></span>
      <b><?php echo $escape($link['label']); ?></b>
    </a>
    <?php endforeach; ?>
  </nav>

  <section class="ai-card">
    <div class="ai-head"><strong>AI ASSISTANT</strong><em>Beta</em></div>
    <p>Hi <?php echo $escape($resolved_user_display); ?>!</p>
    <p><?php echo $escape($resolved_ai_message); ?></p>
    <button type="button">Ask AI Assistant <span>→</span></button>
  </section>

  <section class="profile">
    <div class="avatar"><?php echo $escape($resolved_avatar_text); ?></div>
    <div><strong><?php echo $escape($resolved_user_display); ?></strong><span><?php echo $escape($resolved_profile_label); ?></span></div>

    <small>Station: <?php echo $escape($resolved_station_name !== '' ? $resolved_station_name : '--'); ?></small>
    <!--<small class="online">User ID: <?php echo $escape($resolved_user_id); ?></small>-->
  </section>

  <!--<button type="button" class="collapse"><span>≪</span><b>Collapse Menu</b><span>⌁</span></button>-->
</aside>
