<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$meal_id = isset($input['meal_id']) ? intval($input['meal_id']) : 0;

if ($meal_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid meal ID']);
    exit();
}

$conn->begin_transaction();

try {
    // Ensure meal belongs to user
    $stmt = $conn->prepare("SELECT id FROM meal WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $meal_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception('Meal not found');
    }
    $stmt->close();
    
    // Delete reservations for this meal
    $stmt = $conn->prepare("DELETE FROM meal_reservations WHERE meal_id = ?");
    $stmt->bind_param('i', $meal_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete meal
    $stmt = $conn->prepare("DELETE FROM meal WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $meal_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

