<?php
declare(strict_types=1);

// Start or resume session if needed
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Log logout event if possible
if (!empty($_SESSION['user_login'])) {
    $file = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
    if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $msg = "[$ts][$ip] LOGOUT by user=" . $_SESSION['user_login'] . "\n";
    @file_put_contents($file, $msg, FILE_APPEND);
}

// Clear all session data
$_SESSION = [];

// Remove session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally destroy the session
session_destroy();

// Redirect back to login
header("Location: home.php");
exit;
?>
