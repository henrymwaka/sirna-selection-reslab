<?php
declare(strict_types=1);
/**
 * WI siRNA Selection Program – User Registration
 * Author: Dr. Henry Mwaka
 * Version: 2025-10
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/logger.php';

$recaptcha = recaptcha_config();
$siteKey   = $recaptcha['site_key'];
$secretKey = $recaptcha['secret_key'];

// -----------------------------------------------------------------------------
// reCAPTCHA helper
// -----------------------------------------------------------------------------
function verify_recaptcha_v3(string $token, string $secret, float $threshold = 0.5): bool {
    if (empty($token)) return false;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $payload = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    $res = @file_get_contents($url, false, stream_context_create([
        'http' => ['method' => 'POST', 'header' => "Content-type: application/x-www-form-urlencoded\r\n", 'content' => $payload]
    ]));
    $json = json_decode($res ?: '{}', true);
    $ok = ($json['success'] ?? false) && (($json['score'] ?? 0) >= $threshold);
    log_event('register', "reCAPTCHA success=" . ($json['success'] ?? 0) . " score=" . ($json['score'] ?? 'null') . " result=" . ($ok ? 'OK' : 'FAIL'));
    return $ok;
}

// -----------------------------------------------------------------------------
// Session setup for CSRF
// -----------------------------------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true, 'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>WI siRNA Selection Program Registration</title>
<script src="siRNAhelp.js"></script>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey; ?>"></script>
<script>
function onRegisterSubmit(e){
  e.preventDefault();
  grecaptcha.ready(function(){
    grecaptcha.execute('<?php echo $siteKey; ?>',{action:'register'}).then(function(token){
      document.getElementById('recaptchaToken').value=token;
      document.getElementById('registration').submit();
    }).catch(function(err){
      console.error('reCAPTCHA failed:',err);
      alert('reCAPTCHA verification failed. Please refresh and try again.');
    });
  });
}
</script>
<style>
body {font-family:Arial,sans-serif;background:#f9f9f9;margin:0;}
input[type=submit],input[type=reset]{
  padding:8px 16px;font-size:14px;border:none;border-radius:4px;color:#fff;cursor:pointer;
}
input[type=submit]{background:#0066cc;}
input[type=submit]:hover{background:#005bb5;}
input[type=reset]{background:#999;}
input[type=reset]:hover{background:#777;}
table{background:#fff;border-radius:6px;padding:10px;}
.fade-in{opacity:0;animation:fadeIn 1.5s forwards;}
@keyframes fadeIn{to{opacity:1;}}
</style>
</head>
<body>
<img src="keep/header_wi_01.jpg" alt="Header" />
<center>
<?php
extract($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($register)) {
    $csrf_ok = isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
               hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    if (!$csrf_ok) {
        log_event('register', 'CSRF token mismatch');
        die("<div style='color:red;'>Session expired. Please refresh and try again.</div>");
    }

    if ($password !== $password2) {
        echo "<div style='color:red;'>Passwords did not match.</div>";
        echo "<meta http-equiv='refresh' content='2;url=register.php'>";
        exit;
    }

    if (!verify_recaptcha_v3($_POST['recaptchaToken'] ?? '', $secretKey)) {
        die("<h3 style='color:red;'>reCAPTCHA verification failed. Please retry.</h3>");
    }

    try {
        $pdo = db_pdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (empty($email) || empty($login) || empty($password)) {
            echo "<meta http-equiv='refresh' content='0;url=register.php?error=1'>";
            exit;
        }

        // Check for existing user
        $stmt = $pdo->prepare("SELECT login FROM accounts WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            echo "<div style='color:red;'>Please choose a different login.</div>";
            echo "<meta http-equiv='refresh' content='4;url=register.php'>";
            exit;
        }

        $pdo->beginTransaction();
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO accounts (login,password) VALUES (" . $pdo->quote($login) . "," . $pdo->quote($hashed) . ")");
        $pId = (int)$pdo->lastInsertId();
        $pdo->exec("INSERT INTO names VALUES ($pId," . $pdo->quote($fName) . "," . $pdo->quote($lName) . ")");
        $pdo->exec("INSERT INTO institutions VALUES ($pId," . $pdo->quote($institution) . "," . $pdo->quote($address1) . "," . $pdo->quote($address2) . "," . $pdo->quote($city) . "," . $pdo->quote($state) . "," . $pdo->quote($zip) . "," . $pdo->quote($country) . ")");
        $pdo->exec("INSERT INTO emails VALUES ($pId," . $pdo->quote($email) . ")");
        $pdo->exec("INSERT INTO permissions VALUES ($pId,1,0)");
        $pdo->exec("INSERT INTO counts VALUES ($pId,DAY(NOW()),MONTH(NOW()),YEAR(NOW()),0)");
        $authCode = random_int(100000,999999);
        $pdo->exec("INSERT INTO authentication VALUES ($pId,$authCode)");
        $pdo->commit();

        @mail($email,"Welcome to siRNA Selection Program","Welcome! Your authentication code: $authCode","Reply-To: admin@sirna.reslab.dev");

        log_event('register', "SUCCESS registration for '$login' ($email)");

        echo "<div class='fade-in' style='margin-top:40px; text-align:center; font-family:arial;'>";
        echo "<h2 style='color:green;'>✔ Registration successful</h2>";
        echo "<p>Thank you for registering. You will be redirected shortly.</p>";
        echo "<p><a href='home.php'>Click here if not redirected.</a></p>";
        echo "</div>";
        echo "<meta http-equiv='refresh' content='4;url=home.php'>";

    } catch (Throwable $e) {
        log_event('register', 'EXCEPTION: '.$e->getMessage());
        echo "<div style='color:red;'>System error. Please contact admin.</div>";
    }

} else {
    $csrf = $_SESSION['csrf_token'] ?? '';
?>
<form id="registration" method="POST" action="register.php" onsubmit="onRegisterSubmit(event)">
  <input type="hidden" id="recaptchaToken" name="recaptchaToken">
  <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
  <table cellpadding="6">
    <tr><td colspan=2><b>Identification Information</b></td></tr>
    <tr><td>First Name:</td><td><input type="text" name="fName"></td></tr>
    <tr><td>Last Name:</td><td><input type="text" name="lName"></td></tr>
    <tr><td>* Email:</td><td><input type="email" name="email" required></td></tr>

    <tr><td colspan=2><b>Login Information</b></td></tr>
    <tr><td>* Login:</td><td><input type="text" name="login" required></td></tr>
    <tr><td>* Password:</td><td><input type="password" name="password" required></td></tr>
    <tr><td>* Verify Password:</td><td><input type="password" name="password2" required></td></tr>

    <tr><td colspan=2><b>Institution Information</b></td></tr>
    <tr><td>Institution Name:</td><td><input type="text" name="institution"></td></tr>
    <tr><td>Address 1:</td><td><input type="text" name="address1"></td></tr>
    <tr><td>Address 2:</td><td><input type="text" name="address2"></td></tr>
    <tr><td>City:</td><td><input type="text" name="city"></td></tr>
    <tr><td>State/Province:</td><td><input type="text" name="state"></td></tr>
    <tr><td>Zip Code:</td><td><input type="text" name="zip"></td></tr>
    <tr><td>Country:</td><td><input type="text" name="country"></td></tr>
  </table>
  <input type="hidden" name="register" value="1">
  <br><input type="submit" value="Submit Registration"> &nbsp; <input type="reset" value="Reset">
</form>
<?php } ?>
</center>
</body>
</html>
