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
$quantity_to_donate = 0;
$pickup_location = '';
$contact_method = '';

if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    // Handle JSON input from browse page
    $input = json_decode(file_get_contents('php://input'), true);
    $item_id = isset($input['item_id']) ? intval($input['item_id']) : 0;
    $quantity_to_donate = isset($input['quantity']) ? intval($input['quantity']) : 0;
    $user_id = isset($input['user_id']) ? intval($input['user_id']) : $user_id;
    $pickup_location = isset($input['pickup_location']) ? trim($input['pickup_location']) : '';
    $contact_method = isset($input['contact_method']) ? trim($input['contact_method']) : '';
} else {
    // Handle form data from inventory page
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity_to_donate = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $pickup_location = isset($_POST['pickup_location']) ? trim($_POST['pickup_location']) : '';
    $contact_method = isset($_POST['contact_method']) ? trim($_POST['contact_method']) : '';
}

// Validate required fields
if ($item_id <= 0 || $quantity_to_donate <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID or quantity']);
    exit;
}

// Require pickup location and contact method for both JSON and form inputs
if (empty($pickup_location) || empty($contact_method)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
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
        throw new Exception('Cannot donate expired items');
    }
    
    // Check if quantity is available
    if ($quantity_to_donate > $item['quantity']) {
        throw new Exception('Not enough quantity available');
    }
    
    // Add to donation table
    $stmt = $conn->prepare("INSERT INTO donation (user_id, name, quantity, expiry_date, category, storage_type, remarks, pickup_location, contact_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $donation_status = 'pending';
    $stmt->bind_param('isisssssss', $item['user_id'], $item['name'], $quantity_to_donate, $item['expiry_date'], $item['category'], $item['storage_type'], $item['remarks'], $pickup_location, $contact_method, $donation_status);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add to donation: ' . $stmt->error);
    }
    $stmt->close();
    
    // Update inventory quantity
    $new_quantity = $item['quantity'] - $quantity_to_donate;
    
    if ($new_quantity <= 0) {
        // Remove item from inventory if quantity becomes 0
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->bind_param('i', $item_id);
    } else {
        // Update quantity
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $stmt->bind_param('ii', $new_quantity, $item_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update inventory: ' . $stmt->error);
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Item donated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
