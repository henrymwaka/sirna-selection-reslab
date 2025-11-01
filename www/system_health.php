<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

session_start();
if (empty($_SESSION['pId']) || empty($_SESSION['user_login'])) {
    header("Location: home.php");
    exit;
}

$pId  = (int)$_SESSION['pId'];
$user = $_SESSION['user_login'];

$pdo = null;
$db_status = '❌';
$db_msg = '';
$checks = [];
$metrics = [];

// -----------------------------------------------------------------------------
// 1. Database connectivity + latency
// -----------------------------------------------------------------------------
try {
    $start = microtime(true);
    $pdo = db_pdo();
    $pdo->query("SELECT 1");
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    $db_status = '✅';
    $db_msg = "Connected in {$elapsed} ms";
    $metrics['DB Ping'] = "{$elapsed} ms";
} catch (Throwable $e) {
    $db_msg = 'Connection failed: ' . $e->getMessage();
}

// -----------------------------------------------------------------------------
// 2. Table existence + record counts
// -----------------------------------------------------------------------------
$expectedTables = ['accounts','permissions','emails','counts','authentication'];
$tableResults = [];
if ($pdo) {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expectedTables as $t) {
            if (in_array($t, $tables)) {
                $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                $tableResults[$t] = ['status'=>'✅','count'=>$count];
            } else {
                $tableResults[$t] = ['status'=>'❌','count'=>0];
            }
        }
    } catch (Throwable $e) {
        $checks[] = ['Database Tables','❌','Error fetching table info: '.$e->getMessage()];
    }
}

// -----------------------------------------------------------------------------
// 3. Log directory
// -----------------------------------------------------------------------------
$logDir = '/home/shaykins/Projects/siRNA/logs';
$logDirStatus = is_dir($logDir) ? '✅' : '❌';
$logWritable = is_writable($logDir) ? '✅' : '❌';

// -----------------------------------------------------------------------------
// 4. reCAPTCHA Config
// -----------------------------------------------------------------------------
$recaptcha = recaptcha_config();
$siteKey = $recaptcha['site_key'] ?? '';
$secretKey = $recaptcha['secret_key'] ?? '';
$recaptcha_status = (!empty($siteKey) && !empty($secretKey)) ? '✅' : '❌';

// -----------------------------------------------------------------------------
// 5. PHP Version & Extensions
// -----------------------------------------------------------------------------
$phpVersion = PHP_VERSION;
$required = ['pdo_mysql','openssl','curl','json'];
$extStatus = [];
foreach ($required as $e) $extStatus[$e] = extension_loaded($e) ? '✅' : '❌';

// -----------------------------------------------------------------------------
// 6. Disk usage
// -----------------------------------------------------------------------------
$total = @disk_total_space("/");
$free  = @disk_free_space("/");
$used  = $total - $free;
$usedPct = $total ? round(($used/$total)*100,1) : 0;
$metrics['Disk Usage'] = "$usedPct%";

// -----------------------------------------------------------------------------
// 7. PHP-FPM uptime (approx)
// -----------------------------------------------------------------------------
$uptime = @shell_exec("ps -eo comm,etimes | grep php-fpm | awk '{print \$2}' | head -1");
$uptimeH = $uptime ? round(((int)$uptime)/3600,1).' hrs' : 'N/A';
$metrics['PHP-FPM Uptime'] = $uptimeH;

// -----------------------------------------------------------------------------
// 8. Memory usage
// -----------------------------------------------------------------------------
$mem = @shell_exec("free -m | grep Mem | awk '{print \$3\"/\"\$2\" MB\"}'");
$metrics['Memory Usage'] = trim($mem) ?: 'N/A';

