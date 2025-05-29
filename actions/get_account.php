<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid account ID']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, name, type, balance, currency, description FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Account not found']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode($account);
        exit;
    } catch(PDOException $e) {
        error_log("Error fetching account: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to fetch account data']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
} 