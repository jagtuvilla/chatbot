<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

require_once '../dbconn.php';

try {
    echo "<pre>";
    echo "Current user ID: {$_SESSION['user_id']}\n\n";
    
    // Get ALL categories
    $stmt = $conn->query("SELECT * FROM categories ORDER BY id ASC");
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "ALL CATEGORIES IN DATABASE:\n";
    echo "=========================\n";
    foreach ($allCategories as $category) {
        echo "ID: {$category['id']}\n";
        echo "Name: {$category['name']}\n";
        echo "Type: {$category['type']}\n";
        echo "Color: {$category['color']}\n";
        echo "User ID: " . ($category['user_id'] ? $category['user_id'] : 'NULL') . "\n";
        echo "Created At: {$category['created_at']}\n";
        echo "-------------------\n";
    }
    
    // Get categories for current user
    $stmt = $conn->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $userCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCATEGORIES FOR CURRENT USER:\n";
    echo "=========================\n";
    foreach ($userCategories as $category) {
        echo "ID: {$category['id']}\n";
        echo "Name: {$category['name']}\n";
        echo "Type: {$category['type']}\n";
        echo "Color: {$category['color']}\n";
        echo "User ID: {$category['user_id']}\n";
        echo "Created At: {$category['created_at']}\n";
        echo "-------------------\n";
    }
    
    // Get categories without user_id
    $stmt = $conn->query("SELECT * FROM categories WHERE user_id IS NULL OR user_id = 0 ORDER BY id ASC");
    $orphanedCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nORPHANED CATEGORIES (no user_id):\n";
    echo "=========================\n";
    foreach ($orphanedCategories as $category) {
        echo "ID: {$category['id']}\n";
        echo "Name: {$category['name']}\n";
        echo "Type: {$category['type']}\n";
        echo "Color: {$category['color']}\n";
        echo "User ID: " . ($category['user_id'] ? $category['user_id'] : 'NULL') . "\n";
        echo "Created At: {$category['created_at']}\n";
        echo "-------------------\n";
    }
    echo "</pre>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 