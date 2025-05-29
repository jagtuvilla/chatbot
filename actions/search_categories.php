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
        SELECT c.*, 
        (SELECT COUNT(*) FROM transactions t WHERE t.category_id = c.id AND t.user_id = ?) as transaction_count
        FROM categories c 
        WHERE (c.user_id = ? OR c.user_id IS NULL)
        AND (c.name LIKE ? OR c.type LIKE ?)
        ORDER BY c.type, c.name ASC
    ");
    
    $searchTerm = "%{$keyword}%";
    $stmt->execute([$user_id, $user_id, $searchTerm, $searchTerm]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories)) {
        echo '<tr><td colspan="5" class="py-4 px-6 text-center text-gray-500 dark:text-gray-400">No categories found matching your search.</td></tr>';
    } else {
        foreach ($categories as $category) {
            ?>
            <tr class="bg-white dark:bg-dark-bg-secondary">
                <td class="py-4 px-6">
                    <div class="w-6 h-6 rounded-full border border-gray-300 dark:border-gray-600" style="background-color: <?php echo htmlspecialchars($category['color'] ?? '#4F46E5'); ?>"></div>
                </td>
                <td class="py-4 px-6 text-sm text-gray-900 dark:text-gray-300"><?php echo htmlspecialchars($category['name']); ?></td>
                <td class="py-4 px-6 text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $category['type'] === 'income' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                        <?php echo ucfirst($category['type']); ?>
                    </span>
                </td>
                <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400"><?php echo $category['transaction_count']; ?></td>
                <td class="py-4 px-6 text-sm font-medium">
                    <button type="button" onclick="openEditCategoryOverlay(<?php echo $category['id']; ?>)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">Edit</button>
                    <button type="button" onclick="deleteCategory(<?php echo $category['id']; ?>)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                </td>
            </tr>
            <?php
        }
    }
} catch(PDOException $e) {
    error_log("Error searching categories: " . $e->getMessage());
    echo '<tr><td colspan="5" class="py-4 px-6 text-center text-red-500">Error searching categories</td></tr>';
}
?> 