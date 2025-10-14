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

// Read form data (support both POST and JSON)
$item_id = 0;
$quantity_to_use = 0;

if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    // Handle JSON input from browse page
    $input = json_decode(file_get_contents('php://input'), true);
    $item_id = isset($input['item_id']) ? intval($input['item_id']) : 0;
    $quantity_to_use = isset($input['quantity']) ? intval($input['quantity']) : 0;
    $user_id = isset($input['user_id']) ? intval($input['user_id']) : $user_id;
} else {
    // Handle form data from inventory page
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity_to_use = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
}

// Validate required fields
if ($item_id <= 0 || $quantity_to_use <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID or quantity']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current item details
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Item not found');
    }
    
    $item = $result->fetch_assoc();
    $stmt->close();
    
    // Check if item is expired
    $today = new DateTime();
    $expiry = new DateTime($item['expiry_date']);
    if ($expiry < $today) {
        throw new Exception('Cannot use expired items');
    }
    
    // Check if quantity is available
    if ($quantity_to_use > $item['quantity']) {
        throw new Exception('Not enough quantity available');
    }
    
    // Update inventory quantity
    $new_quantity = $item['quantity'] - $quantity_to_use;
    
    if ($new_quantity <= 0) {
        // Remove item from inventory if quantity becomes 0
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $item_id, $user_id);
    } else {
        // Update quantity
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('iii', $new_quantity, $item_id, $user_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update inventory: ' . $stmt->error);
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Item used successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
