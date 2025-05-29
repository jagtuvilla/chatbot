<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

require_once '../dbconn.php';

if (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$id) {
        header('Location: ../transaction.php?error=Invalid transaction ID');
        exit;
    }

    try {
        // Start transaction
        $conn->beginTransaction();

        // Get the transaction details before deleting
        $stmt = $conn->prepare("SELECT amount, account_id, category_id FROM transactions t 
                               LEFT JOIN categories c ON t.category_id = c.id 
                               WHERE t.id = ? AND t.user_id = ?");
        $stmt->execute([$id, $user_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        // Get the category type to determine if it was income or expense
        $stmt = $conn->prepare("SELECT type FROM categories WHERE id = ?");
        $stmt->execute([$transaction['category_id']]);
        $category_type = $stmt->fetchColumn();

        // Update account balance - reverse the original transaction effect
        if ($category_type === 'income') {
            // If it was income, subtract it from balance
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        } else {
            // If it was expense, add it back to balance
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        }
        $stmt->execute([$transaction['amount'], $transaction['account_id'], $user_id]);

        // Delete the transaction
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        // Commit transaction
        $conn->commit();

        header('Location: ../transaction.php?success=1');
        exit;
    } catch(Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error deleting transaction: " . $e->getMessage());
        header('Location: ../transaction.php?error=Failed to delete transaction');
        exit;
    }
} else {
    header('Location: ../transaction.php');
    exit;
} 