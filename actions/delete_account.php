<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        header('Location: ../account.php?error=Invalid account ID');
        exit;
    }
    
    try {
        // Check if account has any transactions
        $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE account_id = ?");
        $stmt->execute([$id]);
        $transactionCount = $stmt->fetchColumn();
        
        if ($transactionCount > 0) {
            header('Location: ../account.php?error=Cannot delete account with existing transactions');
            exit;
        }
        
        // Delete the account
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        header('Location: ../account.php?success=deleted');
        exit;
    } catch(PDOException $e) {
        error_log("Error deleting account: " . $e->getMessage());
        header('Location: ../account.php?error=Failed to delete account');
        exit;
    }
} else {
    header('Location: ../account.php');
    exit;
} 