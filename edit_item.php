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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$storage_type = isset($_POST['storage_type']) ? $_POST['storage_type'] : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

// Validate required fields
if ($item_id <= 0 || empty($name) || $quantity <= 0 || empty($expiry_date) || empty($category) || empty($storage_type)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Calculate status based on expiry date
$today = new DateTime();
$expiry = new DateTime($expiry_date);
$days_diff = $today->diff($expiry)->days;

if ($expiry < $today) {
    $status = 'expired';
} elseif ($days_diff <= 5) {
    $status = 'expiring_soon';
} else {
    $status = 'good';
}

// Update database
$stmt = $conn->prepare("UPDATE inventory SET name = ?, quantity = ?, expiry_date = ?, category = ?, storage_type = ?, remarks = ?, status = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('sisssssii', $name, $quantity, $expiry_date, $category, $storage_type, $remarks, $status, $item_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>