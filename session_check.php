<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to signin page if not logged in
    header("Location: signin.html");
    exit();
}

// Get current user information
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_email = $_SESSION['email'];
?>
