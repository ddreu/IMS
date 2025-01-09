<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

$mail = new PHPMailer(true);

// Enable SMTP debugging (optional)
// $mail->SMTPDebug = SMTP::DEBUG_SERVER;

$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->Host = "smtp.sendgrid.net"; // Use SendGrid SMTP server
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
$mail->Port = 587; // Port number for TLS
$mail->Username = "apikey"; // This should be the literal string "apikey"
$mail->Password = "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"; // Replace with your actual SendGrid API key

$mail->isHtml(true);
$mail->setFrom("intramuralsims2024@gmail.com", "IMS"); // Use your verified sender email

return $mail;
