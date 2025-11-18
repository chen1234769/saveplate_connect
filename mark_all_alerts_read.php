<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE alerts SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo json_encode(['success' => true, 'count' => $affected]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update alerts']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>

