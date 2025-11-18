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

// Get week range from query parameters or default to current week
if (isset($_GET['from']) && isset($_GET['to'])) {
    $mondayStr = $_GET['from'];
    $sundayStr = $_GET['to'];
} else {
    $monday = new DateTime('monday this week');
    $sunday = clone $monday;
    $sunday->modify('+6 days');
    $mondayStr = $monday->format('Y-m-d');
    $sundayStr = $sunday->format('Y-m-d');
}

// Fetch meal plans from meal table
$stmt = $conn->prepare("SELECT id, meal_date, meal_type, meal_name, ingredients FROM meal 
    WHERE user_id = ? AND meal_date >= ? AND meal_date <= ? 
    ORDER BY meal_date ASC, FIELD(meal_type, 'breakfast', 'lunch', 'dinner')");
$stmt->bind_param('iss', $user_id, $mondayStr, $sundayStr);
$stmt->execute();
$result = $stmt->get_result();

$meals = [];
while ($row = $result->fetch_assoc()) {
    $meal = [
        'id' => $row['id'],
        'date' => $row['meal_date'],
        'meal_type' => $row['meal_type'],
        'meal_name' => $row['meal_name'],
        'ingredients' => $row['ingredients'] ? json_decode($row['ingredients'], true) : []
    ];
    
    // Fetch reservations for this meal
    $reserveStmt = $conn->prepare("SELECT r.inventory_id, r.reserved_quantity, r.reservation_date, i.name as inventory_name
        FROM meal_reservations r
        JOIN inventory i ON r.inventory_id = i.id
        WHERE r.meal_id = ?");
    $reserveStmt->bind_param('i', $row['id']);
    $reserveStmt->execute();
    $reserveResult = $reserveStmt->get_result();
    
    $reservations = [];
    while ($reserve = $reserveResult->fetch_assoc()) {
        $reservations[] = [
            'inventory_id' => $reserve['inventory_id'],
            'name' => $reserve['inventory_name'],
            'quantity' => $reserve['reserved_quantity']
        ];
    }
    $reserveStmt->close();
    
    $meal['ingredients'] = $reservations;
    $meals[] = $meal;
}

$stmt->close();
$conn->close();

echo json_encode($meals);
?>

