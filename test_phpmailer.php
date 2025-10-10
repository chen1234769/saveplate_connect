@'
<?php
// test_phpmailer.php - Command Line Version
echo "🚀 PHPMailer Command Line Test\n";
echo "===============================\n";

// Check if required files exist
if (!file_exists("mail_functions.php")) {
    die("❌ mail_functions.php not found\n");
}

require_once "mail_functions.php";

// Test configuration
$test_email = "junyan10082004@gmail.com";  // Change this to your actual email
$test_username = "Test User";
$test_otp = "123456";

echo "Test Details:\n";
echo "--------------\n";
echo "Email: $test_email\n";
echo "Username: $test_username\n";
echo "OTP: $test_otp\n";
echo "--------------\n\n";

echo "Sending test email...\n";

// Test the email function
$result = sendOTPEmail($test_email, $test_username, $test_otp);

if ($result['success']) {
    echo "✅ SUCCESS: Email sent successfully!\n";
    echo "Please check your email inbox (and spam folder)\n";
} else {
    echo "❌ FAILED: " . $result['message'] . "\n";
    
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check Gmail credentials in mail_functions.php\n";
    echo "2. Verify you're using Gmail app password (not regular password)\n";
    echo "3. Check internet connection\n";
    echo "4. Make sure 2-factor authentication is enabled on Gmail\n";
}

echo "\nTest completed.\n";
?>
'@ | Out-File -FilePath "test_phpmailer.php" -Encoding utf8