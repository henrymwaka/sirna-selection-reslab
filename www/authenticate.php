<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

// Load reCAPTCHA keys
$recaptcha = recaptcha_config();
$siteKey   = $recaptcha['site_key'];
$secretKey = $recaptcha['secret_key'];

// Secure session setup
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Authentication | WI siRNA Selection Program</title>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey; ?>"></script>
<script>
function getToken(){
  grecaptcha.ready(function(){
    grecaptcha.execute('<?php echo $siteKey; ?>',{action:'authenticate'}).then(function(token){
      let f=document.forms["authForm"];
      let i=document.getElementById("recaptchaToken");
      if(!i){i=document.createElement("input");i.type="hidden";i.id="recaptchaToken";i.name="recaptchaToken";f.appendChild(i);}
      i.value=token;
    });
  });
}
document.addEventListener("DOMContentLoaded",getToken);
</script>
<style>
body{font-family:Arial;background:#f9f9f9;}
input[type=submit]{padding:8px 16px;background:#0074D9;color:#fff;border:none;border-radius:4px;cursor:pointer;}
input[type=submit]:hover{background:#005fa3;}
</style>
</head>
<body>
<img src="keep/header_wi_01.jpg" />
<center><img src="keep/animation.gif" height="250"><br>
<?php
require_once __DIR__ . '/logger.php'; // unified logger (we’ll add this next)
extract($_POST); extract($_GET);

function verify_recaptcha_v3(string $token,string $secret):bool{
  if(empty($token)) return false;
  $url='https://www.google.com/recaptcha/api/siteverify';
  $payload=http_build_query(['secret'=>$secret,'response'=>$token,'remoteip'=>$_SERVER['REMOTE_ADDR']??'']);
  $opts=['http'=>['method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\n",'content'=>$payload]];
  $res=@file_get_contents($url,false,stream_context_create($opts));
  $json=json_decode($res?:'{}',true);
  return ($json['success']??false)&&(($json['score']??0)>=0.5);
}

if(isset($tasto)){
  $tasto += 0;
  $pdo = db_pdo();
  $stmt = $pdo->prepare("SELECT pId FROM logins WHERE rId=?");
  $stmt->execute([$tasto]);
  $loginRow = $stmt->fetch(PDO::FETCH_ASSOC);

  if($loginRow){
    if(isset($authCode)){
      // Verify reCAPTCHA
      if(!verify_recaptcha_v3($_POST['recaptchaToken'] ?? '', $secretKey)){
        die("<h3 style='color:red;'>reCAPTCHA verification failed. Please retry.</h3>");
      }

      $authCode += 0;
      $check = $pdo->prepare("SELECT authentication.pId, authentication.authCode 
                              FROM authentication 
                              JOIN logins ON authentication.pId=logins.pId 
                              WHERE logins.rId=?");
      $check->execute([$tasto]);
      $row = $check->fetch(PDO::FETCH_ASSOC);

      if($row && (int)$row['authCode'] === (int)$authCode){
        $pdo->prepare("UPDATE permissions SET authenticate=1 WHERE pId=?")->execute([$row['pId']]);

        // ✅ establish logged-in session
        $_SESSION['user_id'] = $row['pId'];
        $_SESSION['auth_time'] = time();

        log_event('login', "User ID {$row['pId']} authenticated successfully");
        echo "<p style='color:green;font-family:arial;'>Authentication successful! Redirecting...</p>";
        echo "<meta http-equiv='refresh' content='2;url=home.php'>";
      } else {
        log_event('login', "FAILED authentication attempt for rId=$tasto");
        echo "<p style='color:red;font-family:arial;'>Authentication FAILED. Please retry.</p>";
        echo "<meta http-equiv='refresh' content='2;url=index.php'>";
      }

    } else {
      echo "<form name='authForm' action='authenticate.php' method='POST'>";
      echo "<input type='hidden' name='tasto' value='$tasto'>";
      echo "<table><tr><td>Enter Authentication Code: <input type='text' name='authCode' maxlength='15' required></td></tr>";
      echo "<tr><td align='center'><input type='submit' value='Submit Authentication'></td></tr></table>";
      echo "</form>";
    }
  } else {
    echo "<h3>No valid session found. Please login again.</h3>";
    echo "<meta http-equiv='refresh' content='2;url=index.php'>";
  }

}else{
  echo "<h2>Please Login into Your Account First</h2>";
  echo "<meta http-equiv='refresh' content='2;url=index.php'>";
}
?>
</center>
</body>
</html>
