<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $recurring = isset($_POST['recurring']) ? 1 : 0;
    $recurring_type = isset($_POST['recurring_type']) ? $_POST['recurring_type'] : null;
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Budget name is required']);
        exit;
    }
    
    if ($amount === false || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    if ($category_id === false) {
        $category_id = null;
    }
    
    if (empty($start_date) || empty($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Start and end dates are required']);
        exit;
    }
    
    try {
        // Check if a budget already exists for this category
        if ($category_id !== null) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM budgets WHERE category_id = ? AND user_id = ?");
            $stmt->execute([$category_id, $_SESSION['user_id']]);
            $existing_budget_count = $stmt->fetchColumn();
            
            if ($existing_budget_count > 0) {
                echo json_encode(['success' => false, 'message' => 'A budget already exists for this category']);
                exit;
            }
        }

        $stmt = $conn->prepare("INSERT INTO budgets (name, amount, category_id, start_date, end_date, user_id, recurring, recurring_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $amount, $category_id, $start_date, $end_date, $_SESSION['user_id'], $recurring, $recurring_type]);
        
        $budget_id = $conn->lastInsertId();
        
        // Get category name if category_id exists
        $category_name = null;
        if ($category_id) {
            $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            $category_name = $category ? $category['name'] : null;
        }
        
        echo json_encode(['success' => true, 'message' => 'Budget added successfully']);
        exit;
    } catch(PDOException $e) {
        error_log("Error adding budget: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add budget: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
} 