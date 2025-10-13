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

// Read form data
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

// Validate required fields
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Delete from database
$stmt = $conn->prepare("DELETE FROM inventory WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $item_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>