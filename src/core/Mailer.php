<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public function sendNotification(string $fullName, string $email, string $company = null): bool
    {
        // Require PHPMailer files
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom(SMTP_USER, 'Registration App');
            $mail->addAddress(ADMIN_EMAIL, 'Admin');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New User Registered!';
            $body = "A new user has registered:<br>";
            $body .= "<strong>Full Name:</strong> " . htmlspecialchars($fullName) . "<br>";
            $body .= "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>";
            if (!empty($company)) {
                $body .= "<strong>Company:</strong> " . htmlspecialchars($company) . "<br>";
            }
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}