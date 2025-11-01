<?php
declare(strict_types=1);

/*
 * reCAPTCHA v3 live test for sirna.reslab.dev
 * Uses keys from .env via config.php
 * Author: Henry Mwaka
 */

ini_set('display_errors','1');
error_reporting(E_ALL);

require_once dirname(__DIR__).'/config.php';
$cfg = recaptcha_config();
$siteKey   = $cfg['site_key'];
$secretKey = $cfg['secret_key'];
$threshold = $cfg['threshold'] ?? 0.5;

/* -------------------------------------------------------------------------- */
function verify_token(string $token,string $secret): array {
    $res = @file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify',
        false,
        stream_context_create([
            'http'=>[
                'method'=>'POST',
                'header'=>"Content-type: application/x-www-form-urlencoded\r\n",
                'content'=>http_build_query([
                    'secret'=>$secret,
                    'response'=>$token,
                    'remoteip'=>$_SERVER['REMOTE_ADDR']??''
                ]),
                'timeout'=>10
            ]
        ])
    );
    return json_decode($res?:'{}',true);
}
/* -------------------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_POST['recaptchaToken']??'';
    if(!$token){echo"<p style='color:red'>No token received.</p>";exit;}
    $result=verify_token($token,$secretKey);
    echo "<h3>Verification result</h3><pre>";
    print_r($result);
    echo "</pre><p><a href='test_recaptcha_live.php'>&laquo; Back</a></p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>reCAPTCHA v3 Test</title>
<!-- ✅ correct non-enterprise endpoint -->
<script src="https://www.recaptcha.net/recaptcha/api.js?render=<?=$siteKey?>"></script>
<script>
function runTest(e){
  e.preventDefault();
  grecaptcha.ready(function(){
    grecaptcha.execute('<?=$siteKey?>',{action:'test'}).then(function(token){
      document.getElementById('recaptchaToken').value=token;
      document.getElementById('form').submit();
    }).catch(function(err){
      console.error('reCAPTCHA error:',err);
      alert('reCAPTCHA error: '+err);
    });
  });
}
</script>
<style>
body{font-family:Arial,Helvetica,sans-serif;margin:2em}
button{padding:8px 16px;background:#0077cc;color:#fff;border:0;border-radius:4px;cursor:pointer}
button:hover{background:#005fa3}
</style>
</head>
<body>
<h2>reCAPTCHA v3 Live Test</h2>
<form id="form" method="POST" onsubmit="runTest(event)">
  <input type="hidden" id="recaptchaToken" name="recaptchaToken">
  <button type="submit">Run Test</button>
</form>
<p>Keys loaded from <code>.env</code>; results logged to browser and displayed below.</p>
</body>
</html>
