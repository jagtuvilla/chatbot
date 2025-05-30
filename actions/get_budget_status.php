<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

if (!isset($_GET['category_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = $_GET['category_id'];

try {
    // Get user's currency
    $stmt = $conn->prepare("SELECT currency FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_currency = $stmt->fetchColumn() ?: 'PHP';
    
    // Get currency symbol
    $currency_symbols = [
        'PHP' => '₱',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'KRW' => '₩',
        'CAD' => 'C$',
        'AUD' => 'A$'
    ];
    $currency_symbol = $currency_symbols[$user_currency] ?? $user_currency;

    // Get active budget for this category
    $stmt = $conn->prepare("
        SELECT b.*, 
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t 
         WHERE t.category_id = b.category_id 
         AND t.user_id = :user_id
         AND t.date BETWEEN b.start_date AND b.end_date) as spent
        FROM budgets b 
        WHERE b.category_id = :category_id 
        AND b.user_id = :user_id
        AND b.end_date >= CURDATE()
        AND CURDATE() BETWEEN b.start_date AND b.end_date
        ORDER BY b.end_date ASC
        LIMIT 1
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->execute();
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($budget) {
        $spent = floatval($budget['spent']);
        $total = floatval($budget['amount']);
        $percentage = $total > 0 ? round(($spent / $total) * 100) : 0;
        
        // Calculate status dynamically
        $status = 'normal';
        if ($spent > $total) {
            $status = 'exceeded';
        } elseif ($spent >= $total * 0.9) {
            $status = 'warning';
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'has_budget' => true,
            'status' => $status,
            'spent' => $spent,
            'total' => $total,
            'percentage' => $percentage,
            'currency_symbol' => $currency_symbol
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'has_budget' => false
        ]);
    }
} catch (Exception $e) {
    error_log("Error getting budget status: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to get budget status']);
} 