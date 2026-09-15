<?php

require_once __DIR__ . '/env.php';

define("CRM_WEBHOOK_URL", env('CRM_WEBHOOK_URL', 'https://crm-project-3kd1.onrender.com/leads/api/webhook/myplatform/registration/'));
// Render's env for this service has the var named WEBHOOK_SECRET (no CRM_
// prefix) while local .env uses CRM_WEBHOOK_SECRET - accept either so this
// doesn't depend on renaming anything in Render's dashboard.
define("CRM_WEBHOOK_SECRET", env('CRM_WEBHOOK_SECRET', env('WEBHOOK_SECRET', '')));

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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log("CRM webhook error (HTTP $httpCode) posting to " . CRM_WEBHOOK_URL . ": " . ($curlError ?: $response));
        return false;
    }

    return true;
}
