<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

// -----------------------------------------------------------------------------
// Secure session
// -----------------------------------------------------------------------------
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['pId']) || empty($_SESSION['user_login'])) {
  header("Location: home.php");
  exit;
}

$pId   = (int) $_SESSION['pId'];
$login = htmlspecialchars($_SESSION['user_login']);

// -----------------------------------------------------------------------------
// Load reCAPTCHA keys
// -----------------------------------------------------------------------------
$recaptcha = recaptcha_config();
$siteKey   = $recaptcha['site_key'];
$secretKey = $recaptcha['secret_key'];

// Logging helper
function log_event(string $msg): void {
  $file = '/home/shaykins/Projects/siRNA/logs/login_audit.log';
  if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
  $ts = date('Y-m-d H:i:s');
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  @file_put_contents($file, "[$ts][$ip] $msg\n", FILE_APPEND);
}

// Verify reCAPTCHA
function verify_recaptcha_v3(string $token, string $secret): bool {
  if (empty($token)) return false;
  $url = 'https://www.google.com/recaptcha/api/siteverify';
  $data = http_build_query([
    'secret' => $secret,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
  ]);
  $res = @file_get_contents($url, false, stream_context_create([
    'http' => [
      'method' => 'POST',
      'header' => "Content-type: application/x-www-form-urlencoded\r\n",
      'content' => $data,
      'timeout' => 10
    ]
  ]));
  $json = json_decode($res ?: '{}', true);
  return ($json['success'] ?? false) && (($json['score'] ?? 0) >= 0.5);
}

// -----------------------------------------------------------------------------
// CSRF protection
// -----------------------------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -----------------------------------------------------------------------------
// Handle form
// -----------------------------------------------------------------------------
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf_ok = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
  if (!$csrf_ok) die("<p style='color:red;'>Session expired. Please reload the page.</p>");

  $token = $_POST['recaptchaToken'] ?? '';
  if (!verify_recaptcha_v3($token, $secretKey)) {
    die("<p style='color:red;'>reCAPTCHA verification failed. Please retry.</p>");
  }

  $old = trim($_POST['old_password'] ?? '');
  $new = trim($_POST['new_password'] ?? '');
  $confirm = trim($_POST['confirm_password'] ?? '');

  if ($new !== $confirm) {
    $message = "<span style='color:red;'>New passwords do not match.</span>";
  } elseif (strlen($new) < 8) {
    $message = "<span style='color:red;'>Password must be at least 8 characters.</span>";
  } else {
    try {
      $pdo = db_pdo();
      $stmt = $pdo->prepare("SELECT password FROM accounts WHERE pId=?");
      $stmt->execute([$pId]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row || !password_verify($old, $row['password'])) {
        log_event("Password change failed for $login (wrong old password)");
        $message = "<span style='color:red;'>Incorrect old password.</span>";
      } else {
        $hashed = password_hash($new, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE accounts SET password=? WHERE pId=?");
        $upd->execute([$hashed, $pId]);
        log_event("Password successfully changed for $login");
        $message = "<span style='color:green;'>✅ Password changed successfully.</span>";
      }
    } catch (Throwable $e) {
      log_event("EXCEPTION in password change: " . $e->getMessage());
      $message = "<span style='color:red;'>System error. Please contact admin.</span>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Change Password | WI siRNA Portal</title>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey; ?>"></script>
<script>
function onSubmitForm(e){
  e.preventDefault();
  grecaptcha.ready(function(){
    grecaptcha.execute('<?php echo $siteKey; ?>',{action:'password_change'}).then(function(token){
      document.getElementById('recaptchaToken').value = token;
      document.getElementById('pwForm').submit();
    });
  });
}
</script>
<style>
body{font-family:Arial;background:#f4f6f8;margin:0;padding:20px;text-align:center;}
form{background:#fff;padding:20px;border-radius:6px;box-shadow:0 0 10px rgba(0,0,0,0.1);width:400px;margin:auto;text-align:left;}
h2{color:#003366;text-align:center;}
label{display:block;margin-top:10px;font-weight:bold;}
input[type=password]{width:100%;padding:6px;margin-top:4px;border:1px solid #ccc;border-radius:4px;}
button{margin-top:15px;background:#0074D9;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;}
button:hover{background:#005fa3;}
a{display:block;margin-top:20px;text-align:center;color:#0074D9;}
</style>
</head>
<body>
<h2>🔑 Change Password</h2>
<?php if($message) echo "<p>$message</p>"; ?>
<form id="pwForm" method="POST" onsubmit="onSubmitForm(event)">
  <input type="hidden" id="recaptchaToken" name="recaptchaToken">
  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

  <label>Current Password:</label>
  <input type="password" name="old_password" required>

  <label>New Password:</label>
  <input type="password" name="new_password" required>

  <label>Confirm New Password:</label>
  <input type="password" name="confirm_password" required>

  <button type="submit">Change Password</button>
</form>
<a href="dashboard.php">⬅ Back to Dashboard</a>
</body>
</html>
