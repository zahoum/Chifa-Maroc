
<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$lat = floatval($_POST['lat'] ?? 0);
$lng = floatval($_POST['lng'] ?? 0);
$type = $_POST['type'] ?? 'all';
$results = intval($_POST['results'] ?? 0);

try {
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->prepare("INSERT INTO user_searches (user_id, search_type, latitude, longitude, search_radius, results_count, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $type,
        $lat,
        $lng,
        5000,
        $results,
        $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Error in save_search.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
