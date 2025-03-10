<?php

function sendPasswordResetEmail($email, $token)
{
    $mail = require __DIR__ . "/mailer.php";
    $mail->addAddress($email);

    $mail->Subject = "Password Reset Request";
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

    $host = $_SERVER['HTTP_HOST'];

    $resetUrl = "$protocol://$host/reset-password.php?token=" . urlencode($token);

    $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2a2d54;'>Password Reset Request</h2>
                <p>We received a request to reset your password. If you didn't make this request, you can safely ignore this email.</p>
                <div style='background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                    <p>To reset your password, click the button below:</p>
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href=\"$resetUrl\" 
                           style='background-color: #2a2d54; 
                                  color: white; 
                                  padding: 12px 25px; 
                                  text-decoration: none; 
                                  border-radius: 5px;
                                  display: inline-block;'>
                            Reset Password
                        </a>
                    </div>
                    <p style='font-size: 0.9em; color: #666;'>This link will expire in 30 minutes for security reasons.</p>
                </div>
                <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                <p style='word-break: break-all; font-size: 0.9em; color: #666;'>$resetUrl</p>
                <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                <p style='font-size: 0.8em; color: #666;'>
                    If you did not request a password reset, please ignore this email or contact support if you have concerns.
                </p>
            </div>
        </body>
        </html>
    ";

    try {
        $mail->send();
        return [
            'success' => true,
            'message' => "Password reset link has been sent to your email."
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Email could not be sent. Mailer error: {$mail->ErrorInfo}"
        ];
    }
}

function generateToken()
{
    return bin2hex(random_bytes(16));
}

function storeToken($conn, $email, $token)
{
    $token_hash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30);

    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'message' => "Error preparing statement: " . $conn->error
        ];
    }

    $stmt->bind_param("sss", $token_hash, $expiry, $email);
    $success = $stmt->execute();

    return [
        'success' => $success,
        'message' => $success ? "Token stored successfully" : "Error storing token: " . $stmt->error
    ];
}
