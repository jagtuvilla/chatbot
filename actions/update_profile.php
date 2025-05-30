<?php
session_start();
require_once '../dbconn.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $currency = $_POST['currency'];
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (!in_array($currency, ['PHP', 'USD', 'EUR', 'GBP', 'JPY', 'KRW', 'CAD', 'AUD'])) {
        $errors[] = 'Invalid currency';
    }
    
    if (!empty($errors)) {
        header('Location: ../profile.php?error=' . urlencode(implode(', ', $errors)));
        exit;
    }
    
    try {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: ../profile.php?error=Email is already taken');
            exit;
        }
        
        // Update user profile
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, currency = ? WHERE id = ?");
        $stmt->execute([$name, $email, $currency, $_SESSION['user_id']]);
        
        // Update all accounts to use the new currency
        $stmt = $conn->prepare("UPDATE accounts SET currency = ? WHERE user_id = ?");
        $stmt->execute([$currency, $_SESSION['user_id']]);
        
        // Update session name
        $_SESSION['user_name'] = $name;
        
        header('Location: ../profile.php?success=Profile and account currencies updated successfully');
        exit;
    } catch (PDOException $e) {
        error_log("Error updating profile: " . $e->getMessage());
        header('Location: ../profile.php?error=Failed to update profile');
        exit;
    }
} else {
    header('Location: ../profile.php');
    exit;
}
?>