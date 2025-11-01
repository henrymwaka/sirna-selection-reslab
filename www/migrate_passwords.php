<?php
/**
 * migrate_passwords.php — One-time password hashing script
 *
 * Converts legacy plaintext passwords in the `accounts` table
 * to secure bcrypt hashes using PHP’s password_hash().
 *
 * Maintainer: Dr. Henry Mwaka (shaykins)
 * Created: 2025-10-27
 */

//require_once __DIR__ . '/../config/siRNA_env.pm.php'; // optional PHP config if you have one

// ---- CONFIG ----
$DB_HOST = 'localhost';
$DB_USER = 'sirna_user';
$DB_PASS = 'sirna_pass';
$DB_NAME = 'sirna';
$LOG_FILE = __DIR__ . '/logs/password_migration.log';

// ---- INIT ----
date_default_timezone_set('Africa/Kampala');
$log = fopen($LOG_FILE, 'a') or die("Cannot open log file.");

function log_line($msg) {
    global $log;
    fwrite($log, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n");
}

// ---- CONNECT ----
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    log_line("ERROR: DB connection failed ({$mysqli->connect_error})");
    die("Database connection failed. Check logs.");
}

// ---- FETCH USERS ----
$res = $mysqli->query("SELECT login, password FROM accounts");
if (!$res) {
    log_line("ERROR: Query failed: " . $mysqli->error);
    die("Query error. Check logs.");
}

$count = 0;
while ($row = $res->fetch_assoc()) {
    $login = $row['login'];
    $pass  = $row['password'];

    // Skip if already hashed (bcrypt hashes start with $2y$)
    if (preg_match('/^\$2y\$/', $pass)) {
        log_line("SKIP [$login] — already hashed");
        continue;
    }

    // Hash plaintext
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    if (!$hash) {
        log_line("ERROR [$login] — could not hash password");
        continue;
    }

    // Update DB
    $stmt = $mysqli->prepare("UPDATE accounts SET password=? WHERE login=?");
    $stmt->bind_param('ss', $hash, $login);
    if ($stmt->execute()) {
        log_line("OK [$login] — password migrated");
        $count++;
    } else {
        log_line("FAIL [$login] — update error: " . $stmt->error);
    }
    $stmt->close();
}

log_line("Migration completed: $count accounts updated.");
fclose($log);

echo "<h3>Password migration complete.</h3>";
echo "<p>Check the log file at <code>$LOG_FILE</code> for details.</p>";
?>
