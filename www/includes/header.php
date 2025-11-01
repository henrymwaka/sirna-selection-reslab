<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once dirname(__DIR__) . '/config.php';

$pId  = $_SESSION['pId'] ?? 0;
$user = $_SESSION['user_login'] ?? 'Guest';
$MySessionID = $_SESSION['MySessionID'] ?? ('S_' . time() . '_' . bin2hex(random_bytes(4)));

function cgi_link(string $path, string $MySessionID): string {
    $sep = (strpos($path, '?') === false) ? '?' : '&';
    return htmlspecialchars(
        $path . $sep . 'MySessionID=' . urlencode($MySessionID) .
        '&tasto=' . urlencode($MySessionID),
        ENT_QUOTES,
        'UTF-8'
    );
}

$isAdmin = false;
try {
    $pdo = db_pdo();
    if ($pId) {
        $stmt = $pdo->prepare("SELECT admin FROM permissions WHERE pId=? LIMIT 1");
        $stmt->execute([$pId]);
        $isAdmin = (bool)$stmt->fetchColumn();
    }
} catch (Throwable $e) {
    $isAdmin = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>siRNA Portal</title>
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f8;
  }
  /* Sidebar */
  .sidebar {
    height: 100vh;
    width: 240px;
    position: fixed;
    top: 0;
    left: 0;
    background: #003366;
    color: #fff;
    display: flex;
    flex-direction: column;
    padding-top: 20px;
    overflow-y: auto;
    transition: transform 0.3s ease;
  }
  .sidebar.hidden {
    transform: translateX(-240px);
  }
  .sidebar h2 {
    text-align: center;
    font-size: 18px;
    margin-bottom: 15px;
    color: #fff;
  }
  .sidebar a {
    color: #fff;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
  }
  .sidebar a:hover {
    background: #005fa3;
  }
  .sidebar hr {
    border: 0;
    border-top: 1px solid #1f4f88;
    margin: 10px 0;
  }
  .main {
    margin-left: 240px;
    padding: 20px;
    transition: margin-left 0.3s ease;
  }
  .main.shifted {
    margin-left: 0;
  }
  .menu-toggle {
    display: none;
    background: #003366;
    color: #fff;
    border: none;
    font-size: 22px;
    padding: 10px 15px;
    position: fixed;
    top: 10px;
    left: 10px;
    cursor: pointer;
    z-index: 1001;
  }
  .section-caption {
    color: #ccc;
    font-size: 13px;
    text-align: center;
    margin: 6px 0 2px;
  }
  .admin-badge {
    background: #0074D9;
    color: #fff;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 5px;
  }

  /* Responsive behavior */
  @media (max-width: 768px) {
    .menu-toggle { display: block; }
    .sidebar {
      transform: translateX(-240px);
    }
    .sidebar.show {
      transform: translateX(0);
      z-index: 1000;
    }
    .main {
      margin-left: 0;
      padding-top: 60px;
    }
  }
</style>
<script>
function toggleMenu() {
  const sidebar = document.querySelector('.sidebar');
  sidebar.classList.toggle('show');
}
</script>
</head>
<body>

<button class="menu-toggle" onclick="toggleMenu()">☰</button>

<div class="sidebar">
  <h2>siRNA Portal</h2>

  <a href="/dashboard.php">🏠 Dashboard</a>
  <a href="/profile.php">👤 Profile</a>
  <a href="/activity.php">🕓 Activity Log</a>
  <a href="/change_password.php">🔑 Change Password</a>
  <hr>

  <div class="section-caption">🧬 siRNA Tools</div>
  <a href="<?= cgi_link('siRNA.cgi', $MySessionID) ?>">🎯 Design New siRNA</a>
  <a href="<?= cgi_link('siRNA_search.cgi', $MySessionID) ?>">🔍 Search Database</a>
  <a href="<?= cgi_link('show_result.cgi', $MySessionID) ?>">📈 View Results</a>
  <a href="<?= cgi_link('show_oligo.cgi', $MySessionID) ?>">🧫 Oligo Info</a>
  <a href="<?= cgi_link('seed2gene.cgi', $MySessionID) ?>">🌱 Seed → Gene Mapping</a>
  <a href="/reference.php">📚 Reference Data</a>
  <hr>

  <div class="section-caption">📘 Documentation</div>
  <a href="/help.php">❓ Help & Tutorials</a>
  <a href="/docs.php">📖 Algorithm Docs</a>
  <a href="/contact.php">✉️ Contact</a>
  <hr>

  <?php if ($isAdmin): ?>
    <div class="section-caption">⚙️ Admin Tools</div>
    <a href="/admin_users.php">👥 Manage Users</a>
    <a href="/system_health.php">📊 System Health</a>
    <a href="/audit_logs.php">🧾 Audit Logs</a>
    <a href="/db_viewer.php">📂 DB Browser</a>
    <hr>
  <?php endif; ?>

  <a href="/logout.php">🚪 Logout</a>
</div>

<div class="main">
