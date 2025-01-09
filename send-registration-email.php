<?php

function sendRegistrationEmail($email, $password, $school_name) {
    $mail = require __DIR__ . "/mailer.php";
    $mail->addAddress($email);

    $mail->Subject = "Welcome to IMS - School Registration Successful";
    
    // Create a more welcoming HTML email body
    $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Welcome to IMS!</h2>
            <p>Dear School Administrator of {$school_name},</p>
            <p>Your school has been successfully registered in the IMS system. Here are your login credentials:</p>
            <div style='background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Password:</strong> {$password}</p>
            </div>
            <p>For security reasons, we strongly recommend changing your password after your first login.</p>
            <p>You can log in at: <a href='http://localhost/IMS/login.php'>IMS Login</a></p>
            <p>If you have any questions or need assistance, please don't hesitate to contact the system administrator.</p>
            <br>
            <p>Best regards,<br>IMS Team</p>
        </body>
        </html>
    ";

    try {
        $mail->send();
        return ["success" => true, "message" => "Registration successful and email sent."];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return ["success" => false, "message" => "email could not be sent. Error: " . $e->getMessage()];
    }
}

function sendUserRegistrationEmail($email, $firstname, $password, $role, $school_name) {
    $mail = require __DIR__ . "/mailer.php";
    $mail->addAddress($email);

    $mail->Subject = "Welcome to IMS - Account Registration";
    
    // Customize message based on role
    $roleSpecificMessage = "";
    switch($role) {
        case 'School Admin':
            $roleSpecificMessage = "You have been registered as a School Administrator for {$school_name}.";
            break;
        case 'Department Admin':
            $roleSpecificMessage = "You have been registered as a Department Administrator for {$school_name}.";
            break;
        case 'Committee':
            $roleSpecificMessage = "You have been registered as a Committee Member for {$school_name}.";
            break;
    }
    
    // Create a more welcoming HTML email body
    $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Welcome to IMS!</h2>
            <p>Dear {$firstname},</p>
            <p>{$roleSpecificMessage}</p>
            <p>Here are your login credentials:</p>
            <div style='background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Password:</strong> {$password}</p>
            </div>
            <p>For security reasons, we strongly recommend changing your password after your first login.</p>
            <p>You can log in at: <a href='http://localhost/IMS/login.php'>IMS Login</a></p>
            <p>If you have any questions or need assistance, please contact your school administrator.</p>
            <br>
            <p>Best regards,<br>IMS Team</p>
        </body>
        </html>
    ";

    try {
        $mail->send();
        return ["success" => true, "message" => "Registration successful and email sent."];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return ["success" => false, "message" => "email could not be sent. Error: " . $e->getMessage()];
    }
}
