<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

echo "<pre>=== SI RNA SYSTEM INTEGRITY CHECK ===\n\n";

// 1️⃣ Check .env load
try {
    $recaptcha = recaptcha_config();
    echo "✅ .env and reCAPTCHA keys loaded.\n";
    echo "   Site key: " . substr($recaptcha['site_key'], 0, 12) . "...\n";
} catch (Throwable $e) {
    echo "❌ Failed to load .env: " . $e->getMessage() . "\n";
}

// 2️⃣ Check DB connection
try {
    $pdo = db_pdo();
    $stmt = $pdo->query("SELECT NOW() as now");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database connection OK. Server time: {$row['now']}\n";
} catch (Throwable $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// 3️⃣ Check logs directory
$logdir = '/home/shaykins/Projects/siRNA/logs';
if (is_writable($logdir)) {
    echo "✅ Logs directory is writable ($logdir)\n";
} else {
    echo "❌ Logs directory not writable: $logdir\n";
}

echo "\nAll checks complete.\n</pre>";
?>
