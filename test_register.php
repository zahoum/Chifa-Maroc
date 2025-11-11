<?php
session_start();
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    // Create test user
    $email = 'test@example.com';
    $password = 'test123'; // In production, use password_hash()
    
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute(['Test', 'User', $email, $password]);
    
    echo "Test user created: test@example.com / test123";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>