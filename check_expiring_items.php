<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/alert_helpers.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Function to calculate status based on expiry date
function calculateStatus($expiry_date) {
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $expiry = new DateTime($expiry_date);
    $expiry->setTime(0, 0, 0);
    
    if ($expiry < $today) {
        return 'expired';
    } else {
        $days_diff = $today->diff($expiry)->days;
        if ($days_diff <= 5) {
            return 'expiring_soon';
        } else {
            return 'good';
        }
    }
}

// Fetch inventory items that might be expiring soon
$stmt = $conn->prepare("SELECT id, name, expiry_date, status FROM inventory WHERE user_id = ? ORDER BY expiry_date ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$alerts_created = 0;

while ($row = $result->fetch_assoc()) {
    $expiry_date = new DateTime($row['expiry_date']);
    $new_status = calculateStatus($row['expiry_date']);
    
    // Update status if changed
    if ($row['status'] !== $new_status) {
        $updateStmt = $conn->prepare("UPDATE inventory SET status = ? WHERE id = ? AND user_id = ?");
        $updateStmt->bind_param('sii', $new_status, $row['id'], $user_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Create alert for expiring soon items
    if ($new_status === 'expiring_soon') {
        $message = "{$row['name']} is expiring on " . $expiry_date->format('M d, Y');
        $created = create_alert($conn, $user_id, 'expiring_soon', 'Expiring Soon', $message, 'inventory', $row['id'], 'inventory.php', 24);
        if ($created) {
            $alerts_created++;
        }
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'alerts_created' => $alerts_created]);
?>

