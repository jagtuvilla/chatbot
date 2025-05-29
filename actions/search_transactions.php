<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

require_once '../dbconn.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT t.*, 
               c.name as category_name, 
               c.type as category_type,
               c.color as category_color,
               a.name as account_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ?
        AND (
            t.description LIKE ? OR
            t.date LIKE ? OR
            c.name LIKE ? OR
            a.name LIKE ?
        )
        ORDER BY t.date DESC, t.id DESC
    ");
    
    $searchTerm = "%{$keyword}%";
    $stmt->execute([$user_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($transactions)) {
        echo '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No transactions found matching your search.</td></tr>';
    } else {
        foreach ($transactions as $transaction) {
            ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    <?php echo date('M d, Y', strtotime($transaction['date'])); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    <?php echo htmlspecialchars($transaction['description']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold" style="background-color: <?php echo $transaction['category_color']; ?>20; color: <?php echo $transaction['category_color']; ?>">
                        <?php echo htmlspecialchars($transaction['category_name']); ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    <?php echo htmlspecialchars($transaction['account_name']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $transaction['category_type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                    <?php echo $transaction['category_type'] === 'income' ? '+' : '-'; ?><?php echo number_format($transaction['amount'], 2); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="openEditTransactionModal(<?php echo htmlspecialchars(json_encode($transaction)); ?>)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 mr-3">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php
        }
    }
} catch(PDOException $e) {
    error_log("Error searching transactions: " . $e->getMessage());
    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error searching transactions</td></tr>';
}
?> 