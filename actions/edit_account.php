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
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $balance = filter_input(INPUT_POST, 'balance', FILTER_VALIDATE_FLOAT);
    $currency = $_POST['currency'];
    $description = trim($_POST['description']);
    
    if (!$id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid account ID']);
        exit;
    }
    
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Account name is required']);
        exit;
    }
    
    if (!in_array($type, ['checking', 'savings', 'credit', 'cash', 'ewallet', 'investment', 'other'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid account type']);
        exit;
    }
    
    if ($balance === false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid balance']);
        exit;
    }
    
    if (!in_array($currency, ['PHP', 'USD', 'EUR', 'GBP', 'JPY', 'KRW', 'CAD', 'AUD'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid currency']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE accounts SET name = ?, type = ?, balance = ?, currency = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $type, $balance, $currency, $description, $id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No changes made or account not found']);
        }
        exit;
    } catch (PDOException $e) {
        error_log("Error updating account: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update account']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
} 