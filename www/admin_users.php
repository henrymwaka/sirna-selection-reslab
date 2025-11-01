<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

// -----------------------------------------------------------------------------
// Secure session setup
// -----------------------------------------------------------------------------
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['pId']) || empty($_SESSION['user_login'])) {
  header("Location: home.php");
  exit;
}

$pId  = (int)$_SESSION['pId'];
$user = htmlspecialchars($_SESSION['user_login']);

// -----------------------------------------------------------------------------
// Database fetches
// -----------------------------------------------------------------------------
$pdo = db_pdo();

$stmt = $pdo->prepare("
  SELECT e.email, c.year, c.month, c.day, p.admin
  FROM emails e
  JOIN counts c ON e.pId = c.pId
  JOIN permissions p ON e.pId = p.pId
  WHERE e.pId = ? LIMIT 1
");
$stmt->execute([$pId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$email   = $row['email'] ?? 'Not set';
$regDate = isset($row['year']) ? sprintf("%02d-%02d-%04d", $row['day'], $row['month'], $row['year']) : 'Unknown';
$isAdmin = !empty($row['admin']);

// -----------------------------------------------------------------------------
// Logging
// -----------------------------------------------------------------------------
function log_event(string $msg): void {
  $file = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
  if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
  $ts = date('Y-m-d H:i:s');
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  @file_put_contents($file, "[$ts][$ip] $msg\n", FILE_APPEND);
}
log_event("Dashboard accessed by $user (Admin=" . ($isAdmin ? "Yes" : "No") . ")");

// -----------------------------------------------------------------------------
// Load recent login history
// -----------------------------------------------------------------------------
$logFile = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
$recentLogins = [];
if (is_readable($logFile)) {
  $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $userLines = array_filter($lines, fn($l) => str_contains($l, "LOGIN") && str_contains($l, $user));
  $recentLogins = array_slice(array_reverse($userLines), 0, 5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Dashboard | WI siRNA Selection Program</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  background-color: #f4f6f8;
}
.sidebar {
  height: 100vh;
  width: 240px;
  position: fixed;
  top: 0; left: 0;
  background-color: #003366;
  color: white;
  display: flex;
  flex-direction: column;
  padding-top: 20px;
  overflow-y: auto;
}
.sidebar h2 {
  text-align: center;
  font-size: 18px;
  margin-bottom: 15px;
  color: #fff;
}
.sidebar a {
  color: white;
  padding: 10px 15px;
  text-decoration: none;
  display: block;
}
.sidebar a:hover {
  background-color: #005fa3;
}
.sidebar hr {
  border: 0; border-top: 1px solid #1f4f88; margin: 10px 0;
}
.main {
  margin-left: 240px;
  padding: 20px;
  text-align: center;
}
.card {
  background-color: white;
  border-radius: 6px;
  padding: 20px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  display: inline-block;
  width: 70%;
  text-align: left;
}
.log-box {
  background:#f4f4f4;
  border-radius:4px;
  padding:10px;
  font-size:13px;
  max-height:150px;
  overflow-y:auto;
  white-space:pre-wrap;
}
.footer {
  font-size: 12px;
  color: #777;
  margin-top: 40px;
}
.logout-btn {
  background-color:#cc0000;
  color:white;
  border:none;
  padding:10px 15px;
  border-radius:4px;
  cursor:pointer;
}
.logout-btn:hover { background-color:#990000; }
.admin-badge {
  display:inline-block;
  background:#0074D9;
  color:#fff;
  font-size:12px;
  padding:2px 6px;
  border-radius:4px;
  margin-left:5px;
}
.section-title {
  color:#003366;
  font-size:18px;
  text-align:center;
  margin-top:30px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>siRNA Portal</h2>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="#profile">👤 Profile</a>
  <a href="#activity">🕓 Activity Log</a>
  <a href="change_password.php">🔑 Change Password</a>
  <hr>

  <h3 style="color:#ccc;font-size:13px;text-align:center;">🧬 Analysis Tools</h3>
  <a href="siRNA.cgi">🎯 siRNA Design Tool</a>
  <a href="siRNA_search.cgi">🔍 Search siRNA Results</a>
  <a href="show_result.cgi">📈 View Result Files</a>
  <a href="show_oligo.cgi">🧫 Oligo Details</a>
  <a href="seed2gene.cgi">🌱 Seed→Gene Mapping</a>
  <a href="reference.php">📚 Reference Database</a>
  <hr>

  <?php if ($isAdmin): ?>
  <h3 style="color:#ccc;font-size:13px;text-align:center;">⚙️ Admin Tools</h3>
  <a href="admin_users.php">👥 Manage Users</a>
  <a href="system_health.php">📊 System Health</a>
  <a href="audit_logs.php">🧾 Audit Logs</a>
  <a href="db_viewer.php">📂 Database Browser</a>
  <hr>
  <?php endif; ?>

  <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main content -->
<div class="main">
  <img src="keep/header_wi_01.jpg" alt="Header" style="max-width:100%; height:auto; margin-bottom:20px;">

  <div class="card" id="profile">
    <h2>Welcome, <?php echo $user; ?>
      <?php if ($isAdmin): ?><span class="admin-badge">ADMIN</span><?php endif; ?>
    </h2>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    <p><strong>Registration Date:</strong> <?php echo htmlspecialchars($regDate); ?></p>
    <p style="color:green;">✅ Session active and verified.</p>
  </div>

  <div class="card" id="activity" style="margin-top:20px;">
    <h3>Recent Login Activity</h3>
    <div class="log-box">
      <?php
      if ($recentLogins) {
        foreach ($recentLogins as $line) echo htmlspecialchars($line) . "\n";
      } else echo "No recent login entries found.";
      ?>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <h3>Quick Access</h3>
    <p>Select a tool to begin your analysis:</p>
    <p>
      <a href="siRNA.cgi">🎯 Run siRNA Design</a> |
      <a href="siRNA_search.cgi">🔍 Search Results</a> |
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
