<?php
/**
 * OTP helpers — generate, email (Gmail SMTP via PHPMailer), and verify codes.
 * Requires config/mail.php and the bundled PHPMailer in lib/PHPMailer/.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

const OTP_TTL_SECONDS = 600;   // code valid for 10 minutes
const OTP_MAX_ATTEMPTS = 5;

/**
 * Create a 6-digit OTP for an email+purpose, store its hash, email it.
 * Returns true if the email was sent.
 */
function otp_send(string $email, string $purpose): bool
{
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);
    $expires = (new DateTime('+' . OTP_TTL_SECONDS . ' seconds'))->format('Y-m-d H:i:s');

    // remove any previous codes for this email+purpose
    db()->prepare("DELETE FROM email_otps WHERE email=? AND purpose=?")->execute([$email, $purpose]);
    db()->prepare("INSERT INTO email_otps (email, otp_hash, purpose, expires_at) VALUES (?,?,?,?)")
        ->execute([$email, $hash, $purpose, $expires]);

    return otp_mail($email, $code, $purpose);
}

/** Actually send the email via Gmail SMTP. */
function otp_mail(string $to, string $code, string $purpose): bool
{
    $reason = $purpose === 'login' ? 'log in to' : 'verify your account on';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = 'Your verification code: ' . $code;
        $mail->Body =
            "Hello,\n\nYour one-time code to {$reason} PG Rent Manager is:\n\n" .
            "    {$code}\n\n" .
            "It expires in 10 minutes. If you didn't request this, you can ignore this email.\n";
        $mail->send();
        return true;
    } catch (MailException $e) {
        // Surface nothing sensitive to the user; log for the owner/admin.
        error_log('OTP mail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Verify a submitted code. Returns true on success (and consumes the code).
 * On failure increments the attempt counter; locks after OTP_MAX_ATTEMPTS.
 */
function otp_verify(string $email, string $purpose, string $code): bool
{
    $st = db()->prepare("SELECT * FROM email_otps WHERE email=? AND purpose=? LIMIT 1");
    $st->execute([$email, $purpose]);
    $row = $st->fetch();

    if (!$row) return false;
    if (new DateTime() > new DateTime($row['expires_at'])) {
        db()->prepare("DELETE FROM email_otps WHERE id=?")->execute([$row['id']]);
        return false;
    }
    if ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) {
        return false;
    }
    if (password_verify($code, $row['otp_hash'])) {
        db()->prepare("DELETE FROM email_otps WHERE id=?")->execute([$row['id']]); // consume
        return true;
    }
    db()->prepare("UPDATE email_otps SET attempts = attempts + 1 WHERE id=?")->execute([$row['id']]);
    return false;
}
