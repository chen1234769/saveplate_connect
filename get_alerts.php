<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

function formatRelativeTime($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' min ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hrs ago';
    return date('M d, Y', $timestamp);
}

// Get filter from query parameter
$filter = $_GET['filter'] ?? 'all';

// Build WHERE clause based on filter
$whereClause = "WHERE user_id = ?";
$params = [$user_id];
$paramTypes = 'i';

if ($filter === 'unread') {
    $whereClause .= " AND is_read = 0";
} elseif ($filter === 'inventory') {
    $whereClause .= " AND alert_type IN ('expiring_soon')";
} elseif ($filter === 'donations') {
    $whereClause .= " AND alert_type IN ('donation_success', 'donation_available')";
} elseif ($filter === 'meal_plans') {
    $whereClause .= " AND alert_type IN ('meal_plan', 'meal_reminder')";
}

// Get total and unread counts
$countStmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
    FROM alerts WHERE user_id = ?");
$countStmt->bind_param('i', $user_id);
$countStmt->execute();
$countResult = $countStmt->get_result();
$counts = $countResult->fetch_assoc();
$countStmt->close();

// Get alerts
$stmt = $conn->prepare("SELECT id, alert_type, title, message, link_url, is_read, created_at 
    FROM alerts 
    $whereClause
    ORDER BY created_at DESC 
    LIMIT 50");
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$alerts = [];
while ($row = $result->fetch_assoc()) {
    $alerts[] = [
        'id' => (int)$row['id'],
        'type' => $row['alert_type'],
        'title' => $row['title'],
        'message' => $row['message'],
        'link' => $row['link_url'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => $row['created_at'],
        'time' => formatRelativeTime($row['created_at'])
    ];
}
$stmt->close();

echo json_encode([
    'alerts' => $alerts,
    'counts' => [
        'total' => (int)$counts['total'],
        'unread' => (int)$counts['unread']
    ]
]);

$conn->close();