// -----------------------------------------------------------------------------
// 9. Hostname and Time
// -----------------------------------------------------------------------------
$metrics['Hostname'] = gethostname();
$metrics['Server Time'] = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>System Health | siRNA Portal</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:20px;}
h2{color:#003366;text-align:center;}
.section{margin-top:40px;}
table{border-collapse:collapse;width:90%;margin:10px auto;background:#fff;box-shadow:0 0 8px rgba(0,0,0,0.1);}
th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:14px;}
th{background:#003366;color:#fff;}
tr:nth-child(even){background:#f9f9f9;}
.status-ok{color:green;font-weight:bold;}
.status-fail{color:red;font-weight:bold;}
.status-warn{color:orange;font-weight:bold;}
.progress{width:100%;background:#eee;border-radius:4px;overflow:hidden;height:16px;}
.bar{height:100%;background:#0074D9;}
.footer{text-align:center;font-size:12px;color:#666;margin-top:40px;}
</style>
</head>
<body>

<h2>🩺 System Health & Diagnostics</h2>

<div class="section">
<h3>Database Connection</h3>
<table>
<tr><th>Status</th><th>Message</th></tr>
<tr>
<td class="<?= $db_status==='✅'?'status-ok':'status-fail' ?>"><?= $db_status ?></td>
<td><?= htmlspecialchars($db_msg) ?></td>
</tr>
</table>
</div>

<div class="section">
<h3>Database Tables</h3>
<table>
<tr><th>Table</th><th>Status</th><th>Records</th></tr>
<?php foreach ($tableResults as $t=>$info): ?>
<tr>
<td><?= htmlspecialchars($t) ?></td>
<td class="<?= $info['status']==='✅'?'status-ok':'status-fail' ?>"><?= $info['status'] ?></td>
<td><?= htmlspecialchars($info['count']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="section">
<h3>Log Directory</h3>
<table>
<tr><th>Check</th><th>Status</th><th>Details</th></tr>
<tr><td>Directory Exists</td><td class="<?= $logDirStatus==='✅'?'status-ok':'status-fail' ?>"><?= $logDirStatus ?></td><td><?= htmlspecialchars($logDir) ?></td></tr>
<tr><td>Writable</td><td class="<?= $logWritable==='✅'?'status-ok':'status-fail' ?>"><?= $logWritable ?></td><td>Permissions OK</td></tr>
</table>
</div>

<div class="section">
<h3>Configuration</h3>
<table>
<tr><th>Component</th><th>Status</th><th>Details</th></tr>
<tr><td>reCAPTCHA</td><td class="<?= $recaptcha_status==='✅'?'status-ok':'status-fail' ?>"><?= $recaptcha_status ?></td><td><?= $recaptcha_status==='✅'?'Keys loaded':'Keys missing' ?></td></tr>
<tr><td>PHP Version</td><td class="<?= version_compare($phpVersion,'8.0','>=')?'status-ok':'status-warn' ?>"><?= $phpVersion ?></td><td><?= version_compare($phpVersion,'8.0','>=')?'OK':'Consider upgrade' ?></td></tr>
<?php foreach ($extStatus as $ext=>$st): ?>
<tr><td><?= htmlspecialchars($ext) ?></td><td class="<?= $st==='✅'?'status-ok':'status-fail' ?>"><?= $st ?></td><td><?= $st==='✅'?'Loaded':'Missing' ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="section">
<h3>Server Metrics</h3>
<table>
<tr><th>Metric</th><th>Value</th></tr>
<?php foreach ($metrics as $k=>$v): ?>
<tr><td><?= htmlspecialchars($k) ?></td><td><?= htmlspecialchars($v) ?></td></tr>
<?php endforeach; ?>
<tr><td>Disk Usage</td>
<td>
<div class="progress"><div class="bar" style="width:<?= $usedPct ?>%;"></div></div>
<?= $usedPct ?>%
</td></tr>
</table>
</div>

<p style="text-align:center;margin-top:20px;">
<a href="dashboard.php">⬅ Back to Dashboard</a>
</p>

<div class="footer">
<p>© 2004 Whitehead Institute for Biomedical Research. Checked <?= date('Y-m-d H:i:s') ?> on <?= htmlspecialchars(gethostname()) ?></p>
</div>
</body>
</html>
