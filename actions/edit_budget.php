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
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $recurring = isset($_POST['recurring']) ? 1 : 0;
    $recurring_type = isset($_POST['recurring_type']) ? $_POST['recurring_type'] : null;
    
    if (!$id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid budget ID']);
        exit;
    }
    
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Budget name is required']);
        exit;
    }
    
    if ($amount === false || $amount <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    if (!$category_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit;
    }
    
    if (empty($start_date) || empty($end_date)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Start and end dates are required']);
        exit;
    }
    
    try {
        // First verify that the budget belongs to the current user
        $stmt = $conn->prepare("SELECT id FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Budget not found or access denied']);
            exit;
        }
        
        // Update the budget
        $stmt = $conn->prepare("UPDATE budgets SET name = ?, amount = ?, category_id = ?, start_date = ?, end_date = ?, recurring = ?, recurring_type = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $amount, $category_id, $start_date, $end_date, $recurring, $recurring_type, $id, $_SESSION['user_id']]);
        
        // Get updated budget data including category name
        $stmt = $conn->prepare("
            SELECT b.*, c.name as category_name 
            FROM budgets b 
            LEFT JOIN categories c ON b.category_id = c.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Budget updated successfully',
            'budget' => $budget
        ]);
        exit;
    } catch(PDOException $e) {
        error_log("Error updating budget: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update budget']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
} 