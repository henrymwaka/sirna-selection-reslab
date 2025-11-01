<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

session_start();
if (empty($_SESSION['pId']) || empty($_SESSION['user_login'])) {
  header("Location: home.php");
  exit;
}

$pId  = (int)$_SESSION['pId'];
$pdo  = db_pdo();
$stmt = $pdo->prepare("SELECT admin FROM permissions WHERE pId=? LIMIT 1");
$stmt->execute([$pId]);
$isAdmin = (bool)$stmt->fetchColumn();
if (!$isAdmin) {
  header("Location: dashboard.php");
  exit;
}

$logDir = '/home/shaykins/Projects/siRNA/logs';
$files = [];
if (is_dir($logDir)) {
  foreach (glob("$logDir/*.log") as $f) {
    $files[basename($f)] = filesize($f);
  }
}

$selected = $_GET['file'] ?? '';
$content = '';
if ($selected && isset($files[$selected])) {
  $path = "$logDir/$selected";
  $content = htmlspecialchars(@file_get_contents($path));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Audit Logs | siRNA Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial;background:#f4f6f8;margin:0;padding:20px;text-align:center;}
select{padding:6px;font-size:14px;}
pre{background:#fff;border:1px solid #ccc;padding:10px;width:90%;margin:auto;max-height:500px;overflow-y:auto;text-align:left;}
a{color:#0074D9;text-decoration:none;}
a:hover{text-decoration:underline;}
</style>
</head>
<body>

<h2>🧾 System Audit Logs</h2>

<form method="GET">
<select name="file" onchange="this.form.submit()">
<option value="">-- Select Log File --</option>
<?php foreach ($files as $f => $size): ?>
<option value="<?= htmlspecialchars($f) ?>" <?= $f===$selected?'selected':'' ?>>
<?= htmlspecialchars($f) ?> (<?= round($size/1024,1) ?> KB)
</option>
<?php endforeach; ?>
</select>
</form>

<?php if ($content): ?>
<pre><?= $content ?></pre>
<?php elseif ($selected): ?>
<p style="color:red;">File not found or unreadable.</p>
<?php endif; ?>

<p style="margin-top:20px;"><a href="dashboard.php">⬅ Back to Dashboard</a></p>

</body>
</html>
