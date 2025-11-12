<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// التحقق من أن المستخدم مسؤول
function isAdminUser() {
    session_start();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// التحقق من أن المستخدم مسجل الدخول
function isRegularUser() {
    session_start();
    return isset($_SESSION['user_id']);
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $maintenance_mode = ($result && $result['setting_value'] == '1');
    $is_admin = isAdminUser();
    $is_logged_in = isRegularUser();
    
    // السماح للمسؤولين والمستخدمين المسجلين بالوصول
    $should_show_maintenance = $maintenance_mode && !$is_admin && !$is_logged_in;
    
    echo json_encode([
        'maintenance' => $maintenance_mode,
        'isAdmin' => $is_admin,
        'isLoggedIn' => $is_logged_in,
        'shouldShowMaintenance' => $should_show_maintenance
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'maintenance' => false,
        'isAdmin' => false,
        'isLoggedIn' => false,
        'shouldShowMaintenance' => false,
        'error' => 'Database error'
    ]);
}
?>