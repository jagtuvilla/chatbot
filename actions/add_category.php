<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $color = $_POST['color'];
    $user_id = $_SESSION['user_id'];
    
    if (empty($name)) {
        header('Location: ../categories.php?error=Category name is required');
        exit;
    }
    
    if (!in_array($type, ['income', 'expense'])) {
        header('Location: ../categories.php?error=Invalid category type');
        exit;
    }
    
    try {
        // Simple insert of new category
        $stmt = $conn->prepare("INSERT INTO categories (name, type, color, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $type, $color, $user_id]);
        
        header('Location: ../categories.php?add_success=Category added successfully');
        exit;
    } catch(PDOException $e) {
        error_log("Error adding category: " . $e->getMessage());
        header('Location: ../categories.php?error=Failed to add category');
        exit;
    }
} else {
    header('Location: ../categories.php');
    exit;
} 