<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    header('Location: ../index.php?error=not_logged_in');
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $balance = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_FLOAT);
    $currency = $_POST['currency'] ?? null;
    $description = trim($_POST['description']);
    
    // If no currency is provided, get user's preferred currency
    if (!$currency) {
        try {
            $stmt = $conn->prepare("SELECT currency FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currency = $stmt->fetchColumn() ?: 'PHP';
        } catch(PDOException $e) {
            error_log("Error fetching user currency: " . $e->getMessage());
            $currency = 'PHP';
        }
    }
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Account name is required';
    }
    
    if (!in_array($type, ['checking', 'savings', 'credit', 'cash', 'ewallet', 'investment', 'other'])) {
        $errors[] = 'Invalid account type';
    }
    
    if ($balance === false) {
        $errors[] = 'Invalid balance';
    }
    
    if (!in_array($currency, ['PHP', 'USD', 'EUR', 'GBP', 'JPY', 'KRW', 'CAD', 'AUD'])) {
        $errors[] = 'Invalid currency';
    }
    
    if (!empty($errors)) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }
        header('Location: ../account.php?error=' . urlencode(implode(', ', $errors)));
        exit;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, name, type, balance, currency, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $name, $type, $balance, $currency, $description]);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: ../account.php?success=1');
        exit;
    } catch (PDOException $e) {
        error_log("Error adding account: " . $e->getMessage());
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to add account']);
            exit;
        }
        header('Location: ../account.php?error=Failed to add account');
        exit;
    }
} else {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    header('Location: ../account.php');
    exit;
} 