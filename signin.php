<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "saveplate";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $rememberMe = isset($_POST['rememberMe']) ? true : false;

    // Validate input
    if (empty($username) || empty($password)) {
        header("Location: signin.html?error=" . urlencode("Please fill in all fields"));
        exit();
    }

    // Check if user exists by username or email
    $sql = "SELECT id, username, email, password, verified FROM sp_users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            
            // Check if email is verified
            if (!$user['verified']) {
                header("Location: signin.html?error=" . urlencode("Please verify your email before signing in"));
                exit();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;

            // Set remember me cookie if selected
            if ($rememberMe) {
                $cookie_value = $user['id'] . ':' . hash('sha256', $user['password']);
                setcookie('remember_me', $cookie_value, time() + (30 * 24 * 60 * 60), "/"); // 30 days
            }

            // Redirect to dashboard
            header("Location: signin.html?success=true");
            exit();
            
        } else {
            // Invalid password
            header("Location: signin.html?error=" . urlencode("Invalid username or password"));
            exit();
        }
    } else {
        // User not found
        header("Location: signin.html?error=" . urlencode("Invalid username or password"));
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
