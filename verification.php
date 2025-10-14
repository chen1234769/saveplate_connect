<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');


// Include PHPMailer functions
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($action === 'verify') {
            $email = $_POST['email'] ?? '';
            $otp = $_POST['otp'] ?? '';
            
            if (empty($email) || empty($otp)) {
                throw new Exception('Email and OTP are required');
            }
            
            // Add this before the OTP check for debugging
            error_log("Checking OTP - Email: $email, OTP: $otp");

            $stmt = $conn->prepare("
                SELECT vc.*, u.id as user_id 
                FROM sp_verification_codes vc 
                JOIN sp_users u ON u.email = vc.email 
                WHERE vc.email = ? AND vc.code = ? AND vc.expires_at > NOW() AND vc.used = 0 
                ORDER BY vc.created_at DESC LIMIT 1
            ");
            $stmt->bind_param("ss", $email, $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            $otp_record = $result->fetch_assoc();

            // Debug what was found
            if ($otp_record) {
                error_log("OTP Found: " . print_r($otp_record, true));
            } else {
                error_log("No OTP found - checking what exists in database");
    
                // Check what OTPs actually exist for this email
                $debug_stmt = $conn->prepare("
                    SELECT email, code, expires_at, used, created_at 
                    FROM sp_verification_codes 
                    WHERE email = ? 
                    ORDER BY created_at DESC LIMIT 5
                ");
                $debug_stmt->bind_param("s", $email);
                $debug_stmt->execute();
                $debug_result = $debug_stmt->get_result();
    
                $all_otps = [];
                while ($row = $debug_result->fetch_assoc()) {
                    $all_otps[] = $row;
                }
                error_log("All OTPs for $email: " . print_r($all_otps, true));
            }
            // Check OTP with PHP time to avoid timezone issues
            $current_time = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("
                SELECT vc.*, u.id as user_id 
                FROM sp_verification_codes vc 
                JOIN sp_users u ON u.email = vc.email 
                WHERE vc.email = ? AND vc.code = ? AND vc.expires_at > ? AND vc.used = 0 
                ORDER BY vc.created_at DESC LIMIT 1
            ");
            $stmt->bind_param("sss", $email, $otp, $current_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $otp_record = $result->fetch_assoc();

            // Temporary simple test - remove this later
            if ($otp === "123456") {
                $_SESSION['user_email'] = $email;
                $_SESSION['verified'] = true;
                $response['success'] = true;
                $response['message'] = 'Test verification successful!';
                $response['redirect'] = 'dashboard.php';
                echo json_encode($response);
                exit;
            }

// Your existing OTP check code below...
            
            if ($otp_record) {
                // Mark OTP as used
                $stmt = $conn->prepare("UPDATE sp_verification_codes SET used = 1 WHERE id = ?");
                $stmt->bind_param("i", $otp_record['id']);
                $stmt->execute();
                
                // Activate user account
                $stmt = $conn->prepare("UPDATE sp_users SET verified = 1 WHERE id = ?");
                $stmt->bind_param("i", $otp_record['user_id']);
                $stmt->execute();
                
                $_SESSION['user_id'] = $otp_record['user_id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['verified'] = true;
                
                $response['success'] = true;
                $response['message'] = 'Email verified successfully!';
                $response['redirect'] = 'dashboard.php';
            } else {
                throw new Exception('Invalid or expired verification code');
            }
            
        } elseif ($action === 'resend') {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                throw new Exception('Email is required');
            }
            
            // Check user exists and is not verified
            $stmt = $conn->prepare("SELECT id, username FROM sp_users WHERE email = ? AND verified = 0");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                // Generate new OTP
                $otp = sprintf("%06d", random_int(0, 999999));
                $otp_expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                // Invalidate old OTPs
                $stmt = $conn->prepare("UPDATE sp_verification_codes SET used = 1 WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                // Store new OTP
                $stmt = $conn->prepare("INSERT INTO sp_verification_codes (email, code, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $otp, $otp_expires);
                $stmt->execute();
                
                // Send email using PHPMailer
                $emailResult = sendOTPEmail($email, $user['username'], $otp);
                
                if ($emailResult['success']) {
                    $response['success'] = true;
                    $response['message'] = 'New verification code sent to your email.';
                } else {
                    // Fallback: show OTP if email fails
                    $response['success'] = true;
                    $response['message'] = 'Email service temporarily unavailable. Use this code: ' . $otp;
                    $response['debug_code'] = $otp;
                }
                
            } else {
                throw new Exception('Email not found or already verified');
            }
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

$conn->close();
?>
