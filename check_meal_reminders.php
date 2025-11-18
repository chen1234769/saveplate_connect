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

// Get today and tomorrow dates
$today = new DateTime();
$today->setTime(0, 0, 0);
$tomorrow = clone $today;
$tomorrow->modify('+1 day');
$dayAfterTomorrow = clone $today;
$dayAfterTomorrow->modify('+2 days');

$todayStr = $today->format('Y-m-d');
$tomorrowStr = $tomorrow->format('Y-m-d');
$dayAfterStr = $dayAfterTomorrow->format('Y-m-d');

// Check for meals coming in the next 2 days
$stmt = $conn->prepare("SELECT id, meal_date, meal_type, meal_name 
    FROM meal 
    WHERE user_id = ? 
    AND meal_date IN (?, ?, ?)
    ORDER BY meal_date, meal_type");
$stmt->bind_param('isss', $user_id, $todayStr, $tomorrowStr, $dayAfterStr);
$stmt->execute();
$result = $stmt->get_result();

$reminders_created = 0;
$mealTypeNames = [
    'breakfast' => 'Breakfast',
    'lunch' => 'Lunch',
    'dinner' => 'Dinner'
];

while ($meal = $result->fetch_assoc()) {
    $mealDate = new DateTime($meal['meal_date']);
    $daysUntil = $today->diff($mealDate)->days;
    
    $message = '';
    if ($daysUntil === 0) {
        $message = "Your {$mealTypeNames[$meal['meal_type']]} - {$meal['meal_name']} is scheduled for today!";
    } elseif ($daysUntil === 1) {
        $message = "Your {$mealTypeNames[$meal['meal_type']]} - {$meal['meal_name']} is scheduled for tomorrow!";
    } else {
        $message = "Your {$mealTypeNames[$meal['meal_type']]} - {$meal['meal_name']} is coming in {$daysUntil} days!";
    }
    
    // Create reminder alert (dedupe within 12 hours)
    $created = create_alert(
        $conn,
        $user_id,
        'meal_reminder',
        'Meal Plan Reminder',
        $message,
        'meal',
        $meal['id'],
        'meal.php',
        12
    );
    
    if ($created) {
        $reminders_created++;
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'reminders_created' => $reminders_created]);
?>

