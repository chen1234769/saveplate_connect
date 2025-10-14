<?php
session_start();

require_once 'mail_functions.php';

// Database configuration
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "test"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $household_size = isset($_POST['household_size']) ? intval($_POST['household_size']) : 0;
    $privacy_toggle = isset($_POST['privacyToggle']) ? 1 : 0;
    
    // Check if username already exists
    $check_username_sql = "SELECT id FROM sp_users WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $check_username_result = $check_username_stmt->get_result();
    
    // Check if email already exists
    $check_email_sql = "SELECT id FROM sp_users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    // Check if phone already exists
    $check_phone_sql = "SELECT id FROM sp_users WHERE phone = ?";
    $check_phone_stmt = $conn->prepare($check_phone_sql);
    $check_phone_stmt->bind_param("s", $phone);
    $check_phone_stmt->execute();
    $check_phone_result = $check_phone_stmt->get_result();
    
    if ($check_username_result->num_rows > 0) {
        // Username already exists
        echo "<script>
            alert('Username already exists. Please choose a different username.');
            window.location.href = 'register.html';
        </script>";
    } elseif ($check_email_result->num_rows > 0) {
        // Email already exists
        echo "<script>
            alert('Email address already registered. Please use a different email or login.');
            window.location.href = 'register.html';
        </script>";
    } elseif ($check_phone_result->num_rows > 0) {
        // Phone already exists
        echo "<script>
            alert('Phone number already registered. Please use a different phone number or login.');
            window.location.href = 'register.html';
        </script>";
    } else {
        // Insert new user
        $sql = "INSERT INTO sp_users (username, email, phone, password, household_size, anonymous_data, email_notifications, two_factor_auth, personalized_ads) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // Set default values for privacy preferences
        $anonymous_data = $privacy_toggle;
        $email_notifications = $privacy_toggle;
        $two_factor_auth = $privacy_toggle;
        $personalized_ads = $privacy_toggle;
        
        $stmt->bind_param("ssssiiiii", $username, $email, $phone, $password, $household_size, $anonymous_data, $email_notifications, $two_factor_auth, $personalized_ads);
        
        if ($stmt->execute()) {
            // Generate OTP
            $otp = sprintf("%06d", random_int(0, 999999));
            $otp_expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Store OTP in database
            $otp_sql = "INSERT INTO sp_verification_codes (email, code, expires_at) VALUES (?, ?, ?)";
            $otp_stmt = $conn->prepare($otp_sql);
            $otp_stmt->bind_param("sss", $email, $otp, $otp_expires);
            $otp_stmt->execute();
            $otp_stmt->close();
            
            // Send OTP email
            $emailResult = sendOTPEmail($email, $username, $otp);
            if ($emailResult['success']) {
                //Success
            }else{
                //Handle error
            }
            
            // Registration successful - redirect to verification page
            echo "<script>
                alert('Registration successful! Verification code sent to your email.');
                window.location.href = 'verification.html?email=" . urlencode($email) . "';
            </script>";
        } else {
            echo "<script>
                alert('Error: " . addslashes($stmt->error) . "');
                window.location.href = 'register.html';
            </script>";
        }
        
        $stmt->close();
    }
    
    $check_username_stmt->close();
    $check_email_stmt->close();
    $check_phone_stmt->close();
}

/*
function sendOTPEmail($email, $username, $otp) {
    $subject = "SavePlate Connect - Email Verification Code";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #2e7d32; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .otp-code { font-size: 32px; font-weight: bold; text-align: center; color: #2e7d32; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; letter-spacing: 5px; }
            .footer { padding: 20px; text-align: center; background: #f8f9fa; color: #666; font-size: 12px; }
            .warning { background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>SavePlate Connect</h1>
                <p>Smart Food Waste Reduction Platform</p>
            </div>
            <div class='content'>
                <h2>Welcome $username!</h2>
                <p>Thank you for registering with SavePlate Connect. Your verification code is:</p>
                <div class='otp-code'>$otp</div>
                <div class='warning'>
                    <strong>Important:</strong> This code will expire in 5 minutes. Do not share this code with anyone.
                </div>
                <p>Enter this code on the verification page to complete your registration.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 SavePlate Connect. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: SavePlate Connect <noreply@saveplate.com>" . "\r\n";
    $headers .= "Reply-To: no-reply@saveplate.com" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}
*/
$conn->close();
?>
