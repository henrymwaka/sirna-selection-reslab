<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

// -----------------------------------------------------------------------------
// Session setup (important for Cloudflare / HTTPS persistence)
// -----------------------------------------------------------------------------
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// -----------------------------------------------------------------------------
// Load keys and helpers
// -----------------------------------------------------------------------------
$recaptcha = recaptcha_config();
$siteKey   = $recaptcha['site_key'];
$secretKey = $recaptcha['secret_key'];

// Logging utility
function log_event(string $msg): void {
  $file = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
  if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
  $ts = date('Y-m-d H:i:s');
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  @file_put_contents($file, "[$ts][$ip] $msg\n", FILE_APPEND);
}

// Verify reCAPTCHA
function verify_recaptcha(string $token, string $secret): bool {
  if (empty($token)) return false;
  $url = 'https://www.google.com/recaptcha/api/siteverify';
  $payload = http_build_query([
    'secret' => $secret,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
  ]);
  $res = @file_get_contents($url, false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-type: application/x-www-form-urlencoded\r\n", 'content' => $payload]
  ]));
  $json = json_decode($res ?: '{}', true);
  $ok = ($json['success'] ?? false) && (($json['score'] ?? 0) >= 0.5);
  log_event("reCAPTCHA verify: success=" . ($json['success'] ?? 0) . ", score=" . ($json['score'] ?? 0) . ", result=" . ($ok ? 'OK' : 'FAIL'));
  return $ok;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WI siRNA Selection Program | Login</title>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey; ?>"></script>
<script>
function onLoginSubmit(e) {
  e.preventDefault();
  grecaptcha.ready(function() {
    grecaptcha.execute('<?php echo $siteKey; ?>', {action: 'login'}).then(function(token) {
      document.getElementById('recaptchaToken').value = token;
      document.getElementById('loginForm').submit();
    }).catch(function(err){
      alert('reCAPTCHA failed to load, please refresh and try again.');
      console.error(err);
    });
  });
}
</script>
<style>
body { font-family: Arial, sans-serif; background-color: #f9f9f9; text-align:center; margin:0; padding:0; }
.container { margin-top: 20px; }
input[type=text], input[type=password] {
  padding: 6px; width: 200px; border:1px solid #ccc; border-radius:4px;
}
input[type=submit] {
  background-color:#0074D9; color:#fff; border:none; padding:8px 16px;
  border-radius:4px; cursor:pointer;
}
input[type=submit]:hover { background-color:#005fa3; }
.navbar { background:#003366; color:#fff; padding:10px; }
.navbar a { color:#fff; margin:0 10px; text-decoration:none; font-weight:bold; }
.navbar a:hover { text-decoration:underline; }
.footer { font-size:12px; color:#777; margin-top:40px; }
</style>
</head>
<body>
<img src="keep/header_wi_01.jpg" alt="Header">

<div class="navbar">
  <a href="home.php">Home</a>
  <a href="register.php">Register</a>
  <a href="contact.php">Contact</a>
  <?php if (!empty($_SESSION['user_login'])): ?>
    <a href="logout.php">Logout</a>
  <?php endif; ?>
</div>

<!-- Legacy animation restored -->
<div style="margin-top:20px;">
  <img src="keep/animation.gif" alt="siRNA Animation" height="250">
</div>

<div class="container">
<h2>Login to WI siRNA Selection Program</h2>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $token = $_POST['recaptchaToken'] ?? '';

    if (empty($login) || empty($password)) {
        echo "<p style='color:red;'>Please enter both login and password.</p>";
    } elseif (!verify_recaptcha($token, $secretKey)) {
        echo "<p style='color:red;'>reCAPTCHA verification failed. Please retry.</p>";
    } else {
        try {
            $pdo = db_pdo();
            $stmt = $pdo->prepare("SELECT pId, password FROM accounts WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['pId'] = $user['pId'];
                $_SESSION['user_login'] = $login;
                log_event("LOGIN success for $login");
                header("Location: dashboard.php");
                exit;
            } else {
                log_event("LOGIN failed for $login");
                echo "<p style='color:red;'>Invalid credentials. Please try again.</p>";
            }
        } catch (Throwable $e) {
            log_event("EXCEPTION: " . $e->getMessage());
            echo "<p style='color:red;'>System error. Please contact admin.</p>";
        }
    }
}
?>

<form id="loginForm" method="POST" onsubmit="onLoginSubmit(event)">
  <input type="hidden" id="recaptchaToken" name="recaptchaToken">
  <table align="center" cellpadding="6">
    <tr><td>Login:</td><td><input type="text" name="login" required></td></tr>
    <tr><td>Password:</td><td><input type="password" name="password" required></td></tr>
    <tr><td colspan="2" align="center"><input type="submit" value="Login"></td></tr>
  </table>
</form>

<p style="font-size:13px; color:#666; margin-top:20px;">
Need an account? <a href="register.php">Register here</a>.
</p>
</div>

<div class="footer">
  <p>© 2004 Whitehead Institute for Biomedical Research. All rights reserved.<br>
  Comments and suggestions to: <img src="keep/contact.jpg" alt="Contact"></p>
</div>
</body>
</html>
