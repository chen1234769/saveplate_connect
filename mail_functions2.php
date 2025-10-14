<?php
// Ensure no spaces or BOM at file beginning
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Silent include to avoid warning output
@require 'PHPMailer/src/Exception.php';
@require 'PHPMailer/src/PHPMailer.php';
@require 'PHPMailer/src/SMTP.php';

function sendPasswordResetEmail($email, $username, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'junyan10082004@gmail.com';
        $mail->Password   = 'fvye jumm obsb hnod';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@saveplate.com', 'SavePlate Connect');
        $mail->addAddress($email, $username);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SavePlate Connect - Password Reset OTP';
        $mail->Body    = createPasswordResetEmailTemplate($username, $otp);
        $mail->AltBody = "Your password reset OTP is: $otp\nThis code will expire in 5 minutes.";
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => "Failed to send email"];
    }
}

function createPasswordResetEmailTemplate($username, $otp) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #2e7d32; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .otp-code { font-size: 32px; font-weight: bold; text-align: center; color: #2e7d32; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; letter-spacing: 5px; }
            .warning { background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800; }
            .footer { padding: 20px; text-align: center; background: #f8f9fa; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>SavePlate Connect</h1>
                <p>Smart Food Waste Reduction Platform</p>
            </div>
            <div class='content'>
                <h2>Hello $username!</h2>
                <p>You have requested to reset your password. Use the OTP below to verify your identity:</p>
                <div class='otp-code'>$otp</div>
                <div class='warning'>
                    <strong>Important:</strong> This OTP will expire in 5 minutes. Do not share this code with anyone.
                </div>
                <p>Enter this OTP on the verification page to reset your password.</p>
                <p>If you didn't request this password reset, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 SavePlate Connect. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

?>
