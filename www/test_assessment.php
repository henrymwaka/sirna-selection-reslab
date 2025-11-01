<?php
require_once dirname(__DIR__) . '/config.php';

$project_id = $CONFIG['google']['project_id'];
$api_key    = $CONFIG['google']['api_key'];
$site_key   = $CONFIG['recaptcha']['site_key'];

$fake_token = "TEST_TOKEN";  // placeholder for now

$url = "https://recaptchaenterprise.googleapis.com/v1/projects/$project_id/assessments?key=$api_key";

$data = [
  "event" => [
    "token"          => $fake_token,
    "siteKey"        => $site_key,
    "expectedAction" => "submit"
  ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
  CURLOPT_POSTFIELDS     => json_encode($data)
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP status: $httpcode\n\n";
echo "Response:\n$response\n";
