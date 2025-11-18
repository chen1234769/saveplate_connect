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

$week_start = isset($input['week_start']) ? $input['week_start'] : null;
$week_end = isset($input['week_end']) ? $input['week_end'] : null;

if (!$week_start || !$week_end) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid week range']);
    exit();
}

$conn->begin_transaction();

try {
    // Remove reservations for this week
    $stmt = $conn->prepare("DELETE FROM meal_reservations WHERE user_id = ? AND reservation_date >= ? AND reservation_date <= ?");
    $stmt->bind_param('iss', $user_id, $week_start, $week_end);
    $stmt->execute();
    $stmt->close();
    
    // Remove meals for this week
    $stmt = $conn->prepare("DELETE FROM meal WHERE user_id = ? AND meal_date >= ? AND meal_date <= ?");
    $stmt->bind_param('iss', $user_id, $week_start, $week_end);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Meals cleared']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to clear meals']);
}

$conn->close();

