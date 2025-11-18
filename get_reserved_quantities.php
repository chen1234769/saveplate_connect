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

$user_id = $_SESSION['user_id'];

// Get reserved quantities for all inventory items
// Only count reservations for future dates
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT 
    r.inventory_id,
    SUM(r.reserved_quantity) as total_reserved
FROM meal_reservations r
WHERE r.user_id = ? AND r.reservation_date >= ?
GROUP BY r.inventory_id");

$stmt->bind_param('is', $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

$reserved = [];
while ($row = $result->fetch_assoc()) {
    $reserved[$row['inventory_id']] = (int)$row['total_reserved'];
}

$stmt->close();
$conn->close();

echo json_encode($reserved);
?>

