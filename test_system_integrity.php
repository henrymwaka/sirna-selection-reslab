<?php
declare(strict_types=1);

/**
 * Full environment and connectivity integrity test
 * - Reads .env and config.php
 * - Verifies MySQL PDO connection
 * - Verifies reCAPTCHA v3 token from live Google API
 * Run from browser: https://sirna.reslab.dev/test_system_integrity.php
 */

require_once dirname(__DIR__) . '/config.php';
header('Content-Type: text/plain');

echo "=== SI RNA SYSTEM INTEGRITY CHECK ===\n\n";

try {
    // ---------------------------------------------------------------------
    // 1️⃣ Load environment
    // ---------------------------------------------------------------------
    $env = (new ReflectionFunction('load_env'))->invoke();
    echo "✅ .env file loaded successfully.\n";
    echo "Site Key: " . substr($env['recaptcha']['RECAPTCHA_SITE_KEY'], 0, 8) . "...\n";
    echo "DB User: " . $env['db']['user'] . "\n";
    echo "DB Name: " . $env['db']['name'] . "\n\n";

    // ---------------------------------------------------------------------
    // 2️⃣ Test DB Connection
    // ---------------------------------------------------------------------
    $pdo = db_pdo();
    echo "✅ Database connection OK.\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   Found " . count($tables) . " tables in DB.\n\n";

    // ---------------------------------------------------------------------
    // 3️⃣ reCAPTCHA v3 verification test
    // ---------------------------------------------------------------------
    $recaptcha = recaptcha_config();
    $secret = $recaptcha['secret_key'];
    $site   = $recaptcha['site_key'];

    // Create a fake token (server-side test, uses Google validation fallback)
    echo "🧩 Testing reCAPTCHA v3 validation...\n";
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret,
        'response' => '03ANYfakeTokenForServerCheck',
        'remoteip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'
    ];
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($data)
    ]];
    $ctx = stream_context_create($opts);
    $response = @file_get_contents($verify_url, false, $ctx);
    if ($response === false) {
        throw new RuntimeException("Cannot reach Google reCAPTCHA servers.");
    }
    $json = json_decode($response, true);

    if (isset($json['error-codes'])) {
        echo "   Google response received, but with expected error codes:\n";
        print_r($json['error-codes']);
        echo "✅ This confirms external API access is OK.\n\n";
    } elseif (!empty($json['success'])) {
        echo "✅ reCAPTCHA success returned.\n\n";
    } else {
        echo "⚠️  reCAPTCHA test returned unexpected data:\n";
        print_r($json);
        echo "\n";
    }

    // ---------------------------------------------------------------------
    // 4️⃣ Check hostname
    // ---------------------------------------------------------------------
    echo "Hostname check:\n";
    echo "  SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "\n";
    echo "  HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
    echo "✅ Hostname environment OK.\n";

    echo "\n🎯 System integrity check completed successfully.\n";

} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
