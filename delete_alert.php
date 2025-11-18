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
    $input = json_decode(file_get_contents('php://input'), true);
    $alert_id = $input['alert_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($alert_id) {
        $stmt = $conn->prepare("DELETE FROM alerts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $alert_id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete alert']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid alert ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
