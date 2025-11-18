<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/alert_helpers.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['meals']) || !is_array($input['meals'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$week_start = isset($input['week_start']) ? $input['week_start'] : null;
$week_end = isset($input['week_end']) ? $input['week_end'] : null;

if (!$week_start || !$week_end) {
    $weekStartDate = new DateTime('monday this week');
    $weekEndDate = clone $weekStartDate;
    $weekEndDate->modify('+6 days');
    $week_start = $weekStartDate->format('Y-m-d');
    $week_end = $weekEndDate->format('Y-m-d');
}
$conn->begin_transaction();

function getWeekRangeLabel($start, $end) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    return $startDate->format('M d') . ' - ' . $endDate->format('M d, Y');
}

try {
    if (count($input['meals']) > 0) {
        // Delete existing meal plans for this week range
        $stmt = $conn->prepare("DELETE FROM meal WHERE user_id = ? AND meal_date >= ? AND meal_date <= ?");
        $stmt->bind_param('iss', $user_id, $week_start, $week_end);
        $stmt->execute();
        $stmt->close();
        
        // Delete existing reservations for deleted meals
        $stmt = $conn->prepare("DELETE FROM meal_reservations WHERE user_id = ? AND reservation_date >= ? AND reservation_date <= ?");
        $stmt->bind_param('iss', $user_id, $week_start, $week_end);
        $stmt->execute();
        $stmt->close();
    }
    
    $saved_plans = [];
    
    foreach ($input['meals'] as $meal) {
        if (!isset($meal['date']) || !isset($meal['meal_type']) || !isset($meal['meal_name'])) {
            continue;
        }
        
        // Insert meal plan into meal table
        $stmt = $conn->prepare("INSERT INTO meal (user_id, meal_date, meal_type, meal_name, ingredients) VALUES (?, ?, ?, ?, ?)");
        $ingredients_json = isset($meal['ingredients']) ? json_encode($meal['ingredients']) : null;
        $stmt->bind_param('issss', $user_id, $meal['date'], $meal['meal_type'], $meal['meal_name'], $ingredients_json);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save meal plan: ' . $stmt->error);
        }
        
        $meal_id = $conn->insert_id;
        $stmt->close();
        
        // Save reservations if ingredients are provided
        if (isset($meal['ingredients']) && is_array($meal['ingredients'])) {
            foreach ($meal['ingredients'] as $ingredient) {
                if (!isset($ingredient['inventory_id']) || !isset($ingredient['quantity']) || $ingredient['quantity'] <= 0) {
                    continue;
                }
                
                // Check if inventory item exists and has enough quantity
                $checkStmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ? AND user_id = ?");
                $checkStmt->bind_param('ii', $ingredient['inventory_id'], $user_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows === 0) {
                    $checkStmt->close();
                    continue; // Skip if item doesn't exist
                }
                
                $item = $result->fetch_assoc();
                $checkStmt->close();
                
                // Check available quantity (total - reserved)
                $reservedStmt = $conn->prepare("SELECT COALESCE(SUM(reserved_quantity), 0) as total_reserved 
                    FROM meal_reservations 
                    WHERE inventory_id = ? AND user_id = ? AND reservation_date = ?");
                $reservedStmt->bind_param('iis', $ingredient['inventory_id'], $user_id, $meal['date']);
                $reservedStmt->execute();
                $reservedResult = $reservedStmt->get_result();
                $reservedData = $reservedResult->fetch_assoc();
                $total_reserved = $reservedData['total_reserved'];
                $reservedStmt->close();
                
                $available = $item['quantity'] - $total_reserved;
                
                if ($ingredient['quantity'] > $available) {
                    throw new Exception("Not enough quantity available for {$ingredient['name']}. Available: {$available}, Requested: {$ingredient['quantity']}");
                }
                
                // Insert reservation
                $reserveStmt = $conn->prepare("INSERT INTO meal_reservations (user_id, inventory_id, meal_id, reserved_quantity, reservation_date) VALUES (?, ?, ?, ?, ?)");
                $reserveStmt->bind_param('iiiis', $user_id, $ingredient['inventory_id'], $meal_id, $ingredient['quantity'], $meal['date']);
                
                if (!$reserveStmt->execute()) {
                    throw new Exception('Failed to create reservation: ' . $reserveStmt->error);
                }
                
                $reserveStmt->close();
            }
        }
        
        $saved_plans[] = $meal_id;
    }
    
    $conn->commit();
    
    if (count($saved_plans) > 0) {
        $range = getWeekRangeLabel($week_start, $week_end);
        // Use week_start as related_id to allow notifications for different weeks
        // Convert week_start to integer (YYYYMMDD format) for use as related_id
        $weekId = (int)str_replace('-', '', $week_start);
        create_alert(
            $conn,
            $user_id,
            'meal_plan',
            'Meal Plan Confirmed',
            "Your meal plan for {$range} has been saved.",
            'meal',
            $weekId,
            'meal.php',
            0.083 // 5 minutes dedupe window - allows same week to be confirmed multiple times within 5 min
        );
    }
    
    echo json_encode(['success' => true, 'message' => 'Meal plan saved successfully', 'saved_count' => count($saved_plans)]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>

