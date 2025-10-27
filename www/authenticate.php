<!DOCTYPE html>
<html>
<head>
<title>WI siRNA Selection Program</title>
<script src="./siRNAhelp.js"></script>
<script src="https://www.google.com/recaptcha/enterprise.js?render=6Le32fcrAAAAAJGC1MokNfvhkn8RRHpMB55-S2ja" async defer></script>

<script>
// -------------------------
// reCAPTCHA Auto-Refresh
// -------------------------
let recaptchaInterval;
function refreshRecaptcha() {
  if (typeof grecaptcha !== "undefined") {
    grecaptcha.enterprise.ready(function() {
      grecaptcha.enterprise.execute("6Le32fcrAAAAAJGC1MokNfvhkn8RRHpMB55-S2ja", {action: "AUTH"}).then(function(token) {
        // Update or create hidden token input field dynamically
        let input = document.getElementById("g-recaptcha-response");
        if (!input) {
          input = document.createElement("input");
          input.type = "hidden";
          input.id = "g-recaptcha-response";
          input.name = "g-recaptcha-response";
          document.forms["authForm"].appendChild(input);
        }
        input.value = token;
      });
    });
  }
}
document.addEventListener("DOMContentLoaded", function() {
  setTimeout(refreshRecaptcha, 2000);
  recaptchaInterval = setInterval(refreshRecaptcha, 90000); // refresh every 90 seconds
});
</script>

<style>
input[type=submit] {
  padding: 8px 16px;
  background-color: #0074D9;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
input[type=submit]:hover { background-color: #005fa3; }
</style>
</head>
<body>
<img src="keep/header_wi_01.jpg" />
<center>
<img src="keep/animation.gif" height="300">

<?php
extract($_POST);
extract($_GET);

$link = mysqli_connect("localhost","sirna_user","sirna_pass","sirna")
    or die("Database connection failed: " . mysqli_connect_error());
mysqli_select_db($link, "sirna") or die("Cannot SELECT_DB");

if (isset($tasto)) {
    $tasto += 0;
    $PermissionsQuery = mysqli_query($link, "SELECT pId FROM logins WHERE rId=$tasto") or die("Cannot SELECT FROM logins");

    while ($row = mysqli_fetch_array($PermissionsQuery)) {

        // If form submitted
        if (isset($authCode)) {

            // ---------------------------------------------------------
            // reCAPTCHA VERIFICATION (Enterprise + Legacy fallback)
            // ---------------------------------------------------------
            $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';

            if ($recaptcha_token == '') {
                die("<h3 style='color:red;'>reCAPTCHA missing or expired. Please try again.</h3>");
            }

            // Enterprise setup
            $api_key = "AIzaSyBdq1G_pOekFJvQ9O4rVaQFJZYY3pBaUfA"; // your API key
            $site_key = "6Le32fcrAAAAAJGC1MokNfvhkn8RRHpMB55-S2ja";
            $verification_url = "https://recaptchaenterprise.googleapis.com/v1/projects/sirna-selection--1761486784144/assessments?key={$api_key}";
            $legacy_secret = "6Le32fcrAAAAAHEsMPsi6UBL0fsqlwaRFkEBpmcH"; // fallback secret key

            $recaptcha_valid = false;
            $recaptcha_error = "";

            // Enterprise verification
            $request_body = json_encode([
                "event" => [
                    "token" => $recaptcha_token,
                    "expectedAction" => "AUTH",
                    "siteKey" => $site_key
                ]
            ]);

            $ch = curl_init($verification_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response && $http_code == 200) {
                $result = json_decode($response, true);
                if (isset($result['riskAnalysis']['score']) && $result['riskAnalysis']['score'] >= 0.5) {
                    $recaptcha_valid = true;
                } else {
                    $recaptcha_error = "Low confidence score from Enterprise verification.";
                }
            } else {
                $recaptcha_error = "Enterprise API request failed.";
            }

            // Fallback to legacy
            if (!$recaptcha_valid) {
                error_log("Enterprise reCAPTCHA failed: $recaptcha_error — Fallback to legacy.");
                $verify_url = "https://www.google.com/recaptcha/api/siteverify";
                $data = [
                    'secret' => $legacy_secret,
                    'response' => $recaptcha_token,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ];
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    ]
                ];
                $context = stream_context_create($options);
                $response = file_get_contents($verify_url, false, $context);
                $legacy_result = json_decode($response, true);

                if (isset($legacy_result['success']) && $legacy_result['success']) {
                    $recaptcha_valid = true;
                } else {
                    $recaptcha_error = "Legacy reCAPTCHA also failed.";
                }
            }

            if (!$recaptcha_valid) {
                die("<h3 style='color:red;'>reCAPTCHA verification failed. Please retry.</h3>");
            }

            // ---------------------------------------------------------
            // Proceed with authentication
            // ---------------------------------------------------------
            $authCode += 0;
            $CheckAuth = mysqli_query($link, "SELECT logins.pId, authentication.authCode FROM authentication, logins WHERE authentication.pId=logins.pId AND logins.rId=$tasto");

            while ($row = mysqli_fetch_array($CheckAuth)) {
                if ($row["authCode"] == $authCode) {
                    mysqli_query($link, "UPDATE permissions SET authenticate=1 WHERE pId=" . $row["pId"]);
                    echo "<p><font face='arial' size='4'>Thank you for authenticating, please login again</font></p>";
                    echo "<meta http-equiv='refresh' content='2;url=home.php'>";
                } else {
                    echo "<p><font face='arial' size='4' color='red'>Authentication FAILED, please login again</font></p>";
                    echo "<meta http-equiv='refresh' content='2;url=home.php'>";
                }
            }
        } else {
            // Display form if not yet submitted
            echo "<form name='authForm' action='authenticate.php' method='POST'>";
            echo "<input type='hidden' name='tasto' value='$tasto'>";
            echo "<table>";
            echo "<tr><td><font face='arial' size='2'>Your authentication code can be found in the email you received from us</font></td></tr>";
            echo "<tr><td><font face='arial' size='2'>Enter Authentication Code: </font><input type='text' size='11' maxlength='15' name='authCode'></td></tr>";
            echo "<tr><td align='center'><input type='submit' value='Submit Authentication'></td></tr>";
            echo "</table>";
            echo "</form>";

            // Footer links remain unchanged
            echo <<<END
<br><br>
<table align="center" border=1 cellspacing=1 bgcolor="#92C5F8">
<tr>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/news.html')"><font face=arial color=08437E>NEWS</font></a></td>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/about.html')"><font face=arial color=08437E>About</font></a></td>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/FAQ.html')"><font face=arial color=08437E>FAQ</font></a></td>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/example.html')"><font face=arial color=08437E>Example</font></a></td>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/compatibility.html')"><font face=arial color=08437E>Compatibility</font></a></td>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/disclaimer.html')"><font face=arial color=08437E>Disclaimer</font></a></td>
    <td align="center" width=80><a class="internal" href="javascript:help('./keep/acknowledgements.html')"><font face=arial color=08437E>Acknowledgements</font></a></td>
    <td align="center"><a class="internal" href="home.php"><font face=arial color=08437E>Home</font></a></td>
</tr>
</table>
<br>
<p><font face="arial" color="000000" size="2">Copyright 2004 Whitehead Institute for Biomedical Research. All rights reserved.<br>Comments and suggestions to: </font><img src='keep/contact.gif'>
END;
        }
    }
} else {
    echo "<h2>Please Login into Your Account First</h2>";
    echo "<meta http-equiv='refresh' content='2;url=home.php'>";
}
?>
</center>
</body>
</html>
