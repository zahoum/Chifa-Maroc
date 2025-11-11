
<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$lat = floatval($_POST['lat'] ?? 0);
$lng = floatval($_POST['lng'] ?? 0);
$type = $_POST['type'] ?? 'all';

if ($lat === 0 || $lng === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    $query = "SELECT *, 
             (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
             cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
             sin(radians(latitude)))) AS distance 
             FROM medical_facilities 
             WHERE is_active = 1";
    
    $params = [$lat, $lng, $lat];
    
    if ($type !== 'all') {
        $query .= " AND type = ?";
        $params[] = $type;
    }
    
    $query .= " HAVING distance < 5 ORDER BY distance LIMIT 20";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $facilities = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'facilities' => $facilities,
        'count' => count($facilities)
    ]);
    
} catch (PDOException $e) {
    error_log("Error in get_facilities.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
