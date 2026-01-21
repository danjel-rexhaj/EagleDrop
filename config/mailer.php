<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

function sendVerificationEmail($email, $code) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "e"; //change those
        $mail->Password   = "h"; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

   
        $mail->setFrom("e", "EagleDrop");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";
        $mail->Subject = "Verifikimi i Llogarise – EagleDrop";


        $mail->Body = "
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

        return $mail->send();

    } catch (Exception $e) {

        return false;
    }
}

function sendResetPasswordEmail($email) {

    $resetLink = "https://stalagmitical-emma-unpoached.ngrok-free.dev/myplatform/reset_password.php";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "em";
        $mail->Password   = "v";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom("@gmail.com", "EagleDrop Support");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";
        $mail->Subject = "Reset Password - EagleDrop";

        $mail->Body = "
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

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}
