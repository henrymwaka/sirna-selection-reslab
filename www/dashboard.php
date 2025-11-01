<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config.php';

/* ---------- Secure session ---------- */
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['pId']) || empty($_SESSION['user_login'])) {
  header("Location: home.php");
  exit;
}

$pId  = (int) $_SESSION['pId'];
$user = (string) $_SESSION['user_login'];

/* ---------- Perl bridge: generate valid MySessionID ---------- */
$tmpDir = '/home/shaykins/Projects/siRNA/www/tmp';
if (!is_dir($tmpDir)) {
  @mkdir($tmpDir, 0775, true);
  @chown($tmpDir, 'www-data');
  @chgrp($tmpDir, 'www-data');
}

if (empty($_SESSION['MySessionID'])) {
  $sid = 'S_' . time() . '_' . bin2hex(random_bytes(4));
  $_SESSION['MySessionID'] = $sid;
  $sessionFile = $tmpDir . '/' . $sid;
  @file_put_contents($sessionFile, "Session initialized for {$user}\n");
  @chmod($sessionFile, 0664);
}
$MySessionID = urlencode($_SESSION['MySessionID']);

/* ---------- DB fetch ---------- */
$pdo = db_pdo();
$stmt = $pdo->prepare("
  SELECT
    COALESCE(e.email, '')   AS email,
    COALESCE(c.year,  0)    AS y,
    COALESCE(c.month, 0)    AS m,
    COALESCE(c.day,   0)    AS d,
    COALESCE(p.admin, 0)    AS is_admin
  FROM accounts a
  LEFT JOIN emails      e ON a.pId = e.pId
  LEFT JOIN counts      c ON a.pId = c.pId
  LEFT JOIN permissions p ON a.pId = p.pId
  WHERE a.pId = ?
  LIMIT 1
");
$stmt->execute([$pId]);
$info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
  'email'    => '',
  'y'        => 0,
  'm'        => 0,
  'd'        => 0,
  'is_admin' => 0,
];

$email   = $info['email'] ?: 'Not set';
$regDate = ($info['y'] && $info['m'] && $info['d'])
  ? sprintf("%02d-%02d-%04d", (int)$info['d'], (int)$info['m'], (int)$info['y'])
  : 'Unknown';
$isAdmin = ((int)$info['is_admin']) === 1;

/* ---------- Logging ---------- */
function dash_log(string $msg): void {
  $file = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
  if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
  $ts = date('Y-m-d H:i:s');
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  @file_put_contents($file, "[$ts][$ip] $msg\n", FILE_APPEND);
}
dash_log("DASHBOARD view by {$user} (pId={$GLOBALS['pId']}, admin=" . ($isAdmin ? '1' : '0') . ")");

/* ---------- Recent login extract ---------- */
$recentLogins = [];
$logFile = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
if (is_readable($logFile)) {
  $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  foreach (array_reverse($lines) as $line) {
    if (strpos($line, 'LOGIN') !== false && strpos($line, $user) !== false) {
      $recentLogins[] = $line;
      if (count($recentLogins) >= 5) break;
    }
  }
}

