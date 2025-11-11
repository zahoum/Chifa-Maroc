<?php
session_start();
require_once 'config.php';

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "isLoggedIn(): " . (isLoggedIn() ? 'TRUE' : 'FALSE') . "\n";
echo "</pre>";

// Test database connection
try {
    $pdo = getDatabaseConnection();
    echo "<p style='color: green;'>Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection: FAILED - " . $e->getMessage() . "</p>";
}
?>