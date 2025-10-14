<?php
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$itemId = $input['itemId'] ?? 0;
$user_id = $input['user_id'] ?? 0;

if (!$itemId || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    switch ($action) {
        case 'used':
            // Remove from inventory
            $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $itemId, $user_id);
            $stmt->execute();
            break;
            
        case 'meal':
            // Stay in inventory (no action needed)
            echo json_encode(['success' => true, 'message' => 'Item marked for meal']);
            exit;
            
        case 'donate':
            // Move from inventory to donation
            $conn->begin_transaction();
            
            // Get item from inventory
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $itemId, $user_id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            
            if (!$item) {
                throw new Exception('Item not found');
            }
            
            // Insert into donation
            $stmt = $conn->prepare("INSERT INTO donation (user_id, name, quantity, expiry_date, storage_type, category, status, pickup_location, contact_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iissssssss', 
                $item['user_id'], $item['name'], $item['quantity'], 
                $item['expiry_date'], $item['storage_type'], $item['category'], 
                $item['status'], $item['pickup_location'], $item['contact_method'], $item['notes']
            );
            $stmt->execute();
            
            // Delete from inventory
            $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $itemId, $user_id);
            $stmt->execute();
            
            $conn->commit();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
