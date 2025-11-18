<?php
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

// Read form data (support both POST and JSON)
$item_id = 0;
$quantity_to_move = 0;

if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    // Handle JSON input from browse page
    $input = json_decode(file_get_contents('php://input'), true);
    $item_id = isset($input['item_id']) ? intval($input['item_id']) : 0;
    $quantity_to_move = isset($input['quantity']) ? intval($input['quantity']) : 0;
} else {
    // Handle form data from inventory page
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $quantity_to_move = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
}

// Validate required fields
if ($item_id <= 0 || $quantity_to_move <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID or quantity']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current item details
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->bind_param('i', $item_id);
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
        throw new Exception('Cannot move expired items to meal plan');
    }
    
    // Check if quantity is available
    if ($quantity_to_move > $item['quantity']) {
        throw new Exception('Not enough quantity available');
    }
    
    // Calculate status for meal item
    function calculateStatus($expiryDate) {
        $today = new DateTime();
        $expiry = new DateTime($expiryDate);
        $diff = $today->diff($expiry);
        $days = $diff->days;
        
        if ($expiry < $today) {
            $days = -$days;
        }
        
        if ($days < 0) {
            return 'Expired';
        } else if ($days <= 5) {
            return 'Expiring Soon';
        } else {
            return 'Good';
        }
    }
    
    $status = calculateStatus($item['expiry_date']);
    
    // Insert into meal table
    $stmt = $conn->prepare("INSERT INTO meal (user_id, name, quantity, expiry_date, storage_type, category, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isisssss', $item['user_id'], $item['name'], $quantity_to_move, $item['expiry_date'], $item['storage_type'], $item['category'], $status, $item['remarks']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add item to meal plan: ' . $stmt->error);
    }
    $stmt->close();
    
    // Update inventory quantity
    $new_quantity = $item['quantity'] - $quantity_to_move;
    
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
    echo json_encode(['success' => true, 'message' => 'Item moved to meal plan successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
