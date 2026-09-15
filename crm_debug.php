<?php
// Temporary diagnostic endpoint. Remove after diagnosing.
require "./config/env.php";

header('Content-Type: application/json');

$crmSecretRaw = getenv('CRM_WEBHOOK_SECRET');
$webhookSecretRaw = getenv('WEBHOOK_SECRET');
$resolved = env('CRM_WEBHOOK_SECRET', env('WEBHOOK_SECRET', ''));

echo json_encode([
    'deploy_marker' => 'crm_debug_v1',
    'CRM_WEBHOOK_SECRET_set' => $crmSecretRaw !== false,
    'CRM_WEBHOOK_SECRET_len' => $crmSecretRaw !== false ? strlen($crmSecretRaw) : null,
    'WEBHOOK_SECRET_set' => $webhookSecretRaw !== false,
    'WEBHOOK_SECRET_len' => $webhookSecretRaw !== false ? strlen($webhookSecretRaw) : null,
    'resolved_secret_len' => strlen($resolved),
    'resolved_secret_last4' => substr($resolved, -4),
]);
