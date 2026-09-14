<?php
require __DIR__ . '/config/env.php';

header('Content-Type: application/json');

$apiKey = env('SENDGRID_API_KEY');
$fromEmail = env('MAIL_FROM_EMAIL', 'eagledrop19@gmail.com');
$fromName = env('MAIL_FROM_NAME', 'EagleDrop');

$result = [
    'api_key_set' => (bool) $apiKey,
    'api_key_prefix' => $apiKey ? substr($apiKey, 0, 6) . '...' : null,
    'from_email' => $fromEmail,
    'from_name' => $fromName,
];

if ($apiKey) {
    $payload = json_encode([
        'personalizations' => [['to' => [['email' => 'eagledrop19@gmail.com']]]],
        'from' => ['email' => $fromEmail, 'name' => $fromName],
        'subject' => 'Debug test nga myplatform',
        'content' => [['type' => 'text/html', 'value' => '<p>Debug test.</p>']],
    ]);

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $result['sendgrid_http_code'] = $httpCode;
    $result['sendgrid_response'] = $response;
    $result['curl_error'] = $curlError ?: null;
}

echo json_encode($result, JSON_PRETTY_PRINT);
