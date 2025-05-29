<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once '../dbconn.php';

// Get user's currency
$stmt = $conn->prepare("SELECT currency FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
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

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT b.*, c.name as category_name,
        (SELECT COALESCE(SUM(amount), 0) 
         FROM transactions t 
         WHERE t.category_id = b.category_id 
         AND t.user_id = :user_id
         AND t.date BETWEEN b.start_date AND b.end_date) as spent
        FROM budgets b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = :user_id
        AND (
            b.name LIKE :keyword OR
            c.name LIKE :keyword OR
            DATE_FORMAT(b.start_date, '%M %d, %Y') LIKE :keyword OR
            DATE_FORMAT(b.end_date, '%M %d, %Y') LIKE :keyword OR
            CONCAT(DATE_FORMAT(b.start_date, '%M %d'), ' - ', DATE_FORMAT(b.end_date, '%M %d, %Y')) LIKE :keyword
        )
        ORDER BY b.end_date DESC
    ");
    
    $searchTerm = "%{$keyword}%";
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':keyword', $searchTerm);
    $stmt->execute();
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($budgets)) {
        echo '<tr><td colspan="5" class="py-4 px-6 text-center text-gray-500 dark:text-gray-400">No budgets found matching your search.</td></tr>';
    } else {
        foreach ($budgets as $budget) {
            ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-600 transition duration-150">
                <td class="py-4 px-6 text-sm text-gray-900 dark:text-gray-300 font-medium">
                    <?php echo htmlspecialchars($budget['name']); ?>
                </td>
                <td class="py-4 px-6 text-sm font-medium text-gray-900 dark:text-gray-300">
                    <?php echo $currency_symbol . number_format($budget['amount'], 2); ?>
                </td>
                <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">
                    <?php echo htmlspecialchars($budget['category_name'] ?? 'All Categories'); ?>
                </td>
                <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">
                    <?php 
                    echo date('M d', strtotime($budget['start_date'])) . ' - ' . 
                         date('M d, Y', strtotime($budget['end_date'])); 
                    ?>
                </td>
                <td class="py-4 px-6 text-sm font-medium">
                    <button onclick="handleEditBudget(<?php echo htmlspecialchars(json_encode($budget)); ?>)" 
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3 transition duration-150">
                        Edit
                    </button>
                    <button onclick="handleDeleteBudget(<?php echo $budget['id']; ?>)" 
                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition duration-150">
                        Delete
                    </button>
                </td>
            </tr>
            <?php
        }
    }
} catch(PDOException $e) {
    error_log("Error searching budgets: " . $e->getMessage());
    echo '<tr><td colspan="5" class="py-4 px-6 text-center text-red-500">Error searching budgets</td></tr>';
}
?> 