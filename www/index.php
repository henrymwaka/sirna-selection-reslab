<?php
declare(strict_types=1);
/**
 * WI siRNA Selection Program – Index Landing Page
 * Author: Dr. Henry Mwaka
 * Version: 2025-10
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/logger.php';

// -------------------------------
// Secure session setup
// -------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// -------------------------------
// Security headers
// -------------------------------
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// -------------------------------
// Redirect if already logged in
// -------------------------------
if (!empty($_SESSION['user_id'])) {
    log_event('access', 'User ID ' . $_SESSION['user_id'] . ' visited index.php (already logged in)');
    header('Location: home.php');
    exit;
}

// -------------------------------
// Load reCAPTCHA keys
// -------------------------------
$recaptcha = recaptcha_config();
$siteKey   = $recaptcha['site_key'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Welcome | WI siRNA Selection Program</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="keep/favicon.ico" type="image/x-icon">
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey; ?>"></script>
<style>
body {
  font-family: Arial, sans-serif;
  background: #f9f9f9;
  text-align: center;
  margin: 0;
  padding: 0;
}
h1 { color: #08437E; margin-top: 40px; }
p { color: #333; font-size: 15px; }
a.button {
  display: inline-block;
  background: #0077cc;
  color: #fff;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: bold;
  margin-top: 20px;
}
a.button:hover { background: #005fa3; }
footer {
  font-size: 12px;
  color: #777;
  margin-top: 40px;
}
</style>
</head>
<body>
<img src="keep/header_wi_01.jpg" alt="Header">
<h1>Welcome to the WI siRNA Selection Program</h1>
<p>
This platform allows users to log in, register new accounts, and access siRNA design and BLAST databases.<br>
Please proceed to log in or create a new account.
</p>

<a href="home.php" class="button">🔐 Login</a>
<a href="register.php" class="button" style="background:#777;">🧾 Register</a>

<footer>
  <br>
  <p>
    © 2004–2025 Whitehead Institute for Biomedical Research.<br>
    Comments and suggestions to: <img src="keep/contact.jpg" alt="Contact"><br>
    <small>Hosted at sirna.reslab.dev</small>
  </p>
</footer>

<script>
grecaptcha.ready(function(){
  grecaptcha.execute('<?php echo $siteKey; ?>',{action:'index'}).then(function(token){
    console.log("reCAPTCHA token (index page):", token);
  });
});
</script>
</body>
</html>
