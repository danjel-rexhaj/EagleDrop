<?php
require_once __DIR__ . '/env.php';

function sendViaSendGrid($toEmail, $subject, $htmlBody) {
    $apiKey = env('SENDGRID_API_KEY');
    if (!$apiKey) {
        error_log("SendGrid error: SENDGRID_API_KEY is not set");
        return false;
    }

    $fromEmail = env('MAIL_FROM_EMAIL', 'eagledrop19@gmail.com');
    $fromName  = env('MAIL_FROM_NAME', 'EagleDrop');

    $payload = json_encode([
        'personalizations' => [
            ['to' => [['email' => $toEmail]]],
        ],
        'from'    => ['email' => $fromEmail, 'name' => $fromName],
        'subject' => $subject,
        'content' => [
            ['type' => 'text/html', 'value' => $htmlBody],
        ],
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

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("SendGrid error (HTTP $httpCode): " . ($curlError ?: $response));
        return false;
    }

    return true;
}

function sendVerificationEmail($email, $code) {
    $html = "
    <div style='font-family: Arial, sans-serif; background:#f4f6f8; padding:30px'>
        <div style='max-width:520px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden'>

            <div style='background:#0d6efd; color:white; padding:20px; text-align:center'>
                <h2 style='margin:0'>EagleDrop</h2>
            </div>

            <div style='padding:30px; color:#333'>
                <h3 style='margin-top:0'>Verifikimi i Email-it</h3>

                <p>
                    Pershendetje,<br><br>
                    Faleminderit qe u regjistruat ne <b>EagleDrop</b>.
                    Per te aktivizuar llogarine tuaj, ju lutemi perdorni kodin e meposhtem:
                </p>

                <div style='
                    margin:30px 0;
                    text-align:center;
                    font-size:28px;
                    letter-spacing:6px;
                    font-weight:bold;
                    color:#0d6efd;
                '>
                    $code
                </div>

                <p style='font-size:14px; color:#666'>
                    Ky kod eshte i vlefshem vetem per nje verifikim.
                    Nese nuk e keni kerkuar kete veprim, mund ta injoroni kete email.
                </p>

                <p style='margin-top:30px'>
                    Me respekt,<br>
                    <b>Ekipi EagleDrop</b>
                </p>
            </div>

            <div style='background:#f1f1f1; padding:15px; text-align:center; font-size:12px; color:#777'>
                © " . date('Y') . " EagleDrop. Te gjitha te drejtat e rezervuara.
            </div>
        </div>
    </div>
    ";

    return sendViaSendGrid($email, "Verifikimi i Llogarise – EagleDrop", $html);
}

function sendResetPasswordEmail($email, $token) {
    $resetLink = env('APP_BASE_URL', 'https://stalagmitical-emma-unpoached.ngrok-free.dev/myplatform')
        . '/reset_password.php?token=' . urlencode($token);

    $html = "
    <div style='font-family:Arial;background:#f4f6f8;padding:30px'>
        <div style='max-width:520px;margin:auto;background:#fff;padding:30px;border-radius:12px'>
            <h3>Reset Password</h3>
            <p>Kliko linkun me poshte per te vendosur nje password te ri:</p>
            <p style='text-align:center;margin:30px 0'>
                <a href='$resetLink'
                   style='background:#0d6efd;color:#fff;padding:12px 24px;
                          text-decoration:none;border-radius:8px'>
                    Reset Password
                </a>
            </p>
        </div>
    </div>
    ";

    return sendViaSendGrid($email, "Reset Password - EagleDrop", $html);
}
