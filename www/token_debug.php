<?php
require_once dirname(__DIR__) . '/config.php';
$recaptcha = recaptcha_config();
$siteKey   = trim($recaptcha['site_key']);
$secretKey = trim($recaptcha['secret_key']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>reCAPTCHA v3 Token Debug (Legacy)</title>
  <!-- Load only the legacy v3 script -->
  <script src="https://www.google.com/recaptcha/api.js?render=<?=$siteKey?>"></script>
  <script>
    function generateToken() {
      grecaptcha.ready(function() {
        grecaptcha.execute('<?=$siteKey?>', {action: 'debug'}).then(function(token) {
          document.getElementById('token').value = token;
          document.getElementById('debugForm').submit();
        }).catch(err => {
          console.error("reCAPTCHA execution failed:", err);
          alert("reCAPTCHA execution failed: " + err);
        });
      });
    }
  </script>
</head>
<body>
  <h2>Google reCAPTCHA v3 Token Debug</h2>
  <form id="debugForm" method="POST">
    <input type="hidden" name="token" id="token">
    <button type="button" onclick="generateToken()">Generate & Verify Token</button>
  </form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!$token) { echo "<p>No token received.</p>"; exit; }

    $resp = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false,
        stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'secret'   => $secretKey,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]),
            ],
        ])
    );

    echo "<pre>Response from Google:\n$resp</pre>";
}
?>
</body>
</html>
