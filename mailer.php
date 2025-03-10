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
$mail->Host = "smtp.sendgrid.net";
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->Username = "apikey";
$mail->Password = "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";

$mail->isHtml(true);
$mail->setFrom("intramuralsims2024@gmail.com", "IMS");

return $mail;
