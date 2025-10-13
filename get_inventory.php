<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch inventory items
$stmt = $conn->prepare("SELECT * FROM inventory WHERE user_id = ? ORDER BY expiry_date ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    // Format expiry date
    $expiry_date = new DateTime($row['expiry_date']);
    $row['expiry'] = $expiry_date->format('M d');
    
    // Format category and storage type
    $row['category'] = ucfirst(str_replace('_', ' ', $row['category']));
    $row['location'] = ucfirst(str_replace('_', ' ', $row['storage_type']));
    
    $items[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($items);
?>