/* ---------- Helper: make correct CGI links ---------- */
function cgi_link(string $path, string $MySessionID): string {
  $sep = (strpos($path, '?') === false) ? '?' : '&';
  // Pass both MySessionID (new) and tasto (legacy) for backward compatibility
  return htmlspecialchars(
    $path . $sep .
    'MySessionID=' . urlencode($MySessionID) .
    '&tasto=' . urlencode($MySessionID),
    ENT_QUOTES,
    'UTF-8'
  );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Dashboard | WI siRNA Selection Program</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { margin:0; font-family:Arial, sans-serif; background:#f4f6f8; }
  .sidebar {
    height:100vh; width:240px; position:fixed; top:0; left:0;
    background:#003366; color:#fff; display:flex; flex-direction:column;
    padding-top:20px; overflow-y:auto;
  }
  .sidebar h2 { text-align:center; font-size:18px; margin-bottom:15px; color:#fff; }
  .sidebar a { color:#fff; padding:10px 15px; text-decoration:none; display:block; }
  .sidebar a:hover { background:#005fa3; }
  .sidebar hr { border:0; border-top:1px solid #1f4f88; margin:10px 0; }
  .main { margin-left:240px; padding:20px; text-align:center; }
  .card {
    background:#fff; border-radius:6px; padding:20px;
    box-shadow:0 0 10px rgba(0,0,0,0.1); display:inline-block; width:70%; text-align:left;
  }
  .log-box {
    background:#f4f4f4; border-radius:4px; padding:10px; font-size:13px;
    max-height:150px; overflow-y:auto; white-space:pre-wrap;
  }
  .footer { font-size:12px; color:#777; margin-top:40px; }
  .logout-btn { background:#cc0000; color:#fff; border:none; padding:10px 15px; border-radius:4px; cursor:pointer; }
  .logout-btn:hover { background:#990000; }
  .admin-badge { display:inline-block; background:#0074D9; color:#fff; font-size:12px; padding:2px 6px; border-radius:4px; margin-left:5px; }
  .section-caption { color:#ccc; font-size:13px; text-align:center; margin:6px 0 2px; }
</style>
</head>
<body>

<div class="sidebar">
  <h2>siRNA Portal</h2>

  <!-- Core navigation -->
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="#profile">👤 Profile</a>
  <a href="#activity">🕓 Activity Log</a>
  <a href="change_password.php">🔑 Change Password</a>
  <hr>

  <!-- siRNA Tools Section -->
  <div class="section-caption">🧬 siRNA Analysis Tools</div>
  <a href="<?= cgi_link('siRNA.cgi', $MySessionID) ?>">🎯 Design New siRNA</a>
  <a href="<?= cgi_link('siRNA_search.cgi', $MySessionID) ?>">🔍 Search siRNA Database</a>
  <a href="<?= cgi_link('show_result.cgi', $MySessionID) ?>">📈 View Design Results</a>
  <a href="<?= cgi_link('show_oligo.cgi', $MySessionID) ?>">🧫 Oligo Information</a>
  <a href="<?= cgi_link('seed2gene.cgi', $MySessionID) ?>">🌱 Seed → Gene Mapping</a>
  <a href="reference.php">📚 Reference Database</a>
  <hr>

  <!-- New modern additions -->
  <div class="section-caption">📘 Documentation & Support</div>
  <a href="help.php">❓ Help & Tutorials</a>
  <a href="docs.php">📖 API & Algorithm Docs</a>
  <a href="contact.php">✉️ Contact Support</a>
  <hr>

  <?php if ($isAdmin): ?>
    <div class="section-caption">⚙️ Admin Tools</div>
    <a href="admin_users.php">👥 Manage Users</a>
    <a href="system_health.php">📊 System Health</a>
    <a href="audit_logs.php">🧾 Audit Logs</a>
    <a href="db_viewer.php">📂 Database Browser</a>
    <hr>
  <?php endif; ?>

  <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">
  <img src="keep/header_wi_01.jpg" alt="Header" style="max-width:100%; height:auto; margin-bottom:20px;">

  <div class="card" id="profile">
    <h2>Welcome, <?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?>
      <?php if ($isAdmin): ?><span class="admin-badge">ADMIN</span><?php endif; ?>
    </h2>
    <p><strong>Email:</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Registration Date:</strong> <?= htmlspecialchars($regDate, ENT_QUOTES, 'UTF-8') ?></p>
    <p style="color:green;">✅ Session active and linked to Perl.</p>
  </div>

  <div class="card" id="activity" style="margin-top:20px;">
    <h3>Recent Login Activity</h3>
    <div class="log-box">
      <?php
        if (!empty($recentLogins)) {
          foreach ($recentLogins as $line) {
            echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
          }
        } else {
          echo "No recent login entries found.";
        }
      ?>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <h3>Quick Access</h3>
    <p>Select a tool to begin your analysis:</p>
    <p>
      <a href="<?= cgi_link('siRNA.cgi', $MySessionID) ?>">🎯 Run siRNA Design</a> |
      <a href="<?= cgi_link('siRNA_search.cgi', $MySessionID) ?>">🔍 Search Results</a> |
      <a href="reference.php">📚 Reference Data</a>
    </p>
  </div>

  <div style="margin-top:30px;">
    <form method="POST" action="logout.php">
      <button type="submit" class="logout-btn">Logout Securely</button>
    </form>
  </div>

  <div class="footer">
    <p>© 2004 Whitehead Institute for Biomedical Research. All rights reserved.<br>
    Comments and suggestions to: <img src="keep/contact.jpg" alt="Contact"></p>
  </div>
</div>

</body>
</html>
