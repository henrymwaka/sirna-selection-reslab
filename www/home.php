<?php
/**
 * home.php — secure + legacy compatible version
 * - Supports reCAPTCHA v2 Checkbox
 * - Safe CSP (no blocked:csp)
 * - Hashed passwords (fallback to plaintext)
 * - CSRF & session protection
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ------------------------- CONFIG -------------------------------------------
$CONFIG = [
  'recaptcha_site_key' => '6Le32fcrAAAAAJGC1MokNfvhkn8RRHpMB55-S2ja',
  'recaptcha_secret'   => '6Le32fcrAAAAAHEsMPsi6UBL0fsqlwaRFkEBpmcH',
  'db' => [
    'host' => 'localhost',
    'user' => 'sirna_user',
    'pass' => 'sirna_pass',
    'name' => 'sirna',
    'charset' => 'utf8mb4',
  ],
  'logfile' => __DIR__ . '/logs/login_audit.log',
  'daily_limit' => 25,
  'usage_tz' => 'America/New_York',
  'force_https' => false,
];

// ------------------------- SECURITY HEADERS ---------------------------------
if ($CONFIG['force_https'] && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
  header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
  exit;
}
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
/*
 * ✅ FIXED CSP — allows Google reCAPTCHA + fonts + blob inline scripts.
 */
header("Content-Security-Policy: "
 . "default-src 'self' https://www.google.com https://www.gstatic.com https://www.recaptcha.net https://fonts.googleapis.com https://fonts.gstatic.com blob:; "
 . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://www.recaptcha.net blob:; "
 . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
 . "font-src 'self' https://fonts.gstatic.com data:; "
 . "img-src 'self' data: https://www.google.com https://www.gstatic.com; "
);

// ------------------------- LOGGING ------------------------------------------
function log_event(string $msg): void {
  global $CONFIG;
  $file = $CONFIG['logfile'];
  if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
  $ts = date('Y-m-d H:i:s');
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  @file_put_contents($file, "[$ts][$ip] $msg\n", FILE_APPEND);
}

// ------------------------- SESSION / CSRF -----------------------------------
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ------------------------- DB CONNECTION ------------------------------------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function db(): mysqli {
  static $conn = null;
  if ($conn instanceof mysqli) return $conn;
  global $CONFIG;
  $db = $CONFIG['db'];
  $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
  $conn->set_charset($db['charset']);
  return $conn;
}

// ------------------------- HELPERS ------------------------------------------
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function verify_recaptcha(string $token): bool {
  global $CONFIG;
  $post = http_build_query([
    'secret'   => $CONFIG['recaptcha_secret'],
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
  ]);
  $ctx = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
    'content' => $post,
    'timeout' => 10,
  ]]);
  $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
  if (!$resp) { log_event('reCAPTCHA HTTP failure'); return false; }
  $data = json_decode($resp, true);
  if (empty($data['success'])) {
    log_event('reCAPTCHA failed: ' . json_encode($data['error-codes'] ?? []));
    return false;
  }
  return true;
}

function today_parts_ny(): array {
  global $CONFIG;
  $tz = new DateTimeZone($CONFIG['usage_tz']);
  $now = new DateTime('now', $tz);
  return [(int)$now->format('j'), (int)$now->format('n'), (int)$now->format('Y')];
}

function generate_tasto(): string { return bin2hex(random_bytes(8)); }

// ------------------------- RENDER LOGIN -------------------------------------
function render_form(string $feedbackHTML = ''): void {
  global $CONFIG;
  $siteKey = $CONFIG['recaptcha_site_key'];
  $csrf = $_SESSION['csrf_token'] ?? '';
  echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>WI siRNA Selection Program</title>
  <script src="siRNAhelp.js"></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    body { font-family: Arial, sans-serif; }
    .feedback { margin-top: 10px; }
    input[type="submit"] {
      background-color: #0066cc; color: white; border: none; border-radius: 4px;
      padding: 6px 12px; cursor: pointer;
    }
  </style>
</head>
<body>
<img src="keep/header_wi_01.jpg" />
<center>
  <table width="100%"><tr>
    <td width="20%" align="left">
      <table align="center" border="0" cellspacing="1" bgcolor="#FFFFFF">
        <tr><td align="center"><a href="javascript:help('./keep/about.html')"><img src="keep/about.gif" border="0"></a></td></tr>
        <tr><td align="center"><a href="javascript:help('./keep/FAQ.html')"><img src="keep/faq.gif" border="0"></a></td></tr>
        <tr><td align="center"><a href="javascript:help('./keep/example.html')"><img src="keep/example.gif" border="0"></a></td></tr>
        <tr><td align="center"><a href="javascript:help('./keep/compatibility.html')"><img src="keep/compatibility.gif" border="0"></a></td></tr>
        <tr><td align="center"><a href="javascript:help('./keep/disclaimer.html')"><img src="keep/disclaimer.gif" border="0"></a></td></tr>
        <tr><td align="center"><a href="javascript:help('./keep/acknowledgements.html')"><img src="keep/acknowledgements.gif" border="0"></a></td></tr>
      </table>
    </td>
    <td width="50%" align="center">
      <img src="keep/animation.gif" height="300"><br><br>
      <form id="loginForm" method="POST" action="home.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="$csrf">
        <table>
          <tr><td align="right">Enter your login: <input type="text" name="login" size="15" maxlength="64" required></td></tr>
          <tr><td align="right">Enter your password: <input type="password" name="password" size="15" maxlength="128" required></td></tr>
          <tr><td align="center" style="padding-top:8px;"><div class="g-recaptcha" data-sitekey="$siteKey"></div></td></tr>
          <tr><td align="right" style="padding-top:8px;"><input type="submit" value="Login"></td></tr>
        </table>
      </form>
      <div id="feedback" class="feedback">$feedbackHTML</div>
      <br>
      <a href="register.php"><font color="08437E">REGISTRATION</font></a><br>
      <a href="reference.php"><font color="08437E">How To Reference siRNA Selection Program</font></a>
      <h4>This website is no longer being maintained except for the BLAST databases.</h4>
    </td>
  </tr></table>
  <br><font size="2">Copyright 2004 Whitehead Institute for Biomedical Research. All rights reserved.<br>Comments and suggestions to: <img src="./keep/contact.jpg"></font>
</center>
</body>
</html>
HTML;
}

