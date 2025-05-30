<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    error_log("Password change attempt: User not logged in");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Log the attempt (without sensitive data)
    error_log("Password change attempt for user ID: " . $_SESSION['user_id']);
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        error_log("Password change failed: Empty fields");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        error_log("Password change failed: Passwords don't match");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        error_log("Password change failed: Password too short");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
        exit;
    }
    
    try {
        // First, check if the password column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
        if ($checkColumn->rowCount() === 0) {
            error_log("Password change failed: 'password' column not found in users table");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database configuration error']);
            exit;
        }

        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_hash = $stmt->fetchColumn();
        
        if (!$current_hash) {
            error_log("Password change failed: User not found or no password set");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User account error']);
            exit;
        }
        
        if (!password_verify($current_password, $current_hash)) {
            error_log("Password change failed: Current password incorrect");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        // Update password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$new_hash, $_SESSION['user_id']]);
        
        if ($result) {
            error_log("Password change successful for user ID: " . $_SESSION['user_id']);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            error_log("Password change failed: Update query returned false");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to update password']);
        }
        exit;
        
    } catch(PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit;
    }
} else {
    error_log("Password change attempt: Invalid request method");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}