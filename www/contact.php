<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
session_start();

// -----------------------------------------------------------------------------
// Optional logging of contact attempts
// -----------------------------------------------------------------------------
function log_contact(string $msg): void {
    $file = '/home/shaykins/Projects/siRNA/logs/contact_form.log';
    if (!is_dir(dirname($file))) @mkdir(dirname($file), 0775, true);
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    @file_put_contents($file, "[$ts][$ip] $msg\n", FILE_APPEND);
}

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $feedback = "<p style='color:red;'>All fields are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = "<p style='color:red;'>Please enter a valid email address.</p>";
    } else {
        log_contact("Message from $name <$email>: $message");
        $feedback = "<p style='color:green;'>Thank you, your message has been recorded.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contact | WI siRNA Selection Program</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f9f9f9;
  margin: 0;
  padding: 0;
  text-align: center;
}
.container { margin-top: 40px; max-width: 600px; margin-left:auto; margin-right:auto; }
input[type=text], input[type=email], textarea {
  width: 90%; padding:8px; margin:6px 0;
  border:1px solid #ccc; border-radius:4px;
}
input[type=submit] {
  background-color:#0074D9; color:#fff; border:none; padding:8px 16px;
  border-radius:4px; cursor:pointer;
}
input[type=submit]:hover { background-color:#005fa3; }
.navbar { background:#003366; color:#fff; padding:10px; }
.navbar a { color:#fff; margin:0 10px; text-decoration:none; font-weight:bold; }
.navbar a:hover { text-decoration:underline; }
.footer {
  font-size: 12px;
  color: #777;
  margin-top: 40px;
}
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

<div class="container">
<h2>Contact Us</h2>
<p>If you have questions, comments, or suggestions about the WI siRNA Selection Program, please use the form below.</p>

<?php echo $feedback; ?>

<form method="POST" action="contact.php">
  <input type="text" name="name" placeholder="Your Name" required><br>
  <input type="email" name="email" placeholder="Your Email" required><br>
  <textarea name="message" rows="5" placeholder="Your Message" required></textarea><br>
  <input type="submit" value="Send Message">
</form>
</div>

<div class="footer">
  <p>© 2004 Whitehead Institute for Biomedical Research. All rights reserved.<br>
  Comments and suggestions to: <img src="keep/contact.jpg" alt="Contact"></p>
</div>
</body>
</html>
