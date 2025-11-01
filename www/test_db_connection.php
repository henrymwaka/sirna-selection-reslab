<?php
declare(strict_types=1);
/**
 * Diagnostic: verify DB + .env + PDO connection chain
 * Use ONLY for testing (delete after successful check)
 */

require_once dirname(__DIR__) . '/config.php';
header('Content-Type: text/plain');

echo "=== SI RNA DB CONNECTION TEST ===\n";

try {
    $env = (new ReflectionFunction('load_env'))->invoke();
    echo "Loaded .env successfully.\n";
    echo "Database credentials:\n";
    echo "  Host: " . $env['db']['host'] . "\n";
    echo "  Name: " . $env['db']['name'] . "\n";
    echo "  User: " . $env['db']['user'] . "\n";
    echo "  Charset: " . $env['db']['charset'] . "\n\n";

    $pdo = db_pdo();
    echo "✅ PDO connection established.\n";

    // Simple query test
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables in database:\n";
    foreach ($tables as $t) echo " - $t\n";

    echo "\n✅ Database connectivity test passed successfully.\n";

} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
