<?php
// MUST BE AT THE VERY TOP - NO SPACES BEFORE THIS
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set headers as the first thing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function sendJsonResponse($success, $message) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Database config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test"; 

// Check required files
if (!file_exists('mail_functions2.php')) {
    sendJsonResponse(false, 'System error: Email functions not found');
}

// Include mail functions
require_once 'mail_functions2.php';

// Create database connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
}

// Get and validate email
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    sendJsonResponse(false, 'Email is required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Invalid email address');
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, username, email FROM sp_users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database preparation failed');
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // For security, don't reveal if email exists
        sendJsonResponse(true, 'If registered, you will receive an OTP shortly.');
    }

    $user = $result->fetch_assoc();
    $userName = $user['username'];
    $userEmail = $user['email'];

    // Check for existing active OTP using PHP time comparison
    $checkStmt = $conn->prepare("SELECT id, reset_code, created_at, expires_at FROM password_resets WHERE email = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
    $checkStmt->bind_param("s", $userEmail);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $existingOtp = $checkResult->fetch_assoc();
        $currentTime = time();
        $expiresAt = strtotime($existingOtp['expires_at']);
        $createdAt = strtotime($existingOtp['created_at']);
        
        // Check if OTP is still valid using PHP time
        if ($currentTime < $expiresAt) {
            $timeSinceLastOtp = $currentTime - $createdAt;
            
            // Prevent OTP spam - allow resend only after 1 minute
            if ($timeSinceLastOtp < 60) {
                $waitTime = 60 - $timeSinceLastOtp;
                sendJsonResponse(false, "Please wait $waitTime seconds before requesting a new OTP.");
            }
        }
        
        // Mark old OTP as expired
        $expireStmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
        $expireStmt->bind_param("s", $userEmail);
        $expireStmt->execute();
        $expireStmt->close();
    }
    $checkStmt->close();

    // Generate OTP
    $resetCode = sprintf("%06d", mt_rand(1, 999999));
    $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now

    // Store OTP
    $stmt = $conn->prepare("INSERT INTO password_resets (email, reset_code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $userEmail, $resetCode, $expiresAt);

    if ($stmt->execute()) {
        // Send email
        $emailResult = sendPasswordResetEmail($userEmail, $userName, $resetCode);
        
        if ($emailResult['success']) {
            sendJsonResponse(true, 'OTP sent to your email. Check inbox and spam.');
        } else {
            // Clean up on failure
            $cleanupStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND reset_code = ?");
            $cleanupStmt->bind_param("ss", $userEmail, $resetCode);
            $cleanupStmt->execute();
            $cleanupStmt->close();
            
            sendJsonResponse(false, 'Failed to send email. Please try again.');
        }
    } else {
        throw new Exception('Failed to store OTP in database');
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred. Please try again.');
}
?>
