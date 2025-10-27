<!DOCTYPE html>
<html>
<head>
<title>WI siRNA Selection Program Registration</title>
<script src="siRNAhelp.js"></script>

<!-- Google reCAPTCHA Enterprise (visible + token validation) -->
<script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const siteKey = "6Le32fcrAAAAAJGC1MokNfvhkn8RRHpMB55-S2ja";
  const form = document.getElementById("registration");

  // Ensure the form exists
  if (!form) return;

  // Add reCAPTCHA badge inside the form
  const badgeContainer = document.createElement("div");
  badgeContainer.innerHTML = `
    <div class="g-recaptcha"
         data-sitekey="${siteKey}"
         data-callback="onSubmitRecaptcha"
         data-action="REGISTER"></div>`;
  form.appendChild(badgeContainer);

  // reCAPTCHA callback
  window.onSubmitRecaptcha = function(token) {
    let input = document.getElementById("g-recaptcha-response");
    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.id = "g-recaptcha-response";
      input.name = "g-recaptcha-response";
      form.appendChild(input);
    }
    input.value = token;
    form.submit();
  };

  // Intercept form submission until token is generated
  form.addEventListener("submit", function(e) {
    if (!document.getElementById("g-recaptcha-response")?.value) {
      e.preventDefault();
      grecaptcha.enterprise.ready(function() {
        grecaptcha.enterprise.execute(siteKey, {action: 'REGISTER'}).then(function(token) {
          let input = document.getElementById("g-recaptcha-response");
          if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.id = "g-recaptcha-response";
            input.name = "g-recaptcha-response";
            form.appendChild(input);
          }
          input.value = token;
          form.submit();
        });
      });
    }
  });
});
</script>

