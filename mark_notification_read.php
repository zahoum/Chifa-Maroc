<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No notification ID']);
    exit;
}

$notification_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $success = $stmt->execute([$notification_id, $user_id]);
    
    echo json_encode(['success' => $success]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>