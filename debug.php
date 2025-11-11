<?php
session_start();
require_once 'config.php';

echo "<h2>Debug Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "isLoggedIn(): " . (isLoggedIn() ? 'TRUE' : 'FALSE') . "\n";

// Check if users table exists and has data
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>