<style>
input[type=submit], input[type=reset] {
  padding: 8px 16px;
  font-size: 14px;
  border: none;
  border-radius: 4px;
  color: #fff;
  cursor: pointer;
}
input[type=submit] { background-color:#0066cc; }
input[type=submit]:hover { background-color:#005bb5; }
input[type=reset]  { background-color:#999; }
input[type=reset]:hover { background-color:#777; }

.fade-in { opacity: 0; animation: fadeIn 1.5s forwards; }
@keyframes fadeIn { to { opacity: 1; } }
</style>
</head>

<body>
<img src="keep/header_wi_01.jpg" />
<center>

<?php
extract($_POST);
extract($_GET);

if (isset($register)) {
    if ($password != $password2) {
        echo "<font face='arial' color='000000'>Password was not verified</font>";
        echo "<meta http-equiv='refresh' content='2;url=register.php'>";
    } else {
        $link = mysqli_connect("localhost","sirna_user","sirna_pass","sirna")
            or die("Database connection failed: " . mysqli_connect_error());
        mysqli_select_db($link, "sirna") or die("Cannot select db");

        date_default_timezone_set("America/New_York");
        $today = getdate();
        $day = $today["mday"];
        $month = $today["mon"];
        $year = $today["year"];
        $tasto +=0;

        // ------------------------------
        // reCAPTCHA Verification
        // ------------------------------
        $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
        if ($recaptcha_token == '') {
            die("<h3 style='color:red;'>reCAPTCHA missing or expired. Please refresh and try again.</h3>");
        }

        $api_key = "AIzaSyBFz-I1aN586F9yzE7awZ4HlHvQi7L2jLQ";
        $site_key = "6Le32fcrAAAAAJGC1MokNfvhkn8RRHpMB55-S2ja";
        $verification_url = "https://recaptchaenterprise.googleapis.com/v1/projects/sirna-selection--1761486784144/assessments?key={$api_key}";
        $legacy_secret = "6Le32fcrAAAAAHEsMPsi6UBL0fsqlwaRFkEBpmcH";

        $recaptcha_valid = false;
        $recaptcha_score = null;
        $recaptcha_error = "";

        $request_body = json_encode([
            "event" => [
                "token" => $recaptcha_token,
                "expectedAction" => "REGISTER",
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
            if (isset($result['riskAnalysis']['score'])) {
                $recaptcha_score = $result['riskAnalysis']['score'];
            }
            if ($recaptcha_score !== null && $recaptcha_score >= 0.5) {
                $recaptcha_valid = true;
            } else {
                $recaptcha_error = "Low confidence score";
            }
        } else {
            $recaptcha_error = "Enterprise API not reachable";
        }

        // Log each reCAPTCHA validation attempt
        error_log("[siRNA Registration] reCAPTCHA Enterprise: HTTP={$http_code}, Score=" . ($recaptcha_score ?? "null") . ", Valid=" . ($recaptcha_valid ? "true" : "false") . " from IP=" . $_SERVER['REMOTE_ADDR']);

        // Fallback to legacy check if needed
        if (!$recaptcha_valid) {
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
                error_log("[siRNA Registration] Legacy reCAPTCHA success for IP=" . $_SERVER['REMOTE_ADDR']);
            } else {
                die("<h3 style='color:red;'>reCAPTCHA verification failed. Please retry.</h3>");
            }
        }

        // ------------------------------
        // Registration Logic
        // ------------------------------
        if ($email == "" || $login == "" || $password == "") {
            echo "<meta http-equiv='refresh' content='0;url=register.php?error=1'>";
        } else {
            $LoginCheck = mysqli_query($link, "SELECT login FROM accounts") or die("Could not select from accounts");
            $loginBit = 0;
            while($row = mysqli_fetch_array($LoginCheck)) {
                if($login == $row["login"]) $loginBit = 1;
            }

            if ($loginBit == 1) {
                echo "<br><br><center><font face='arial' size=5 color='red'>**** Please Select a Different Login ****</font></center><br><br>";
                echo "<meta http-equiv='refresh' content='5;url=register.php'>";
            } else {
                // Escape input
                $login = addslashes($login);
                $password = addslashes($password);
                $fName = addslashes($fName);
                $lName = addslashes($lName);
                $institution = addslashes($institution);
                $address1 = addslashes($address1);
                $address2 = addslashes($address2);
                $city = addslashes($city);
                $state = addslashes($state);
                $zip = addslashes($zip);
                $country = addslashes($country);
                $email2 = addslashes($email);

                $LoginsQuery = mysqli_query($link, "SELECT pId FROM logins WHERE rId=$tasto") or die("Could not select from logins");
                while($row = mysqli_fetch_array($LoginsQuery)) {
                    $pId = $row["pId"];
                    mysqli_query($link, "INSERT INTO accounts VALUES($pId, \"$login\", \"$password\")");
                    mysqli_query($link, "INSERT INTO names VALUES($pId, \"$fName\", \"$lName\")");
                    mysqli_query($link, "INSERT INTO institutions VALUES($pId, \"$institution\", \"$address1\", \"$address2\", \"$city\", \"$state\", \"$zip\", \"$country\")");
                    mysqli_query($link, "INSERT INTO emails VALUES($pId, \"$email2\")");
                    mysqli_query($link, "INSERT INTO permissions VALUES($pId, 1, 0)");
                    mysqli_query($link, "INSERT INTO counts VALUES($pId, $day, $month, $year,0)");

                    srand((double)microtime() * 1000000);
                    $authCode = rand();
                    mysqli_query($link, "INSERT INTO authentication VALUES($pId, $authCode)");

                    $message = "Welcome to the WI siRNA tool. Your authentication code: $authCode";
                    mail($email,"Welcome to siRNA Selection Program",$message,"Reply-To: admin@domain.com");

                    error_log("[siRNA Registration] SUCCESS for user={$login}, email={$email}, score=" . ($recaptcha_score ?? "legacy") . " from IP=" . $_SERVER['REMOTE_ADDR']);

                    echo "<div class='fade-in' style='margin-top:40px; text-align:center; font-family:arial;'>";
                    echo "<h2 style='color:green;'>✔ Registration verified successfully</h2>";
                    echo "<p style='color:#000; font-size:14px;'>Thank you for registering. You will be redirected shortly.</p>";
                    echo "<p style='color:#666; font-size:12px;'>If not redirected automatically, <a href='home.php'>click here</a>.</p>";
                    echo "</div>";

                    echo "<meta http-equiv='refresh' content='4;url=home.php'>";
                }
            }
        }
    }
} else {
?>
<form id="registration" name="registration" action="register.php" method="POST">
<table cellpadding=6>
<tr><td colspan=2><b>Identification Information</b></td></tr>
<tr><td>First Name:</td><td><input type="text" name="fName"></td></tr>
<tr><td>Last Name:</td><td><input type="text" name="lName"></td></tr>
<tr><td>* Email:</td><td><input type="text" name="email"></td></tr>

<tr><td colspan=2><b>Login Information</b></td></tr>
<tr><td>* Login:</td><td><input type="text" name="login"></td></tr>
<tr><td>* Password:</td><td><input type="password" name="password"></td></tr>
<tr><td>* Verify Password:</td><td><input type="password" name="password2"></td></tr>

<tr><td colspan=2><b>Institution Information</b></td></tr>
<tr><td>Institution Name:</td><td><input type="text" name="institution"></td></tr>
<tr><td>Address 1:</td><td><input type="text" name="address1"></td></tr>
<tr><td>Address 2:</td><td><input type="text" name="address2"></td></tr>
<tr><td>City:</td><td><input type="text" name="city"></td></tr>
<tr><td>State/Province:</td><td><input type="text" name="state"></td></tr>
<tr><td>Zip Code:</td><td><input type="text" name="zip"></td></tr>
<tr><td>Country:</td><td><input type="text" name="country"></td></tr>
</table>
<?php
srand((double)microtime() * 1000000);
$pId = rand();
srand((double)microtime() * 1000000);
$rId = rand();
$ip = $_SERVER["REMOTE_ADDR"];
$link = mysqli_connect("localhost","sirna_user","sirna_pass","sirna");
mysqli_query($link, "INSERT INTO logins VALUES($pId, $rId, \"$ip\")");
?>
<input type="hidden" name="register" value="1">
<input type="hidden" name="tasto" value="<?php echo $rId; ?>">
<br><input type="submit" value="Submit Registration"> &nbsp; <input type="reset" value="Reset">
</form>
<?php } ?>
</center>
</body>
</html>
