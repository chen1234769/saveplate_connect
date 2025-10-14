<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function sendJsonResponse($success, $message) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Database config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test"; 

// Create connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$otp = filter_var($_POST['otp'] ?? '', FILTER_SANITIZE_STRING);

if (empty($email) || empty($otp)) {
    sendJsonResponse(false, 'Email and OTP are required');
}

try {
    // Verify OTP using PHP time comparison
    $stmt = $conn->prepare("SELECT id, email, reset_code, expires_at FROM password_resets WHERE email = ? AND reset_code = ? AND used = 0");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendJsonResponse(false, 'Invalid OTP');
    }

    $otpRecord = $result->fetch_assoc();
    $currentTime = time();
    $expiresAt = strtotime($otpRecord['expires_at']);

    // Check if OTP is expired using PHP time
    if ($currentTime > $expiresAt) {
        sendJsonResponse(false, 'OTP has expired. Please request a new one.');
    }

    // Mark OTP as used
    $markUsedStmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND reset_code = ?");
    $markUsedStmt->bind_param("ss", $email, $otp);
    $markUsedStmt->execute();

    sendJsonResponse(true, 'OTP verified successfully! You can now reset your password.');

    $stmt->close();
    $markUsedStmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("OTP verification error: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred. Please try again.');
}
?>
