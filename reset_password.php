<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function sendJsonResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Database config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "saveplate";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    sendJsonResponse(false, 'Database connection failed');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$newPassword = $_POST['newPassword'] ?? '';

if (empty($email) || empty($newPassword)) {
    sendJsonResponse(false, 'Email and new password are required');
}

// Validate password length
if (strlen($newPassword) < 6) {
    sendJsonResponse(false, 'Password must be at least 6 characters long');
}

// Check if user exists and OTP was verified
$checkStmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND used = 1 ORDER BY created_at DESC LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    sendJsonResponse(false, 'Please verify your email with OTP first');
}

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password in sp_users table
$updateStmt = $conn->prepare("UPDATE sp_users SET password = ? WHERE email = ?");
$updateStmt->bind_param("ss", $hashedPassword, $email);

if ($updateStmt->execute()) {
    if ($updateStmt->affected_rows > 0) {
        // Clean up used OTP records
        $cleanupStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $cleanupStmt->bind_param("s", $email);
        $cleanupStmt->execute();
        $cleanupStmt->close();
        
        sendJsonResponse(true, 'Password has been reset successfully! You can now login with your new password.');
    } else {
        sendJsonResponse(false, 'User not found or password unchanged');
    }
} else {
    error_log("Password update failed: " . $updateStmt->error);
    sendJsonResponse(false, 'Failed to reset password. Please try again.');
}

$checkStmt->close();
$updateStmt->close();
$conn->close();
?>