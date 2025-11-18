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

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM alerts WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['count' => (int)$result['unread']]);

$conn->close();

