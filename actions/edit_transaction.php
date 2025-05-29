<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $account_id = filter_input(INPUT_POST, 'account_id', FILTER_VALIDATE_INT);
    $transaction_type = $_POST['transaction_type'];
    $user_id = $_SESSION['user_id'];

    if (!$id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
        exit;
    }

    if (!$amount || $amount <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    if (empty($description)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Description is required']);
        exit;
    }

    if (!$category_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit;
    }

    if (!$account_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Account is required']);
        exit;
    }

    if (!in_array($transaction_type, ['income', 'expense'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
        exit;
    }

    try {
        // Start transaction
        $conn->beginTransaction();

        // Get the old transaction details
        $stmt = $conn->prepare("SELECT amount, account_id, transaction_type FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $old_transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old_transaction) {
            throw new Exception('Transaction not found');
        }

        // If changing to an expense or updating an expense amount, check if there's sufficient balance
        if ($transaction_type === 'expense') {
            // Get current account balance
            $stmt = $conn->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$account_id, $user_id]);
            $current_balance = $stmt->fetchColumn();

            // Calculate the effective change in balance
            $balance_change = 0;
            if ($old_transaction['transaction_type'] === 'expense') {
                // If it was already an expense, only consider the difference
                $balance_change = $amount - $old_transaction['amount'];
            } else {
                // If it was income being changed to expense, consider both the reversal and the new expense
                $balance_change = $amount + $old_transaction['amount'];
            }

            // Check if the change would result in negative balance
            if ($current_balance < $balance_change) {
                $conn->rollBack();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Insufficient Balance: Cannot process expense greater than account balance']);
                exit;
            }
        }

        // Reverse the old transaction's effect on the account balance
        if ($old_transaction['transaction_type'] === 'income') {
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        }
        $stmt->execute([$old_transaction['amount'], $old_transaction['account_id'], $user_id]);

        // Apply the new transaction's effect on the account balance
        if ($transaction_type === 'income') {
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        }
        $stmt->execute([$amount, $account_id, $user_id]);

        // Update the transaction
        $stmt = $conn->prepare("UPDATE transactions SET amount = ?, description = ?, date = ?, category_id = ?, account_id = ?, transaction_type = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $description, $date, $category_id, $account_id, $transaction_type, $id, $user_id]);

        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
        exit;
    } catch(Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error editing transaction: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to edit transaction']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
} 