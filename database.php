<?php
$host = "localhost";
$user = "root";   // XAMPP default
$pass = "";       // usually empty
$db   = "test";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