// ------------------------- REQUEST LOGIC ------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { render_form(); exit; }

try {
  $csrf_ok = isset($_POST['csrf_token'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']);
  if (!$csrf_ok) { log_event('CSRF token mismatch'); render_form("<div style='color:red;'>Session expired. Please try again.</div>"); exit; }

  $login = trim($_POST['login'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $rec_tok = $_POST['g-recaptcha-response'] ?? '';

  if ($login === '' || $password === '') { render_form("<div style='color:red;'>Login and password are required.</div>"); exit; }
  if ($rec_tok === '' || !verify_recaptcha($rec_tok)) { render_form("<div style='color:red;'>reCAPTCHA verification failed. Please try again.</div>"); exit; }

  $conn = db();
  $stmt = $conn->prepare("SELECT a.pId, a.password AS pass_db, p.permit, p.authenticate FROM accounts a JOIN permissions p ON a.pId=p.pId WHERE a.login=? AND p.permit=1 LIMIT 1");
  $stmt->bind_param('s', $login);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { log_event("FAILED login (no account) for '$login'"); render_form("<div style='color:red;'>Login failed.</div>"); exit; }
  $r = $res->fetch_assoc();
  $pId = (int)$r['pId']; $pass_db = (string)$r['pass_db']; $authenticate = (int)$r['authenticate'];

  $ok = false;
  if (preg_match('/^\$2[aby]\$|^\$argon2/', $pass_db)) $ok = password_verify($password, $pass_db);
  else $ok = hash_equals($pass_db, $password);

  if (!$ok) { log_event("FAILED password for '$login'"); render_form("<div style='color:red;'>Login failed. Please check your credentials.</div>"); exit; }

  date_default_timezone_set($CONFIG['usage_tz']);
  [$day, $month, $year] = today_parts_ny();
  $conn->begin_transaction();
  $stmt = $conn->prepare("SELECT count,day,month,year FROM counts WHERE pId=? FOR UPDATE");
  $stmt->bind_param('i', $pId); $stmt->execute(); $counts=$stmt->get_result();
  if ($counts->num_rows===0){
    $stmt=$conn->prepare("INSERT INTO counts(pId,count,day,month,year)VALUES(?,?,?,?,?)");
    $z=0;$stmt->bind_param('iiiii',$pId,$z,$day,$month,$year);$stmt->execute();
    $cCount=0;
  }else{
    $c=$counts->fetch_assoc();
    if($c['day']!=$day||$c['month']!=$month||$c['year']!=$year){
      $stmt=$conn->prepare("UPDATE counts SET count=0,day=?,month=?,year=? WHERE pId=?");
      $stmt->bind_param('iiii',$day,$month,$year,$pId);$stmt->execute();$cCount=0;
    } else $cCount=(int)$c['count'];
  }
  if ($cCount >= $CONFIG['daily_limit']) { $conn->commit(); render_form("<div style='color:red;'>You have exceeded the daily usage limit.</div>"); exit; }

  $tasto = generate_tasto(); $ip=$_SERVER['REMOTE_ADDR']??'';
  $stmt=$conn->prepare("REPLACE INTO logins(pId,rId,ip) VALUES(?,?,?)");
  $stmt->bind_param('iss',$pId,$tasto,$ip);$stmt->execute();$conn->commit();

  $_SESSION['pId']=$pId;$_SESSION['login']=$login;$_SESSION['tasto']=$tasto;
  log_event("SUCCESS login for '$login'");

  if ($authenticate===0) echo "<div class='feedback' style='color:green;'>Login OK. Redirecting…</div><meta http-equiv='refresh' content='1;url=authenticate.php?tasto=".h($tasto)."'>";
  else echo "<div class='feedback' style='color:green;'>Login successful. Redirecting…</div><meta http-equiv='refresh' content='1;url=siRNA_search.cgi?tasto=".h($tasto)."'>";
  exit;

} catch (Throwable $e) {
  log_event('EXCEPTION: '.$e->getMessage());
  render_form("<div style='color:red;'>System error. Please contact admin.</div>");
  exit;
}
?>
