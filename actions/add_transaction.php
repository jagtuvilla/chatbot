<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the POST data
    error_log("POST data received: " . print_r($_POST, true));
    
    $user_id = $_SESSION['user_id'];
    $amount = floatval($_POST['amount']);
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $account_id = $_POST['account_id'];
    $transaction_type = $_POST['transaction_type'];

    $errors = [];

    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0';
    }

    if (empty($category_id)) {
        $errors[] = 'Category is required';
    }

    if (empty($description)) {
        $errors[] = 'Description is required';
    }

    if (empty($date)) {
        $errors[] = 'Date is required';
    }

    if (empty($account_id)) {
        $errors[] = 'Account is required';
    }

    if (empty($transaction_type)) {
        $errors[] = 'Transaction type is required';
    }

    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Get category type to verify transaction type matches
        $stmt = $conn->prepare("SELECT type FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        $category_type = $stmt->fetchColumn();

        if ($category_type !== $transaction_type) {
            throw new Exception('Category type does not match transaction type');
        }

        // Check account balance for expense transactions
        if ($transaction_type === 'expense') {
            $stmt = $conn->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$account_id, $user_id]);
            $current_balance = $stmt->fetchColumn();

            if ($amount > $current_balance) {
                throw new Exception('Insufficient Balance: Cannot process expense greater than account balance');
            }
        }

        // Add transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, account_id, category_id, amount, description, date) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $account_id, $category_id, $amount, $description, $date]);

        // Update account balance
        if ($transaction_type === 'income') {
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        }
        $stmt->execute([$amount, $account_id, $user_id]);

        // If it's an expense, check for active budgets and update them
        if ($transaction_type === 'expense') {
            // Budget validation removed
        }

        $conn->commit();
        
        error_log("Transaction added successfully");
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Transaction added successfully']);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error adding transaction: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add transaction: ' . $e->getMessage()]);
        exit;
    }
} else {
    // If not POST request, return error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
} 