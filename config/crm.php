<?php

require_once __DIR__ . '/env.php';

define("CRM_WEBHOOK_URL", env('CRM_WEBHOOK_URL', 'http://127.0.0.1:8000/leads/api/webhook/myplatform/registration/'));
define("CRM_WEBHOOK_SECRET", env('CRM_WEBHOOK_SECRET', ''));

function sendLeadToCRM($first, $last, $email, $phone) {

    $payload = json_encode([
        "first_name" => $first,
        "last_name"  => $last,
        "email"      => $email,
        "phone"      => $phone,
    ]);

    $ch = curl_init(CRM_WEBHOOK_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-Webhook-Secret: " . CRM_WEBHOOK_SECRET,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    curl_exec($ch);
    curl_close($ch);
}
